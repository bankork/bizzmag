<?php

namespace App\Domain\Catalogue\Models;

use App\Domain\Shared\Tenancy\BelongsToBusiness;
use Database\Factories\CatalogueItemFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A service or product, or a variant of a service (FR-SVC-002 to 009).
 *
 * A service with variants has no price of its own; each variant has one.
 * Variants carry a copy of their parent's taxable flag, quantity flag and
 * credit split (P2 Q1), so a visit can snapshot one row.
 *
 * @property int $id
 * @property int $business_id
 * @property int $category_id
 * @property ?int $parent_id
 * @property ItemType $type
 * @property string $name
 * @property ?string $description
 * @property bool $has_variants
 * @property ?int $price_kobo
 * @property bool $taxable
 * @property bool $quantity_allowed
 * @property ?SplitMethod $allocation_method
 * @property ?int $primary_share_bp
 * @property int $sort_order
 * @property bool $is_active
 * @property int $version
 * @property-read CatalogueCategory $category
 * @property-read ?CatalogueItem $parent
 * @property-read Collection<int, CatalogueItem> $variants
 */
#[UseFactory(CatalogueItemFactory::class)]
class CatalogueItem extends Model
{
    use BelongsToBusiness;

    /** @use HasFactory<CatalogueItemFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['has_variants' => false, 'taxable' => true, 'quantity_allowed' => false, 'sort_order' => 0, 'is_active' => true, 'version' => 1];

    /**
     * Settings a variant takes from its parent.
     */
    public const INHERITED = ['type', 'category_id', 'taxable', 'quantity_allowed', 'allocation_method', 'primary_share_bp'];

    protected $fillable = [
        'business_id', 'category_id', 'parent_id', 'type', 'name', 'description', 'has_variants', 'price_kobo',
        'taxable', 'quantity_allowed', 'allocation_method', 'primary_share_bp', 'sort_order', 'is_active', 'version',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'type' => ItemType::class,
            'allocation_method' => SplitMethod::class,
            'has_variants' => 'boolean',
            'price_kobo' => 'integer',
            'taxable' => 'boolean',
            'quantity_allowed' => 'boolean',
            'primary_share_bp' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'version' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<CatalogueCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CatalogueCategory::class, 'category_id');
    }

    /**
     * @return BelongsTo<CatalogueItem, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<CatalogueItem, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @param  Builder<CatalogueItem>  $query
     */
    public function scopeTopLevel(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    public function isVariant(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Whether it can be chosen for a new visit (FR-SVC-005): active, with
     * an active parent and category, and not a service whose variants are
     * what gets chosen.
     */
    public function isOrderable(): bool
    {
        if (! $this->is_active || $this->has_variants || ! $this->category->is_active) {
            return false;
        }

        return $this->parent_id === null || $this->parent?->is_active === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        return [
            'category_id' => $this->category_id,
            'parent_id' => $this->parent_id,
            'type' => $this->type->value,
            'name' => $this->name,
            'description' => $this->description,
            'has_variants' => $this->has_variants,
            'price_kobo' => $this->price_kobo,
            'taxable' => $this->taxable,
            'quantity_allowed' => $this->quantity_allowed,
            'allocation_method' => $this->allocation_method?->value,
            'primary_share_bp' => $this->primary_share_bp,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }
}
