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
        Schema::create('bookings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('service_id')->constrained('services')->restrictOnDelete();
            $table->string('type', 20);
            $table->string('status', 20);

            $table->foreignUuid('slot_id')->nullable()->constrained('time_slots')->nullOnDelete();
            $table->timestampTz('start_at')->nullable();
            $table->timestampTz('end_at')->nullable();
            $table->date('check_in')->nullable();
            $table->date('check_out')->nullable();
            $table->unsignedInteger('quantity')->nullable();

            $table->decimal('total_price_amount', 12, 2);
            $table->char('total_price_currency', 3);

            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->index('user_id');
            $table->index('service_id');
            $table->index('status');
            $table->index('slot_id');
        });

        DB::statement("
            ALTER TABLE bookings ADD CONSTRAINT bookings_type_chk
            CHECK (type IN ('time_slot', 'quantity'))
        ");
        DB::statement("
            ALTER TABLE bookings ADD CONSTRAINT bookings_status_chk
            CHECK (status IN ('pending', 'confirmed', 'cancelled', 'completed'))
        ");
        DB::statement("
            ALTER TABLE bookings ADD CONSTRAINT bookings_time_slot_fields_chk
            CHECK (
                type <> 'time_slot' OR (
                    slot_id IS NOT NULL
                    AND start_at IS NOT NULL
                    AND end_at IS NOT NULL
                    AND end_at > start_at
                )
            )
        ");
        DB::statement("
            ALTER TABLE bookings ADD CONSTRAINT bookings_quantity_fields_chk
            CHECK (
                type <> 'quantity' OR (
                    check_in IS NOT NULL
                    AND check_out IS NOT NULL
                    AND check_out > check_in
                    AND quantity IS NOT NULL
                    AND quantity > 0
                )
            )
        ");
        DB::statement("
            CREATE INDEX bookings_quantity_active_idx
            ON bookings (service_id, status, check_in, check_out)
            WHERE type = 'quantity' AND status IN ('pending', 'confirmed')
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
