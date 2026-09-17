<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssessmentRequest;
use App\Mail\AssessmentConfirmationMail;
use App\Mail\NewAssessmentNotificationMail;
use App\Models\SiteVisit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class AssessmentController extends Controller
{
    public function create(): View
    {
        return view('assessment');
    }

    public function store(AssessmentRequest $request): RedirectResponse
    {
        $visit = SiteVisit::create($request->safe()->except(['consent', 'website']) + ['reference' => (string) Str::uuid(), 'status' => 'new']);

        $this->sendNotification(function () use ($visit): void {
            Mail::to($visit->email)->queue(new AssessmentConfirmationMail($visit));
        });

        $this->sendNotification(function () use ($visit): void {
            Mail::to(config('mail.notifications.to'))->queue(new NewAssessmentNotificationMail($visit));
        });

        return redirect()->route('assessment.create')->with('reference', $visit->reference);
    }

    private function sendNotification(callable $send): void
    {
        try {
            $send();
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
