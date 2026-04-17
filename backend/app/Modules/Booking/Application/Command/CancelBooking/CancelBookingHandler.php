<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application\Command\CancelBooking;

use App\Modules\Booking\Domain\Exception\BookingNotFoundException;
use App\Modules\Booking\Domain\Repository\BookingRepositoryInterface;
use App\Modules\Booking\Domain\Repository\TimeSlotRepositoryInterface;
use App\Modules\Booking\Domain\Specification\CancellationPolicy;
use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Booking\Domain\ValueObject\BookingType;
use App\Shared\Application\Event\DomainEventDispatcherInterface;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use RuntimeException;

/**
 * Отменяет бронирование. Проверяет авторизацию, затем делегирует доменное
 * правило отмены CancellationPolicy (через Booking::cancel).
 *
 * Для TIME_SLOT бронирования дополнительно освобождает привязанный слот.
 * Вся операция выполняется в одной DB-транзакции.
 */
final readonly class CancelBookingHandler
{
    public function __construct(
        private BookingRepositoryInterface $bookingRepo,
        private TimeSlotRepositoryInterface $slotRepo,
        private CancellationPolicy $cancellationPolicy,
        private DomainEventDispatcherInterface $dispatcher,
        private TransactionManagerInterface $tx,
    ) {}

    /**
     * @throws BookingNotFoundException если бронирование не найдено
     * @throws RuntimeException если не-админ пытается отменить чужое бронирование
     */
    public function handle(CancelBookingCommand $cmd): void
    {
        $booking = $this->bookingRepo->findById(new BookingId($cmd->bookingId));
        if ($booking === null) {
            throw BookingNotFoundException::byId(new BookingId($cmd->bookingId));
        }

        if (! $cmd->isAdmin && $booking->userId->toString() !== $cmd->actorUserId) {
            throw new RuntimeException('Forbidden cancellation');
        }

        $this->tx->transactional(function () use ($booking): void {
            $booking->cancel($this->cancellationPolicy);
            $this->bookingRepo->save($booking);

            if ($booking->type === BookingType::TIME_SLOT && $booking->slotId !== null) {
                $this->slotRepo->markAsFree($booking->slotId);
            }

            $this->dispatcher->dispatchAll($booking->pullDomainEvents());
        });
    }
}
