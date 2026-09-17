<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessDocument;
use App\Models\InvoicePayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InvoicePaymentController extends Controller
{
    public function store(Request $request, BusinessDocument $document): RedirectResponse
    {
        $data = $request->validate([
            'revision' => ['required', 'integer'],
            'payment_type' => ['required', Rule::in(['full', 'partial'])],
            'amount' => ['nullable', 'required_if:payment_type,partial', 'regex:/^\d{1,11}(\.\d{1,2})?$/'],
            'method' => ['required', Rule::in(array_keys(InvoicePayment::METHODS))],
            'paid_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $document, $data): void {
            // Serialize payments and status changes on the same invoice row.
            $invoice = BusinessDocument::lockForUpdate()->findOrFail($document->id);
            if ($invoice->revision !== (int) $data['revision']) {
                throw ValidationException::withMessages(['revision' => 'This invoice changed. Reload before recording a payment.']);
            }
            if ($invoice->type !== 'invoice' || ! in_array($invoice->status, ['issued', 'partially_paid'], true)) {
                throw ValidationException::withMessages(['payment' => 'Payments can only be recorded against issued or partially paid invoices.']);
            }
            $balance = $invoice->balanceCents();
            $amount = $data['payment_type'] === 'full' ? $balance : BusinessDocument::cents((string) $data['amount']);
            if ($amount <= 0 || $amount > $balance || ($data['payment_type'] === 'partial' && $amount >= $balance)) {
                throw ValidationException::withMessages(['amount' => 'Enter a partial amount greater than zero and less than the balance, or choose full payment to settle the balance.']);
            }
            $invoice->payments()->create([
                'amount_cents' => $amount,
                'method' => $data['method'],
                'paid_on' => $data['paid_on'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $request->user()->id,
            ]);
            $invoice->status = $amount === $balance ? 'paid' : 'partially_paid';
            $invoice->revision++;
            $invoice->save();
        });

        return redirect()->route('admin.documents.show', $document)->with('success', 'Payment recorded. Invoice balance and status updated.');
    }
}
