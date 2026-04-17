<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application\Command\CompleteBooking;

use App\Modules\Booking\Domain\Exception\BookingNotFoundException;
use App\Modules\Booking\Domain\Exception\InvalidBookingStateTransitionException;
use App\Modules\Booking\Domain\Repository\BookingRepositoryInterface;
use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Shared\Application\Event\DomainEventDispatcherInterface;

/**
 * Завершает бронирование. Переход статуса CONFIRMED -> COMPLETED.
 *
 * Доменная логика перехода находится в Booking::complete().
 */
final readonly class CompleteBookingHandler
{
    public function __construct(
        private BookingRepositoryInterface $bookingRepo,
        private DomainEventDispatcherInterface $dispatcher,
    ) {}

    /**
     * @throws BookingNotFoundException если бронирование не найдено
     * @throws InvalidBookingStateTransitionException если статус не CONFIRMED
     */
    public function handle(CompleteBookingCommand $cmd): void
    {
        $booking = $this->bookingRepo->findById(new BookingId($cmd->bookingId));
        if ($booking === null) {
            throw BookingNotFoundException::byId(new BookingId($cmd->bookingId));
        }

        $booking->complete();
        $this->bookingRepo->save($booking);
        $this->dispatcher->dispatchAll($booking->pullDomainEvents());
    }
}
