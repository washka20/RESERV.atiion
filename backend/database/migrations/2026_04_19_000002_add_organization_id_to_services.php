<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $t): void {
            // Nullable сначала — существующие rows backfill'ится через ServicesSeeder (Task 18).
            // NOT NULL constraint применяется отдельной миграцией после backfill.
            $t->foreignUuid('organization_id')
                ->nullable()
                ->after('subcategory_id')
                ->constrained('organizations')
                ->restrictOnDelete();

            $t->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $t): void {
            $t->dropForeign(['organization_id']);
            $t->dropIndex(['organization_id']);
            $t->dropColumn('organization_id');
        });
    }
};
