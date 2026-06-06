<?php

namespace Afterburner\Subscriptions\Notifications;

use Afterburner\Support\EntityLabel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Model $team) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $entityName = $this->team->name ?? 'your '.EntityLabel::singular();
        $subscriptionsUrl = route('teams.subscriptions.index', $this->team);

        return team_mail_message($this->team)
            ->subject("Subscription cancelled for {$entityName}")
            ->line("The subscription for {$entityName} has been cancelled or ended.")
            ->line('Resubscribe before access is removed to avoid interruption.')
            ->action('Manage Subscriptions', $subscriptionsUrl);
    }
}
