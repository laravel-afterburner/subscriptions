<?php

namespace Afterburner\Subscriptions\Http\Controllers;

use Afterburner\Subscriptions\Actions\Stripe\SyncCompletedCheckout;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class TeamSubscriptionsController
{
    public function __invoke(Team $team): View|RedirectResponse
    {
        Gate::authorize('viewBilling', [$team]);

        if (request('checkout') === 'success') {
            $sessionId = request('session_id');

            if (is_string($sessionId) && $sessionId !== '') {
                try {
                    app(SyncCompletedCheckout::class)($team, $sessionId);
                } catch (\Throwable $exception) {
                    Log::error('Failed to sync completed Stripe checkout session.', [
                        'team_id' => $team->getKey(),
                        'session_id' => $sessionId,
                        'message' => $exception->getMessage(),
                    ]);
                }
            }

            return redirect()
                ->route('teams.subscriptions.index', $team)
                ->with('flash', [
                    'bannerStyle' => 'success',
                    'banner' => 'Subscription updated successfully. Thank you for subscribing.',
                ]);
        }

        if (request('checkout') === 'cancelled') {
            return redirect()
                ->route('teams.subscriptions.index', $team)
                ->with('flash', [
                    'bannerStyle' => 'warning',
                    'banner' => 'Checkout was cancelled. No changes were made to your subscription.',
                ]);
        }

        return view('afterburner-subscriptions::subscriptions.index', [
            'team' => $team,
        ]);
    }
}
