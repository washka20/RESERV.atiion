<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\ValueObject\MembershipRole;

it('creates from string', function (): void {
    expect(MembershipRole::from('owner'))->toBe(MembershipRole::OWNER);
    expect(MembershipRole::from('admin'))->toBe(MembershipRole::ADMIN);
    expect(MembershipRole::from('staff'))->toBe(MembershipRole::STAFF);
    expect(MembershipRole::from('viewer'))->toBe(MembershipRole::VIEWER);
});

it('owner can everything', function (string $permission): void {
    expect(MembershipRole::OWNER->can($permission))->toBeTrue();
})->with([
    'services.create', 'services.edit', 'services.delete',
    'bookings.confirm', 'bookings.cancel', 'bookings.view',
    'payouts.view', 'team.view', 'team.manage',
    'settings.view', 'settings.manage', 'organization.archive',
]);

it('admin can services + bookings + payouts + settings, но не archive и не team.manage', function (): void {
    expect(MembershipRole::ADMIN->can('services.create'))->toBeTrue();
    expect(MembershipRole::ADMIN->can('services.edit'))->toBeTrue();
    expect(MembershipRole::ADMIN->can('services.delete'))->toBeTrue();
    expect(MembershipRole::ADMIN->can('bookings.confirm'))->toBeTrue();
    expect(MembershipRole::ADMIN->can('payouts.view'))->toBeTrue();
    expect(MembershipRole::ADMIN->can('settings.manage'))->toBeTrue();
    expect(MembershipRole::ADMIN->can('team.view'))->toBeTrue();

    expect(MembershipRole::ADMIN->can('team.manage'))->toBeFalse();
    expect(MembershipRole::ADMIN->can('organization.archive'))->toBeFalse();
});

it('staff can operate on bookings + edit services, не delete, не payouts', function (): void {
    expect(MembershipRole::STAFF->can('services.edit'))->toBeTrue();
    expect(MembershipRole::STAFF->can('bookings.confirm'))->toBeTrue();
    expect(MembershipRole::STAFF->can('bookings.cancel'))->toBeTrue();
    expect(MembershipRole::STAFF->can('bookings.view'))->toBeTrue();
    expect(MembershipRole::STAFF->can('team.view'))->toBeTrue();

    expect(MembershipRole::STAFF->can('services.create'))->toBeFalse();
    expect(MembershipRole::STAFF->can('services.delete'))->toBeFalse();
    expect(MembershipRole::STAFF->can('payouts.view'))->toBeFalse();
    expect(MembershipRole::STAFF->can('team.manage'))->toBeFalse();
    expect(MembershipRole::STAFF->can('settings.manage'))->toBeFalse();
    expect(MembershipRole::STAFF->can('organization.archive'))->toBeFalse();
});

it('viewer can only view bookings, ничего не может менять', function (): void {
    expect(MembershipRole::VIEWER->can('bookings.view'))->toBeTrue();

    expect(MembershipRole::VIEWER->can('services.edit'))->toBeFalse();
    expect(MembershipRole::VIEWER->can('services.create'))->toBeFalse();
    expect(MembershipRole::VIEWER->can('bookings.confirm'))->toBeFalse();
    expect(MembershipRole::VIEWER->can('team.view'))->toBeFalse();
    expect(MembershipRole::VIEWER->can('payouts.view'))->toBeFalse();
});

it('unknown permission returns false for any role', function (MembershipRole $role): void {
    expect($role->can('totally.unknown'))->toBeFalse();
})->with([
    'owner' => MembershipRole::OWNER,
    'admin' => MembershipRole::ADMIN,
    'staff' => MembershipRole::STAFF,
    'viewer' => MembershipRole::VIEWER,
]);

it('lists all known permissions', function (): void {
    $permissions = MembershipRole::permissions();
    expect($permissions)->toContain('services.create');
    expect($permissions)->toContain('organization.archive');
    expect(count($permissions))->toBeGreaterThanOrEqual(10);
});
