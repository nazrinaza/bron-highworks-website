<?php

namespace Tests\Feature;

use App\Models\BusinessDocument;
use App\Models\SiteVisit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OperationsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function enquiry(): array
    {
        return ['name' => 'Test Customer', 'email' => 'client@example.test', 'phone' => '0191234567', 'service' => 'Solar panel cleaning', 'address' => 'Test site, Kuala Lumpur', 'preferred_date' => today()->addDay()->toDateString(), 'consent' => '1'];
    }

    private function document(string $type = 'quotation'): array
    {
        return ['type' => $type, 'party_name' => 'Test Customer', 'party_address' => 'Test address', 'issued_on' => today()->toDateString(), 'tax_percent' => '6.00', 'items' => [['description' => 'Cleaning', 'quantity' => '2.50', 'unit_price' => '19.99']]];
    }

    public function test_public_request_is_saved_without_accepting_admin_fields(): void
    {
        $this->get('/site-assessment')->assertOk()->assertSee('Request a site assessment');
        $this->post('/site-assessment', $this->enquiry() + ['status' => 'completed', 'internal_notes' => 'injected'])->assertRedirect('/site-assessment')->assertSessionHas('reference');
        $this->assertDatabaseHas('site_visits', ['email' => 'client@example.test', 'status' => 'new', 'internal_notes' => null]);
    }

    public function test_invalid_and_bot_requests_are_rejected(): void
    {
        $this->post('/site-assessment', [])->assertSessionHasErrors(['name', 'email', 'phone', 'service', 'address', 'consent']);
        $this->post('/site-assessment', array_replace($this->enquiry(), ['preferred_date' => today()->subDay()->toDateString(), 'website' => 'spam']))->assertSessionHasErrors(['preferred_date', 'website']);
        $this->assertDatabaseCount('site_visits', 0);
    }

    public function test_guests_and_non_admins_cannot_access_operations(): void
    {
        $visit = SiteVisit::factory()->create();
        $doc = BusinessDocument::factory()->create();
        foreach (['/admin/visits', "/admin/visits/{$visit->id}", '/admin/documents', "/admin/documents/{$doc->id}"] as $url) {
            $this->get($url)->assertRedirect('/admin/login');
        }
        $this->post('/admin/documents', $this->document())->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->get('/admin/visits')->assertForbidden();
        $this->post('/admin/documents', $this->document())->assertForbidden();
    }

    public function test_staff_login_logout_and_throttling(): void
    {
        $admin = $this->admin();
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect('/admin/visits');
        $this->assertAuthenticatedAs($admin);
        $this->post('/logout')->assertRedirect('/admin/login');
        $this->assertGuest();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', ['email' => 'blocked@example.test', 'password' => 'wrong']);
        }
        $this->post('/admin/login', ['email' => 'blocked@example.test', 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_admin_schedules_visit_and_records_assessment(): void
    {
        $visit = SiteVisit::factory()->create();
        $this->actingAs($this->admin());
        $this->put(route('admin.visits.update', $visit), ['status' => 'scheduled'])->assertSessionHasErrors('scheduled_at');
        $this->put(route('admin.visits.update', $visit), ['status' => 'scheduled', 'scheduled_at' => now()->addDay()->format('Y-m-d H:i'), 'assigned_to' => 'Test Staff'])->assertSessionHasNoErrors();
        $this->put(route('admin.visits.update', $visit), ['status' => 'completed', 'assessment' => 'Access assessed'])->assertSessionHasNoErrors();
        $this->assertSame('completed', $visit->fresh()->status);
    }

    public function test_all_document_types_have_correct_server_calculated_totals_and_render(): void
    {
        $this->actingAs($this->admin());
        foreach (array_keys(BusinessDocument::TYPES) as $type) {
            $this->post('/admin/documents', $this->document($type) + ['total_cents' => 1, 'status' => 'paid'])->assertSessionHasNoErrors();
            $doc = BusinessDocument::latest('id')->first();
            $this->assertSame('draft', $doc->status);
            $this->assertEquals(4998, $doc->subtotal_cents);
            $this->assertEquals(300, $doc->tax_cents);
            $this->assertEquals(5298, $doc->total_cents);
            $this->get(route('admin.documents.show', $doc))->assertOk()->assertSee($doc->number);
            $this->get(route('admin.documents.edit', $doc))->assertOk();
            $this->get(route('admin.documents.index', ['type' => $type]))->assertOk();
            $this->get(route('admin.documents.create', ['type' => $type, 'parent' => $doc->id]))->assertOk();
        }
        $this->assertSame(5, BusinessDocument::distinct()->count('number'));
    }

    public function test_invalid_money_is_rejected_and_totals_cannot_overflow(): void
    {
        $this->actingAs($this->admin());
        $data = $this->document();
        $data['items'][0]['unit_price'] = '-1';
        $this->post('/admin/documents', $data)->assertSessionHasErrors('items.0.unit_price');
        $data['items'][0]['unit_price'] = '1.999';
        $this->post('/admin/documents', $data)->assertSessionHasErrors('items.0.unit_price');
        $data['items'][0]['unit_price'] = '10000000';
        $data['items'][0]['quantity'] = '100000';
        $this->post('/admin/documents', $data)->assertSessionHasErrors('items');
        $this->assertDatabaseCount('business_documents', 0);
    }

    public function test_draft_editing_status_transitions_and_stale_edits(): void
    {
        $doc = BusinessDocument::factory()->create();
        $this->actingAs($this->admin());
        $this->put(route('admin.documents.update', $doc), $this->document() + ['revision' => 1])->assertSessionHasNoErrors();
        $this->put(route('admin.documents.update', $doc), $this->document() + ['revision' => 1])->assertSessionHasErrors('revision');
        $this->patch(route('admin.documents.status', $doc), ['status' => 'accepted', 'revision' => 2])->assertSessionHasErrors('status');
        $this->patch(route('admin.documents.status', $doc), ['status' => 'issued', 'revision' => 2])->assertSessionHasNoErrors();
        $this->put(route('admin.documents.update', $doc), $this->document() + ['revision' => 3])->assertForbidden();
        $this->patch(route('admin.documents.status', $doc), ['status' => 'draft', 'revision' => 3])->assertSessionHasErrors('status');
        $this->patch(route('admin.documents.status', $doc), ['status' => 'accepted', 'revision' => 3])->assertSessionHasNoErrors();
        $this->assertSame('accepted', $doc->fresh()->status);
    }

    public function test_document_updates_work_when_database_returns_revision_as_text(): void
    {
        $doc = BusinessDocument::factory()->create();
        $statusDoc = BusinessDocument::factory()->create();
        $this->actingAs($this->admin());
        $pdo = DB::connection()->getPdo();
        $original = $pdo->getAttribute(\PDO::ATTR_STRINGIFY_FETCHES);
        $pdo->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, true);

        try {
            $this->patch(route('admin.documents.status', $statusDoc), ['status' => 'issued', 'revision' => '1'])
                ->assertSessionHasNoErrors();
            $this->assertSame('issued', $statusDoc->fresh()->status);
            $this->put(route('admin.documents.update', $doc), $this->document() + ['revision' => '1'])
                ->assertSessionHasNoErrors();
            $this->patch(route('admin.documents.status', $doc), ['status' => 'issued', 'revision' => '2'])
                ->assertSessionHasNoErrors();
            $this->assertSame('issued', $doc->fresh()->status);
            $this->patch(route('admin.documents.status', $doc), ['status' => 'accepted', 'revision' => '2'])
                ->assertSessionHasErrors('revision');
            $this->assertSame('issued', $doc->fresh()->status);
            $this->patch(route('admin.documents.status', $doc), ['status' => 'accepted', 'revision' => '3'])
                ->assertSessionHasNoErrors();
            $this->assertSame('accepted', $doc->fresh()->status);
        } finally {
            $pdo->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, $original);
        }
    }

    public function test_linked_document_must_use_same_visit(): void
    {
        $parent = BusinessDocument::factory()->create(['site_visit_id' => SiteVisit::factory()->create()->id]);
        $other = SiteVisit::factory()->create();
        $this->actingAs($this->admin())->post('/admin/documents', $this->document('invoice') + ['parent_id' => $parent->id, 'site_visit_id' => $other->id])->assertSessionHasErrors('parent_id');
        $this->post('/admin/documents', $this->document('invoice') + ['parent_id' => $parent->id, 'site_visit_id' => $parent->site_visit_id])->assertSessionHasNoErrors();
    }

    public function test_admin_views_escape_customer_content(): void
    {
        $visit = SiteVisit::factory()->create(['name' => '<script>alert(1)</script>']);
        $this->actingAs($this->admin());
        $this->get(route('admin.visits.show', $visit))->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
        $this->get('/admin/visits')->assertOk();
    }
}
