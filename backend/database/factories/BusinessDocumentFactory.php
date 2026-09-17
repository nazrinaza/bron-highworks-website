<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessDocumentFactory extends Factory
{
    public function definition(): array
    {
        return ['number' => 'TEST-'.fake()->unique()->numerify('########'), 'type' => 'quotation', 'status' => 'draft', 'party_name' => fake()->company(), 'party_address' => fake()->address(), 'issued_on' => today(), 'items' => [['description' => 'Cleaning', 'quantity' => '1', 'unit_price' => '100.00', 'total_cents' => 10000]], 'subtotal_cents' => 10000, 'tax_basis_points' => 0, 'tax_cents' => 0, 'total_cents' => 10000];
    }
}
