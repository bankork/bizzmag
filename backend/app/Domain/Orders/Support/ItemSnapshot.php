<?php

namespace App\Domain\Orders\Support;

use App\Domain\Catalogue\Models\CatalogueItem;
use App\Domain\Catalogue\Models\ItemType;
use App\Domain\Orders\Exceptions\ItemNotOrderable;
use App\Domain\Orders\Exceptions\QuantityNotAllowed;

/**
 * What an order item keeps from the catalogue when it is added (FR-ORD-003,
 * SRS §7.4, P3 Q4): name with the variant, type, price, taxable flag and the
 * credit split. Later catalogue changes never reach it (§14.18).
 */
final class ItemSnapshot
{
    public const MAX_QUANTITY = 99;

    /**
     * @return array{catalogue_item_id: int, type: ItemType, name_snapshot: string, unit_price_kobo: int, taxable_snapshot: bool, quantity: int, allocation_method_snapshot: ?string, primary_share_bp_snapshot: ?int}
     */
    public static function of(CatalogueItem $item, int $quantity = 1, ?ItemType $onlyType = null): array
    {
        $item->loadMissing(['category', 'parent']);

        if (! $item->isOrderable() || $item->price_kobo === null || ($onlyType !== null && $item->type !== $onlyType)) {
            throw new ItemNotOrderable;
        }

        $allowsQuantity = $item->quantity_allowed || $item->type === ItemType::Product;

        if ($quantity < 1 || $quantity > self::MAX_QUANTITY || ($quantity > 1 && ! $allowsQuantity)) {
            throw new QuantityNotAllowed;
        }

        $isService = $item->type === ItemType::Service;

        return [
            'catalogue_item_id' => $item->id,
            'type' => $item->type,
            'name_snapshot' => $item->parent !== null ? $item->parent->name.', '.$item->name : $item->name,
            'unit_price_kobo' => $item->price_kobo,
            'taxable_snapshot' => $item->taxable,
            'quantity' => $quantity,
            'allocation_method_snapshot' => $isService ? $item->allocation_method?->value : null,
            'primary_share_bp_snapshot' => $isService ? $item->primary_share_bp : null,
        ];
    }
}
