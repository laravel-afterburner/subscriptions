<?php

namespace Afterburner\Subscriptions\Tests\Unit;

use Afterburner\Subscriptions\Support\IncludedAppFeatures;
use Afterburner\Subscriptions\Tests\TestCase;

class IncludedAppFeaturesTest extends TestCase
{
    public function test_labels_returns_trimmed_strings_from_config(): void
    {
        config()->set('afterburner-subscriptions.included_app_features', [
            '  Properties  ',
            'Finances',
            '',
            42,
        ]);

        $this->assertSame([
            'Properties',
            'Finances',
        ], IncludedAppFeatures::labels());
    }

    public function test_is_configured_is_false_when_empty(): void
    {
        config()->set('afterburner-subscriptions.included_app_features', []);

        $this->assertFalse(IncludedAppFeatures::isConfigured());
    }

    public function test_is_configured_is_true_when_labels_exist(): void
    {
        config()->set('afterburner-subscriptions.included_app_features', ['Properties']);

        $this->assertTrue(IncludedAppFeatures::isConfigured());
    }
}
