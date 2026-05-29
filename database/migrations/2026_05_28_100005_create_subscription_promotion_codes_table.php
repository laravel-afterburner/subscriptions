<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_promotion_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('stripe_coupon_id')->nullable();
            $table->string('stripe_promotion_code_id')->nullable();
            $table->unsignedTinyInteger('percent_off')->nullable();
            $table->unsignedInteger('amount_off_cents')->nullable();
            $table->string('duration')->default('once');
            $table->unsignedTinyInteger('duration_in_months')->nullable();
            $table->unsignedInteger('max_redemptions')->nullable();
            $table->timestamp('redeem_by')->nullable();
            $table->foreignId('subscription_plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_promotion_codes');
    }
};
