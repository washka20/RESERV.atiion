<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\Command\CreateService\CreateServiceCommand;
use App\Modules\Catalog\Infrastructure\Persistence\Model\ServiceModel;
use App\Modules\Catalog\Interface\Filament\Resource\ServiceResource\Pages\EditService;
use App\Modules\Identity\Infrastructure\Persistence\Model\UserModel;
use App\Shared\Application\Bus\CommandBusInterface;
use Database\Seeders\CategoriesSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SpatieRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([RoleSeeder::class, SpatieRoleSeeder::class, CategoriesSeeder::class]);
});

it('admin edits service via filament form', function (): void {
    $admin = UserModel::factory()->create();
    $admin->assignRole('admin');
    actingAs($admin, 'web');

    $categoryId = DB::table('categories')->value('id');
    $bus = app(CommandBusInterface::class);

    /** @var string $serviceId */
    $serviceId = $bus->dispatch(new CreateServiceCommand(
        name: 'Initial Name',
        description: 'Initial description long enough',
        priceAmount: 100000,
        priceCurrency: 'RUB',
        type: 'time_slot',
        categoryId: $categoryId,
        durationMinutes: 30,
    ));

    Livewire::test(EditService::class, ['record' => $serviceId])
        ->set('data.name', 'Updated Name')
        ->set('data.description', 'Updated description for service')
        ->set('data.price_amount', 300000)
        ->set('data.price_currency', 'USD')
        ->call('save')
        ->assertHasNoFormErrors();

    $service = ServiceModel::query()->findOrFail($serviceId);
    expect($service->name)->toBe('Updated Name');
    expect($service->description)->toBe('Updated description for service');
    expect($service->price_amount)->toBe(300000);
    expect($service->price_currency)->toBe('USD');
});
