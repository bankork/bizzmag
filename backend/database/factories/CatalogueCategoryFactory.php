<?php

namespace Database\Factories;

use App\Domain\Catalogue\Models\CatalogueCategory;
use App\Domain\Catalogue\Models\ItemType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Create inside CurrentBusiness::within(), which fills business_id.
 *
 * @extends Factory<CatalogueCategory>
 */
class CatalogueCategoryFactory extends Factory
{
    protected $model = CatalogueCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => ItemType::Service,
            'name' => fake()->unique()->words(2, true),
            'sort_order' => fake()->numberBetween(1, 50),
            'is_active' => true,
        ];
    }

    public function products(): static
    {
        return $this->state(['type' => ItemType::Product]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
