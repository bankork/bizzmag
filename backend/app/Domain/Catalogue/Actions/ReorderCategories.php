<?php

namespace App\Domain\Catalogue\Actions;

use App\Domain\Catalogue\Models\CatalogueCategory;
use App\Domain\Catalogue\Models\ItemType;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Sets the display order of all service or all product categories. The
 * list must name every category of that type exactly once.
 */
final class ReorderCategories
{
    public function __construct(private readonly AuditRecorder $audit) {}

    /**
     * @param  list<int>  $ids  In the new order.
     * @return list<CatalogueCategory>
     */
    public function __invoke(ItemType $type, array $ids): array
    {
        return DB::transaction(function () use ($type, $ids): array {
            $categories = CatalogueCategory::query()->where('type', $type)->lockForUpdate()->get()->keyBy('id');
            $known = $categories->keys()->all();
            $given = $ids;
            sort($known);
            sort($given);

            if ($known !== $given) {
                throw ValidationException::withMessages(['ids' => 'List every category of this type once. Reload and try again.']);
            }

            $before = $categories->sortBy('sort_order')->keys()->values()->all();

            foreach ($ids as $position => $id) {
                $category = $categories[$id];

                if ($category->sort_order !== $position + 1) {
                    $category->sort_order = $position + 1;
                    $category->version++;
                    $category->save();
                }
            }

            if ($before !== $ids) {
                $this->audit->record(new AuditEntry('catalogue.categories_reordered', after: ['type' => $type->value, 'order' => $ids], before: ['type' => $type->value, 'order' => $before]));
            }

            return array_map(fn (int $id) => $categories[$id], $ids);
        });
    }
}
