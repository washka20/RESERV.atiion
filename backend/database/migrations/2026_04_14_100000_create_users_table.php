<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('email', 255)->unique();
            $t->string('first_name', 120);
            $t->string('last_name', 120);
            $t->string('middle_name', 120)->nullable();
            $t->string('password', 255);
            $t->timestampTz('email_verified_at')->nullable();
            $t->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
