<?php

namespace Database\Factories;

use App\Models\DroneOperation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DroneOperation> */
class DroneOperationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reference' => 'OPS-'.fake()->unique()->numerify('######'),
            'title' => fake()->company().' facade cleaning',
            'customer_name' => fake()->company(),
            'customer_email' => fake()->companyEmail(),
            'customer_phone' => fake()->phoneNumber(),
            'site_address' => fake()->address(),
            'scheduled_start' => now()->addWeek(),
            'scheduled_end' => now()->addWeek()->addHours(4),
            'assigned_pilot_id' => User::factory()->state(['role' => 'pilot', 'status' => 'active']),
            'created_by_id' => User::factory()->state(['is_admin' => true, 'role' => 'admin', 'status' => 'active']),
            'status' => 'planning',
        ];
    }
}
