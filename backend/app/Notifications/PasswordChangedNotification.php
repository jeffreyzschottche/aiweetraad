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
            ->greeting('Je wachtwoord is gewijzigd')
            ->line('We bevestigen dat het wachtwoord van je AI Weet Raad-account zojuist is aangepast.')
            ->line('Was jij dit niet? Reset dan meteen opnieuw je wachtwoord en neem contact met ons op.')
            ->action('Inloggen', rtrim(config('app.frontend_url'), '/') . '/login');
    }
}
