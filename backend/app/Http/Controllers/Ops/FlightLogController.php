<?php

namespace App\Http\Controllers\Ops;

use App\Http\Controllers\Controller;
use App\Models\DroneOperation;
use App\Models\FlightLogEntry;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FlightLogController extends Controller
{
    public function store(Request $request, DroneOperation $operation): RedirectResponse
    {
        $this->authorizeOperation($request, $operation);
        $data = $request->validate([
            'flight_date' => ['required', 'date'],
            'task' => ['required', 'string', 'max:300'],
            'approval_category' => ['nullable', 'string', 'max:150'],
            'client' => ['nullable', 'string', 'max:200'],
            'uas_model' => ['required', 'string', 'max:150'],
            'uas_serial' => ['required', 'string', 'max:150'],
            'location' => ['required', 'string', 'max:500'],
            'observer' => ['nullable', 'string', 'max:150'],
            'takeoff_time' => ['required', 'date_format:H:i'],
            'landing_time' => ['required', 'date_format:H:i'],
            'duty_start_time' => ['nullable', 'date_format:H:i'],
            'duty_finish_time' => ['nullable', 'date_format:H:i'],
            'additional_equipment' => ['nullable', 'string', 'max:2000'],
            'remarks' => ['nullable', 'string', 'max:3000'],
        ]);
        $takeoff = CarbonImmutable::parse($data['flight_date'].' '.$data['takeoff_time']);
        $landing = CarbonImmutable::parse($data['flight_date'].' '.$data['landing_time']);
        if ($landing->lessThanOrEqualTo($takeoff)) {
            $landing = $landing->addDay();
        }
        if ($landing->diffInMinutes($takeoff, true) > 1440) {
            return back()->withErrors(['landing_time' => 'The flight duration must not exceed 24 hours.'])->withInput();
        }
        $pilotId = $request->user()->isOperationsAdmin() ? ($operation->assigned_pilot_id ?? $request->user()->id) : $request->user()->id;
        $operation->flightLogs()->create([
            ...collect($data)->except(['takeoff_time', 'landing_time', 'duty_start_time', 'duty_finish_time'])->all(),
            'pilot_id' => $pilotId,
            'takeoff_at' => $takeoff,
            'landing_at' => $landing,
            'total_minutes' => (int) $takeoff->diffInMinutes($landing),
            'duty_started_at' => ($data['duty_start_time'] ?? null) ? CarbonImmutable::parse($data['flight_date'].' '.$data['duty_start_time']) : null,
            'duty_finished_at' => ($data['duty_finish_time'] ?? null) ? CarbonImmutable::parse($data['flight_date'].' '.$data['duty_finish_time']) : null,
        ]);

        return back()->with('success', 'Pilot logbook entry added.');
    }

    public function destroy(Request $request, DroneOperation $operation, FlightLogEntry $flightLog): RedirectResponse
    {
        $this->authorizeOperation($request, $operation);
        abort_unless($flightLog->drone_operation_id === $operation->id, 404);
        abort_unless($request->user()->isOperationsAdmin() || $flightLog->pilot_id === $request->user()->id, 403);
        $flightLog->delete();

        return back()->with('success', 'Pilot logbook entry removed.');
    }

    private function authorizeOperation(Request $request, DroneOperation $operation): void
    {
        abort_unless($request->user()->isOperationsAdmin() || $operation->assigned_pilot_id === $request->user()->id, 403);
    }
}
