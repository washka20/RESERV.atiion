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
        Schema::create('memberships', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $t->string('role', 16);
            $t->foreignUuid('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestampTz('accepted_at')->nullable();
            $t->timestampsTz();

            // Один user имеет ровно одно Membership в одной organization.
            // Повторные invite'ы или grant'ы меняют роль existing членства, не создают дубли.
            $t->unique(['user_id', 'organization_id']);
            $t->index('user_id');
            $t->index('organization_id');
        });

        DB::statement("ALTER TABLE memberships ADD CONSTRAINT memberships_role_chk CHECK (role IN ('owner','admin','staff','viewer'))");
        // Partial index для быстрого подсчёта owner'ов организации (last-owner protection).
        DB::statement("CREATE INDEX memberships_owners_idx ON memberships (organization_id) WHERE role = 'owner'");
    }

    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
