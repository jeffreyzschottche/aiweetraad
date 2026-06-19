<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_one_time_code_can_be_exchanged_for_session(): void
    {
        $user = User::query()->create([
            'name' => 'Google User',
            'email' => 'google@example.com',
            'password' => null,
            'google_id' => 'google-123',
            'email_verified_at' => now(),
        ]);

        $code = Str::random(64);
        Cache::put('google-login:' . hash('sha256', $code), $user->id, now()->addMinutes(5));

        $this->postJson('/api/v1/auth/google/exchange', [
            'code' => $code,
        ])
            ->assertOk()
            ->assertJsonPath('user.email', 'google@example.com')
            ->assertJsonStructure(['token', 'user']);

        $this->postJson('/api/v1/auth/google/exchange', [
            'code' => $code,
        ])->assertUnprocessable();
    }
}
