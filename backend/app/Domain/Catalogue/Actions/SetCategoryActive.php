<?php

namespace App\Domain\Catalogue\Actions;

use App\Domain\Catalogue\Exceptions\CategoryInUse;
use App\Domain\Catalogue\Models\CatalogueCategory;
use App\Domain\Catalogue\Models\CatalogueItem;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Locking\RowLock;
use Illuminate\Support\Facades\DB;

/**
 * Deactivates or reactivates a category. Categories are never deleted. A
 * category with active items cannot be deactivated, so nothing disappears
 * from the till by surprise.
 */
final class SetCategoryActive
{
    public function __construct(
        private readonly RowLock $locks,
        private readonly AuditRecorder $audit,
    ) {}

    public function __invoke(CatalogueCategory $category, bool $active): CatalogueCategory
    {
        return DB::transaction(function () use ($category, $active): CatalogueCategory {
            $this->locks->forUpdate('catalogue_categories', $category->id);
            $category->refresh();

            if ($category->is_active === $active) {
                return $category;
            }

            if (! $active) {
                $activeItems = CatalogueItem::query()->topLevel()->where('category_id', $category->id)->where('is_active', true)->count();

                if ($activeItems > 0) {
                    throw CategoryInUse::withActiveItems($activeItems);
                }
            }

            $before = $category->snapshot();
            $category->is_active = $active;
            $category->version++;
            $category->save();

            $this->audit->record(new AuditEntry(
                $active ? 'catalogue.category_activated' : 'catalogue.category_deactivated',
                'catalogue_category',
                $category->id,
                before: $before,
                after: $category->snapshot(),
            ));

            return $category;
        });
    }
}
