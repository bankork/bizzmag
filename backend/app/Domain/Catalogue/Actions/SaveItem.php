<?php

namespace App\Domain\Catalogue\Actions;

use App\Domain\Catalogue\Models\CatalogueCategory;
use App\Domain\Catalogue\Models\CatalogueItem;
use App\Domain\Catalogue\Models\ItemType;
use App\Domain\Catalogue\Models\SplitMethod;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Exceptions\StaleVersion;
use App\Domain\Shared\Locking\RowLock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Creates and edits services, products and variants (FR-SVC-002 to 009).
 *
 * Rules applied here, because they depend on stored records:
 * - an item takes its type from its category;
 * - products allow a quantity, have no credit split and no variants;
 * - services have a credit split, and a primary share only with the
 *   Primary and assistants method (50% when not given, FR-ATTR-007);
 * - a service with variants has no price; each variant has one;
 * - a variant takes its parent's category, taxable flag, quantity flag and
 *   credit split, and changes to the parent are copied to its variants.
 *
 * Price changes never touch existing visits: visits keep a snapshot
 * (FR-SVC-006, FR-SVC-007). Every change is audited with before and after
 * values, which is the item's price history.
 */
final class SaveItem
{
    public function __construct(
        private readonly RowLock $locks,
        private readonly AuditRecorder $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data  Validated input.
     */
    public function create(array $data): CatalogueItem
    {
        return DB::transaction(function () use ($data): CatalogueItem {
            $parentId = $data['parent_id'] ?? null;

            $item = $parentId === null
                ? $this->newTopLevel($data)
                : $this->newVariant($this->lockedParent((int) $parentId), $data);

            $this->assertNameFree($item);
            $item->save();

            $this->audit->record(new AuditEntry('catalogue.item_created', 'catalogue_item', $item->id, after: $item->snapshot()));

            return $item;
        });
    }

    /**
     * @param  array<string, mixed>  $data  Validated input; only the keys given change.
     */
    public function update(CatalogueItem $item, int $expectedVersion, array $data): CatalogueItem
    {
        return DB::transaction(function () use ($item, $expectedVersion, $data): CatalogueItem {
            $this->locks->forUpdate('catalogue_items', $item->id);
            $item->refresh();

            if ($item->version !== $expectedVersion) {
                throw new StaleVersion(context: ['current_version' => $item->version]);
            }

            $before = $item->snapshot();

            if ($item->isVariant()) {
                $item->fill(array_intersect_key($data, array_flip(['name', 'description', 'price_kobo', 'sort_order'])));
            } else {
                $this->applyTopLevel($item, $data);
            }

            if (array_key_exists('name', $data)) {
                $item->name = trim((string) $data['name']);
            }

            if ($item->snapshot() === $before) {
                return $item;
            }

            $this->assertNameFree($item);
            $item->version++;
            $item->save();

            if (! $item->isVariant()) {
                $this->copyToVariants($item);
            }

            $this->audit->record(new AuditEntry('catalogue.item_updated', 'catalogue_item', $item->id, before: $before, after: $item->snapshot()));

            return $item;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function newTopLevel(array $data): CatalogueItem
    {
        $item = new CatalogueItem([
            'sort_order' => 0,
            'is_active' => true,
            'has_variants' => false,
            'taxable' => true,
            'quantity_allowed' => false,
        ]);

        $this->applyTopLevel($item, $data + ['allocation_method' => null]);
        $item->name = trim((string) $data['name']);

        return $item;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function newVariant(CatalogueItem $parent, array $data): CatalogueItem
    {
        if ($parent->isVariant() || ! $parent->has_variants) {
            throw ValidationException::withMessages(['parent_id' => 'Variants can only be added to a service that is set to have variants.']);
        }

        if (! array_key_exists('price_kobo', $data) || $data['price_kobo'] === null) {
            throw ValidationException::withMessages(['price_kobo' => 'Enter a price for this variant.']);
        }

        $item = new CatalogueItem([
            'parent_id' => $parent->id,
            'name' => trim((string) $data['name']),
            'description' => $data['description'] ?? null,
            'price_kobo' => (int) $data['price_kobo'],
            'has_variants' => false,
            'is_active' => true,
            'sort_order' => $data['sort_order'] ?? (int) CatalogueItem::query()->where('parent_id', $parent->id)->max('sort_order') + 1,
        ]);

        $item->fill($this->inherited($parent));

        return $item;
    }

    /**
     * Apply the fields a top-level service or product may have, then make
     * the combination valid.
     *
     * @param  array<string, mixed>  $data
     */
    private function applyTopLevel(CatalogueItem $item, array $data): void
    {
        if (array_key_exists('category_id', $data)) {
            $category = CatalogueCategory::query()->whereKey((int) $data['category_id'])->first()
                ?? throw ValidationException::withMessages(['category_id' => 'Choose a category.']);

            if ($item->exists && $category->type !== $item->type) {
                throw ValidationException::withMessages(['category_id' => 'A service cannot move to a product category, or the other way round.']);
            }

            $item->category_id = $category->id;
            $item->type = $category->type;
        }

        if (! isset($item->category_id)) {
            throw ValidationException::withMessages(['category_id' => 'Choose a category.']);
        }

        $item->fill(array_intersect_key($data, array_flip(['description', 'taxable', 'quantity_allowed', 'sort_order', 'has_variants', 'price_kobo'])));

        if ($item->type === ItemType::Product) {
            if ($item->has_variants) {
                throw ValidationException::withMessages(['has_variants' => 'Only services can have variants.']);
            }

            if (($data['allocation_method'] ?? null) !== null) {
                throw ValidationException::withMessages(['allocation_method' => 'Products are not credited to staff, so they have no credit split.']);
            }

            $item->quantity_allowed = true;
            $item->allocation_method = null;
            $item->primary_share_bp = null;
        } else {
            $this->applySplit($item, $data);
        }

        $this->applyVariantMode($item);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applySplit(CatalogueItem $item, array $data): void
    {
        if (array_key_exists('allocation_method', $data) && $data['allocation_method'] !== null) {
            $item->allocation_method = SplitMethod::from((string) $data['allocation_method']);
        }

        if ($item->allocation_method === null) {
            throw ValidationException::withMessages(['allocation_method' => 'Choose how credit for this service is shared.']);
        }

        if ($item->allocation_method === SplitMethod::Equal) {
            $item->primary_share_bp = null;

            return;
        }

        $share = array_key_exists('primary_share_bp', $data) ? $data['primary_share_bp'] : $item->primary_share_bp;
        $item->primary_share_bp = $share === null ? SplitMethod::DEFAULT_PRIMARY_SHARE_BP : (int) $share;
    }

    private function applyVariantMode(CatalogueItem $item): void
    {
        if ($item->has_variants) {
            $item->price_kobo = null;

            return;
        }

        if ($item->exists && $item->getOriginal('has_variants') === true && CatalogueItem::query()->where('parent_id', $item->id)->exists()) {
            throw ValidationException::withMessages(['has_variants' => 'This service has variants. Deactivate them instead; each visit keeps the variant it used.']);
        }

        if ($item->price_kobo === null) {
            throw ValidationException::withMessages(['price_kobo' => 'Enter a price, or set this service to have variants.']);
        }
    }

    private function copyToVariants(CatalogueItem $parent): void
    {
        $inherited = $this->inherited($parent);

        foreach (CatalogueItem::query()->where('parent_id', $parent->id)->lockForUpdate()->get() as $variant) {
            $variant->fill($inherited);

            if ($variant->isDirty()) {
                $variant->version++;
                $variant->save();
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function inherited(CatalogueItem $parent): array
    {
        return [
            'category_id' => $parent->category_id,
            'type' => $parent->type,
            'taxable' => $parent->taxable,
            'quantity_allowed' => $parent->quantity_allowed,
            'allocation_method' => $parent->allocation_method,
            'primary_share_bp' => $parent->primary_share_bp,
        ];
    }

    private function lockedParent(int $id): CatalogueItem
    {
        $parent = CatalogueItem::query()->whereKey($id)->first()
            ?? throw ValidationException::withMessages(['parent_id' => 'Choose the service this variant belongs to.']);

        $this->locks->forUpdate('catalogue_items', $parent->id);

        return $parent->refresh();
    }

    private function assertNameFree(CatalogueItem $item): void
    {
        $taken = CatalogueItem::query()
            ->where('category_id', $item->category_id)
            ->where('parent_key', $item->parent_id ?? 0)
            ->where('name', $item->name)
            ->when($item->exists, fn ($query) => $query->whereKeyNot($item->id))
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages(['name' => $item->isVariant()
                ? 'This service already has a variant with this name.'
                : 'There is already an item with this name in this category.']);
        }
    }
}
