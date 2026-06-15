<?php

namespace App\Services;

use Illuminate\Support\Str;

class QuestionModeration
{
    public function inspect(string $title, ?string $body = null): array
    {
        $text = trim($title . ' ' . ($body ?? ''));
        $normalized = Str::lower($text);

        if ($this->looksEmpty($normalized)) {
            return $this->reject('Stel een echte vraag met genoeg context.');
        }

        if ($this->containsProfanity($normalized)) {
            return $this->reject('Houd je vraag netjes en zonder scheldwoorden.');
        }

        if ($this->looksLikeSpam($normalized)) {
            return $this->reject('Je vraag lijkt op spam. Maak hem concreter en zonder overbodige links of herhaling.');
        }

        return ['allowed' => true, 'message' => null];
    }

    private function looksEmpty(string $text): bool
    {
        preg_match_all('/[\p{L}\p{N}]+/u', $text, $words);

        return count($words[0] ?? []) < 3;
    }

    private function containsProfanity(string $text): bool
    {
        $blocked = [
            'asshole',
            'bitch',
            'cunt',
            'fuck',
            'kanker',
            'kut',
            'lul',
            'shit',
            'tering',
            'tyfus',
        ];

        foreach ($blocked as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/u', $text)) {
                return true;
            }
        }

        return false;
    }

    private function looksLikeSpam(string $text): bool
    {
        preg_match_all('/https?:\/\/|www\./i', $text, $links);
        if (count($links[0] ?? []) > 1) {
            return true;
        }

        if (preg_match('/(.)\1{7,}/u', $text)) {
            return true;
        }

        if (preg_match('/\b([\p{L}\p{N}]{3,})\b(?:\s+\1\b){3,}/u', $text)) {
            return true;
        }

        $letters = preg_match_all('/\p{L}/u', $text);
        $symbols = preg_match_all('/[^\p{L}\p{N}\s.,?!:;\'"()\-]/u', $text);

        return $letters > 0 && $symbols / max(1, mb_strlen($text)) > 0.35;
    }

    private function reject(string $message): array
    {
        return ['allowed' => false, 'message' => $message];
    }
}
