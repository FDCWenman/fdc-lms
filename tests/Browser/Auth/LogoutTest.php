<?php

namespace Tests\Browser\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LogoutTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $user->assignRole('employee');

        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::where('email', 'test@example.com')->first())
                    ->visit('/portal')
                    ->assertAuthenticated()
                    ->click('button[aria-label="User menu"]')
                    ->waitFor('a[href="/logout"]')
                    ->click('a[href="/logout"]')
                    ->waitForLocation('/')
                    ->assertPathIs('/')
                    ->assertGuest()
                    ->assertSee('Sign in to your account');
        });
    }

    public function test_logout_clears_session(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $user->assignRole('employee');

        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::where('email', 'test@example.com')->first())
                    ->visit('/portal')
                    ->click('button[aria-label="User menu"]')
                    ->waitFor('a[href="/logout"]')
                    ->click('a[href="/logout"]')
                    ->waitForLocation('/')
                    ->visit('/portal')
                    ->assertPathIs('/')
                    ->assertSee('Sign in to your account');
        });
    }
}
