<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Catalog\Application\Command\CreateService\CreateServiceCommand;
use App\Shared\Application\Bus\CommandBusInterface;
use Database\Seeders\Identity\OrganizationsSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Сидер примеров услуг каталога: 5 TIME_SLOT + 5 QUANTITY.
 *
 * Цены хранятся в копейках (amount × 100). Зависит от CategoriesSeeder,
 * SubcategoriesSeeder (lookup по slug) и Identity\OrganizationsSeeder
 * (organization_id lookup по фиксированным константам).
 *
 * TIME_SLOT (стрижки, консультации) → Salon Savvin.
 * QUANTITY (отели) → Loft 23.
 */
final class ServicesSeeder extends Seeder
{
    public function __construct(private readonly CommandBusInterface $commandBus) {}

    public function run(): void
    {
        $this->assertOrganizationExists(OrganizationsSeeder::SALON_SAVVIN_ID, 'salon-savvin');
        $this->assertOrganizationExists(OrganizationsSeeder::LOFT_23_ID, 'loft-23');

        $haircutsCat = $this->categoryIdBySlug('haircuts');
        $hotelsCat = $this->categoryIdBySlug('hotels');
        $consultCat = $this->categoryIdBySlug('consultations');

        $menSubcat = $this->subcategoryIdBySlug('men-haircuts');
        $womenSubcat = $this->subcategoryIdBySlug('women-haircuts');
        $standardSubcat = $this->subcategoryIdBySlug('standard-rooms');
        $luxurySubcat = $this->subcategoryIdBySlug('luxury-rooms');

        $services = [
            [
                'name' => 'Мужская стрижка',
                'description' => 'Классическая мужская стрижка ножницами',
                'priceAmount' => 150000,
                'type' => 'time_slot',
                'categoryId' => $haircutsCat,
                'subcategoryId' => $menSubcat,
                'durationMinutes' => 45,
                'totalQuantity' => null,
                'organizationId' => OrganizationsSeeder::SALON_SAVVIN_ID,
            ],
            [
                'name' => 'Женская стрижка',
                'description' => 'Женская стрижка с укладкой',
                'priceAmount' => 250000,
                'type' => 'time_slot',
                'categoryId' => $haircutsCat,
                'subcategoryId' => $womenSubcat,
                'durationMinutes' => 60,
                'totalQuantity' => null,
                'organizationId' => OrganizationsSeeder::SALON_SAVVIN_ID,
            ],
            [
                'name' => 'Детская стрижка',
                'description' => 'Стрижка для детей до 12 лет',
                'priceAmount' => 100000,
                'type' => 'time_slot',
                'categoryId' => $haircutsCat,
                'subcategoryId' => null,
                'durationMinutes' => 30,
                'totalQuantity' => null,
                'organizationId' => OrganizationsSeeder::SALON_SAVVIN_ID,
            ],
            [
                'name' => 'Консультация юриста',
                'description' => 'Юридическая консультация по гражданским делам',
                'priceAmount' => 300000,
                'type' => 'time_slot',
                'categoryId' => $consultCat,
                'subcategoryId' => null,
                'durationMinutes' => 60,
                'totalQuantity' => null,
                'organizationId' => OrganizationsSeeder::SALON_SAVVIN_ID,
            ],
            [
                'name' => 'Психологическая консультация',
                'description' => 'Индивидуальная консультация с психологом',
                'priceAmount' => 400000,
                'type' => 'time_slot',
                'categoryId' => $consultCat,
                'subcategoryId' => null,
                'durationMinutes' => 90,
                'totalQuantity' => null,
                'organizationId' => OrganizationsSeeder::SALON_SAVVIN_ID,
            ],
            [
                'name' => 'Номер Стандарт',
                'description' => 'Стандартный двухместный номер, цена за сутки',
                'priceAmount' => 500000,
                'type' => 'quantity',
                'categoryId' => $hotelsCat,
                'subcategoryId' => $standardSubcat,
                'durationMinutes' => null,
                'totalQuantity' => 10,
                'organizationId' => OrganizationsSeeder::LOFT_23_ID,
            ],
            [
                'name' => 'Номер Люкс',
                'description' => 'Люксовый номер с панорамным видом, цена за сутки',
                'priceAmount' => 1000000,
                'type' => 'quantity',
                'categoryId' => $hotelsCat,
                'subcategoryId' => $luxurySubcat,
                'durationMinutes' => null,
                'totalQuantity' => 5,
                'organizationId' => OrganizationsSeeder::LOFT_23_ID,
            ],
            [
                'name' => 'Номер Семейный',
                'description' => 'Семейный номер для 4 человек, цена за сутки',
                'priceAmount' => 800000,
                'type' => 'quantity',
                'categoryId' => $hotelsCat,
                'subcategoryId' => $standardSubcat,
                'durationMinutes' => null,
                'totalQuantity' => 7,
                'organizationId' => OrganizationsSeeder::LOFT_23_ID,
            ],
            [
                'name' => 'Номер Президентский',
                'description' => 'Президентский сьют со всеми удобствами, цена за сутки',
                'priceAmount' => 2500000,
                'type' => 'quantity',
                'categoryId' => $hotelsCat,
                'subcategoryId' => $luxurySubcat,
                'durationMinutes' => null,
                'totalQuantity' => 2,
                'organizationId' => OrganizationsSeeder::LOFT_23_ID,
            ],
            [
                'name' => 'Эконом номер',
                'description' => 'Бюджетный одноместный номер, цена за сутки',
                'priceAmount' => 300000,
                'type' => 'quantity',
                'categoryId' => $hotelsCat,
                'subcategoryId' => $standardSubcat,
                'durationMinutes' => null,
                'totalQuantity' => 15,
                'organizationId' => OrganizationsSeeder::LOFT_23_ID,
            ],
        ];

        foreach ($services as $s) {
            if (DB::table('services')->where('name', $s['name'])->exists()) {
                continue;
            }

            $this->commandBus->dispatch(new CreateServiceCommand(
                name: $s['name'],
                description: $s['description'],
                priceAmount: $s['priceAmount'],
                priceCurrency: 'RUB',
                type: $s['type'],
                categoryId: $s['categoryId'],
                organizationId: $s['organizationId'],
                subcategoryId: $s['subcategoryId'],
                durationMinutes: $s['durationMinutes'],
                totalQuantity: $s['totalQuantity'],
            ));
        }
    }

    private function assertOrganizationExists(string $id, string $slug): void
    {
        $exists = DB::table('organizations')->where('id', $id)->exists();
        if (! $exists) {
            throw new RuntimeException(
                "Organization '{$slug}' (id={$id}) not found. Run Identity\\OrganizationsSeeder first."
            );
        }
    }

    private function categoryIdBySlug(string $slug): string
    {
        $id = DB::table('categories')->where('slug', $slug)->value('id');

        if ($id === null) {
            throw new RuntimeException("Category with slug '{$slug}' not found. Run CategoriesSeeder first.");
        }

        return (string) $id;
    }

    private function subcategoryIdBySlug(string $slug): string
    {
        $id = DB::table('subcategories')->where('slug', $slug)->value('id');

        if ($id === null) {
            throw new RuntimeException("Subcategory with slug '{$slug}' not found. Run SubcategoriesSeeder first.");
        }

        return (string) $id;
    }
}
