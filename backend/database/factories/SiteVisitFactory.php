<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SiteVisitFactory extends Factory
{
    public function definition(): array
    {
        return ['reference' => (string) Str::uuid(), 'name' => fake()->name(), 'company' => fake()->company(), 'email' => fake()->safeEmail(), 'phone' => '0196522238', 'service' => 'Building & façade cleaning', 'address' => fake()->address(), 'status' => 'new'];
    }
}
