<?php

namespace Database\Factories;

use App\Domain\Orders\Models\OrderStatus;
use App\Domain\Orders\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A visit row for tests that need a state the P3 actions cannot reach yet,
 * such as a finalised visit for the late-window rule. Real visits are
 * opened with CreateOrder. Create inside CurrentBusiness::within().
 *
 * @extends Factory<ServiceOrder>
 */
class ServiceOrderFactory extends Factory
{
    protected $model = ServiceOrder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => 'T-'.fake()->unique()->numerify('######'),
            'guest_name' => fake()->firstName(),
            'status' => OrderStatus::Open,
            'opened_at' => now(),
            'opened_business_day' => now()->toDateString(),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Finalised the given number of hours ago, with a late window of the given length.
     */
    public function finalised(int $hoursAgo = 1, int $windowHours = 48): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Finalised,
            'finalised_at' => now()->subHours($hoursAgo),
            'finalised_business_day' => now()->subHours($hoursAgo)->toDateString(),
            'late_window_ends_at' => now()->subHours($hoursAgo)->addHours($windowHours),
        ]);
    }
}
