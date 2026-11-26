<?php

namespace App\Domain\Catalogue\Actions;

use App\Domain\Catalogue\Models\CatalogueItem;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Locking\RowLock;
use Illuminate\Support\Facades\DB;

/**
 * Deactivates or reactivates an item or variant (FR-SVC-005). Items are
 * never deleted, so past visits keep their link. An inactive item cannot be
 * chosen for new visits; the variants of an inactive service cannot either.
 */
final class SetItemActive
{
    public function __construct(
        private readonly RowLock $locks,
        private readonly AuditRecorder $audit,
    ) {}

    public function __invoke(CatalogueItem $item, bool $active): CatalogueItem
    {
        return DB::transaction(function () use ($item, $active): CatalogueItem {
            $this->locks->forUpdate('catalogue_items', $item->id);
            $item->refresh();

            if ($item->is_active === $active) {
                return $item;
            }

            $before = $item->snapshot();
            $item->is_active = $active;
            $item->version++;
            $item->save();

            $this->audit->record(new AuditEntry(
                $active ? 'catalogue.item_activated' : 'catalogue.item_deactivated',
                'catalogue_item',
                $item->id,
                before: $before,
                after: $item->snapshot(),
            ));

            return $item;
        });
    }
}
