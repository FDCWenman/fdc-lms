<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->configureAuthentication();
    }

    /**
     * Configure authentication logic.
     */
    private function configureAuthentication(): void
    {
        // Customize authentication logic to check status and verification
        Fortify::authenticateUsing(function (Request $request) {
            $user = \App\Models\User::where('email', $request->email)->first();

            if ($user && 
                \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
                
                // Check if user is active (status = 1) AND verified (verified_at not null)
                if ($user->status !== 1) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'email' => ['Your account has been deactivated.'],
                    ]);
                }

                if (! $user->verified_at) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'email' => ['Your account has not been verified yet. Please check your Slack for the verification link.'],
                    ]);
                }

                return $user;
            }

            return null;
        });

        // Configure redirect after authentication based on role
        Fortify::redirects('login', function () {
            $user = auth()->user();

            // Redirect based on role
            if ($user->hasRole('employee')) {
                return '/leaves';
            }

            if ($user->isApprover()) {
                return '/portal';
            }

            return '/dashboard';
        });
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn () => view('pages::auth.login'));
        Fortify::verifyEmailView(fn () => view('pages::auth.verify-email'));
        Fortify::twoFactorChallengeView(fn () => view('pages::auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('pages::auth.confirm-password'));
        Fortify::registerView(fn () => view('pages::auth.register'));
        Fortify::resetPasswordView(fn () => view('pages::auth.reset-password'));
        Fortify::requestPasswordResetLinkView(fn () => view('pages::auth.forgot-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
