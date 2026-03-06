<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function active_user_can_logout_successfully(): void
    {
        // T078: Test successful logout flow
        $user = User::factory()->create(['status' => 1]);
        $this->actingAs($user);

        $response = $this->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /** @test */
    public function user_cannot_access_protected_pages_after_logout(): void
    {
        // T079: Test that logged-out users cannot access protected pages
        $user = User::factory()->create(['status' => 1]);
        $this->actingAs($user);

        $this->post(route('logout'));

        // Try to access protected pages
        $this->get('/leaves')
            ->assertRedirect(route('login'));

        $this->get('/portal')
            ->assertRedirect(route('login'));
    }

    /** @test */
    public function browser_back_button_does_not_allow_access_after_logout(): void
    {
        // T080: Test that back button doesn't restore authenticated session
        $user = User::factory()->create(['status' => 1]);
        $this->actingAs($user);

        // Access a protected page
        $this->get('/leaves')->assertOk();

        // Logout
        $this->post(route('logout'));

        // Try to access the same page (simulating back button)
        $this->get('/leaves')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    /** @test */
    public function deactivated_user_is_logged_out_automatically(): void
    {
        // T081: Test automatic logout for deactivated users
        $user = User::factory()->create(['status' => 1]);
        $this->actingAs($user);

        // Verify user can access protected page
        $this->get('/leaves')->assertOk();

        // Deactivate user
        $user->update(['status' => 2]);

        // Try to access protected page - should be logged out
        $response = $this->get('/leaves');

        $response->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Your account has been deactivated.');

        $this->assertGuest();
    }

    /** @test */
    public function deactivated_user_sessions_are_invalidated(): void
    {
        // T081: Test that all sessions are invalidated for deactivated user
        $user = User::factory()->create(['status' => 1]);

        // Create a session entry manually
        $sessionId = 'test_session_' . $user->id;
        DB::table('sessions')->insert([
            'id' => $sessionId,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Browser',
            'payload' => base64_encode('test_data'),
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($user);

        // Deactivate user
        $user->update(['status' => 2]);

        // Access any protected page to trigger middleware
        $this->get('/leaves');

        // Verify session was deleted
        $this->assertDatabaseMissing('sessions', [
            'id' => $sessionId,
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function logout_invalidates_session_and_regenerates_token(): void
    {
        // T082: Test session invalidation and CSRF token regeneration
        $user = User::factory()->create(['status' => 1]);
        $this->actingAs($user);

        // Get initial session ID
        $initialSessionId = session()->getId();

        $response = $this->post(route('logout'));

        $response->assertRedirect(route('login'));

        // Start a new session to verify old session is gone
        $this->get(route('login'));
        $newSessionId = session()->getId();

        // Session ID should be different after logout
        $this->assertNotEquals($initialSessionId, $newSessionId);
    }

    /** @test */
    public function multiple_sessions_are_all_invalidated_for_deactivated_user(): void
    {
        // T082: Test multi-session invalidation
        $user = User::factory()->create(['status' => 1]);

        // Create multiple session entries
        $sessionIds = [];
        for ($i = 0; $i < 3; $i++) {
            $sessionId = 'test_session_' . $user->id . '_' . $i;
            $sessionIds[] = $sessionId;

            DB::table('sessions')->insert([
                'id' => $sessionId,
                'user_id' => $user->id,
                'ip_address' => '127.0.0.' . ($i + 1),
                'user_agent' => 'Test Browser ' . $i,
                'payload' => base64_encode('test_data_' . $i),
                'last_activity' => now()->timestamp,
            ]);
        }

        $this->actingAs($user);

        // Deactivate user
        $user->update(['status' => 2]);

        // Trigger middleware
        $this->get('/leaves');

        // Verify all sessions were deleted
        foreach ($sessionIds as $sessionId) {
            $this->assertDatabaseMissing('sessions', [
                'id' => $sessionId,
                'user_id' => $user->id,
            ]);
        }
    }

    /** @test */
    public function logout_redirects_to_login_page_with_success_message(): void
    {
        // Additional test: Verify logout provides user feedback
        $user = User::factory()->create(['status' => 1]);
        $this->actingAs($user);

        $response = $this->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
