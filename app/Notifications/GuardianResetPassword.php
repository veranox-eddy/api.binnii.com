<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The parent-portal reset email. Laravel's built-in ResetPassword
 * notification links to a `password.reset` route; this API serves no HTML,
 * so the link points at the SPA instead and the SPA posts the token back to
 * /api/v1/auth/reset-password.
 */
class GuardianResetPassword extends Notification
{
    use Queueable;

    public function __construct(public string $token) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = config('parent.app_url').'/reset-password?'.http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $minutes = config('auth.passwords.guardians.expire');

        return (new MailMessage)
            ->subject('Reset your '.config('app.name').' password')
            ->greeting('Hi '.$notifiable->first_name.',')
            ->line('You asked to reset the password for your parent account.')
            ->action('Reset password', $url)
            ->line("This link expires in {$minutes} minutes.")
            ->line('If you did not ask for this, you can ignore this email — your password stays as it is.');
    }
}
