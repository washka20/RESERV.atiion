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
        Schema::create('organization_payout_settings', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->foreignUuid('organization_id')
                ->unique()
                ->constrained('organizations')
                ->cascadeOnDelete();
            $t->string('bank_name', 255);
            $t->text('account_number_encrypted');
            $t->string('account_number_masked', 20);
            $t->string('account_holder', 255);
            $t->string('bic', 12);
            $t->string('payout_schedule', 20)->default('weekly');
            $t->bigInteger('minimum_payout_cents')->default(100000);
            $t->timestampsTz();
        });

        DB::statement("ALTER TABLE organization_payout_settings ADD CONSTRAINT org_payout_settings_schedule_check CHECK (payout_schedule IN ('weekly','biweekly','monthly','on_request'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_payout_settings');
    }
};
