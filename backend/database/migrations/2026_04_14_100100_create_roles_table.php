<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('name', 50)->unique();
            $t->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
