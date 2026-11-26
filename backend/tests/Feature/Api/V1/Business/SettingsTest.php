<?php

namespace Tests\Feature\Api\V1\Business;

use App\Domain\Audit\AuditEvent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Api\V1\ApiTestCase;

class SettingsTest extends ApiTestCase
{
    public function test_a_new_salon_starts_with_the_approved_defaults(): void
    {
        $this->travelTo('2026-11-25 10:00:00');
        $salon = $this->installSalon('Adunni Hair & Beauty Studio');

        $this->signIn($salon['owner'])->getJson('api/v1/settings')
            ->assertOk()
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.profile.name', 'Adunni Hair & Beauty Studio')
            ->assertJsonPath('data.profile.timezone', 'Africa/Lagos')
            ->assertJsonPath('data.profile.currency', 'NGN')
            ->assertJsonPath('data.numbering.order.example', 'V-261125-001')
            ->assertJsonPath('data.numbering.invoice.example', 'INV-2026-00001')
            ->assertJsonPath('data.numbering.receipt.example', 'RCT-2026-00001')
            ->assertJsonPath('data.windows.late_submission_hours.value', 48)
            ->assertJsonPath('data.windows.transfer_alert_days.value', 2)
            ->assertJsonPath('data.windows.share_link_days.value', 7)
            ->assertJsonPath('data.sessions.idle_minutes_privileged.value', 30)
            ->assertJsonPath('data.sessions.idle_minutes_privileged.range', ['min' => 5, 'max' => 120])
            ->assertJsonPath('data.sessions.idle_days_low_risk.value', 7);
    }

    public function test_saving_changes_only_what_was_sent_and_audits_before_and_after(): void
    {
        $this->travelTo('2026-11-25 10:00:00');
        $salon = $this->installSalon();

        $this->signIn($salon['owner'])->patchJson('api/v1/settings', [
            'version' => 1,
            'profile' => ['phone' => '0803 000 0100', 'receipt_footer' => 'Thank you for choosing us.'],
            'numbering' => ['invoice' => 'ADU/{YY}/{SEQ:4}'],
            'windows' => ['late_submission_hours' => 24],
        ])->assertOk()
            ->assertJsonPath('data.version', 2)
            ->assertJsonPath('data.profile.phone', '+2348030000100')
            ->assertJsonPath('data.numbering.invoice.example', 'ADU/26/0001')
            ->assertJsonPath('data.windows.late_submission_hours.value', 24)
            ->assertJsonPath('data.windows.share_link_days.value', 7);

        $event = AuditEvent::query()->where('action', 'settings.updated')->sole();
        $this->assertEquals(['phone' => null, 'receipt_footer' => null, 'numbering.invoice' => 'INV-{YYYY}-{SEQ:5}', 'windows.late_submission_hours' => 48], $event->before);
        $this->assertSame('ADU/{YY}/{SEQ:4}', $event->after['numbering.invoice']);
        $this->assertSame($salon['owner']->id, $event->actor_id);
    }

    public function test_a_save_based_on_an_old_version_is_refused(): void
    {
        $salon = $this->installSalon();
        $this->signIn($salon['owner'])->patchJson('api/v1/settings', ['version' => 1, 'windows' => ['transfer_alert_days' => 3]])->assertOk();

        $this->patchJson('api/v1/settings', ['version' => 1, 'windows' => ['transfer_alert_days' => 5]])
            ->assertStatus(409)
            ->assertJsonPath('code', 'STALE')
            ->assertJsonPath('data.current_version', 2);

        $this->getJson('api/v1/settings')->assertJsonPath('data.windows.transfer_alert_days.value', 3);
    }

    public function test_saving_nothing_new_does_not_change_the_version(): void
    {
        $salon = $this->installSalon();

        $this->signIn($salon['owner'])->patchJson('api/v1/settings', ['version' => 1, 'windows' => ['late_submission_hours' => 48]])
            ->assertOk()
            ->assertJsonPath('data.version', 1);
        $this->assertDatabaseMissing('audit_events', ['action' => 'settings.updated']);
    }

    public function test_number_formats_are_checked_with_plain_messages(): void
    {
        $salon = $this->installSalon();

        $this->signIn($salon['owner'])->patchJson('api/v1/settings', [
            'version' => 1,
            'numbering' => ['invoice' => 'INV-{YYYY}', 'receipt' => 'R {SEQ:4}', 'order' => 'V-{WEEK}-{SEQ:3}'],
        ])->assertStatus(422)
            ->assertJsonPath('errors', [
                'numbering.order' => ['Use only these codes: {YYYY}, {YY}, {MM}, {DD}, {YYMMDD} and {SEQ:n}.'],
                'numbering.invoice' => ['Include the running number exactly once, for example {SEQ:5}.'],
                'numbering.receipt' => ['Use only letters, numbers and - / _ . between the codes.'],
            ]);
    }

    public function test_windows_and_idle_limits_must_stay_within_their_ranges(): void
    {
        $salon = $this->installSalon();

        $this->signIn($salon['owner'])->patchJson('api/v1/settings', [
            'version' => 1,
            'sessions' => ['idle_minutes_privileged' => 2, 'idle_days_low_risk' => 31],
            'windows' => ['share_link_days' => 0],
        ])->assertStatus(422)->assertJsonValidationErrors(['sessions.idle_minutes_privileged', 'sessions.idle_days_low_risk', 'windows.share_link_days'], 'errors');
    }

    public function test_the_logo_is_uploaded_and_served_publicly(): void
    {
        Storage::fake('local');
        $salon = $this->installSalon();

        $logoUrl = $this->signIn($salon['owner'])
            ->post('api/v1/settings/logo', ['logo' => UploadedFile::fake()->image('logo.png', 400, 400)], ['Accept' => 'application/json'])
            ->assertOk()
            ->json('data.profile.logo_url');

        $this->assertNotNull($logoUrl);
        $this->postJson('api/v1/auth/logout');
        $this->get($logoUrl)->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertDatabaseHas('audit_events', ['action' => 'settings.logo_updated']);
    }

    public function test_a_logo_that_is_not_an_image_is_refused(): void
    {
        Storage::fake('local');
        $salon = $this->installSalon();

        $this->signIn($salon['owner'])
            ->post('api/v1/settings/logo', ['logo' => UploadedFile::fake()->create('logo.png', 10, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('errors.logo.0', 'Upload a PNG or JPEG image.');
    }

    public function test_a_missing_logo_is_not_found(): void
    {
        $salon = $this->installSalon();

        $this->getJson("api/v1/businesses/{$salon['business']->id}/logo")->assertNotFound();
    }

    public function test_the_change_history_lists_settings_and_tax_changes_newest_first(): void
    {
        $salon = $this->installSalon();
        $this->signIn($salon['owner'])->patchJson('api/v1/settings', ['version' => 1, 'windows' => ['transfer_alert_days' => 3]])->assertOk();
        $this->patchJson('api/v1/settings/tax', ['version' => 1, 'enabled' => true, 'name' => 'VAT', 'rate_bp' => 750, 'mode' => 'exclusive'])->assertOk();

        $this->getJson('api/v1/settings/history')
            ->assertOk()
            ->assertJsonPath('data.0.action', 'tax.updated')
            ->assertJsonPath('data.0.actor.name', 'Salon Owner')
            ->assertJsonPath('data.1.action', 'settings.updated')
            ->assertJsonPath('data.2.action', 'business.installed');

        $this->getJson('api/v1/settings/history?filter[area]=tax')->assertJsonCount(1, 'data');
    }
}
