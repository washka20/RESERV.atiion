<?php

declare(strict_types=1);

namespace App\Modules\Booking;

use App\Modules\Booking\Application\Command\CreateBooking\CreateBookingHandler;
use App\Modules\Booking\Domain\Repository\BookingRepositoryInterface;
use App\Modules\Booking\Domain\Repository\TimeSlotRepositoryInterface;
use App\Modules\Booking\Domain\Service\AvailabilityChecker;
use App\Modules\Booking\Domain\Specification\BookingNotAlreadyCompleted;
use App\Modules\Booking\Domain\Specification\BookingPolicy;
use App\Modules\Booking\Domain\Specification\CancellationPolicy;
use App\Modules\Booking\Domain\Specification\UserNotExceedsLimit;
use App\Modules\Booking\Domain\Specification\WithinBookingWindow;
use App\Modules\Booking\Domain\Specification\WithinCancellationWindow;
use App\Modules\Booking\Infrastructure\Persistence\Repository\EloquentBookingRepository;
use App\Modules\Booking\Infrastructure\Persistence\Repository\EloquentTimeSlotRepository;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Shared\Application\Event\DomainEventDispatcherInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Booking BC service provider.
 *
 * - Биндит доменные интерфейсы репозиториев на Eloquent реализации
 * - Конструирует Booking/Cancellation policies с параметрами из config/booking.php
 * - Команды/запросы разрешаются по соглашению XxxCommand → XxxHandler через LaravelCommandBus
 */
final class Provider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BookingRepositoryInterface::class, EloquentBookingRepository::class);
        $this->app->bind(TimeSlotRepositoryInterface::class, EloquentTimeSlotRepository::class);

        $this->app->singleton(AvailabilityChecker::class);

        $this->app->bind(BookingPolicy::class, static function (Application $app): BookingPolicy {
            return new BookingPolicy(
                new WithinBookingWindow(
                    minAdvanceMinutes: (int) config('booking.min_advance_minutes', 60),
                    maxAdvanceDays: (int) config('booking.max_advance_days', 365),
                ),
                new UserNotExceedsLimit,
            );
        });

        $this->app->bind(CancellationPolicy::class, static function (Application $app): CancellationPolicy {
            return new CancellationPolicy(
                new BookingNotAlreadyCompleted,
                new WithinCancellationWindow(
                    minHoursBefore: (int) config('booking.min_cancellation_hours_before', 24),
                ),
            );
        });

        $this->app->bind(CreateBookingHandler::class, static function (Application $app): CreateBookingHandler {
            return new CreateBookingHandler(
                $app->make(ServiceRepositoryInterface::class),
                $app->make(BookingRepositoryInterface::class),
                $app->make(TimeSlotRepositoryInterface::class),
                $app->make(BookingPolicy::class),
                $app->make(DomainEventDispatcherInterface::class),
                userBookingsLimit: (int) config('booking.user_active_limit', 10),
            );
        });
    }

    public function boot(): void {}
}
