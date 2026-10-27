<?php

namespace App\Models;

use Database\Factories\FlightLogEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlightLogEntry extends Model
{
    /** @use HasFactory<FlightLogEntryFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'flight_date' => 'date',
            'takeoff_at' => 'datetime',
            'landing_at' => 'datetime',
            'duty_started_at' => 'datetime',
            'duty_finished_at' => 'datetime',
            'total_minutes' => 'integer',
        ];
    }

    public function operation(): BelongsTo
    {
        return $this->belongsTo(DroneOperation::class, 'drone_operation_id');
    }

    public function pilot(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
