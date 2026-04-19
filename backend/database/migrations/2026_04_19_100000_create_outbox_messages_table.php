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
        Schema::create('outbox_messages', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->uuid('aggregate_id')->index();
            $t->string('event_type', 255)->index();
            $t->jsonb('payload');
            $t->string('status', 20)->default('pending');
            $t->unsignedInteger('retry_count')->default(0);
            $t->timestampTz('next_attempt_at')->nullable();
            $t->timestampTz('published_at')->nullable();
            $t->timestampTz('failed_at')->nullable();
            $t->text('last_error')->nullable();
            $t->timestampsTz();
            $t->index(['status', 'next_attempt_at'], 'outbox_pending_idx');
        });

        DB::statement("ALTER TABLE outbox_messages ADD CONSTRAINT outbox_status_check CHECK (status IN ('pending','published','failed'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_messages');
    }
};
