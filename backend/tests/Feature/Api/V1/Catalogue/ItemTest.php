<?php

namespace Tests\Feature\Api\V1\Catalogue;

use App\Domain\Audit\AuditEvent;
use App\Domain\Catalogue\Models\CatalogueItem;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use Tests\Feature\Api\V1\ApiTestCase;
use Tests\Support\BuildsCatalogue;

/**
 * FR-SVC-002 to 009 and the catalogue credit split defaults (FR-ATTR-004,
 * FR-ATTR-007): services, products and variants, their rules, and the
 * price history.
 */
class ItemTest extends ApiTestCase
{
    use BuildsCatalogue;

    public function test_a_service_takes_its_type_from_the_category_and_defaults_the_primary_share_to_half(): void
    {
        $salon = $this->installSalon();
        $braids = $this->category($salon['business'], 'Braids');
        $this->signIn($salon['owner']);

        $this->postJson('api/v1/catalogue/items', [
            'category_id' => $braids->id,
            'name' => 'Frontal wig installation',
            'price_kobo' => 2_500_000,
            'allocation_method' => 'primary_assistant',
        ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'service')
            ->assertJsonPath('data.price_kobo', 2_500_000)
            ->assertJsonPath('data.taxable', true)
            ->assertJsonPath('data.quantity_allowed', false)
            ->assertJsonPath('data.allocation_method', 'primary_assistant')
            ->assertJsonPath('data.primary_share_bp', 5000)
            ->assertJsonPath('data.orderable', true);

        $event = AuditEvent::query()->where('action', 'catalogue.item_created')->sole();
        $this->assertSame(2_500_000, $event->after['price_kobo']);
    }

    public function test_an_equal_split_has_no_primary_share(): void
    {
        $salon = $this->installSalon();
        $this->signIn($salon['owner']);

        $this->postJson('api/v1/catalogue/items', [
            'category_id' => $this->category($salon['business'], 'Nails')->id,
            'name' => 'Classic manicure',
            'price_kobo' => 700_000,
            'allocation_method' => 'equal',
            'primary_share_bp' => 6000,
        ])->assertCreated()->assertJsonPath('data.primary_share_bp', null);
    }

    public function test_a_service_needs_a_credit_split(): void
    {
        $salon = $this->installSalon();
        $this->signIn($salon['owner']);

        $this->postJson('api/v1/catalogue/items', ['category_id' => $this->category($salon['business'], 'Nails')->id, 'name' => 'Pedicure', 'price_kobo' => 900_000])
            ->assertStatus(422)
            ->assertJsonValidationErrors('allocation_method');
    }

    public function test_a_product_always_allows_a_quantity_and_has_no_credit_split(): void
    {
        $salon = $this->installSalon();
        $products = $this->category($salon['business'], 'Hair products', 'product');
        $this->signIn($salon['owner']);

        $this->postJson('api/v1/catalogue/items', ['category_id' => $products->id, 'name' => 'Edge control', 'price_kobo' => 500_000, 'quantity_allowed' => false])
            ->assertCreated()
            ->assertJsonPath('data.type', 'product')
            ->assertJsonPath('data.quantity_allowed', true)
            ->assertJsonPath('data.allocation_method', null);

        $this->postJson('api/v1/catalogue/items', ['category_id' => $products->id, 'name' => 'Lace glue', 'price_kobo' => 800_000, 'allocation_method' => 'equal'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('allocation_method');
        $this->postJson('api/v1/catalogue/items', ['category_id' => $products->id, 'name' => 'Braiding hair', 'has_variants' => true])
            ->assertStatus(422)
            ->assertJsonValidationErrors('has_variants');
    }

    public function test_prices_and_shares_outside_their_limits_are_refused(): void
    {
        $salon = $this->installSalon();
        $category = $this->category($salon['business'], 'Braids');
        $this->signIn($salon['owner']);
        $base = ['category_id' => $category->id, 'name' => 'Box braids', 'allocation_method' => 'primary_assistant'];

        $this->postJson('api/v1/catalogue/items', [...$base, 'price_kobo' => -1])->assertStatus(422)->assertJsonPath('errors.price_kobo.0', 'A price cannot be below ₦0.');
        $this->postJson('api/v1/catalogue/items', [...$base, 'price_kobo' => 3_500_000, 'primary_share_bp' => 50])->assertStatus(422)->assertJsonValidationErrors('primary_share_bp');
        $this->postJson('api/v1/catalogue/items', [...$base, 'price_kobo' => 3_500_000, 'primary_share_bp' => 10001])->assertStatus(422)->assertJsonValidationErrors('primary_share_bp');
        $this->postJson('api/v1/catalogue/items', $base)->assertStatus(422)->assertJsonPath('errors.price_kobo.0', 'Enter a price, or set this service to have variants.');
        $this->postJson('api/v1/catalogue/items', [...$base, 'price_kobo' => 0])->assertCreated();
    }

    public function test_names_are_unique_within_a_category(): void
    {
        $salon = $this->installSalon();
        $existing = $this->service($salon['business'], 'Silk press', 1_800_000);
        $this->signIn($salon['owner']);

        $this->postJson('api/v1/catalogue/items', ['category_id' => $existing->category_id, 'name' => 'Silk press', 'price_kobo' => 1, 'allocation_method' => 'equal'])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'There is already an item with this name in this category.');
    }

    public function test_variants_have_their_own_price_and_take_everything_else_from_the_service(): void
    {
        $salon = $this->installSalon();
        $this->signIn($salon['owner']);

        $parent = $this->postJson('api/v1/catalogue/items', [
            'category_id' => $this->category($salon['business'], 'Braids')->id,
            'name' => 'Knotless braids',
            'has_variants' => true,
            'price_kobo' => 1_000,
            'taxable' => false,
            'allocation_method' => 'primary_assistant',
            'primary_share_bp' => 6000,
        ])->assertCreated()->assertJsonPath('data.price_kobo', null)->assertJsonPath('data.orderable', false)->json('data');

        foreach (['Small' => 5_500_000, 'Medium' => 4_000_000, 'Large' => 3_000_000] as $name => $price) {
            $this->postJson('api/v1/catalogue/items', ['parent_id' => $parent['id'], 'name' => $name, 'price_kobo' => $price, 'allocation_method' => 'equal'])
                ->assertCreated()
                ->assertJsonPath('data.taxable', false)
                ->assertJsonPath('data.allocation_method', 'primary_assistant')
                ->assertJsonPath('data.primary_share_bp', 6000)
                ->assertJsonPath('data.orderable', true);
        }

        $this->getJson("api/v1/catalogue/items/{$parent['id']}")
            ->assertOk()
            ->assertJsonPath('data.variants.*.name', ['Small', 'Medium', 'Large'])
            ->assertJsonPath('data.variants.1.price_kobo', 4_000_000);
    }

    public function test_changing_the_service_updates_its_variants(): void
    {
        $salon = $this->installSalon();
        $knotless = $this->serviceWithVariants($salon['business'], 'Knotless braids', ['Small' => 5_500_000, 'Medium' => 4_000_000]);
        $this->signIn($salon['owner']);

        $this->patchJson("api/v1/catalogue/items/{$knotless->id}", ['version' => $knotless->version, 'taxable' => false, 'allocation_method' => 'primary_assistant', 'primary_share_bp' => 7000])
            ->assertOk()
            ->assertJsonPath('data.variants.0.taxable', false)
            ->assertJsonPath('data.variants.1.primary_share_bp', 7000);

        $this->assertSame(2, CatalogueItem::query()->withoutGlobalScopes()->where('parent_id', $knotless->id)->where('taxable', false)->where('primary_share_bp', 7000)->count());
    }

    public function test_a_variant_can_only_be_added_to_a_service_set_to_have_variants(): void
    {
        $salon = $this->installSalon();
        $cornrows = $this->service($salon['business'], 'Cornrows', 800_000);
        $this->signIn($salon['owner']);

        $this->postJson('api/v1/catalogue/items', ['parent_id' => $cornrows->id, 'name' => 'Small', 'price_kobo' => 900_000])
            ->assertStatus(422)
            ->assertJsonValidationErrors('parent_id');
    }

    public function test_a_service_with_variants_keeps_them(): void
    {
        $salon = $this->installSalon();
        $acrylic = $this->serviceWithVariants($salon['business'], 'Acrylic full set', ['Short' => 1_800_000]);
        $this->signIn($salon['owner']);

        $this->patchJson("api/v1/catalogue/items/{$acrylic->id}", ['version' => $acrylic->version, 'has_variants' => false, 'price_kobo' => 1_800_000])
            ->assertStatus(422)
            ->assertJsonValidationErrors('has_variants');
    }

    public function test_a_service_cannot_move_into_a_product_category(): void
    {
        $salon = $this->installSalon();
        $silk = $this->service($salon['business'], 'Silk press', 1_800_000);
        $products = $this->category($salon['business'], 'Hair products', 'product');
        $this->signIn($salon['owner']);

        $this->patchJson("api/v1/catalogue/items/{$silk->id}", ['version' => $silk->version, 'category_id' => $products->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('category_id');
    }

    public function test_a_price_change_needs_the_current_version_and_appears_in_the_history(): void
    {
        $salon = $this->installSalon();
        $knotless = $this->serviceWithVariants($salon['business'], 'Knotless braids', ['Medium' => 4_000_000]);
        $medium = $knotless->variants->first();
        $this->signIn($salon['owner']);

        $this->patchJson("api/v1/catalogue/items/{$medium->id}", ['version' => $medium->version, 'price_kobo' => 4_500_000])
            ->assertOk()
            ->assertJsonPath('data.price_kobo', 4_500_000)
            ->assertJsonPath('data.version', $medium->version + 1);
        $this->patchJson("api/v1/catalogue/items/{$medium->id}", ['version' => $medium->version, 'price_kobo' => 1])
            ->assertStatus(409)
            ->assertJsonPath('code', 'STALE');

        $history = $this->getJson("api/v1/catalogue/items/{$knotless->id}/history")->assertOk()->json('data.0');
        $this->assertSame('catalogue.item_updated', $history['action']);
        $this->assertSame(['id' => $medium->id, 'name' => 'Medium', 'is_variant' => true], $history['item']);
        $this->assertSame(4_000_000, $history['before']['price_kobo']);
        $this->assertSame(4_500_000, $history['after']['price_kobo']);
        $this->assertSame('Salon Owner', $history['actor']['name']);
    }

    public function test_saving_without_changes_records_nothing(): void
    {
        $salon = $this->installSalon();
        $silk = $this->service($salon['business'], 'Silk press', 1_800_000);
        $this->signIn($salon['owner']);

        $this->patchJson("api/v1/catalogue/items/{$silk->id}", ['version' => $silk->version, 'price_kobo' => 1_800_000])->assertOk()->assertJsonPath('data.version', $silk->version);

        $this->assertSame(0, AuditEvent::query()->where('action', 'catalogue.item_updated')->count());
    }

    public function test_the_historical_price_fixture_changes_the_price_through_the_action(): void
    {
        $salon = $this->installSalon();
        $silk = $this->service($salon['business'], 'Silk press', 2_000_000);

        $changed = $this->changePrice($silk, 2_500_000);

        $this->assertSame(2_500_000, $changed->price_kobo);
        $this->assertSame(2_000_000, AuditEvent::query()->where('action', 'catalogue.item_updated')->sole()->before['price_kobo']);
    }

    public function test_deactivated_items_are_kept_and_can_be_activated_again(): void
    {
        $salon = $this->installSalon();
        $gele = $this->service($salon['business'], 'Gele tying', 800_000);
        $this->signIn($salon['owner']);

        $this->postJson("api/v1/catalogue/items/{$gele->id}/deactivate")->assertOk()->assertJsonPath('data.is_active', false)->assertJsonPath('data.orderable', false);
        $this->assertContains('Gele tying', array_column($this->getJson('api/v1/catalogue/items')->json('data'), 'name'));
        $this->assertNotContains('Gele tying', array_column($this->getJson('api/v1/catalogue/items?filter[active]=1')->json('data'), 'name'));

        $this->postJson("api/v1/catalogue/items/{$gele->id}/activate")->assertOk()->assertJsonPath('data.orderable', true);
        $this->assertDatabaseCount('catalogue_items', 1);
    }

    public function test_the_list_can_be_filtered_by_type_category_and_name(): void
    {
        $salon = $this->installSalon();
        $this->serviceWithVariants($salon['business'], 'Knotless braids', ['Medium' => 4_000_000]);
        $this->service($salon['business'], 'Silk press', 1_800_000);
        $this->product($salon['business'], 'Edge control', 500_000);
        $this->signIn($salon['owner']);

        $this->assertSame(['Edge control'], array_column($this->getJson('api/v1/catalogue/items?filter[type]=product')->json('data'), 'name'));
        $this->assertSame(['Silk press'], array_column($this->getJson('api/v1/catalogue/items?filter[q]=silk')->json('data'), 'name'));
        $this->assertSame(['Knotless braids'], array_column($this->getJson('api/v1/catalogue/items?filter[q]=medium')->json('data'), 'name'));
    }

    public function test_an_item_in_an_inactive_category_cannot_be_ordered(): void
    {
        $salon = $this->installSalon();
        $silk = $this->service($salon['business'], 'Silk press', 1_800_000);
        app(CurrentBusiness::class)->within($salon['business']->id, fn () => $silk->category->forceFill(['is_active' => false])->save());

        $this->signIn($salon['owner'])->getJson("api/v1/catalogue/items/{$silk->id}")->assertJsonPath('data.orderable', false);
    }
}
