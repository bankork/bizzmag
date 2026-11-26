<?php

namespace App\Http\Presenters;

use App\Domain\Catalogue\Models\CatalogueCategory;
use App\Domain\Catalogue\Models\CatalogueItem;
use App\Models\User;

/**
 * API views of the catalogue. Prices are left out entirely, not set to
 * null, for anyone who may not see them (BR-041): only users with
 * order.view_all or catalogue.manage receive price_kobo.
 */
final class CataloguePresenter
{
    public static function canSeePrices(?User $user): bool
    {
        return $user !== null && ($user->checkPermissionTo('order.view_all') || $user->checkPermissionTo('catalogue.manage'));
    }

    /**
     * @return array<string, mixed>
     */
    public static function category(CatalogueCategory $category): array
    {
        return [
            'id' => $category->id,
            'type' => $category->type->value,
            'name' => $category->name,
            'sort_order' => $category->sort_order,
            'is_active' => $category->is_active,
            'version' => $category->version,
            'items_count' => isset($category->items_count) ? (int) $category->items_count : null,
        ];
    }

    /**
     * An item, with its variants when they are loaded.
     *
     * @return array<string, mixed>
     */
    public static function item(CatalogueItem $item, bool $withPrices): array
    {
        $view = [
            'id' => $item->id,
            'category_id' => $item->category_id,
            'parent_id' => $item->parent_id,
            'type' => $item->type->value,
            'name' => $item->name,
            'description' => $item->description,
            'has_variants' => $item->has_variants,
            'taxable' => $item->taxable,
            'quantity_allowed' => $item->quantity_allowed,
            'allocation_method' => $item->allocation_method?->value,
            'primary_share_bp' => $item->primary_share_bp,
            'sort_order' => $item->sort_order,
            'is_active' => $item->is_active,
            'orderable' => $item->isOrderable(),
            'version' => $item->version,
        ];

        if ($withPrices) {
            $view = array_slice($view, 0, 7, true) + ['price_kobo' => $item->price_kobo] + array_slice($view, 7, null, true);
        }

        if ($item->relationLoaded('variants')) {
            $view['variants'] = $item->variants->map(function (CatalogueItem $variant) use ($item, $withPrices): array {
                $variant->setRelation('parent', $item)->setRelation('category', $item->category);

                return self::item($variant, $withPrices);
            })->values()->all();
        }

        return $view;
    }
}
