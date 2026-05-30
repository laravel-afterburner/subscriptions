<?php

use Afterburner\Subscriptions\Http\Controllers\SubscriptionPlansAdminController;
use Afterburner\Subscriptions\Http\Controllers\SubscriptionPromotionsAdminController;
use Afterburner\Subscriptions\Http\Controllers\TeamSubscriptionsController;
use Afterburner\Subscriptions\Http\Controllers\WebhookController;
use Afterburner\Subscriptions\Models\SubscriptionPlan;
use App\Models\Team;
use Illuminate\Support\Facades\Route;

Route::post('/stripe/webhook', [WebhookController::class, 'handleWebhook'])
    ->name('stripe.webhook');

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    if (! config('afterburner-subscriptions.enabled', true)) {
        return;
    }

    Route::get('/teams/{team}/subscriptions', TeamSubscriptionsController::class)
        ->middleware('can:viewBilling,team')
        ->name('teams.subscriptions.index');

    Route::get('/teams/{team}/subscriptions/billing-portal', function (Team $team) {
        abort_unless(auth()->user()?->can('manageBilling', $team), 403);

        return $team->redirectToBillingPortal(route('teams.subscriptions.index', $team));
    })->name('teams.subscriptions.billing-portal');

    Route::middleware('system.admin')->prefix('admin')->group(function () {
        Route::get('/subscription-plans', [SubscriptionPlansAdminController::class, 'index'])
            ->name('admin.subscription-plans.index');

        Route::get('/subscription-plans/create', [SubscriptionPlansAdminController::class, 'create'])
            ->name('admin.subscription-plans.create');

        Route::get('/subscription-plans/promotion-codes/create', [SubscriptionPromotionsAdminController::class, 'create'])
            ->name('admin.subscription-plans.promotion-codes.create');

        Route::get('/subscription-plans/promotion-codes/{promotion}', [SubscriptionPromotionsAdminController::class, 'show'])
            ->whereNumber('promotion')
            ->name('admin.subscription-plans.promotion-codes.show');

        Route::get('/subscription-plans/promotion-codes/{promotion}/edit', [SubscriptionPromotionsAdminController::class, 'edit'])
            ->whereNumber('promotion')
            ->name('admin.subscription-plans.promotion-codes.edit');

        Route::get('/subscription-plans/{plan}', [SubscriptionPlansAdminController::class, 'show'])
            ->whereNumber('plan')
            ->name('admin.subscription-plans.show');

        Route::get('/subscription-plans/{plan}/edit', [SubscriptionPlansAdminController::class, 'edit'])
            ->whereNumber('plan')
            ->name('admin.subscription-plans.edit');
    });
});
