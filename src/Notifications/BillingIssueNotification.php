<?php

namespace Afterburner\Subscriptions\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BillingIssueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $invoice
     */
    public function __construct(
        public Model $team,
        public array $invoice = []
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $entityName = $this->team->name ?? 'your entity';
        $subscriptionsUrl = route('teams.subscriptions.index', $this->team);

        return (new MailMessage)
            ->subject("Billing issue for {$entityName}")
            ->line("A payment failed for {$entityName}.")
            ->line('Please update your payment method to avoid losing access to the application.')
            ->action('Manage Subscriptions', $subscriptionsUrl);
    }
}
