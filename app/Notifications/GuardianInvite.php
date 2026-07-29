<?php

namespace App\Notifications;

use App\Support\GuardianActivationToken;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The parent-app welcome email a Crew invite sends (API_07). Same idea as
 * the admin console's GuardianInvite mailable, but sent from here when a
 * family member invites another — the activation link points at the SPA and
 * both apps mint interchangeable tokens via GUARDIAN_ACTIVATION_SECRET.
 */
class GuardianInvite extends Notification
{
    use Queueable;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $days = config('parent.activation_ttl_days');

        return (new MailMessage)
            ->subject('You are invited to '.config('app.name'))
            ->greeting('Hi '.$notifiable->first_name.',')
            ->line("You have been invited to follow {$this->childrenLine($notifiable)} on ".config('app.name').' — daily reports, photos and messages from the center, in one place.')
            ->action('Create your account', GuardianActivationToken::link($notifiable))
            ->line("This link expires in {$days} days. Use this email address ({$notifiable->email}) to sign in once your account is set up.");
    }

    private function childrenLine(object $notifiable): string
    {
        $names = $notifiable->children()->pluck('first_name')->all();

        return $names === [] ? 'your family' : implode(' and ', $names);
    }
}
