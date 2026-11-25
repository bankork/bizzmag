<?php

namespace Tests\Unit\Database\Seeders\Demo\Support;

use App\Domain\Shared\Money\Money;
use Database\Seeders\Demo\Support\DemoData;
use PHPUnit\Framework\TestCase;

class DemoDataTest extends TestCase
{
    public function test_every_catalogue_price_is_a_positive_whole_naira_amount(): void
    {
        $catalogue = DemoData::catalogue();
        $prices = [];

        foreach ([...$catalogue['services'], ...$catalogue['products']] as $items) {
            foreach ($items as $item) {
                array_push($prices, ...(isset($item['variants']) ? array_values($item['variants']) : [$item['price']]));
            }
        }

        foreach ($prices as $price) {
            $kobo = Money::fromNaira($price)->kobo;
            $this->assertGreaterThan(0, $kobo, $price);
            $this->assertSame(0, $kobo % 100, "{$price} should be whole naira");
        }
    }

    public function test_every_staff_member_has_a_unique_test_domain_email(): void
    {
        $emails = array_column(DemoData::staff(), 'email');

        $this->assertCount(count($emails), array_unique($emails));

        foreach ($emails as $email) {
            $this->assertStringEndsWith('@adunni.test', $email);
        }
    }

    public function test_staff_cover_every_default_role(): void
    {
        $roles = array_values(array_unique(array_column(DemoData::staff(), 'role')));
        sort($roles);

        $this->assertSame(['cashier', 'employee', 'owner', 'supervisor'], $roles);
    }

    public function test_looks_up_service_and_variant_prices_by_name(): void
    {
        $this->assertSame('40,000', DemoData::price('Knotless braids', 'Medium'));
        $this->assertSame('3,500', DemoData::price('X-pression braiding hair (pack)'));
    }

    public function test_demo_text_contains_no_em_dashes(): void
    {
        $files = glob(dirname(__DIR__, 6).'/database/seeders/Demo/Data/*.php') ?: [];

        $this->assertNotEmpty($files, 'The demo data files were not found.');

        foreach ($files as $file) {
            $this->assertStringNotContainsString("\u{2014}", (string) file_get_contents($file), basename($file));
        }
    }
}
