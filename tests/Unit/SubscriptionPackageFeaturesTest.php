<?php

namespace Afterburner\Subscriptions\Tests\Unit;

use Afterburner\Subscriptions\Support\IncludedAppFeatures;
use Afterburner\Subscriptions\Support\SubscriptionPackageFeatures;
use Afterburner\Subscriptions\Tests\TestCase;
use App\Models\Team;

class SubscriptionPackageFeaturesTest extends TestCase
{
    protected function tearDown(): void
    {
        SubscriptionPackageFeatures::clearRegistered();

        parent::tearDown();
    }

    public function test_trial_feature_labels_include_registered_package_features(): void
    {
        SubscriptionPackageFeatures::register('documents', 'Documents', [
            'File library',
            'Uploads',
        ]);

        config()->set('afterburner-subscriptions.package_composer_names', [
            'documents' => 'laravel-afterburner/documents',
        ]);
        config()->set('afterburner-subscriptions.known_feature_slugs', ['documents']);

        $labels = SubscriptionPackageFeatures::trialFeatureLabels();

        $this->assertSame(['File library', 'Uploads'], $labels);
    }

    public function test_billing_labels_for_team_on_trial_include_packages(): void
    {
        SubscriptionPackageFeatures::register('voting', 'Voting', ['Ballots']);

        config()->set('afterburner-subscriptions.included_app_features', ['Core feature']);
        config()->set('afterburner-subscriptions.known_feature_slugs', ['voting']);
        config()->set('afterburner-subscriptions.package_composer_names', [
            'voting' => 'laravel-afterburner/voting',
        ]);

        [, $team] = $this->createTeamWithUser();
        $team->update(['trial_ends_at' => now()->addDays(10)]);

        $labels = IncludedAppFeatures::billingLabelsForTeam($team);

        $this->assertContains('Core feature', $labels);
        $this->assertContains('Ballots', $labels);
    }

    public function test_billing_labels_exclude_packages_when_not_on_trial(): void
    {
        SubscriptionPackageFeatures::register('voting', 'Voting', ['Ballots']);

        config()->set('afterburner-subscriptions.included_app_features', ['Core feature']);
        config()->set('afterburner-subscriptions.known_feature_slugs', ['voting']);

        [, $team] = $this->createTeamWithUser();
        $team->update(['trial_ends_at' => now()->subDay()]);

        $labels = IncludedAppFeatures::billingLabelsForTeam($team);

        $this->assertSame(['Core feature'], $labels);
    }
}
