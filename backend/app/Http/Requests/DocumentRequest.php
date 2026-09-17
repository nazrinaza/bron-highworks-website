<?php

namespace App\Http\Requests;

use App\Models\BusinessDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_keys(BusinessDocument::TYPES))],
            'site_visit_id' => ['nullable', 'integer', 'exists:site_visits,id'], 'parent_id' => ['nullable', 'integer', 'exists:business_documents,id'],
            'party_name' => ['required', 'string', 'max:200'], 'party_address' => ['required', 'string', 'max:2000'],
            'party_email' => ['nullable', 'email', 'max:200'], 'external_reference' => ['nullable', 'string', 'max:200'],
            'issued_on' => ['required', 'date_format:Y-m-d'], 'due_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:issued_on'],
            'notes' => ['nullable', 'string', 'max:5000'], 'tax_percent' => ['required', 'numeric', 'between:0,100', 'regex:/^\d{1,3}(\.\d{1,2})?$/'],
            'items' => ['required', 'array', 'min:1', 'max:50'], 'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'numeric', 'between:0.01,100000', 'regex:/^\d{1,6}(\.\d{1,2})?$/'],
            'items.*.unit_price' => ['required', 'numeric', 'between:0,10000000', 'regex:/^\d{1,8}(\.\d{1,2})?$/'],
            'revision' => ['sometimes', 'integer', 'min:1']];
    }
}
