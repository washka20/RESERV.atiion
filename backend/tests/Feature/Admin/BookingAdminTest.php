<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\ValueObject\BookingStatus;
use App\Modules\Booking\Infrastructure\Persistence\Model\BookingModel;
use App\Modules\Booking\Interface\Filament\Resource\BookingResource;
use App\Modules\Booking\Interface\Filament\Resource\BookingResource\Pages\ViewBooking;
use App\Modules\Identity\Domain\ValueObject\UserId;
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
function bookingAdminUser(): UserModel
{
    $admin = UserModel::factory()->create();
    $admin->assignRole('admin');
    actingAs($admin, 'web');

    return $admin;
}

it('admin sees bookings list page', function (): void {
    bookingAdminUser();

    $this->get(BookingResource::getUrl('index'))->assertOk();
});

it('manager also sees bookings list page', function (): void {
    $manager = UserModel::factory()->create();
    $manager->assignRole('manager');
    actingAs($manager, 'web');

    $this->get(BookingResource::getUrl('index'))->assertOk();
});

it('customer cannot access bookings admin page', function (): void {
    $customer = UserModel::factory()->create();
    $customer->assignRole('customer');
    actingAs($customer, 'web');

    $this->get(BookingResource::getUrl('index'))->assertForbidden();
});

it('admin confirms pending booking via action', function (): void {
    $admin = bookingAdminUser();
    $customer = bookingInsertUser('admin-confirm-customer@test.com');
    $categoryId = insertCategory('BookingAdminConfirm');
    $service = saveTimeSlotService('Confirm svc', $categoryId);
    $slotId = bookingInsertTimeSlot($service->id(), '+2 days 10:00', '+2 days 11:00', isBooked: true);
    $bookingId = bookingInsertTimeSlotBooking(
        new UserId((string) $customer->getAuthIdentifier()),
        $service->id(),
        $slotId,
    );

    $record = BookingModel::query()->findOrFail($bookingId);

    Livewire::test(ViewBooking::class, ['record' => $bookingId])
        ->callAction('confirm')
        ->assertHasNoActionErrors();

    $updated = BookingModel::query()->findOrFail($bookingId);
    expect($updated->status)->toBe(BookingStatus::CONFIRMED->value);
});

it('admin cancels pending booking via action', function (): void {
    bookingAdminUser();
    $customer = bookingInsertUser('admin-cancel-customer@test.com');
    $categoryId = insertCategory('BookingAdminCancel');
    $service = saveTimeSlotService('Cancel svc', $categoryId);
    $slotId = bookingInsertTimeSlot($service->id(), '+2 days 10:00', '+2 days 11:00', isBooked: true);
    $bookingId = bookingInsertTimeSlotBooking(
        new UserId((string) $customer->getAuthIdentifier()),
        $service->id(),
        $slotId,
    );
    BookingModel::query()->where('id', $bookingId)->update(['status' => BookingStatus::PENDING->value]);

    Livewire::test(ViewBooking::class, ['record' => $bookingId])
        ->callAction('cancel')
        ->assertHasNoActionErrors();

    $updated = BookingModel::query()->findOrFail($bookingId);
    expect($updated->status)->toBe(BookingStatus::CANCELLED->value);
});

it('admin completes confirmed booking via action', function (): void {
    bookingAdminUser();
    $customer = bookingInsertUser('admin-complete-customer@test.com');
    $categoryId = insertCategory('BookingAdminComplete');
    $service = saveTimeSlotService('Complete svc', $categoryId);
    $slotId = bookingInsertTimeSlot($service->id(), '+2 days 10:00', '+2 days 11:00', isBooked: true);
    $bookingId = bookingInsertTimeSlotBooking(
        new UserId((string) $customer->getAuthIdentifier()),
        $service->id(),
        $slotId,
        overrides: ['status' => BookingStatus::CONFIRMED->value],
    );

    Livewire::test(ViewBooking::class, ['record' => $bookingId])
        ->callAction('complete')
        ->assertHasNoActionErrors();

    $updated = BookingModel::query()->findOrFail($bookingId);
    expect($updated->status)->toBe(BookingStatus::COMPLETED->value);
});

it('does not expose create/edit/delete abilities', function (): void {
    bookingAdminUser();

    expect(BookingResource::canCreate())->toBeFalse();

    $customer = bookingInsertUser('admin-perm-customer@test.com');
    $categoryId = insertCategory('BookingAdminPerm');
    $service = saveTimeSlotService('Perm svc', $categoryId);
    $slotId = bookingInsertTimeSlot($service->id(), '+2 days 10:00', '+2 days 11:00', isBooked: true);
    $bookingId = bookingInsertTimeSlotBooking(
        new UserId((string) $customer->getAuthIdentifier()),
        $service->id(),
        $slotId,
    );

    $record = BookingModel::query()->findOrFail($bookingId);

    expect(BookingResource::canEdit($record))->toBeFalse();
    expect(BookingResource::canDelete($record))->toBeFalse();
});
