<?php

namespace Database\Factories;

use App\Domain\Catalogue\Models\CatalogueCategory;
use App\Domain\Catalogue\Models\CatalogueItem;
use App\Domain\Catalogue\Models\ItemType;
use App\Domain\Catalogue\Models\SplitMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A priced service with an Equal split by default. Create inside
 * CurrentBusiness::within(), which fills business_id.
 *
 * @extends Factory<CatalogueItem>
 */
class CatalogueItemFactory extends Factory
{
    protected $model = CatalogueItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => CatalogueCategory::factory(),
            'type' => ItemType::Service,
            'name' => fake()->unique()->words(3, true),
            'price_kobo' => fake()->numberBetween(50, 600) * 10_000,
            'taxable' => true,
            'quantity_allowed' => false,
            'allocation_method' => SplitMethod::Equal,
            'is_active' => true,
        ];
    }

    public function product(): static
    {
        return $this->state([
            'category_id' => CatalogueCategory::factory()->products(),
            'type' => ItemType::Product,
            'quantity_allowed' => true,
            'allocation_method' => null,
        ]);
    }

    public function withVariants(): static
    {
        return $this->state(['has_variants' => true, 'price_kobo' => null]);
    }

    /**
     * A variant of the given service, with the parent's settings.
     */
    public function variantOf(CatalogueItem $parent): static
    {
        return $this->state([
            'parent_id' => $parent->id,
            'category_id' => $parent->category_id,
            'type' => $parent->type,
            'taxable' => $parent->taxable,
            'quantity_allowed' => $parent->quantity_allowed,
            'allocation_method' => $parent->allocation_method,
            'primary_share_bp' => $parent->primary_share_bp,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
