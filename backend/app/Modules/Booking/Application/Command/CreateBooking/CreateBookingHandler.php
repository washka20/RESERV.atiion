<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application\Command\CreateBooking;

use App\Modules\Booking\Application\DTO\BookingDTO;
use App\Modules\Booking\Domain\Entity\Booking;
use App\Modules\Booking\Domain\Exception\BookingNotFoundException;
use App\Modules\Booking\Domain\Exception\InsufficientQuantityException;
use App\Modules\Booking\Domain\Exception\InvalidBookingTypeException;
use App\Modules\Booking\Domain\Exception\SlotUnavailableException;
use App\Modules\Booking\Domain\Repository\BookingRepositoryInterface;
use App\Modules\Booking\Domain\Repository\TimeSlotRepositoryInterface;
use App\Modules\Booking\Domain\Specification\BookingPolicy;
use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Booking\Domain\ValueObject\DateRange;
use App\Modules\Booking\Domain\ValueObject\Quantity;
use App\Modules\Booking\Domain\ValueObject\SlotId;
use App\Modules\Catalog\Domain\Entity\Service;
use App\Modules\Catalog\Domain\Exception\ServiceNotFoundException;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Catalog\Domain\ValueObject\ServiceType;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Shared\Application\Event\DomainEventDispatcherInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Создаёт бронирование. Защищает от race conditions:
 *
 * - TIME_SLOT: атомарный markAsBooked на репозитории слотов. Если вернёт false — кто-то успел раньше.
 * - QUANTITY: sumActiveQuantityOverlapping с lockForUpdate=true. Row-lock держится до конца транзакции.
 *
 * Вся операция — в одной DB::transaction. События публикуются только после успешного commit
 * (события пуллятся после save, но DomainEventDispatcher должен быть aware транзакций).
 */
final readonly class CreateBookingHandler
{
    public function __construct(
        private ServiceRepositoryInterface $serviceRepo,
        private BookingRepositoryInterface $bookingRepo,
        private TimeSlotRepositoryInterface $slotRepo,
        private BookingPolicy $bookingPolicy,
        private DomainEventDispatcherInterface $dispatcher,
        private int $userBookingsLimit = 20,
    ) {}

    public function handle(CreateBookingCommand $cmd): BookingDTO
    {
        $service = $this->serviceRepo->findById(new ServiceId($cmd->serviceId));
        if ($service === null) {
            throw ServiceNotFoundException::byId(new ServiceId($cmd->serviceId));
        }

        return DB::transaction(function () use ($cmd, $service): BookingDTO {
            $userId = new UserId($cmd->userId);
            $userActiveBookings = $this->bookingRepo->countActiveByUserId($userId);

            $booking = match ($service->type()) {
                ServiceType::TIME_SLOT => $this->createTimeSlotBooking($cmd, $service),
                ServiceType::QUANTITY => $this->createQuantityBooking($cmd, $service),
            };

            if (! $this->bookingPolicy->isSatisfiedByWithContext($booking, $userActiveBookings, $this->userBookingsLimit)) {
                throw InvalidBookingTypeException::mismatch(
                    $service->type()->value,
                    $this->bookingPolicy->failureReason() ?? 'policy failed',
                );
            }

            $this->bookingRepo->save($booking);

            if ($service->type() === ServiceType::TIME_SLOT && $booking->slotId !== null) {
                if (! $this->slotRepo->markAsBooked($booking->slotId, $booking->id)) {
                    throw SlotUnavailableException::forSlotId($booking->slotId);
                }
            }

            $this->dispatcher->dispatchAll($booking->pullDomainEvents());

            return BookingDTO::fromEntity($booking);
        });
    }

    private function createTimeSlotBooking(CreateBookingCommand $cmd, Service $service): Booking
    {
        if ($cmd->slotId === null) {
            throw new InvalidArgumentException('slot_id is required for TIME_SLOT booking');
        }

        $slotId = new SlotId($cmd->slotId);
        $slot = $this->slotRepo->findById($slotId);
        if ($slot === null) {
            throw BookingNotFoundException::byId(new BookingId($cmd->slotId));
        }
        if ($slot->isBooked()) {
            throw SlotUnavailableException::forSlotId($slotId);
        }

        return Booking::createTimeSlotBooking(
            id: BookingId::generate(),
            userId: new UserId($cmd->userId),
            serviceId: $service->id(),
            slotId: $slotId,
            timeRange: $slot->range,
            totalPrice: $service->price(),
            notes: $cmd->notes,
        );
    }

    private function createQuantityBooking(CreateBookingCommand $cmd, Service $service): Booking
    {
        if ($cmd->checkIn === null || $cmd->checkOut === null || $cmd->quantity === null) {
            throw new InvalidArgumentException('check_in, check_out, quantity are required for QUANTITY booking');
        }

        $totalQty = $service->totalQuantity();
        if ($totalQty === null) {
            throw InvalidBookingTypeException::mismatch($service->type()->value, 'quantity');
        }

        $range = DateRange::fromStrings($cmd->checkIn, $cmd->checkOut);

        $booked = $this->bookingRepo->sumActiveQuantityOverlapping($service->id(), $range, lockForUpdate: true);
        if ($booked + $cmd->quantity > $totalQty) {
            throw InsufficientQuantityException::withDetails(
                $cmd->quantity,
                max(0, $totalQty - $booked),
            );
        }

        $totalPrice = $service->price()->multiply($cmd->quantity * $range->nights());

        return Booking::createQuantityBooking(
            id: BookingId::generate(),
            userId: new UserId($cmd->userId),
            serviceId: $service->id(),
            dateRange: $range,
            quantity: new Quantity($cmd->quantity),
            totalPrice: $totalPrice,
            notes: $cmd->notes,
        );
    }
}
