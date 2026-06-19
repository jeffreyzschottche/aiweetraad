<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChangedNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Je wachtwoord is gewijzigd - AI Weet Raad')
            ->view('emails.password-changed', [
                'url' => rtrim(config('app.frontend_url'), '/') . '/login',
            ]);
    }
}
