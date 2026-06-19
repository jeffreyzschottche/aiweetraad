<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:160'],
        ]);

        NewsletterSubscriber::updateOrCreate(
            ['email' => mb_strtolower($data['email'])],
            ['subscribed_at' => now()],
        );

        return response()->json([
            'message' => 'Bedankt voor je aanmelding!',
        ], 201);
    }
}
