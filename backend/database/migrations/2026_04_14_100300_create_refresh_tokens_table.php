<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('refresh_tokens', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->uuid('user_id');
            $t->string('token_hash', 64)->unique();
            $t->timestampTz('expires_at');
            $t->timestampTz('revoked_at')->nullable();
            $t->timestampsTz();
            $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $t->index(['user_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refresh_tokens');
    }
};
