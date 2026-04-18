<?php

declare(strict_types=1);

namespace Database\Seeders\Booking;

use App\Modules\Booking\Application\Command\CreateBooking\CreateBookingCommand;
use App\Modules\Booking\Infrastructure\Persistence\Model\TimeSlotModel;
use App\Modules\Catalog\Infrastructure\Persistence\Model\ServiceModel;
use App\Shared\Application\Bus\CommandBusInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Sample bookings для test@example.com — один TIME_SLOT, один QUANTITY.
 * Идемпотентен: ничего не делает, если у test-юзера уже есть бронирования.
 */
final class BookingsSeeder extends Seeder
{
    public function __construct(private readonly CommandBusInterface $bus) {}

    public function run(): void
    {
        $userId = DB::table('users')->where('email', 'test@example.com')->value('id');
        if ($userId === null) {
            return;
        }

        $alreadySeeded = DB::table('bookings')->where('user_id', $userId)->exists();
        if ($alreadySeeded) {
            return;
        }

        $this->seedTimeSlotBooking((string) $userId);
        $this->seedQuantityBooking((string) $userId);
    }

    private function seedTimeSlotBooking(string $userId): void
    {
        $slot = TimeSlotModel::query()
            ->where('is_booked', false)
            ->where('start_at', '>', now()->addHours(4))
            ->orderBy('start_at')
            ->first();

        if ($slot === null) {
            return;
        }

        $this->bus->dispatch(new CreateBookingCommand(
            userId: $userId,
            serviceId: $slot->service_id,
            slotId: $slot->id,
            notes: 'Sample TIME_SLOT booking',
        ));
    }

    private function seedQuantityBooking(string $userId): void
    {
        $service = ServiceModel::query()
            ->where('type', 'quantity')
            ->where('is_active', true)
            ->first();

        if ($service === null) {
            return;
        }

        $this->bus->dispatch(new CreateBookingCommand(
            userId: $userId,
            serviceId: $service->id,
            checkIn: now()->addDays(3)->format('Y-m-d'),
            checkOut: now()->addDays(5)->format('Y-m-d'),
            quantity: 1,
            notes: 'Sample QUANTITY booking',
        ));
    }
}
