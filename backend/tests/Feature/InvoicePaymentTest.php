<?php

namespace Tests\Feature;

use App\Models\BusinessDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePaymentTest extends TestCase
{
    use RefreshDatabase;

    private function payment(array $overrides = []): array
    {
        return array_replace(['revision' => 1, 'payment_type' => 'partial', 'amount' => '30.25', 'method' => 'cash', 'paid_on' => today()->toDateString(), 'reference' => 'Receipt-1'], $overrides);
    }

    public function test_partial_and_full_payments_update_balance_status_and_history(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $doc = BusinessDocument::factory()->create(['type' => 'invoice', 'status' => 'issued']);
        $url = route('admin.documents.payments.store', $doc);
        $this->actingAs($admin)->post($url, $this->payment())->assertSessionHasNoErrors();
        $this->assertSame('partially_paid', $doc->fresh()->status);
        $this->assertSame(6975, $doc->fresh()->balanceCents());
        // A repeated POST cannot record the same money twice.
        $this->post($url, $this->payment())->assertSessionHasErrors('revision');
        $this->post($url, $this->payment(['revision' => 2, 'amount' => '20.00', 'method' => 'card']))->assertSessionHasNoErrors();
        $this->post($url, $this->payment(['revision' => 3, 'payment_type' => 'full', 'amount' => null, 'method' => 'fpx']))->assertSessionHasNoErrors();
        $this->assertSame('paid', $doc->fresh()->status);
        $this->assertSame(0, $doc->fresh()->balanceCents());
        $this->assertDatabaseHas('invoice_payments', ['amount_cents' => 4975, 'method' => 'fpx', 'recorded_by' => $admin->id]);
        $this->assertDatabaseCount('invoice_payments', 3);
        $this->get(route('admin.documents.show', $doc))->assertOk()->assertSee('Payment history')->assertSee('Cash')->assertSee('Card')->assertSee('FPX')->assertSee('Balance due');
        $this->post($url, $this->payment(['revision' => 4]))->assertSessionHasErrors('payment');
    }

    public function test_invalid_and_overpaid_amounts_are_rejected(): void
    {
        $doc = BusinessDocument::factory()->create(['type' => 'invoice', 'status' => 'issued']);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        foreach (['0', '-1', '100.00', '100.01', '1.001', '1e2', '99999999999999999999999'] as $amount) {
            $this->post(route('admin.documents.payments.store', $doc), $this->payment(['amount' => $amount]))->assertSessionHasErrors('amount');
        }
        $this->post(route('admin.documents.payments.store', $doc), $this->payment(['method' => 'invalid', 'paid_on' => today()->addDay()->toDateString()]))->assertSessionHasErrors(['method', 'paid_on']);
        $this->assertDatabaseCount('invoice_payments', 0);
    }

    public function test_only_admins_can_record_payments_on_issued_invoices(): void
    {
        $doc = BusinessDocument::factory()->create(['type' => 'invoice', 'status' => 'issued']);
        $url = route('admin.documents.payments.store', $doc);
        $this->post($url, $this->payment())->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->post($url, $this->payment())->assertForbidden();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        foreach ([['invoice', 'draft'], ['invoice', 'cancelled'], ['quotation', 'issued']] as [$type, $status]) {
            $other = BusinessDocument::factory()->create(compact('type', 'status'));
            $this->post(route('admin.documents.payments.store', $other), $this->payment())->assertSessionHasErrors('payment');
        }
        $this->patch(route('admin.documents.status', $doc), ['status' => 'paid', 'revision' => 1])->assertSessionHasErrors('status');
        $this->post($url, $this->payment())->assertSessionHasNoErrors();
        $this->patch(route('admin.documents.status', $doc), ['status' => 'cancelled', 'revision' => 2])->assertSessionHasErrors('status');
        $this->assertSame('partially_paid', $doc->fresh()->status);
    }

    public function test_legacy_paid_invoices_are_not_reinterpreted_and_notes_are_escaped(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $legacy = BusinessDocument::factory()->create(['type' => 'invoice', 'status' => 'paid']);
        $this->get(route('admin.documents.show', $legacy))->assertOk()->assertSee('previously marked paid')->assertDontSee('Balance due');
        $this->post(route('admin.documents.payments.store', $legacy), $this->payment())->assertSessionHasErrors('payment_type');
        $this->post(route('admin.documents.payments.store', $legacy), $this->payment(['payment_type' => 'full', 'amount' => null]))->assertSessionHasNoErrors();
        $this->assertSame('paid', $legacy->fresh()->status);
        $this->assertSame(0, $legacy->fresh()->balanceCents());
        $doc = BusinessDocument::factory()->create(['type' => 'invoice', 'status' => 'issued']);
        $this->post(route('admin.documents.payments.store', $doc), $this->payment(['notes' => '<script>alert(1)</script>']))->assertSessionHasNoErrors();
        $this->get(route('admin.documents.show', $doc))->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
    }
}
