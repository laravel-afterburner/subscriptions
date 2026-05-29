<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            if (! Schema::hasColumn('teams', 'stripe_id')) {
                $table->string('stripe_id')->nullable()->index();
            }

            if (! Schema::hasColumn('teams', 'pm_type')) {
                $table->string('pm_type')->nullable();
            }

            if (! Schema::hasColumn('teams', 'pm_last_four')) {
                $table->string('pm_last_four', 4)->nullable();
            }

            if (! Schema::hasColumn('teams', 'trial_ends_at')) {
                $table->timestamp('trial_ends_at')->nullable();
            }

            if (! Schema::hasColumn('teams', 'subscription_plan_id')) {
                $table->foreignId('subscription_plan_id')
                    ->nullable()
                    ->constrained('subscription_plans')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('teams', 'billing_email')) {
                $table->string('billing_email')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            if (Schema::hasColumn('teams', 'subscription_plan_id')) {
                $table->dropConstrainedForeignId('subscription_plan_id');
            }

            foreach (['billing_email', 'trial_ends_at', 'pm_last_four', 'pm_type', 'stripe_id'] as $column) {
                if (Schema::hasColumn('teams', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
