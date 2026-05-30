<?php

namespace Afterburner\Subscriptions\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialEndingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Model $team,
        public int $daysRemaining
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
        $entityName = $this->team->name ?? 'your '.config('afterburner.entity_label', 'team');
        $subscriptionsUrl = route('teams.subscriptions.index', $this->team);

        return (new MailMessage)
            ->subject("Trial ending in {$this->daysRemaining} day(s) — {$entityName}")
            ->line("The free trial for {$entityName} ends in {$this->daysRemaining} day(s).")
            ->line('Subscribe now to keep access to the application.')
            ->action('Choose a Plan', $subscriptionsUrl);
    }
}
