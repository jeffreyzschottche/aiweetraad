<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\PasswordChangedNotification;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_a_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->postJson('/api/v1/forgot-password', [
            'email' => $user->email,
        ])->assertOk()
            ->assertJson([
                'message' => 'Als dit e-mailadres bij ons bekend is, ontvang je binnen enkele minuten een resetlink.',
            ]);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_user_can_reset_password_and_receives_confirmation(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/v1/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertOk()
            ->assertJson([
                'message' => 'Je wachtwoord is aangepast. We hebben je ook een bevestiging per e-mail gestuurd.',
            ]);

        $user->refresh();

        $this->assertTrue(Hash::check('new-password-123', $user->password));
        Notification::assertSentTo($user, PasswordChangedNotification::class);
    }

    public function test_unknown_email_gets_generic_forgot_password_response(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/forgot-password', [
            'email' => 'unknown@example.com',
        ])->assertOk()
            ->assertJson([
                'message' => 'Als dit e-mailadres bij ons bekend is, ontvang je binnen enkele minuten een resetlink.',
            ]);

        Notification::assertNothingSent();
    }
}
