<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ContactController extends Controller
{
    public function store(ContactRequest $request): JsonResponse
    {
        $message = ContactMessage::create($request->validated());

        $adminEmail = config('ai.alerts.admin_email');
        if (is_string($adminEmail) && trim($adminEmail) !== '') {
            try {
                Mail::to($adminEmail)->send(new ContactMessageMail($message));
            } catch (Throwable $exception) {
                Log::warning('Contact message mail failed.', [
                    'contact_message_id' => $message->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return response()->json([
            'message' => 'Bedankt voor je bericht! We nemen zo snel mogelijk contact op.',
        ], 201);
    }
}
