<?php

namespace Afterburner\Subscriptions\Tests\Feature;

use Afterburner\Subscriptions\Enums\PromotionDuration;
use Afterburner\Subscriptions\Livewire\Admin\SubscriptionPromotions\Show;
use Afterburner\Subscriptions\Models\SubscriptionPromotionCode;
use Afterburner\Subscriptions\Tests\TestCase;
use Livewire\Livewire;

class SubscriptionPromotionAccessTest extends TestCase
{
    public function test_system_admin_can_view_promotion_show_component(): void
    {
        [$user] = $this->createTeamWithUser();
        $user->update(['is_system_admin' => true]);

        $promotion = SubscriptionPromotionCode::query()->create([
            'code' => 'SAVE20',
            'name' => 'Save 20%',
            'percent_off' => 20,
            'duration' => PromotionDuration::Once,
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['promotion' => $promotion])
            ->assertSee('Save 20%')
            ->assertSee('SAVE20')
            ->assertSee('Edit promotion');
    }

    public function test_non_admin_cannot_view_promotion_show_page(): void
    {
        [$user] = $this->createTeamWithUser();

        $promotion = SubscriptionPromotionCode::query()->create([
            'code' => 'SAVE20',
            'name' => 'Save 20%',
            'percent_off' => 20,
            'duration' => PromotionDuration::Once,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('admin.subscription-promotions.show', $promotion))
            ->assertForbidden();
    }
}
