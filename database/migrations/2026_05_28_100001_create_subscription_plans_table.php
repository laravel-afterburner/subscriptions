<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('currency', 3)->default('usd');
            $table->string('stripe_product_id')->nullable();
            $table->unsignedInteger('monthly_price_cents');
            $table->unsignedInteger('annual_price_cents');
            $table->string('stripe_price_id_monthly')->nullable();
            $table->string('stripe_price_id_annual')->nullable();
            $table->unsignedInteger('trial_days')->default(30);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

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
            $table->dropConstrainedForeignId('subscription_plan_id');

            foreach (['billing_email', 'trial_ends_at', 'pm_last_four', 'pm_type', 'stripe_id'] as $column) {
                if (Schema::hasColumn('teams', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('subscription_plans');
    }
};
