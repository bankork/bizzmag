<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Domain\Identity\Permissions\SystemRole;
use App\Domain\Identity\Support\SessionTerminator;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\V1\ApiTestCase;

/**
 * §14.22 (Phase 1 part): a disabled user cannot sign in or carry on, and
 * their record stays.
 */
class DisabledUserTest extends ApiTestCase
{
    public function test_a_user_disabled_while_signed_in_is_refused_on_the_next_request(): void
    {
        $salon = $this->installSalon();
        $employee = $this->staffMember($salon['business'], SystemRole::Employee);

        $this->signIn($employee);
        $employee->forceFill(['is_active' => false])->save();

        $this->getJson('api/v1/me')->assertStatus(403)->assertJsonPath('code', 'ACCOUNT_DISABLED');
        $this->assertGuest('web');
    }

    public function test_disabling_keeps_the_user_and_ends_their_sessions(): void
    {
        config(['session.driver' => 'database']);
        $salon = $this->installSalon();
        $employee = $this->staffMember($salon['business'], SystemRole::Employee);
        DB::table('sessions')->insert([
            ['id' => 'phone-session', 'user_id' => $employee->id, 'payload' => '', 'last_activity' => now()->getTimestamp()],
            ['id' => 'owner-session', 'user_id' => $salon['owner']->id, 'payload' => '', 'last_activity' => now()->getTimestamp()],
        ]);

        $this->signIn($salon['owner'])->postJson("api/v1/users/{$employee->id}/disable", ['reason' => 'Left the salon'])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseMissing('sessions', ['id' => 'phone-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'owner-session']);
        $this->assertNotNull(User::query()->find($employee->id));
        $this->assertDatabaseHas('employee_profiles', ['user_id' => $employee->id]);
        $this->assertDatabaseHas('audit_events', ['action' => 'user.disabled', 'subject_id' => $employee->id, 'reason' => 'Left the salon']);
    }

    public function test_the_session_terminator_can_keep_the_current_session(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create();
        DB::table('sessions')->insert([
            ['id' => 'this-browser', 'user_id' => $user->id, 'payload' => '', 'last_activity' => now()->getTimestamp()],
            ['id' => 'old-phone', 'user_id' => $user->id, 'payload' => '', 'last_activity' => now()->getTimestamp()],
        ]);

        $ended = app(SessionTerminator::class)->endAll($user->id, 'this-browser');

        $this->assertSame(1, $ended);
        $this->assertDatabaseHas('sessions', ['id' => 'this-browser']);
    }
}
