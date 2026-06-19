<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->stateless()
            ->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable $exception) {
            return redirect($this->frontendUrl('/login?error=google_login_failed'));
        }

        $email = $googleUser->getEmail();
        if (! is_string($email) || trim($email) === '') {
            return redirect($this->frontendUrl('/login?error=google_email_missing'));
        }

        $user = User::query()->where('email', $email)->first();

        if ($user) {
            $user->forceFill([
                'name' => $user->name ?: ($googleUser->getName() ?: $googleUser->getNickname() ?: Str::before($email, '@')),
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                'email_verified_at' => $user->email_verified_at ?: now(),
            ])->save();
        } else {
            $user = User::query()->create([
                'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: Str::before($email, '@'),
                'email' => $email,
                'password' => null,
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
            ]);
        }

        $code = Str::random(64);
        Cache::put($this->cacheKey($code), $user->id, now()->addMinutes(5));

        return redirect($this->frontendUrl('/auth/google/callback?code=' . urlencode($code)));
    }

    public function exchange(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:64'],
        ]);

        $userId = Cache::pull($this->cacheKey($data['code']));
        if (! $userId) {
            return response()->json([
                'message' => 'Google-login is verlopen. Probeer opnieuw in te loggen.',
            ], 422);
        }

        $user = User::query()->findOrFail($userId);
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    private function frontendUrl(string $path): string
    {
        return rtrim(config('app.frontend_url'), '/') . $path;
    }

    private function cacheKey(string $code): string
    {
        return 'google-login:' . hash('sha256', $code);
    }
}
