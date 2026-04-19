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
        Schema::create('payments', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->foreignUuid('booking_id')
                ->unique()
                ->constrained('bookings')
                ->restrictOnDelete();
            $t->bigInteger('amount_cents');
            $t->char('currency', 3)->default('RUB');
            $t->string('status', 20)->default('pending');
            $t->string('method', 30)->default('null_gateway');
            $t->string('provider_ref', 255)->nullable();
            $t->smallInteger('marketplace_fee_percent');
            $t->bigInteger('platform_fee_cents');
            $t->bigInteger('net_amount_cents');
            $t->timestampTz('paid_at')->nullable();
            $t->timestampsTz();

            $t->index('status');
        });

        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_status_check CHECK (status IN ('pending','paid','refunded','failed'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
