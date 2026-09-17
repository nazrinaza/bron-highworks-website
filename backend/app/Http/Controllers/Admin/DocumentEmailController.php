<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BusinessDocumentMail;
use App\Models\BusinessDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class DocumentEmailController extends Controller
{
    public function __invoke(Request $request, BusinessDocument $document): RedirectResponse
    {
        $data = $request->validate([
            'recipient' => ['required', 'email:rfc', 'max:200'],
        ]);

        $document->load(['visit', 'parent', 'payments.recorder']);

        try {
            Mail::to($data['recipient'])->send(new BusinessDocumentMail($document));
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'recipient' => 'The document could not be emailed. Check the Resend configuration and try again.',
            ]);
        }

        return back()->with('success', "{$document->number} emailed to {$data['recipient']}.");
    }
}
