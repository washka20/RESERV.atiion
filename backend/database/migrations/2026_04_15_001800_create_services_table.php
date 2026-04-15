<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('category_id')->constrained('categories')->restrictOnDelete();
            $t->foreignUuid('subcategory_id')->nullable()->constrained('subcategories')->nullOnDelete();
            $t->string('name');
            $t->text('description');
            $t->bigInteger('price_amount');
            $t->char('price_currency', 3)->default('RUB');
            $t->string('type', 32);
            $t->integer('duration_minutes')->nullable();
            $t->integer('total_quantity')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->index('category_id');
            $t->index('subcategory_id');
            $t->index('type');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE services
                ADD CONSTRAINT services_type_check CHECK (type IN ('time_slot','quantity')),
                ADD CONSTRAINT services_time_slot_has_duration CHECK (
                    type <> 'time_slot' OR duration_minutes IS NOT NULL
                ),
                ADD CONSTRAINT services_quantity_has_total CHECK (
                    type <> 'quantity' OR total_quantity IS NOT NULL
                ),
                ADD CONSTRAINT services_price_non_negative CHECK (price_amount >= 0)
            SQL);

            DB::statement(
                'CREATE INDEX services_is_active_idx ON services (is_active) WHERE is_active = true'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
