<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $defaultOrgId = DB::table('organizations')->value('id');

        if ($defaultOrgId === null) {
            $defaultOrgId = (string) Str::uuid();
            DB::table('organizations')->insert([
                'id' => $defaultOrgId,
                'slug' => 'platform-default',
                'name' => json_encode(['ru' => 'Платформа (default)'], JSON_UNESCAPED_UNICODE),
                'description' => json_encode(['ru' => ''], JSON_UNESCAPED_UNICODE),
                'type' => 'other',
                'city' => 'Москва',
                'phone' => '79990000000',
                'email' => 'noreply@reserv.atiion',
                'verified' => false,
                'cancellation_policy' => 'flexible',
                'rating' => 0.0,
                'reviews_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('services')
            ->whereNull('organization_id')
            ->update(['organization_id' => $defaultOrgId]);

        Schema::table('services', function (Blueprint $t): void {
            $t->uuid('organization_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $t): void {
            $t->uuid('organization_id')->nullable()->change();
        });
    }
};
