<?php

namespace Database\Seeders\Demo;

use App\Domain\Business\Actions\InstallBusiness;
use App\Domain\Business\Models\Business;
use App\Domain\Catalogue\Models\CatalogueCategory;
use App\Domain\Catalogue\Models\CatalogueItem;
use App\Domain\Catalogue\Models\ItemType;
use App\Domain\Catalogue\Models\SplitMethod;
use App\Domain\Customers\Models\Customer;
use App\Domain\Execution\Actions\DisputeExecution;
use App\Domain\Execution\Actions\SubmitExecution;
use App\Domain\Identity\Actions\ProvisionDefaultRoles;
use App\Domain\Identity\Models\EmployeeProfile;
use App\Domain\Orders\Actions\CreateOrder;
use App\Domain\Orders\Actions\RequestAdditional;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Models\ServiceOrder;
use App\Domain\Shared\Money\Money;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use App\Domain\Shared\Time\BusinessDay;
use App\Models\User;
use Database\Seeders\Demo\Support\DemoData;
use Database\Seeders\Demo\Support\NigerianNames;
use Database\Seeders\Demo\Support\NigerianPhoneNumbers;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Seeds the fictional Nigerian demo salon (Stage 4 §10.2). Safe to run
 * again: it updates what exists and adds what is missing.
 *
 * Phase 1: the business and its profile, default roles, tax switched off,
 * and the 12 staff with their roles, phone numbers and employee profiles.
 * Everyone can sign in with their email or their phone number.
 *
 * Phase 2: the price list from Data/catalogue.php, and about 150 fictional
 * customers. A few share a phone number (family members booked on one
 * phone), so the duplicate warning can be tried, and some have no phone.
 *
 * Phase 3: today's visits in every state (waiting, recorded by one,
 * recorded by three, disputed, extra service waiting for approval), made
 * through the real actions. Added once per business day.
 */
class DemoSeeder extends Seeder
{
    /**
     * Fixed seed, so the demo phone numbers are the same on every machine.
     */
    private const PHONE_SEED = 2026;

    private const CUSTOMER_SEED = 1504;

    public const CUSTOMER_COUNT = 150;

    /**
     * Every Nth customer shares the previous customer's number; every Mth
     * has none.
     */
    private const SHARED_PHONE_EVERY = 25;

    private const NO_PHONE_EVERY = 12;

    /**
     * @var list<string>
     */
    private const CUSTOMER_NOTES = [
        'Prefers medium knotless, no curly ends',
        'Tender-headed, go gently on the edges',
        'Books for owambe Saturdays, likes gele too',
        'Usually brings her own attachment hair',
        'Likes a short almond nail shape',
    ];

    public function __construct(
        private readonly InstallBusiness $install,
        private readonly ProvisionDefaultRoles $provisionRoles,
        private readonly CurrentBusiness $business,
        private readonly CreateOrder $createOrder,
        private readonly SubmitExecution $submit,
        private readonly RequestAdditional $requestAdditional,
        private readonly DisputeExecution $dispute,
    ) {}

    public function run(): void
    {
        $salon = DemoData::salon();
        $staff = DemoData::staff();
        $owner = $staff[0];

        $business = Business::query()->where('name', $salon['name'])->first()
            ?? ($this->install)($salon['name'], [
                'name' => $owner['name'],
                'email' => $owner['email'],
                'phone' => null,
                'password' => $salon['demo_password'],
            ], allowAdditional: true)['business'];

        $business->fill([
            'phone' => $salon['phone'],
            'email' => $salon['email'],
            'address' => $salon['address'],
            'timezone' => $salon['timezone'],
            'receipt_footer' => $salon['receipt_footer'],
        ])->save();

        $this->business->within($business->id, function () use ($business, $staff, $salon): void {
            $this->seedStaff($business, $staff, $salon['demo_password']);
            $this->seedCatalogue();
            $this->seedCustomers();
            $this->seedTodaysVisits($business);
        });
    }

    /**
     * The price list. Existing items keep their id and are brought back in
     * line with the data file, so the owner's edits are reset by --fresh only.
     */
    private function seedCatalogue(): void
    {
        $catalogue = DemoData::catalogue();

        foreach (['services' => ItemType::Service, 'products' => ItemType::Product] as $group => $type) {
            $position = 0;

            foreach ($catalogue[$group] as $categoryName => $items) {
                $category = CatalogueCategory::query()->firstOrCreate(['type' => $type, 'name' => $categoryName], ['sort_order' => ++$position]);

                foreach ($items as $index => $definition) {
                    $this->seedItem($category, $type, $definition, $index + 1);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function seedItem(CatalogueCategory $category, ItemType $type, array $definition, int $position): void
    {
        $allocation = $definition['allocation'] ?? null;
        $variants = $definition['variants'] ?? null;
        $split = match (true) {
            $type === ItemType::Product => [null, null],
            is_array($allocation) => [SplitMethod::PrimaryAssistant, (int) $allocation[1]],
            default => [SplitMethod::Equal, null],
        };

        $item = CatalogueItem::query()->firstOrNew(['category_id' => $category->id, 'parent_id' => null, 'name' => $definition['name']]);
        $item->fill([
            'type' => $type,
            'has_variants' => $variants !== null,
            'price_kobo' => $variants === null ? Money::fromNaira((string) $definition['price'])->kobo : null,
            'taxable' => true,
            'quantity_allowed' => $type === ItemType::Product || ($definition['quantity_allowed'] ?? false),
            'allocation_method' => $split[0],
            'primary_share_bp' => $split[1],
            'sort_order' => $position,
        ])->save();

        $variantPosition = 0;

        foreach ($variants ?? [] as $variantName => $price) {
            CatalogueItem::query()->updateOrCreate(
                ['category_id' => $category->id, 'parent_id' => $item->id, 'name' => $variantName],
                [
                    'type' => $type,
                    'price_kobo' => Money::fromNaira((string) $price)->kobo,
                    'taxable' => $item->taxable,
                    'quantity_allowed' => $item->quantity_allowed,
                    'allocation_method' => $item->allocation_method,
                    'primary_share_bp' => $item->primary_share_bp,
                    'sort_order' => ++$variantPosition,
                ],
            );
        }
    }

    /**
     * Fictional customers, generated from a fixed seed so every machine gets
     * the same list. Added once; running again leaves them as they are.
     */
    /**
     * A morning at the salon, so every visit and staff screen has something
     * to show. Skipped when today already has visits.
     */
    private function seedTodaysVisits(Business $business): void
    {
        $today = (string) BusinessDay::today($business->timezone);

        if (ServiceOrder::query()->where('opened_business_day', $today)->exists()) {
            return;
        }

        $people = User::query()->whereIn('email', array_column(DemoData::staff(), 'email'))->get()->keyBy(fn (User $user) => strtok((string) $user->email, '@'));
        $profile = fn (string $key): int => EmployeeProfile::query()->where('user_id', $people[$key]->id)->value('id');
        $customer = fn (int $number): int => (int) Customer::query()->orderBy('id')->skip($number)->value('id');
        $item = function (string $name, ?string $variant = null): int {
            $parent = CatalogueItem::query()->whereNull('parent_id')->where('name', $name)->firstOrFail();

            return $variant === null ? $parent->id : CatalogueItem::query()->where('parent_id', $parent->id)->where('name', $variant)->value('id');
        };
        $open = fn (string $desk, array $who, array $items) => ($this->createOrder)($people[$desk], $who, $items);
        $record = fn (string $who, OrderItem $orderItem, array $with = []) => ($this->submit)($people[$who], $orderItem, array_map($profile, $with));

        // Funke's knotless braids: three braiders on one record, plus hair packs.
        $braids = $open('kemi', ['customer_id' => $customer(0)], [
            ['catalogue_item_id' => $item('Knotless braids', 'Medium'), 'employee_ids' => [$profile('chiamaka'), $profile('funmilayo'), $profile('blessing')]],
            ['catalogue_item_id' => $item('X-pression braiding hair (pack)'), 'quantity' => 3],
        ]);
        $record('chiamaka', $braids->items()->firstOrFail(), ['funmilayo', 'blessing']);

        // Nails: one done, one still waiting for Zainab.
        $nails = $open('kemi', ['customer_id' => $customer(1)], [
            ['catalogue_item_id' => $item('Gel polish (hands)'), 'employee_ids' => [$profile('efe')]],
            ['catalogue_item_id' => $item('Classic pedicure'), 'employee_ids' => [$profile('zainab')]],
        ]);
        $record('efe', $nails->items()->firstOrFail());

        // A walk-in for a frontal install; the stylist asks for an extra wash.
        $wig = $open('emeka', ['guest_name' => 'Tope'], [
            ['catalogue_item_id' => $item('Frontal wig installation'), 'employee_ids' => [$profile('ngozi')]],
        ]);
        $record('ngozi', $wig->items()->firstOrFail(), ['hauwa']);
        ($this->requestAdditional)($people['hauwa'], $wig, $item('Wash and blow-dry'), 1, 'Customer wants her own hair washed under the wig');

        // Lashes: recorded with the apprentice, who disputes being on it.
        $lashes = $open('kemi', ['customer_id' => $customer(2)], [['catalogue_item_id' => $item('Lash extensions (volume)')]]);
        $disputed = $record('tolani', $lashes->items()->firstOrFail(), ['precious']);
        ($this->dispute)($people['precious'], $disputed, 'I only prepared the lashes, Tolani did the full set');

        // Just arrived, nobody assigned yet.
        $open('emeka', ['guest_name' => null], [['catalogue_item_id' => $item('Cornrows (all back)')]]);
    }

    private function seedCustomers(): void
    {
        if (Customer::query()->exists()) {
            return;
        }

        $random = new Randomizer(new Mt19937(self::CUSTOMER_SEED));
        $names = new NigerianNames($random);
        $phones = new NigerianPhoneNumbers($random);
        $staffPhones = User::query()->whereNotNull('phone')->pluck('phone')->flip();
        $previousPhone = null;

        for ($number = 1; $number <= self::CUSTOMER_COUNT; $number++) {
            $name = $names->fullName();

            $phone = match (true) {
                $number % self::NO_PHONE_EVERY === 0 => null,
                $number % self::SHARED_PHONE_EVERY === 0 && $previousPhone !== null => $previousPhone,
                default => $this->customerPhone($phones, $staffPhones),
            };

            Customer::query()->create([
                'name' => $name,
                'phone_e164' => $phone,
                'email' => $number % 7 === 0 ? $this->demoEmail($name, $number) : null,
                'notes' => $number % 9 === 0 ? self::CUSTOMER_NOTES[intdiv($number, 9) % count(self::CUSTOMER_NOTES)] : null,
            ]);

            $previousPhone = $phone ?? $previousPhone;
        }
    }

    /**
     * @param  Collection<string, int>  $staffPhones
     */
    private function customerPhone(NigerianPhoneNumbers $phones, $staffPhones): string
    {
        do {
            $phone = $phones->next();
        } while ($staffPhones->has($phone));

        return $phone;
    }

    private function demoEmail(string $name, int $number): string
    {
        return strtolower((string) preg_replace('/[^a-z]+/i', '.', $name)).$number.'@example.test';
    }

    /**
     * @param  list<array{key: string, name: string, email: string, role: string, skills: list<string>, apprentice?: bool}>  $staff
     */
    private function seedStaff(Business $business, array $staff, string $password): void
    {
        $roles = ($this->provisionRoles)($business->id);
        $phones = new NigerianPhoneNumbers(new Randomizer(new Mt19937(self::PHONE_SEED)));
        $hash = Hash::make($password);

        foreach ($staff as $member) {
            $phone = $phones->next();

            $user = User::query()->firstOrNew(['email' => $member['email']]);
            $user->forceFill([
                'name' => $member['name'],
                'phone' => $user->phone ?? $phone,
                'password' => $hash,
                'is_active' => true,
                'must_change_password' => false,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            $user->businesses()->syncWithoutDetaching([$business->id => ['is_active' => true]]);
            $user->syncRoles([$roles[$member['role']]]);

            if ($member['role'] !== 'cashier') {
                EmployeeProfile::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'display_name' => strtok($member['name'], ' ') ?: $member['name'],
                        'job_title' => $this->jobTitle($member),
                        'is_active' => true,
                    ],
                );
            }
        }
    }

    /**
     * @param  array{role: string, skills: list<string>, apprentice?: bool}  $member
     */
    private function jobTitle(array $member): string
    {
        if ($member['apprentice'] ?? false) {
            return 'Apprentice';
        }

        return match ($member['role']) {
            'owner' => 'Owner and stylist',
            'supervisor' => 'Supervisor and stylist',
            default => match ($member['skills'][0] ?? null) {
                'braids' => 'Braider',
                'nails' => 'Nail technician',
                'lashes' => 'Lash and makeup artist',
                default => 'Stylist',
            },
        };
    }
}
