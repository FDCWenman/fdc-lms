<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * Account status constants.
     */
    public const STATUS_DEACTIVATED = 0;
    public const STATUS_ACTIVE = 1;
    public const STATUS_FOR_VERIFICATION = 2;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'slack_id',
        'password',
        'status',
        'primary_role_id',
        'secondary_role_id',
        'default_approvers',
        'hired_date',
        'verified_at',
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
            'two_factor_confirmed_at' => 'datetime',
            'default_approvers' => 'array',
            'status' => 'integer',
        ];
    }

    /**
     * Get the primary role relationship.
     */
    public function primaryRole(): BelongsTo
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class, 'primary_role_id');
    }

    /**
     * Get the secondary role relationship.
     */
    public function secondaryRole(): BelongsTo
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class, 'secondary_role_id');
    }

    /**
     * Get all audit logs for this user.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AccountAuditLog::class);
    }

    /**
     * Get audit logs where this user performed actions.
     */
    public function performedAudits(): HasMany
    {
        return $this->hasMany(AccountAuditLog::class, 'performed_by');
    }

    /**
     * Get password reset tokens for this user.
     */
    public function passwordResetTokens(): HasMany
    {
        return $this->hasMany(PasswordResetToken::class);
    }

    /**
     * Get email verification tokens for this user.
     */
    public function emailVerificationTokens(): HasMany
    {
        return $this->hasMany(EmailVerificationToken::class);
    }

    /**
     * Check if user account is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if user account is deactivated.
     */
    public function isDeactivated(): bool
    {
        return $this->status === self::STATUS_DEACTIVATED;
    }

    /**
     * Check if user account is pending verification.
     */
    public function isPendingVerification(): bool
    {
        return $this->status === self::STATUS_FOR_VERIFICATION;
    }

    /**
     * Check if user email is verified.
     */
    public function isVerified(): bool
    {
        return !is_null($this->verified_at);
    }

    /**
     * Check if user has any role (primary or secondary).
     */
    public function hasAnyRole($roles): bool
    {
        // Check both primary and secondary roles
        $userRoles = collect([]);
        
        if ($this->primaryRole) {
            $userRoles->push($this->primaryRole->name);
        }
        
        if ($this->secondaryRole) {
            $userRoles->push($this->secondaryRole->name);
        }
        
        $rolesToCheck = is_array($roles) ? $roles : [$roles];
        
        return $userRoles->intersect($rolesToCheck)->isNotEmpty();
    }

    /**
     * Get combined permissions from both roles.
     */
    public function getAllPermissions()
    {
        $permissions = collect([]);
        
        if ($this->primaryRole) {
            $permissions = $permissions->merge($this->primaryRole->permissions);
        }
        
        if ($this->secondaryRole) {
            $permissions = $permissions->merge($this->secondaryRole->permissions);
        }
        
        return $permissions->unique('id');
    }

    /**
     * Check if user can perform action (checks both roles).
     */
    public function can($ability, $arguments = [])
    {
        // First check native Laravel authorization
        if (parent::can($ability, $arguments)) {
            return true;
        }
        
        // Check permissions from both roles
        return $this->getAllPermissions()
            ->pluck('name')
            ->contains($ability);
    }
}
