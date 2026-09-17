<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentRequest;
use App\Models\BusinessDocument;
use App\Models\SiteVisit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DocumentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['type' => ['nullable', Rule::in(array_keys(BusinessDocument::TYPES))], 'q' => ['nullable', 'string', 'max:100']]);
        $documents = BusinessDocument::query()->when($filters['type'] ?? null, fn ($q, $v) => $q->where('type', $v))->when($filters['q'] ?? null, fn ($q, $v) => $q->where(fn ($q) => $q->where('number', 'like', "%{$v}%")->orWhere('party_name', 'like', "%{$v}%")))->latest('id')->paginate(20)->withQueryString();

        return view('admin.documents', compact('documents'));
    }

    public function create(Request $request): View
    {
        $data = $request->validate(['type' => ['nullable', Rule::in(array_keys(BusinessDocument::TYPES))], 'visit' => ['nullable', 'integer', 'exists:site_visits,id'], 'parent' => ['nullable', 'integer', 'exists:business_documents,id']]);
        $parent = isset($data['parent']) ? BusinessDocument::findOrFail($data['parent']) : null;
        $visit = isset($data['visit']) ? SiteVisit::findOrFail($data['visit']) : $parent?->visit;
        $document = new BusinessDocument(['type' => $data['type'] ?? 'quotation', 'site_visit_id' => $visit?->id, 'parent_id' => $parent?->id, 'party_name' => $parent?->party_name ?? ($visit?->company ?: $visit?->name), 'party_address' => $parent?->party_address ?? $visit?->address, 'party_email' => $parent?->party_email ?? $visit?->email, 'issued_on' => today(), 'items' => $parent?->items ?? [], 'tax_basis_points' => $parent?->tax_basis_points ?? 0]);

        return view('admin.document-form', compact('document', 'visit', 'parent'));
    }

    public function edit(BusinessDocument $document): View
    {
        abort_unless($document->status === 'draft', 403, 'Only drafts can be edited.');
        $visit = $document->visit;
        $parent = $document->parent;

        return view('admin.document-form', compact('document', 'visit', 'parent'));
    }

    private function payload(DocumentRequest $request): array
    {
        $data = $request->safe()->except(['tax_percent', 'revision']);
        $items = [];
        $subtotal = 0;
        foreach ($data['items'] as $item) {
            $quantity = BusinessDocument::cents((string) $item['quantity']);
            $price = BusinessDocument::cents((string) $item['unit_price']);
            $total = intdiv($quantity * $price + 50, 100);
            $subtotal += $total;
            $items[] = ['description' => $item['description'], 'quantity' => $item['quantity'], 'unit_price' => $item['unit_price'], 'total_cents' => $total];
        }
        if ($subtotal > 1000000000000) {
            throw ValidationException::withMessages(['items' => 'The document subtotal exceeds the supported limit.']);
        }
        $tax = BusinessDocument::cents((string) $request->input('tax_percent'));
        $taxCents = intdiv($subtotal * $tax + 5000, 10000);

        return array_merge($data, ['items' => $items, 'subtotal_cents' => $subtotal, 'tax_basis_points' => $tax, 'tax_cents' => $taxCents, 'total_cents' => $subtotal + $taxCents]);
    }

    public function store(DocumentRequest $request): RedirectResponse
    {
        $data = $this->payload($request);
        if (! empty($data['parent_id'])) {
            $parent = BusinessDocument::findOrFail($data['parent_id']);
            if ($parent->site_visit_id != ($data['site_visit_id'] ?? null)) {
                throw ValidationException::withMessages(['parent_id' => 'The linked document must belong to the same site visit.']);
            }
        }
        $document = DB::transaction(function () use ($data): BusinessDocument {
            $document = BusinessDocument::create($data + ['status' => 'draft']);
            $document->number = 'BRON-'.BusinessDocument::PREFIXES[$document->type].'-'.str_pad((string) $document->id, 6, '0', STR_PAD_LEFT);
            $document->save();

            return $document;
        });

        return redirect()->route('admin.documents.show', $document)->with('success', 'Draft created.');
    }

    public function update(DocumentRequest $request, BusinessDocument $document): RedirectResponse
    {
        DB::transaction(function () use ($request, $document): void {
            $locked = BusinessDocument::lockForUpdate()->findOrFail($document->id);
            abort_unless($locked->status === 'draft', 403);
            if ($request->integer('revision') !== $locked->revision) {
                throw ValidationException::withMessages(['revision' => 'This draft changed in another session. Reload before editing.']);
            }
            $data = $this->payload($request);
            unset($data['type'],$data['parent_id'],$data['site_visit_id']);
            $locked->fill($data);
            $locked->revision++;
            $locked->save();
        });

        return redirect()->route('admin.documents.show', $document)->with('success', 'Draft updated.');
    }

    public function show(BusinessDocument $document): View
    {
        $document->load(['visit', 'parent', 'payments.recorder']);

        return view('admin.document', compact('document'));
    }

    public function status(Request $request, BusinessDocument $document): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(BusinessDocument::statuses($document->type))], 'revision' => ['required', 'integer']]);
        DB::transaction(function () use ($document, $data): void {
            $locked = BusinessDocument::lockForUpdate()->findOrFail($document->id);
            if ($locked->revision !== (int) $data['revision']) {
                throw ValidationException::withMessages(['revision' => 'This document changed. Reload before updating.']);
            }
            if ($locked->type === 'invoice' && in_array($data['status'], ['paid', 'partially_paid'], true)) {
                throw ValidationException::withMessages(['status' => 'Use Record payment below to update the payment status.']);
            }
            $allowed = match ($locked->status) {
                'draft' => [$locked->type === 'customer_po' ? 'received' : 'issued', 'cancelled'],'issued' => match ($locked->type) {
                    'quotation' => ['accepted', 'declined', 'cancelled'],'invoice' => ['cancelled'],'delivery_order' => ['delivered', 'cancelled'],'supplier_po' => ['received', 'cancelled'],default => []
                },'received' => $locked->type === 'customer_po' ? ['confirmed', 'cancelled'] : [],'confirmed' => ['fulfilled', 'cancelled'],default => []
            };
            if (! in_array($data['status'], $allowed, true)) {
                throw ValidationException::withMessages(['status' => 'This status change is not allowed. Issued documents cannot return to draft.']);
            }
            $locked->status = $data['status'];
            $locked->revision++;
            $locked->save();
        });

        return back()->with('success', 'Document status updated.');
    }
}
