<?php

namespace App\Domain\Catalogue\Actions;

use App\Domain\Catalogue\Models\CatalogueCategory;
use App\Domain\Catalogue\Models\ItemType;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Exceptions\StaleVersion;
use App\Domain\Shared\Locking\RowLock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Creates or renames a category (FR-SVC-001). New categories go to the end
 * of the list. Audited with before and after values.
 */
final class SaveCategory
{
    public function __construct(
        private readonly RowLock $locks,
        private readonly AuditRecorder $audit,
    ) {}

    public function create(ItemType $type, string $name): CatalogueCategory
    {
        return DB::transaction(function () use ($type, $name): CatalogueCategory {
            $name = trim($name);
            $this->assertNameFree($type, $name);

            $category = CatalogueCategory::query()->create([
                'type' => $type,
                'name' => $name,
                'sort_order' => (int) CatalogueCategory::query()->where('type', $type)->max('sort_order') + 1,
            ]);

            $this->audit->record(new AuditEntry('catalogue.category_created', 'catalogue_category', $category->id, after: $category->snapshot()));

            return $category;
        });
    }

    public function rename(CatalogueCategory $category, int $expectedVersion, string $name): CatalogueCategory
    {
        return DB::transaction(function () use ($category, $expectedVersion, $name): CatalogueCategory {
            $this->locks->forUpdate('catalogue_categories', $category->id);
            $category->refresh();

            if ($category->version !== $expectedVersion) {
                throw new StaleVersion(context: ['current_version' => $category->version]);
            }

            $name = trim($name);

            if ($name === $category->name) {
                return $category;
            }

            $this->assertNameFree($category->type, $name, $category->id);
            $before = $category->snapshot();
            $category->name = $name;
            $category->version++;
            $category->save();

            $this->audit->record(new AuditEntry('catalogue.category_updated', 'catalogue_category', $category->id, before: $before, after: $category->snapshot()));

            return $category;
        });
    }

    private function assertNameFree(ItemType $type, string $name, ?int $except = null): void
    {
        $taken = CatalogueCategory::query()
            ->where('type', $type)
            ->where('name', $name)
            ->when($except !== null, fn ($query) => $query->whereKeyNot($except))
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages(['name' => 'There is already a category with this name.']);
        }
    }
}
