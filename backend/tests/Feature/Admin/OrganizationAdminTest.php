<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Model\OrganizationModel;
use App\Modules\Identity\Infrastructure\Persistence\Model\UserModel;
use App\Modules\Identity\Interface\Filament\Resource\MembershipResource;
use App\Modules\Identity\Interface\Filament\Resource\OrganizationResource;
use App\Modules\Identity\Interface\Filament\Resource\OrganizationResource\Pages\ViewOrganization;
use Database\Seeders\SpatieRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(SpatieRoleSeeder::class);
});

function orgAdminUser(): UserModel
{
    $admin = UserModel::factory()->create();
    $admin->assignRole('admin');
    actingAs($admin, 'web');

    return $admin;
}

function orgManagerUser(): UserModel
{
    $manager = UserModel::factory()->create();
    $manager->assignRole('manager');
    actingAs($manager, 'web');

    return $manager;
}

function orgCustomerUser(): UserModel
{
    $customer = UserModel::factory()->create();
    $customer->assignRole('customer');
    actingAs($customer, 'web');

    return $customer;
}

it('admin sees organizations list page', function (): void {
    orgAdminUser();

    test()->get(OrganizationResource::getUrl('index'))->assertOk();
});

it('manager sees organizations list page', function (): void {
    orgManagerUser();

    test()->get(OrganizationResource::getUrl('index'))->assertOk();
});

it('customer cannot access organizations admin page', function (): void {
    orgCustomerUser();

    test()->get(OrganizationResource::getUrl('index'))->assertForbidden();
});

it('admin verifies unverified organization via action', function (): void {
    orgAdminUser();
    $orgId = insertOrganizationForTests('verify-target');

    Livewire::test(ViewOrganization::class, ['record' => $orgId->toString()])
        ->callAction('verify')
        ->assertHasNoActionErrors();

    $record = OrganizationModel::query()->findOrFail($orgId->toString());
    expect($record->verified)->toBeTrue();
});

it('admin archives active organization via action', function (): void {
    orgAdminUser();
    $orgId = insertOrganizationForTests('archive-target');

    Livewire::test(ViewOrganization::class, ['record' => $orgId->toString()])
        ->callAction('archive')
        ->assertHasNoActionErrors();

    $record = OrganizationModel::query()->findOrFail($orgId->toString());
    expect($record->archived_at)->not()->toBeNull();
});

it('does not expose create/edit/delete abilities', function (): void {
    orgAdminUser();
    $orgId = insertOrganizationForTests('perm-check');
    $record = OrganizationModel::query()->findOrFail($orgId->toString());

    expect(OrganizationResource::canCreate())->toBeFalse();
    expect(OrganizationResource::canEdit($record))->toBeFalse();
    expect(OrganizationResource::canDelete($record))->toBeFalse();
});

it('admin sees memberships list page', function (): void {
    orgAdminUser();

    test()->get(MembershipResource::getUrl('index'))->assertOk();
});

it('customer cannot access memberships admin page', function (): void {
    orgCustomerUser();

    test()->get(MembershipResource::getUrl('index'))->assertForbidden();
});

it('memberships resource has no write abilities', function (): void {
    orgAdminUser();

    expect(MembershipResource::canCreate())->toBeFalse();
});
