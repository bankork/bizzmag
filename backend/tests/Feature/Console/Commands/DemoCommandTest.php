<?php

namespace Tests\Feature\Console\Commands;

use App\Domain\Business\Models\Business;
use App\Domain\Catalogue\Models\CatalogueItem;
use App\Domain\Catalogue\Models\ItemType;
use App\Domain\Catalogue\Models\SplitMethod;
use App\Domain\Customers\Models\Customer;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use App\Models\User;
use Database\Seeders\Demo\DemoSeeder;
use Database\Seeders\Demo\Support\DemoData;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DemoCommandTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seeds_the_demo_staff_accounts(): void
    {
        $this->artisan('app:demo')
            ->expectsOutputToContain('Demo salon loaded: Adunni Hair & Beauty Studio')
            ->assertSuccessful();

        $this->assertDatabaseCount('users', 12);
        $this->assertDatabaseHas('users', ['email' => 'chiamaka@adunni.test', 'name' => 'Chiamaka Eze']);
    }

    public function test_every_demo_staff_member_has_their_role_a_phone_and_a_salon(): void
    {
        $this->artisan('app:demo')->assertSuccessful();

        $business = Business::query()->sole();
        $roles = app(CurrentBusiness::class)->within($business->id, fn () => User::query()
            ->with('roles')->orderBy('id')->get()
            ->mapWithKeys(fn (User $user) => [$user->email => $user->roles->pluck('system_key')->all()])
            ->all());

        $this->assertSame(['owner'], $roles['adunni@adunni.test']);
        $this->assertSame(['supervisor'], $roles['ngozi@adunni.test']);
        $this->assertSame(['cashier'], $roles['kemi@adunni.test']);
        $this->assertSame(['employee'], $roles['precious@adunni.test']);
        $this->assertSame(0, User::query()->whereNull('phone')->count());
        $this->assertDatabaseCount('business_user', 12);
        $this->assertDatabaseCount('employee_profiles', 10);
        $this->assertDatabaseHas('tax_settings', ['business_id' => $business->id, 'enabled' => false]);
        $this->assertDatabaseHas('employee_profiles', ['display_name' => 'Precious', 'job_title' => 'Apprentice']);
    }

    public function test_running_twice_does_not_duplicate_anything(): void
    {
        $this->artisan('app:demo')->assertSuccessful();
        $phones = User::query()->orderBy('id')->pluck('phone')->all();
        $this->artisan('app:demo')->assertSuccessful();

        $this->assertDatabaseCount('users', 12);
        $this->assertDatabaseCount('businesses', 1);
        $this->assertDatabaseCount('roles', 4);
        $this->assertSame($phones, User::query()->orderBy('id')->pluck('phone')->all());
        $this->assertDatabaseCount('catalogue_categories', 7);
        $this->assertSame($this->expectedCatalogueRows(), CatalogueItem::query()->withoutGlobalScopes()->count());
        $this->assertDatabaseCount('customers', DemoSeeder::CUSTOMER_COUNT);
    }

    public function test_loads_the_price_list_with_variants_products_and_credit_splits(): void
    {
        $this->artisan('app:demo')->assertSuccessful();
        $business = Business::query()->sole();

        app(CurrentBusiness::class)->within($business->id, function (): void {
            $knotless = CatalogueItem::query()->where('name', 'Knotless braids')->with('variants')->sole();
            $this->assertTrue($knotless->has_variants);
            $this->assertNull($knotless->price_kobo);
            $this->assertSame(['Small' => 5_500_000, 'Medium' => 4_000_000, 'Large' => 3_000_000], $knotless->variants->pluck('price_kobo', 'name')->all());
            $this->assertTrue($knotless->variants->every(fn (CatalogueItem $variant) => $variant->primary_share_bp === 6000 && $variant->allocation_method === SplitMethod::PrimaryAssistant));

            $nailArt = CatalogueItem::query()->where('name', 'Nail art (per set)')->sole();
            $this->assertTrue($nailArt->quantity_allowed);
            $this->assertSame(SplitMethod::Equal, $nailArt->allocation_method);

            $hair = CatalogueItem::query()->where('name', 'X-pression braiding hair (pack)')->sole();
            $this->assertSame(ItemType::Product, $hair->type);
            $this->assertSame(350_000, $hair->price_kobo);
            $this->assertNull($hair->allocation_method);
        });
    }

    public function test_some_demo_customers_share_a_number_and_none_uses_a_staff_number(): void
    {
        $this->artisan('app:demo')->assertSuccessful();

        $shared = Customer::query()->withoutGlobalScopes()->whereNotNull('phone_e164')
            ->selectRaw('phone_e164, count(*) as total')->groupBy('phone_e164')->havingRaw('count(*) > 1')->get();
        $this->assertGreaterThanOrEqual(3, $shared->count());
        $this->assertGreaterThan(0, Customer::query()->withoutGlobalScopes()->whereNull('phone_e164')->count());
        $this->assertSame(0, Customer::query()->withoutGlobalScopes()->whereIn('phone_e164', User::query()->pluck('phone'))->count());
        $this->assertSame(0, Customer::query()->withoutGlobalScopes()->where('phone_e164', 'not like', '+234%000____')->count());
    }

    private function expectedCatalogueRows(): int
    {
        $rows = 0;

        foreach (DemoData::catalogue() as $categories) {
            foreach ($categories as $items) {
                foreach ($items as $item) {
                    $rows += 1 + count($item['variants'] ?? []);
                }
            }
        }

        return $rows;
    }

    public function test_demo_staff_can_sign_in_with_email_or_phone(): void
    {
        $this->artisan('app:demo')->assertSuccessful();
        $kemi = User::query()->where('email', 'kemi@adunni.test')->sole();
        $headers = ['Origin' => 'http://localhost:3000'];

        $this->postJson('api/v1/auth/login', ['identifier' => 'kemi@adunni.test', 'password' => 'Adunni@2026'], $headers)->assertOk();
        $this->postJson('api/v1/auth/logout', [], $headers)->assertOk();
        $this->postJson('api/v1/auth/login', ['identifier' => (string) $kemi->phone, 'password' => 'Adunni@2026'], $headers)
            ->assertOk()
            ->assertJsonPath('data.home', 'desk');
    }

    public function test_refuses_to_run_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->artisan('app:demo')
            ->expectsOutput('Demo data cannot be loaded in production.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_fresh_rebuild_stops_when_not_confirmed(): void
    {
        $this->artisan('app:demo', ['--fresh' => true])
            ->expectsConfirmation('This deletes ALL data in the "bizzmag_testing" database. Continue?', 'no')
            ->expectsOutput('Nothing changed.')
            ->assertFailed();
    }
}
