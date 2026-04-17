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
        Schema::create('time_slots', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('service_id')->constrained('services')->cascadeOnDelete();
            $table->timestampTz('start_at');
            $table->timestampTz('end_at');
            $table->boolean('is_booked')->default(false);
            $table->uuid('booking_id')->nullable();
            $table->timestampsTz();

            $table->index(['service_id', 'start_at']);
            $table->index('is_booked');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE time_slots ADD CONSTRAINT time_slots_range_chk CHECK (end_at > start_at)');
            DB::statement('CREATE INDEX time_slots_available_idx ON time_slots (service_id, start_at) WHERE is_booked = false');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('time_slots');
    }
};
