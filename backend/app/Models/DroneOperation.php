<?php

namespace App\Models;

use Database\Factories\DroneOperationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DroneOperation extends Model
{
    /** @use HasFactory<DroneOperationFactory> */
    use HasFactory;

    public const STATUSES = ['planning', 'ready', 'in_progress', 'held', 'completed', 'cancelled'];

    public const PREFLIGHT_ITEMS = [
        'approval_map' => 'Approval and operating map verified',
        'weather' => 'Weather acceptable and reassessment time set',
        'exclusion_zone' => 'Exclusion zone secure and marshals positioned',
        'crew' => 'Crew fit, briefed and on tested radios',
        'airframe' => 'Airframe, motors, propellers and fasteners inspected',
        'payload' => 'Payload, nozzle, hose and strain relief secure',
        'controller' => 'Controller, telemetry, GNSS/navigation and settings verified',
        'batteries' => 'Aircraft and controller batteries acceptable',
        'lights' => 'Lights tested and suitable for the planned conditions',
        'pump' => 'Pump, flow, pressure, shut-off and leak test completed',
        'ppe' => 'Detergent, SDS, PPE, spill and eye-wash controls ready',
        'emergency' => 'Emergency landing areas and contacts confirmed',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'scheduled_start' => 'datetime',
            'scheduled_end' => 'datetime',
            'risk_assessment' => 'array',
            'risk_submitted_at' => 'datetime',
            'risk_approved_at' => 'datetime',
            'flight_plan' => 'array',
            'flight_plan_submitted_at' => 'datetime',
            'flight_plan_approved_at' => 'datetime',
            'preflight_checklist' => 'array',
            'preflight_completed_at' => 'datetime',
            'authorisation_details' => 'array',
            'agreement_signed_at' => 'datetime',
        ];
    }

    public function assignedPilot(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_pilot_id')->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id')->withTrashed();
    }

    public function flightLogs(): HasMany
    {
        return $this->hasMany(FlightLogEntry::class)->latest('takeoff_at');
    }

    public function isReadyForRelease(): bool
    {
        return $this->risk_status === 'approved'
            && $this->flight_plan_status === 'approved'
            && $this->preflight_status === 'go'
            && $this->authorisation_status === 'signed';
    }

    public function completedWorkflowSteps(): int
    {
        return collect([
            $this->risk_status === 'approved',
            $this->flight_plan_status === 'approved',
            in_array($this->preflight_status, ['go', 'no_go'], true),
            $this->authorisation_status === 'signed',
            $this->flightLogs()->exists(),
        ])->filter()->count();
    }

    public static function riskDescription(int $likelihood, string $severity): string
    {
        $index = $likelihood.strtoupper($severity);

        if (in_array($index, ['5A', '5B', '5C', '4A', '4B', '3A'], true)) {
            return 'Intolerable';
        }

        if (in_array($index, ['3E', '2D', '2E', '1B', '1C', '1D', '1E'], true)) {
            return 'Acceptable';
        }

        return 'Tolerable';
    }
}
