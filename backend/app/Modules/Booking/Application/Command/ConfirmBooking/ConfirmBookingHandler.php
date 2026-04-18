<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application\Command\ConfirmBooking;

use App\Modules\Booking\Domain\Exception\BookingNotFoundException;
use App\Modules\Booking\Domain\Exception\InvalidBookingStateTransitionException;
use App\Modules\Booking\Domain\Repository\BookingRepositoryInterface;
use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Shared\Application\Event\DomainEventDispatcherInterface;

/**
 * Подтверждает бронирование. Переход статуса PENDING -> CONFIRMED.
 *
 * Доменная логика перехода находится в Booking::confirm().
 */
final readonly class ConfirmBookingHandler
{
    public function __construct(
        private BookingRepositoryInterface $bookingRepo,
        private DomainEventDispatcherInterface $dispatcher,
    ) {}

    /**
     * @throws BookingNotFoundException если бронирование не найдено
     * @throws InvalidBookingStateTransitionException если статус не PENDING
     */
    public function handle(ConfirmBookingCommand $cmd): void
    {
        $booking = $this->bookingRepo->findById(new BookingId($cmd->bookingId));
        if ($booking === null) {
            throw BookingNotFoundException::byId(new BookingId($cmd->bookingId));
        }

        $booking->confirm();
        $this->bookingRepo->save($booking);
        $this->dispatcher->dispatchAll($booking->pullDomainEvents());
    }
}
