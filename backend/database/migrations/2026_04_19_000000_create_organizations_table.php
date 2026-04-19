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
        Schema::create('organizations', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('slug', 64)->unique();
            $t->jsonb('name');
            $t->jsonb('description');
            $t->string('type', 32);
            $t->string('logo_url')->nullable();
            $t->string('city');
            $t->string('district')->nullable();
            $t->string('phone');
            $t->string('email');
            $t->boolean('verified')->default(false);
            $t->string('cancellation_policy', 16)->default('flexible');
            $t->decimal('rating', 3, 2)->default(0);
            $t->unsignedInteger('reviews_count')->default(0);
            $t->timestampTz('archived_at')->nullable();
            $t->timestampsTz();

            $t->index('type');
            $t->index('city');
            $t->index('verified');
        });

        DB::statement("ALTER TABLE organizations ADD CONSTRAINT organizations_type_chk CHECK (type IN ('salon','rental','consult','other'))");
        DB::statement("ALTER TABLE organizations ADD CONSTRAINT organizations_policy_chk CHECK (cancellation_policy IN ('flexible','moderate','strict'))");
        DB::statement("ALTER TABLE organizations ADD CONSTRAINT organizations_slug_fmt_chk CHECK (slug ~ '^[a-z0-9][a-z0-9-]{1,62}[a-z0-9]$' AND slug !~ '--')");
        DB::statement('CREATE INDEX organizations_active_idx ON organizations (created_at DESC) WHERE archived_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
