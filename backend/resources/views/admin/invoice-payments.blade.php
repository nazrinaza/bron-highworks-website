<section class="panel top-gap no-print" aria-labelledby="payments-heading">
<h2 id="payments-heading">Invoice payments</h2>
@if($document->status==='paid' && $document->payments->isEmpty())
<p>This invoice was previously marked paid. No payment amount, date or method was recorded in the earlier system.</p>
@else
<div class="grid"><p>Recorded payments<br><strong>MYR {{ number_format($document->recordedPaidCents()/100,2) }}</strong></p><p>Remaining balance<br><strong>MYR {{ number_format($document->balanceCents()/100,2) }}</strong></p></div>
@endif
@if(in_array($document->status,['issued','partially_paid'],true))
<h3>Record payment</h3>
<p class="muted">Record money already received. This form does not charge a card or initiate an FPX transaction.</p>
<form method="post" action="{{ route('admin.documents.payments.store',$document) }}">
@csrf
<input type="hidden" name="revision" value="{{ $document->revision }}">
<div class="grid">
<label>Payment type<select name="payment_type" required><option value="full" @selected(old('payment_type')==='full')>Full payment — settle remaining balance</option><option value="partial" @selected(old('payment_type')==='partial')>Partial payment</option></select></label>
<label>Partial amount (MYR)<input type="number" name="amount" min="0.01" step="0.01" max="{{ number_format($document->balanceCents()/100,2,'.','') }}" value="{{ old('amount') }}"><small>Required for partial payment. Full payment uses the remaining balance automatically.</small></label>
<label>Payment method<select name="method" required><option value="">Choose a method</option>@foreach(\App\Models\InvoicePayment::METHODS as $key=>$label)<option value="{{ $key }}" @selected(old('method')===$key)>{{ $label }}</option>@endforeach</select></label>
<label>Payment date<input type="date" name="paid_on" required max="{{ today()->toDateString() }}" value="{{ old('paid_on',today()->toDateString()) }}"></label>
<label>Receipt / transaction reference<input name="reference" maxlength="255" value="{{ old('reference') }}"><small>Use a receipt or transaction ID, not card numbers or banking credentials.</small></label>
<label>Internal payment notes<textarea name="notes" maxlength="2000">{{ old('notes') }}</textarea></label>
</div><button>Record payment</button>
</form>
@elseif($document->status==='draft')
<p>Issue this invoice before recording a payment.</p>
@endif
@if($document->payments->isNotEmpty())
<h3>Payment history</h3>
<div class="table-wrap"><table><thead><tr><th>Date</th><th>Method</th><th>Amount (MYR)</th><th>Reference / notes</th><th>Recorded by</th></tr></thead><tbody>
@foreach($document->payments as $payment)
<tr><td>{{ $payment->paid_on->format('d M Y') }}</td><td>{{ \App\Models\InvoicePayment::METHODS[$payment->method] }}</td><td>{{ number_format($payment->amount_cents/100,2) }}</td><td>{{ $payment->reference ?: '—' }}@if($payment->notes)<small class="pre">{{ $payment->notes }}</small>@endif</td><td>{{ $payment->recorder->name }}<small>{{ $payment->created_at->format('d M Y H:i') }}</small></td></tr>
@endforeach
</tbody></table></div>
@endif
</section>
