<?php

namespace Tests\Feature\Api\V1\Catalogue;

use App\Domain\Audit\AuditEvent;
use App\Domain\Business\Models\Business;
use App\Domain\Catalogue\Models\CatalogueCategory;
use App\Domain\Catalogue\Models\CatalogueItem;
use App\Domain\Identity\Permissions\SystemRole;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use Tests\Feature\Api\V1\ApiTestCase;

/**
 * FR-SVC-001: catalogue categories, in the owner's order, deactivated and
 * never deleted.
 */
class CategoryTest extends ApiTestCase
{
    public function test_the_owner_creates_categories_at_the_end_of_the_list(): void
    {
        $salon = $this->installSalon();
        $this->signIn($salon['owner']);

        $this->postJson('api/v1/catalogue/categories', ['type' => 'service', 'name' => ' Braids '])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Braids')
            ->assertJsonPath('data.sort_order', 1)
            ->assertJsonPath('data.is_active', true);
        $this->postJson('api/v1/catalogue/categories', ['type' => 'service', 'name' => 'Nails'])->assertJsonPath('data.sort_order', 2);
        $this->postJson('api/v1/catalogue/categories', ['type' => 'product', 'name' => 'Hair products'])->assertJsonPath('data.sort_order', 1);

        $this->assertSame(['Braids', 'Nails'], array_column($this->getJson('api/v1/catalogue/categories?filter[type]=service')->json('data'), 'name'));
        $this->assertSame(3, AuditEvent::query()->where('action', 'catalogue.category_created')->count());
    }

    public function test_a_category_name_is_unique_per_type(): void
    {
        $salon = $this->installSalon();
        $this->signIn($salon['owner']);
        $this->postJson('api/v1/catalogue/categories', ['type' => 'service', 'name' => 'Nails'])->assertCreated();

        $this->postJson('api/v1/catalogue/categories', ['type' => 'service', 'name' => 'Nails'])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'There is already a category with this name.');
        $this->postJson('api/v1/catalogue/categories', ['type' => 'product', 'name' => 'Nails'])->assertCreated();
    }

    public function test_renaming_needs_the_current_version_and_is_audited(): void
    {
        $salon = $this->installSalon();
        $category = $this->inSalon($salon, fn () => CatalogueCategory::factory()->create(['name' => 'Braid']));
        $this->signIn($salon['owner']);

        $this->patchJson("api/v1/catalogue/categories/{$category->id}", ['version' => 1, 'name' => 'Braids'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Braids')
            ->assertJsonPath('data.version', 2);
        $this->patchJson("api/v1/catalogue/categories/{$category->id}", ['version' => 1, 'name' => 'Hair'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'STALE');

        $event = AuditEvent::query()->where('action', 'catalogue.category_updated')->sole();
        $this->assertSame('Braid', $event->before['name']);
        $this->assertSame('Braids', $event->after['name']);
    }

    public function test_a_category_with_active_items_cannot_be_deactivated(): void
    {
        $salon = $this->installSalon();
        [$category, $item] = $this->inSalon($salon, function (): array {
            $category = CatalogueCategory::factory()->create();

            return [$category, CatalogueItem::factory()->create(['category_id' => $category->id])];
        });
        $this->signIn($salon['owner']);

        $this->postJson("api/v1/catalogue/categories/{$category->id}/deactivate")
            ->assertStatus(409)
            ->assertJsonPath('code', 'CATEGORY_IN_USE')
            ->assertJsonPath('data.active_items', 1);

        $this->postJson("api/v1/catalogue/items/{$item->id}/deactivate")->assertOk();
        $this->postJson("api/v1/catalogue/categories/{$category->id}/deactivate")->assertOk()->assertJsonPath('data.is_active', false);
        $this->postJson("api/v1/catalogue/categories/{$category->id}/activate")->assertOk()->assertJsonPath('data.is_active', true);

        $this->assertSame(
            ['catalogue.category_deactivated', 'catalogue.category_activated'],
            AuditEvent::query()->where('subject_type', 'catalogue_category')->where('action', '!=', 'catalogue.category_created')->orderBy('id')->pluck('action')->all(),
        );
    }

    public function test_reordering_must_name_every_category_of_the_type_once(): void
    {
        $salon = $this->installSalon();
        [$braids, $nails, $lashes] = $this->inSalon($salon, fn (): array => [
            CatalogueCategory::factory()->create(['name' => 'Braids', 'sort_order' => 1]),
            CatalogueCategory::factory()->create(['name' => 'Nails', 'sort_order' => 2]),
            CatalogueCategory::factory()->create(['name' => 'Lashes', 'sort_order' => 3]),
        ]);
        $this->signIn($salon['owner']);

        $this->postJson('api/v1/catalogue/categories/reorder', ['type' => 'service', 'ids' => [$lashes->id, $braids->id]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('ids');

        $this->postJson('api/v1/catalogue/categories/reorder', ['type' => 'service', 'ids' => [$lashes->id, $braids->id, $nails->id]])->assertOk();

        $this->assertSame(['Lashes', 'Braids', 'Nails'], array_column($this->getJson('api/v1/catalogue/categories')->json('data'), 'name'));
        $this->assertSame(1, AuditEvent::query()->where('action', 'catalogue.categories_reordered')->count());
    }

    public function test_staff_who_cannot_manage_the_catalogue_see_only_active_categories(): void
    {
        $salon = $this->installSalon();
        $this->inSalon($salon, function (): void {
            CatalogueCategory::factory()->create(['name' => 'Braids']);
            CatalogueCategory::factory()->inactive()->create(['name' => 'Old category']);
        });

        $names = array_column($this->signIn($this->staffMember($salon['business'], SystemRole::Employee))->getJson('api/v1/catalogue/categories')->assertOk()->json('data'), 'name');

        $this->assertSame(['Braids'], $names);
    }

    /**
     * @template T
     *
     * @param  array{business: Business}  $salon
     * @param  callable(): T  $callback
     * @return T
     */
    private function inSalon(array $salon, callable $callback): mixed
    {
        return app(CurrentBusiness::class)->within($salon['business']->id, $callback);
    }
}
