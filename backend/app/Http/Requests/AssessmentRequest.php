<?php

namespace App\Http\Requests;

use App\Models\SiteVisit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'], 'company' => ['nullable', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:200'], 'phone' => ['required', 'string', 'max:50'],
            'service' => ['required', Rule::in(SiteVisit::SERVICES)], 'address' => ['required', 'string', 'max:2000'],
            'area' => ['nullable', 'string', 'max:100'], 'preferred_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'access_details' => ['nullable', 'string', 'max:3000'], 'requirements' => ['nullable', 'string', 'max:3000'],
            'consent' => ['accepted'], 'website' => ['nullable', 'max:0']];
    }
}
