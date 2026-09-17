<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssessmentRequest;
use App\Models\SiteVisit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function create(): View
    {
        return view('assessment');
    }

    public function store(AssessmentRequest $request): RedirectResponse
    {
        $visit = SiteVisit::create($request->safe()->except(['consent', 'website']) + ['reference' => (string) Str::uuid(), 'status' => 'new']);

        return redirect()->route('assessment.create')->with('reference', $visit->reference);
    }
}
