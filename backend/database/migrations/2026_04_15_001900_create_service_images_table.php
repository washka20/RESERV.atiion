<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_images', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('service_id')->constrained('services')->cascadeOnDelete();
            $t->string('path');
            $t->integer('sort_order')->default(0);
            $t->timestamps();
            $t->index(['service_id', 'sort_order']);
            $t->unique(['service_id', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_images');
    }
};
