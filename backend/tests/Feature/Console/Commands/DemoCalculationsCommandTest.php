<?php

namespace Tests\Feature\Console\Commands;

use Tests\TestCase;

class DemoCalculationsCommandTest extends TestCase
{
    public function test_prints_reconciled_demo_invoices(): void
    {
        $this->artisan('app:demo:calc')
            ->expectsOutputToContain('Customer pays ₦45,472.50')
            ->expectsOutputToContain('Every invoice, discount share and staff credit reconciles to the kobo.')
            ->assertSuccessful();
    }
}
