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
        Schema::create('payout_transactions', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->foreignUuid('booking_id')
                ->unique()
                ->constrained('bookings')
                ->restrictOnDelete();
            $t->foreignUuid('organization_id')
                ->constrained('organizations')
                ->restrictOnDelete();
            $t->foreignUuid('payment_id')
                ->constrained('payments')
                ->restrictOnDelete();
            $t->bigInteger('gross_amount_cents');
            $t->bigInteger('platform_fee_cents');
            $t->bigInteger('net_amount_cents');
            $t->char('currency', 3)->default('RUB');
            $t->string('status', 20)->default('pending');
            $t->timestampTz('scheduled_at')->nullable();
            $t->timestampTz('paid_at')->nullable();
            $t->text('failure_reason')->nullable();
            $t->timestampsTz();

            $t->index('organization_id');
            $t->index('status');
            $t->index(['organization_id', 'status'], 'payout_tx_org_status_idx');
        });

        DB::statement("ALTER TABLE payout_transactions ADD CONSTRAINT payout_tx_status_check CHECK (status IN ('pending','processing','paid','failed'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_transactions');
    }
};
