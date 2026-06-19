<?php

namespace Tests\Feature;

use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactAndNewsletterTest extends TestCase
{
    use RefreshDatabase;

    public function test_newsletter_signup_is_stored_once(): void
    {
        $this->postJson('/api/v1/newsletter', [
            'email' => 'Test@Example.com',
        ])->assertCreated();

        $this->postJson('/api/v1/newsletter', [
            'email' => 'test@example.com',
        ])->assertCreated();

        $this->assertSame(1, NewsletterSubscriber::query()->count());
        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_contact_message_is_stored_and_sent_to_admin(): void
    {
        Mail::fake();

        config(['ai.alerts.admin_email' => 'admin@example.com']);

        $this->postJson('/api/v1/contact', [
            'name' => 'Jeffrey',
            'email' => 'jeffrey@example.com',
            'subject' => 'Vraag over AI Weet Raad',
            'message' => 'Dit is een normaal contactbericht met genoeg lengte.',
        ])->assertCreated();

        $message = ContactMessage::query()->firstOrFail();

        Mail::assertSent(ContactMessageMail::class, fn (ContactMessageMail $mail) => (
            $mail->hasTo('admin@example.com')
            && $mail->contactMessage->is($message)
        ));
    }
}
