<?php

namespace Tests\Feature;

use App\Mail\AssessmentConfirmationMail;
use App\Mail\BusinessDocumentMail;
use App\Mail\NewAssessmentNotificationMail;
use App\Models\BusinessDocument;
use App\Models\SiteVisit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_assessment_request_emails_customer_and_bron_team(): void
    {
        Mail::fake();
        config(['mail.notifications.to' => 'operations@bronhighworks.com']);

        $this->post(route('assessment.store'), [
            'name' => 'Test Customer',
            'company' => 'Test Company',
            'email' => 'customer@example.test',
            'phone' => '0191234567',
            'service' => 'Solar panel cleaning',
            'address' => 'Kuala Lumpur',
            'preferred_date' => today()->addDay()->toDateString(),
            'consent' => '1',
        ])->assertRedirect(route('assessment.create'));

        Mail::assertQueued(AssessmentConfirmationMail::class, fn (AssessmentConfirmationMail $mail): bool => $mail->hasTo('customer@example.test'));
        Mail::assertQueued(NewAssessmentNotificationMail::class, fn (NewAssessmentNotificationMail $mail): bool => $mail->hasTo('operations@bronhighworks.com'));

        $visit = SiteVisit::firstOrFail();
        $this->assertStringContainsString($visit->reference, (new AssessmentConfirmationMail($visit))->render());
        $this->assertStringContainsString('Open in BRON Admin', (new NewAssessmentNotificationMail($visit))->render());
    }

    public function test_admin_can_email_every_business_document_type(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        foreach (array_keys(BusinessDocument::TYPES) as $type) {
            $document = BusinessDocument::factory()->create([
                'type' => $type,
                'party_email' => 'recipient@example.test',
            ]);

            $this->post(route('admin.documents.email', $document), [
                'recipient' => 'recipient@example.test',
            ])->assertSessionHasNoErrors()->assertSessionHas('success');

            $this->assertStringContainsString($document->number, (new BusinessDocumentMail($document->load('payments')))->render());
        }

        Mail::assertQueued(BusinessDocumentMail::class, 5);
    }

    public function test_document_email_requires_an_admin_and_valid_recipient(): void
    {
        Mail::fake();
        $document = BusinessDocument::factory()->create();
        $route = route('admin.documents.email', $document);

        $this->post($route, ['recipient' => 'recipient@example.test'])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->post($route, ['recipient' => 'invalid'])
            ->assertSessionHasErrors('recipient');

        Mail::assertNothingQueued();
    }
}
