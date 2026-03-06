<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'password',
        'slack_id',
        'hired_date',
        'status',
        'verified_at',
        'default_approvers',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verified_at' => 'datetime',
            'hired_date' => 'date',
            'password' => 'hashed',
            'default_approvers' => 'array',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get the verification tokens for the user.
     */
    public function verificationTokens(): HasMany
    {
        return $this->hasMany(VerificationToken::class);
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->status === 1;
    }

    /**
     * Check if user is verified.
     */
    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * Check if user can login (active AND verified).
     */
    public function canLogin(): bool
    {
        return $this->isActive() && $this->isVerified();
    }

    /**
     * Check if user is an approver (has hr, team-lead, or project-manager role).
     */
    public function isApprover(): bool
    {
        return $this->hasAnyRole(['hr', 'team-lead', 'project-manager']);
    }
}
