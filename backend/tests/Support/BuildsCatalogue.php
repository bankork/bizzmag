<?php

namespace Tests\Support;

use App\Domain\Business\Models\Business;
use App\Domain\Catalogue\Actions\SaveItem;
use App\Domain\Catalogue\Models\CatalogueCategory;
use App\Domain\Catalogue\Models\CatalogueItem;
use App\Domain\Shared\Tenancy\CurrentBusiness;

/**
 * Catalogue records for tests, plus the historical price fixture (SRS
 * §14.18): a price change made through the real action, so later phases
 * can prove that a visit keeps the price it was created with.
 */
trait BuildsCatalogue
{
    /**
     * A priced service, for example service($business, 'Cornrows', 800_000).
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function service(Business $business, string $name, int $priceKobo, array $attributes = []): CatalogueItem
    {
        return app(CurrentBusiness::class)->within($business->id, fn () => CatalogueItem::factory()->create([
            'category_id' => $this->category($business, 'Services')->id,
            'name' => $name,
            'price_kobo' => $priceKobo,
            ...$attributes,
        ])->refresh());
    }

    /**
     * A service with variants, for example ['Small' => 5_500_000, 'Medium' => 4_000_000].
     *
     * @param  array<string, int>  $variants
     * @param  array<string, mixed>  $attributes
     */
    protected function serviceWithVariants(Business $business, string $name, array $variants, array $attributes = []): CatalogueItem
    {
        return app(CurrentBusiness::class)->within($business->id, function () use ($business, $name, $variants, $attributes): CatalogueItem {
            $parent = CatalogueItem::factory()->withVariants()->create([
                'category_id' => $this->category($business, 'Services')->id,
                'name' => $name,
                ...$attributes,
            ])->refresh();

            $position = 1;

            foreach ($variants as $variant => $priceKobo) {
                CatalogueItem::factory()->variantOf($parent)->create(['name' => $variant, 'price_kobo' => $priceKobo, 'sort_order' => $position++]);
            }

            return $parent->load('variants');
        });
    }

    protected function product(Business $business, string $name, int $priceKobo): CatalogueItem
    {
        return app(CurrentBusiness::class)->within($business->id, fn () => CatalogueItem::factory()->product()->create([
            'category_id' => $this->category($business, 'Products', 'product')->id,
            'name' => $name,
            'price_kobo' => $priceKobo,
        ])->refresh());
    }

    /**
     * Changes a price the way the owner does: through SaveItem, versioned and audited.
     */
    protected function changePrice(CatalogueItem $item, int $priceKobo): CatalogueItem
    {
        return app(CurrentBusiness::class)->within($item->business_id, fn () => app(SaveItem::class)->update($item->refresh(), $item->version, ['price_kobo' => $priceKobo]));
    }

    protected function category(Business $business, string $name, string $type = 'service'): CatalogueCategory
    {
        return app(CurrentBusiness::class)->within($business->id, fn () => CatalogueCategory::query()->firstOrCreate(
            ['type' => $type, 'name' => $name],
            ['sort_order' => 1],
        ));
    }
}
