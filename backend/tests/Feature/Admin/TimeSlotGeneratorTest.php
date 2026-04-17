<?php

declare(strict_types=1);

use App\Modules\Booking\Infrastructure\Persistence\Model\TimeSlotModel;
use App\Modules\Booking\Interface\Filament\Page\GenerateTimeSlotsPage;
use App\Modules\Booking\Interface\Filament\Resource\TimeSlotResource;
use App\Modules\Identity\Infrastructure\Persistence\Model\UserModel;
use Database\Seeders\SpatieRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(SpatieRoleSeeder::class);
});

/*
 * Создаёт admin-пользователя и логинит его в web-guard.
 */
function timeSlotAdminUser(): UserModel
{
    $admin = UserModel::factory()->create();
    $admin->assignRole('admin');
    actingAs($admin, 'web');

    return $admin;
}

it('admin sees time slots list page', function (): void {
    timeSlotAdminUser();

    $this->get(TimeSlotResource::getUrl('index'))->assertOk();
});

it('manager also sees time slots list page', function (): void {
    $manager = UserModel::factory()->create();
    $manager->assignRole('manager');
    actingAs($manager, 'web');

    $this->get(TimeSlotResource::getUrl('index'))->assertOk();
});

it('customer cannot access time slots admin page', function (): void {
    $customer = UserModel::factory()->create();
    $customer->assignRole('customer');
    actingAs($customer, 'web');

    $this->get(TimeSlotResource::getUrl('index'))->assertForbidden();
});

it('admin generates time slots via Livewire form', function (): void {
    timeSlotAdminUser();
    $catId = insertCategory('TimeSlotGen');
    $service = saveTimeSlotService('Haircut', $catId);

    Livewire::test(GenerateTimeSlotsPage::class)
        ->set('data.service_id', $service->id()->toString())
        ->set('data.date_from', now()->addDay()->format('Y-m-d'))
        ->set('data.date_to', now()->addDays(2)->format('Y-m-d'))
        ->set('data.time_from', '10:00')
        ->set('data.time_to', '13:00')
        ->set('data.slot_duration_minutes', 60)
        ->set('data.break_minutes', 0)
        ->set('data.exclude_days_of_week', [])
        ->call('submit');

    expect(TimeSlotModel::query()->where('service_id', $service->id()->toString())->count())->toBe(6);
});

it('customer cannot access generate time slots page', function (): void {
    $customer = UserModel::factory()->create();
    $customer->assignRole('customer');
    actingAs($customer, 'web');

    $this->get(GenerateTimeSlotsPage::getUrl())->assertForbidden();
});

it('does not expose create/edit/delete abilities on time slot resource', function (): void {
    timeSlotAdminUser();

    expect(TimeSlotResource::canCreate())->toBeFalse();

    $catId = insertCategory('TimeSlotPerm');
    $service = saveTimeSlotService('Perm svc', $catId);
    $slotId = bookingInsertTimeSlot($service->id(), '+2 days 10:00', '+2 days 11:00');
    $record = TimeSlotModel::query()->findOrFail($slotId);

    expect(TimeSlotResource::canEdit($record))->toBeFalse();
    expect(TimeSlotResource::canDelete($record))->toBeFalse();
});
