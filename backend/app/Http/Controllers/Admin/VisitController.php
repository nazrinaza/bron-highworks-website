<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteVisit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VisitController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', Rule::in(SiteVisit::STATUSES)]]);
        $visits = SiteVisit::query()->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))->when($filters['q'] ?? null, fn ($q, $v) => $q->where(fn ($q) => $q->where('name', 'like', "%{$v}%")->orWhere('company', 'like', "%{$v}%")->orWhere('reference', 'like', "%{$v}%")))->latest('id')->paginate(20)->withQueryString();
        $counts = SiteVisit::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.visits', compact('visits', 'counts'));
    }

    public function show(SiteVisit $visit): View
    {
        $visit->load('documents');

        return view('admin.visit', compact('visit'));
    }

    public function update(Request $request, SiteVisit $visit): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(SiteVisit::STATUSES)], 'scheduled_at' => ['nullable', 'date', 'required_if:status,scheduled'], 'assigned_to' => ['nullable', 'string', 'max:150'], 'internal_notes' => ['nullable', 'string', 'max:5000'], 'assessment' => ['nullable', 'string', 'max:5000', 'required_if:status,completed']]);
        $visit->update($data);

        return back()->with('success', 'Site visit updated.');
    }
}
