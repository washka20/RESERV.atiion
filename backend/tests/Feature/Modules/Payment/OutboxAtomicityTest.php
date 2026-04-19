<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Modules\Payment\Domain\Entity\Payment;
use App\Modules\Payment\Domain\Repository\PaymentRepositoryInterface;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Modules\Payment\Domain\ValueObject\PaymentMethod;
use App\Modules\Payment\Domain\ValueObject\Percentage;
use App\Shared\Application\Outbox\OutboxPublisherInterface;
use App\Shared\Domain\DomainEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('rolls back Payment row and outbox message atomically when writer fails', function (): void {
    $failingPublisher = new class implements OutboxPublisherInterface
    {
        public function publish(DomainEvent $event, bool $reliable = false): void
        {
            throw new RuntimeException('boom');
        }
    };
    $this->app->instance(OutboxPublisherInterface::class, $failingPublisher);

    $user = bookingInsertUser('atomicity-'.uniqid().'@test.com');
    $categoryId = insertCategory('Atomicity-'.uniqid());
    $service = saveTimeSlotService('AtomServ', $categoryId);
    $slotId = bookingInsertTimeSlot($service->id());
    $bookingIdStr = bookingInsertTimeSlotBooking(
        new UserId((string) $user->getAuthIdentifier()),
        $service->id(),
        $slotId,
    );
    $bookingId = new BookingId($bookingIdStr);

    try {
        DB::transaction(function () use ($bookingId): void {
            $payment = Payment::initiate(
                PaymentId::generate(),
                $bookingId,
                Money::fromCents(100_000),
                PaymentMethod::CARD,
                Percentage::fromInt(10),
            );

            app(PaymentRepositoryInterface::class)->save($payment);

            foreach ($payment->pullDomainEvents() as $event) {
                app(OutboxPublisherInterface::class)->publish($event, reliable: true);
            }
        });
        test()->fail('expected RuntimeException from writer');
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toBe('boom');
    }

    expect(DB::table('payments')->where('booking_id', $bookingId->toString())->count())->toBe(0);
    expect(DB::table('outbox_messages')->count())->toBe(0);
});
