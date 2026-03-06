<?php

namespace Tests\Browser\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_user_can_view_login_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->assertSee('Sign in to your account')
                    ->assertInputPresent('email')
                    ->assertInputPresent('password')
                    ->assertSee('Sign in');
        });
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $user->assignRole('employee');

        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->type('email', 'test@example.com')
                    ->type('password', 'password')
                    ->press('Sign in')
                    ->waitForLocation('/portal')
                    ->assertPathIs('/portal')
                    ->assertAuthenticated();
        });
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->type('email', 'wrong@example.com')
                    ->type('password', 'wrongpassword')
                    ->press('Sign in')
                    ->waitForText('These credentials do not match our records')
                    ->assertSee('These credentials do not match our records')
                    ->assertGuest();
        });
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => bcrypt('password'),
            'is_active' => false,
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->type('email', 'inactive@example.com')
                    ->type('password', 'password')
                    ->press('Sign in')
                    ->waitForText('Your account has been deactivated')
                    ->assertSee('Your account has been deactivated')
                    ->assertGuest();
        });
    }

    public function test_unverified_user_is_redirected_to_verification(): void
    {
        $user = User::factory()->create([
            'email' => 'unverified@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
            'email_verified_at' => null,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->type('email', 'unverified@example.com')
                    ->type('password', 'password')
                    ->press('Sign in')
                    ->waitForLocation('/auth/verify')
                    ->assertPathIs('/auth/verify')
                    ->assertSee('Verify your email address');
        });
    }

    public function test_hr_user_is_redirected_to_portal_after_login(): void
    {
        $user = User::factory()->create([
            'email' => 'hr@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $user->assignRole('hr');

        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->type('email', 'hr@example.com')
                    ->type('password', 'password')
                    ->press('Sign in')
                    ->waitForLocation('/portal')
                    ->assertPathIs('/portal');
        });
    }
}
