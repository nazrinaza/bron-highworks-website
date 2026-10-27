<?php

namespace App\Http\Controllers\Ops;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManualController extends Controller
{
    public function show(): View
    {
        return view('ops.manual');
    }

    public function download(): BinaryFileResponse
    {
        return response()->download(resource_path('operations/drone-operations-manual.docx'), 'SERI_NS_Complete_Drone_Operations_Manual.docx');
    }

    public function agreementTemplate(): BinaryFileResponse
    {
        return response()->download(resource_path('operations/property-access-authorisation.pdf'), 'BRON_Property_Access_and_Work_Authorisation.pdf');
    }
}
