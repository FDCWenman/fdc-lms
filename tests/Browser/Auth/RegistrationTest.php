<?php

namespace Tests\Browser\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class RegistrationTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        // Create HR user for authentication
        $hrUser = User::factory()->create([
            'email' => 'hr@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $hrUser->assignRole('hr');
    }

    public function test_guest_cannot_access_registration_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/auth/register')
                ->assertPathIs('/')
                ->assertSee('Sign in to your account');
        });
    }

    public function test_non_hr_user_cannot_access_registration_page(): void
    {
        $employee = User::factory()->create([
            'email' => 'employee@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $employee->assignRole('employee');

        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::where('email', 'employee@example.com')->first())
                ->visit('/auth/register')
                ->assertPathIs('/portal')
                ->assertDontSee('Register User');
        });
    }

    public function test_hr_user_can_access_registration_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::where('email', 'hr@example.com')->first())
                ->visit('/auth/register')
                ->assertPathIs('/auth/register')
                ->assertSee('Register User')
                ->assertInputPresent('name')
                ->assertInputPresent('email')
                ->assertInputPresent('slack_user_id');
        });
    }

    public function test_hr_user_can_register_new_user(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::where('email', 'hr@example.com')->first())
                ->visit('/auth/register')
                ->type('name', 'John Doe')
                ->type('email', 'john@example.com')
                ->type('slack_user_id', 'U12345678')
                ->press('Register User')
                ->waitForText('User registered successfully')
                ->assertSee('User registered successfully');
        });

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'slack_user_id' => 'U12345678',
        ]);
    }

    public function test_registration_validates_email_format(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::where('email', 'hr@example.com')->first())
                ->visit('/auth/register')
                ->type('name', 'Jane Doe')
                ->type('email', 'invalid-email')
                ->type('slack_user_id', 'U87654321')
                ->press('Register User')
                ->waitForText('The email field must be a valid email address')
                ->assertSee('The email field must be a valid email address');
        });
    }

    public function test_registration_prevents_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::where('email', 'hr@example.com')->first())
                ->visit('/auth/register')
                ->type('name', 'Duplicate User')
                ->type('email', 'existing@example.com')
                ->type('slack_user_id', 'U11111111')
                ->press('Register User')
                ->waitForText('The email has already been taken')
                ->assertSee('The email has already been taken');
        });
    }
}
