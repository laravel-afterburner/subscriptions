<?php

namespace Afterburner\Subscriptions\Tests\Unit;

use Afterburner\Subscriptions\Support\PlanFeatureSlug;
use Afterburner\Subscriptions\Tests\TestCase;

class PlanFeatureSlugTest extends TestCase
{
    public function test_label_uses_configured_display_name(): void
    {
        config()->set('afterburner-subscriptions.feature_slug_labels', [
            'meetings' => 'Events',
        ]);

        $this->assertSame('Events', PlanFeatureSlug::label('meetings'));
    }

    public function test_label_falls_back_to_humanized_slug(): void
    {
        config()->set('afterburner-subscriptions.feature_slug_labels', []);

        $this->assertSame('Documents', PlanFeatureSlug::label('documents'));
    }
}
