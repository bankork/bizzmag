<?php

namespace Database\Factories;

use App\Domain\Business\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Business>
 */
class BusinessFactory extends Factory
{
    protected $model = Business::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Salon',
            'currency' => 'NGN',
            'timezone' => 'Africa/Lagos',
        ];
    }
}
