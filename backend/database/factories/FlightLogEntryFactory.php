<?php

namespace Database\Factories;

use App\Models\DroneOperation;
use App\Models\FlightLogEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FlightLogEntry> */
class FlightLogEntryFactory extends Factory
{
    public function definition(): array
    {
        $takeoff = now()->subHour();

        return [
            'drone_operation_id' => DroneOperation::factory(),
            'pilot_id' => User::factory()->state(['role' => 'pilot', 'status' => 'active']),
            'flight_date' => $takeoff->toDateString(),
            'task' => 'Facade cleaning',
            'approval_category' => 'CAAM approved operation',
            'client' => fake()->company(),
            'uas_model' => 'Joyance JTC30',
            'uas_serial' => fake()->bothify('JTC30-####'),
            'location' => fake()->city(),
            'observer' => fake()->name(),
            'takeoff_at' => $takeoff,
            'landing_at' => $takeoff->copy()->addMinutes(12),
            'total_minutes' => 12,
        ];
    }
}
