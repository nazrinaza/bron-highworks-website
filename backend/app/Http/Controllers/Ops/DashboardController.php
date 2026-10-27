<?php

namespace App\Http\Controllers\Ops;

use App\Http\Controllers\Controller;
use App\Models\DroneOperation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $operations = DroneOperation::query()
            ->with('assignedPilot')
            ->when(! $request->user()->isOperationsAdmin(), fn ($query) => $query->where('assigned_pilot_id', $request->user()->id))
            ->latest('id')
            ->paginate(20);

        $counts = DroneOperation::query()
            ->when(! $request->user()->isOperationsAdmin(), fn ($query) => $query->where('assigned_pilot_id', $request->user()->id))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('ops.dashboard', [
            'operations' => $operations,
            'counts' => $counts,
            'activePilots' => $request->user()->isOperationsAdmin() ? User::query()->where('role', 'pilot')->where('status', 'active')->count() : null,
        ]);
    }
}
