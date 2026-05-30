<?php

namespace Afterburner\Subscriptions\Console\Commands;

use Afterburner\Subscriptions\Database\Seeders\SubscriptionsPermissionsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'afterburner:subscriptions:install';

    protected $description = 'Install the Afterburner Subscriptions package';

    public function handle(): int
    {
        $this->info('Installing Afterburner Subscriptions package...');

        $this->info('Publishing configuration...');
        $this->call('vendor:publish', [
            '--tag' => 'afterburner-subscriptions-config',
            '--force' => true,
        ]);

        $this->info('Publishing views...');
        $this->call('vendor:publish', [
            '--tag' => 'afterburner-subscriptions-assets',
            '--force' => true,
        ]);

        if ($this->confirm('Publish Cashier migrations?', false)) {
            $this->warn('Skipping default Cashier migrations — this package ships entity-scoped subscription tables.');
        }

        $this->info('Adding environment variables...');
        $this->addEnvironmentVariables();

        if ($this->confirm('Run migrations now?', true)) {
            $this->info('Running migrations...');
            $this->call('migrate');
        }

        if ($this->confirm('Seed subscriptions permissions?', true)) {
            $this->info('Seeding subscriptions permissions...');
            $seeder = new SubscriptionsPermissionsSeeder;
            $seeder->setCommand($this);
            $seeder->run();
        }

        $this->info('Installation complete!');
        $this->newLine();
        $this->comment('Next steps:');
        $this->comment('1. Add the HasSubscriptions trait to App\\Models\\Team');
        $this->comment('2. Apply core template updates for TeamNavigation and SystemAdminNavigation');
        $this->comment('3. Register EnsureSubscriptionActive middleware on authenticated routes (alias: subscription.active)');
        $this->comment('4. Set STRIPE_KEY, STRIPE_SECRET, and STRIPE_WEBHOOK_SECRET');
        $this->comment('5. Configure Stripe webhook endpoint: /stripe/webhook');
        $this->comment('6. Visit /admin/subscription-plans to create plans and promotion codes');
        $this->comment('7. Add installed add-on slugs to known_feature_slugs in config/afterburner-subscriptions.php');

        return Command::SUCCESS;
    }

    protected function addEnvironmentVariables(): void
    {
        $envVars = [
            '',
            '# Afterburner Subscriptions Configuration',
            'AFTERBURNER_SUBSCRIPTIONS_ENABLED=true',
            'AFTERBURNER_SUBSCRIPTIONS_DEFAULT_TRIAL_DAYS=30',
            'AFTERBURNER_SUBSCRIPTIONS_CURRENCY=usd',
            'AFTERBURNER_SUBSCRIPTIONS_BILLING_ROLE_SLUGS=president,treasurer',
            'AFTERBURNER_SUBSCRIPTIONS_PROMOTIONS_ENABLED=true',
            'AFTERBURNER_SUBSCRIPTIONS_ALLOW_CHECKOUT_PROMO_CODES=true',
            'AFTERBURNER_SUBSCRIPTIONS_TRIAL_FULL_ACCESS=true',
            '',
            '# Stripe / Cashier',
            'STRIPE_KEY=',
            'STRIPE_SECRET=',
            'STRIPE_WEBHOOK_SECRET=',
            'CASHIER_CURRENCY=usd',
            'CASHIER_CURRENCY_LOCALE=en',
        ];

        foreach (['.env', '.env.example'] as $file) {
            $path = base_path($file);
            if (! File::exists($path)) {
                continue;
            }

            $content = File::get($path);
            foreach ($envVars as $var) {
                if ($var && ! str_contains($content, explode('=', $var)[0])) {
                    File::append($path, "\n".$var);
                }
            }
        }
    }
}
