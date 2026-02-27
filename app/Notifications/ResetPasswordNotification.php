<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;

class ResetPasswordNotification extends Notification
{
    public string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = rtrim(Config::get('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000')), '/');
        $resetUrl    = $frontendUrl . '/reset-password?token=' . urlencode($this->token) . '&email=' . urlencode($notifiable->getEmailForPasswordReset());

        $expireMinutes = Config::get('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60);

        return (new MailMessage)
            ->subject('XTREMEFIT — Reset Your Password')
            ->view('emails.reset-password', [
                'name'          => $notifiable->name ?? 'Customer',
                'resetUrl'      => $resetUrl,
                'expireMinutes' => $expireMinutes,
            ]);
    }
}
