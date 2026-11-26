<?php

namespace Database\Factories;

use App\Domain\Customers\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Fictional customers with demo-block numbers (+234...000....). Create
 * inside CurrentBusiness::within(), which fills business_id.
 *
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone_e164' => '+234803000'.fake()->unique()->numerify('####'),
            'email' => null,
            'notes' => null,
        ];
    }
}
