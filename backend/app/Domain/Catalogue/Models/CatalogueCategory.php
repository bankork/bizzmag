<?php

namespace App\Domain\Catalogue\Models;

use App\Domain\Shared\Tenancy\BelongsToBusiness;
use Database\Factories\CatalogueCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A group of services or of products (FR-SVC-001).
 *
 * @property int $id
 * @property int $business_id
 * @property ItemType $type
 * @property string $name
 * @property int $sort_order
 * @property bool $is_active
 * @property int $version
 */
#[UseFactory(CatalogueCategoryFactory::class)]
class CatalogueCategory extends Model
{
    use BelongsToBusiness;

    /** @use HasFactory<CatalogueCategoryFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['sort_order' => 0, 'is_active' => true, 'version' => 1];

    protected $fillable = ['business_id', 'type', 'name', 'sort_order', 'is_active', 'version'];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'type' => ItemType::class,
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'version' => 'integer',
        ];
    }

    /**
     * Top-level items, without variants.
     *
     * @return HasMany<CatalogueItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CatalogueItem::class, 'category_id')->whereNull('parent_id');
    }

    /**
     * @return array{type: string, name: string, sort_order: int, is_active: bool}
     */
    public function snapshot(): array
    {
        return [
            'type' => $this->type->value,
            'name' => $this->name,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }
}
