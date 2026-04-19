<?php

declare(strict_types=1);

namespace Database\Seeders\Identity;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Сидер демо-организаций: platform-admin + два provider'а.
 *
 * UUID-ы фиксированы константами — ServicesSeeder и MembershipsSeeder
 * ссылаются на них. Идемпотентно: updateOrInsert по id.
 */
final class OrganizationsSeeder extends Seeder
{
    public const PLATFORM_ADMIN_ORG_ID = '00000000-0000-0000-0000-000000000001';

    public const SALON_SAVVIN_ID = '00000000-0000-0000-0000-000000000010';

    public const LOFT_23_ID = '00000000-0000-0000-0000-000000000011';

    public function run(): void
    {
        DB::table('organizations')->updateOrInsert(
            ['id' => self::PLATFORM_ADMIN_ORG_ID],
            [
                'slug' => 'platform-admin',
                'name' => json_encode(['ru' => 'Platform Admin', 'en' => 'Platform Admin'], JSON_UNESCAPED_UNICODE),
                'description' => json_encode(['ru' => 'Служебная организация администратора'], JSON_UNESCAPED_UNICODE),
                'type' => 'other',
                'logo_url' => null,
                'city' => 'Moscow',
                'district' => null,
                'phone' => '+7 000 000 00 00',
                'email' => 'admin@platform.local',
                'verified' => true,
                'cancellation_policy' => 'flexible',
                'rating' => 0,
                'reviews_count' => 0,
                'archived_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        DB::table('organizations')->updateOrInsert(
            ['id' => self::SALON_SAVVIN_ID],
            [
                'slug' => 'salon-savvin',
                'name' => json_encode(['ru' => 'Салон Саввин', 'en' => 'Salon Savvin'], JSON_UNESCAPED_UNICODE),
                'description' => json_encode(['ru' => 'Премиальный барбершоп в центре Москвы'], JSON_UNESCAPED_UNICODE),
                'type' => 'salon',
                'logo_url' => null,
                'city' => 'Москва',
                'district' => 'Центральный',
                'phone' => '+7 495 123 45 67',
                'email' => 'hello@savvin.ru',
                'verified' => true,
                'cancellation_policy' => 'moderate',
                'rating' => 4.8,
                'reviews_count' => 142,
                'archived_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        DB::table('organizations')->updateOrInsert(
            ['id' => self::LOFT_23_ID],
            [
                'slug' => 'loft-23',
                'name' => json_encode(['ru' => 'Лофт 23', 'en' => 'Loft 23'], JSON_UNESCAPED_UNICODE),
                'description' => json_encode(['ru' => 'Апартаменты с видом на реку'], JSON_UNESCAPED_UNICODE),
                'type' => 'rental',
                'logo_url' => null,
                'city' => 'Санкт-Петербург',
                'district' => null,
                'phone' => '+7 812 999 88 77',
                'email' => 'hello@loft23.ru',
                'verified' => false,
                'cancellation_policy' => 'strict',
                'rating' => 4.6,
                'reviews_count' => 87,
                'archived_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }
}
