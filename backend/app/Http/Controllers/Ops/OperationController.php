<?php

namespace App\Http\Controllers\Ops;

use App\Http\Controllers\Controller;
use App\Models\DroneOperation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OperationController extends Controller
{
    public function create(Request $request): View
    {
        abort_unless($request->user()->isOperationsAdmin(), 403);

        return view('ops.operations.create', ['pilots' => $this->pilots()]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isOperationsAdmin(), 403);
        $data = $request->validate($this->operationRules());
        $data['reference'] = $this->nextReference();
        $data['created_by_id'] = $request->user()->id;
        $operation = DroneOperation::create($data);

        return redirect()->route('ops.operations.show', $operation)->with('success', 'Operation created. Complete the planning workflow before release.');
    }

    public function show(Request $request, DroneOperation $operation): View
    {
        $this->authorizeOperation($request, $operation);
        $operation->load(['assignedPilot', 'flightLogs.pilot']);

        return view('ops.operations.show', ['operation' => $operation, 'pilots' => $this->pilots()]);
    }

    public function update(Request $request, DroneOperation $operation): RedirectResponse
    {
        abort_unless($request->user()->isOperationsAdmin(), 403);
        $operation->update($request->validate($this->operationRules()));

        return back()->with('success', 'Operation details updated.');
    }

    public function risk(Request $request, DroneOperation $operation): RedirectResponse
    {
        $this->authorizeOperation($request, $operation);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'assessment_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:5000'],
            'participants' => ['nullable', 'array', 'max:10'],
            'participants.*.name' => ['nullable', 'string', 'max:150'],
            'participants.*.job_title' => ['nullable', 'string', 'max:150'],
            'hazards' => ['required', 'array', 'min:1', 'max:15'],
            'hazards.*.phase' => ['required', 'string', 'max:150'],
            'hazards.*.hazard' => ['required', 'string', 'max:1000'],
            'hazards.*.consequence' => ['required', 'string', 'max:1000'],
            'hazards.*.causal_factor' => ['required', 'string', 'max:1000'],
            'hazards.*.controls' => ['required', 'string', 'max:2000'],
            'hazards.*.likelihood' => ['required', 'integer', 'between:1,5'],
            'hazards.*.severity' => ['required', Rule::in(['A', 'B', 'C', 'D', 'E'])],
            'reviewed_by' => ['nullable', 'string', 'max:150'],
            'accepted_by' => ['nullable', 'string', 'max:150'],
            'action' => ['required', Rule::in(['save', 'submit', 'approve'])],
        ]);
        $data['participants'] = collect($data['participants'] ?? [])->filter(fn ($participant) => filled($participant['name'] ?? null))->values()->all();
        $data['hazards'] = collect($data['hazards'])->map(function (array $hazard): array {
            $hazard['risk_index'] = $hazard['likelihood'].strtoupper($hazard['severity']);
            $hazard['risk_description'] = DroneOperation::riskDescription((int) $hazard['likelihood'], $hazard['severity']);

            return $hazard;
        })->all();
        $action = $data['action'];
        unset($data['action']);
        if ($action === 'approve') {
            abort_unless($request->user()->isOperationsAdmin(), 403);
            $operation->update(['risk_assessment' => $data, 'risk_status' => 'approved', 'risk_approved_at' => now(), 'risk_approved_by' => $request->user()->id]);
        } else {
            $operation->update(['risk_assessment' => $data, 'risk_status' => $action === 'submit' ? 'submitted' : 'draft', 'risk_submitted_at' => $action === 'submit' ? now() : null, 'risk_approved_at' => null, 'risk_approved_by' => null]);
        }
        $this->refreshReadiness($operation);

        return back()->with('success', $action === 'approve' ? 'Risk assessment approved.' : 'Risk assessment saved.');
    }

    public function flightPlan(Request $request, DroneOperation $operation): RedirectResponse
    {
        $this->authorizeOperation($request, $operation);
        $data = $request->validate([
            'activity' => ['required', 'string', 'max:200'],
            'location' => ['required', 'string', 'max:500'],
            'operating_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'crew' => ['required', 'string', 'max:2000'],
            'uas_model' => ['required', 'string', 'max:150'],
            'uas_serial' => ['required', 'string', 'max:150'],
            'weather' => ['required', 'string', 'max:2000'],
            'threats' => ['required', 'string', 'max:4000'],
            'highest_obstacle_m' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'airspace_limit_m' => ['required', 'numeric', 'min:0', 'max:10000'],
            'notam' => ['nullable', 'string', 'max:2000'],
            'flight_planning' => ['required', 'string', 'max:5000'],
            'emergency_plan' => ['required', 'string', 'max:5000'],
            'filled_by' => ['required', 'string', 'max:150'],
            'checked_by' => ['nullable', 'string', 'max:150'],
            'action' => ['required', Rule::in(['save', 'submit', 'approve'])],
        ]);
        $data['airspace_limit_ft'] = round((float) $data['airspace_limit_m'] * 3.28084, 1);
        $action = $data['action'];
        unset($data['action']);
        if ($action === 'approve') {
            abort_unless($request->user()->isOperationsAdmin(), 403);
            $operation->update(['flight_plan' => $data, 'flight_plan_status' => 'approved', 'flight_plan_approved_at' => now(), 'flight_plan_approved_by' => $request->user()->id]);
        } else {
            $operation->update(['flight_plan' => $data, 'flight_plan_status' => $action === 'submit' ? 'submitted' : 'draft', 'flight_plan_submitted_at' => $action === 'submit' ? now() : null, 'flight_plan_approved_at' => null, 'flight_plan_approved_by' => null]);
        }
        $this->refreshReadiness($operation);

        return back()->with('success', $action === 'approve' ? 'Flight plan approved.' : 'Flight plan saved.');
    }

    public function preflight(Request $request, DroneOperation $operation): RedirectResponse
    {
        $this->authorizeOperation($request, $operation);
        $data = $request->validate([
            'weather_notes' => ['required', 'string', 'max:2000'],
            'temperature' => ['nullable', 'string', 'max:100'],
            'wind' => ['nullable', 'string', 'max:100'],
            'visual_observer' => ['required', 'string', 'max:150'],
            'aircraft_id' => ['required', 'string', 'max:150'],
            'mission' => ['required', 'string', 'max:300'],
            'items' => ['nullable', 'array'],
            'decision' => ['required', Rule::in(['go', 'no_go'])],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);
        $items = collect(DroneOperation::PREFLIGHT_ITEMS)->mapWithKeys(fn ($label, $key) => [$key => (bool) ($data['items'][$key] ?? false)])->all();
        if ($data['decision'] === 'go' && in_array(false, $items, true)) {
            return back()->withErrors(['items' => 'Every preflight item must be confirmed before a GO decision.'])->withInput();
        }
        $data['items'] = $items;
        $operation->update(['preflight_checklist' => $data, 'preflight_status' => $data['decision'], 'preflight_completed_at' => now(), 'preflight_completed_by' => $request->user()->id]);
        $this->refreshReadiness($operation);

        return back()->with('success', $data['decision'] === 'go' ? 'Preflight completed with a GO decision.' : 'Operation placed on hold by the NO GO decision.');
    }

    public function authorisation(Request $request, DroneOperation $operation): RedirectResponse
    {
        $this->authorizeOperation($request, $operation);
        $data = $request->validate([
            'representative_name' => ['required', 'string', 'max:150'],
            'designation' => ['required', 'string', 'max:150'],
            'company' => ['required', 'string', 'max:200'],
            'identity_reference' => ['nullable', 'string', 'max:100'],
            'work_area' => ['required', 'string', 'max:1000'],
            'permitted_hours' => ['required', 'string', 'max:200'],
            'known_hazards' => ['nullable', 'string', 'max:3000'],
            'signed_at' => ['required', 'date'],
            'signed_agreement' => ['nullable', File::types(['pdf', 'jpg', 'jpeg', 'png'])->max('10mb')],
        ]);
        if (! $operation->agreement_path && ! $request->hasFile('signed_agreement')) {
            return back()->withErrors(['signed_agreement' => 'Upload the signed agreement before recording customer authorisation.'])->withInput();
        }
        unset($data['signed_agreement']);
        $attributes = ['authorisation_details' => $data, 'agreement_signed_at' => $data['signed_at'], 'authorisation_status' => 'signed'];
        if ($request->hasFile('signed_agreement')) {
            if ($operation->agreement_path) {
                Storage::disk('local')->delete($operation->agreement_path);
            }
            $file = $request->file('signed_agreement');
            $attributes['agreement_path'] = $file->store("operations/{$operation->id}/agreements", 'local');
            $attributes['agreement_original_name'] = $file->getClientOriginalName();
            $attributes['agreement_uploaded_by'] = $request->user()->id;
        }
        $operation->update($attributes);
        $this->refreshReadiness($operation);

        return back()->with('success', 'Signed customer authorisation recorded.');
    }

    public function status(Request $request, DroneOperation $operation): RedirectResponse
    {
        abort_unless($request->user()->isOperationsAdmin(), 403);
        $data = $request->validate(['status' => ['required', Rule::in(DroneOperation::STATUSES)]]);
        if (in_array($data['status'], ['ready', 'in_progress', 'completed'], true) && ! $operation->isReadyForRelease()) {
            return back()->withErrors(['status' => 'Complete and approve all four release-gate documents before advancing this operation.']);
        }
        $operation->update($data);

        return back()->with('success', 'Operation status updated.');
    }

    public function downloadAgreement(Request $request, DroneOperation $operation): BinaryFileResponse
    {
        $this->authorizeOperation($request, $operation);
        abort_unless($operation->agreement_path && Storage::disk('local')->exists($operation->agreement_path), 404);

        return response()->download(Storage::disk('local')->path($operation->agreement_path), $operation->agreement_original_name);
    }

    private function authorizeOperation(Request $request, DroneOperation $operation): void
    {
        abort_unless($request->user()->isOperationsAdmin() || $operation->assigned_pilot_id === $request->user()->id, 403);
    }

    /** @return Collection<int, User> */
    private function pilots(): Collection
    {
        return User::query()->where('role', 'pilot')->where('status', 'active')->orderBy('name')->get();
    }

    private function operationRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'customer_name' => ['required', 'string', 'max:200'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'site_address' => ['required', 'string', 'max:2000'],
            'scheduled_start' => ['nullable', 'date'],
            'scheduled_end' => ['nullable', 'date', 'after:scheduled_start'],
            'assigned_pilot_id' => ['nullable', Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'pilot')->where('status', 'active')->whereNull('deleted_at'))],
        ];
    }

    private function nextReference(): string
    {
        $prefix = 'OPS-'.now()->format('Ym').'-';
        $last = DroneOperation::query()->where('reference', 'like', $prefix.'%')->orderByDesc('reference')->value('reference');

        return $prefix.str_pad((string) (((int) substr((string) $last, -4)) + 1), 4, '0', STR_PAD_LEFT);
    }

    private function refreshReadiness(DroneOperation $operation): void
    {
        $operation->refresh();
        if ($operation->preflight_status === 'no_go') {
            $operation->update(['status' => 'held']);
        } elseif ($operation->isReadyForRelease() && in_array($operation->status, ['planning', 'held'], true)) {
            $operation->update(['status' => 'ready']);
        } elseif (! $operation->isReadyForRelease() && $operation->status === 'ready') {
            $operation->update(['status' => 'planning']);
        }
    }
}
