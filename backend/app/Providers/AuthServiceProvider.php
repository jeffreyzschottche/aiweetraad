<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Notifications\Messages\MailMessage;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [];

    public function boot(): void
    {
        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            $frontendUrl = config('app.frontend_url') . '/verify-email?url=' . urlencode($url);

            return (new MailMessage)
                ->subject('Bevestig je e-mailadres - AI Weet Raad')
                ->greeting('Welkom bij AI Weet Raad')
                ->line('Bevestig je e-mailadres zodat je account volledig actief is.')
                ->action('E-mailadres bevestigen', $frontendUrl)
                ->line('Heb jij dit account niet aangemaakt? Dan hoef je niets te doen.');
        });
    }
}
