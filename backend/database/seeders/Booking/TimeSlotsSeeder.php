<?php

declare(strict_types=1);

namespace Database\Seeders\Booking;

use App\Modules\Booking\Application\Command\GenerateTimeSlots\GenerateTimeSlotsCommand;
use App\Modules\Catalog\Infrastructure\Persistence\Model\ServiceModel;
use App\Shared\Application\Bus\CommandBusInterface;
use Illuminate\Database\Seeder;

/**
 * Генерирует слоты на ближайшую неделю для всех активных TIME_SLOT услуг.
 */
final class TimeSlotsSeeder extends Seeder
{
    public function __construct(private readonly CommandBusInterface $bus) {}

    public function run(): void
    {
        ServiceModel::query()
            ->where('type', 'time_slot')
            ->where('is_active', true)
            ->get()
            ->each(function (ServiceModel $service): void {
                $this->bus->dispatch(new GenerateTimeSlotsCommand(
                    serviceId: $service->id,
                    dateFrom: now()->format('Y-m-d'),
                    dateTo: now()->addDays(7)->format('Y-m-d'),
                    timeFrom: '09:00',
                    timeTo: '18:00',
                    slotDurationMinutes: 60,
                    breakMinutes: 0,
                    excludeDaysOfWeek: [0],
                ));
            });
    }
}
