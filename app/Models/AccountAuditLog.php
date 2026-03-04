<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountAuditLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'performed_by',
        'action',
        'reason',
        'metadata',
        'ip_address',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the user that this audit log is for.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user who performed the action.
     */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Audit log action constants.
     */
    public const ACTION_ACCOUNT_CREATED = 'account_created';
    public const ACTION_ACCOUNT_ACTIVATED = 'account_activated';
    public const ACTION_ACCOUNT_DEACTIVATED = 'account_deactivated';
    public const ACTION_EMAIL_VERIFIED = 'email_verified';
    public const ACTION_PASSWORD_RESET = 'password_reset';
    public const ACTION_PASSWORD_CHANGED = 'password_changed';
    public const ACTION_ROLE_CHANGED = 'role_changed';
    public const ACTION_APPROVERS_UPDATED = 'approvers_updated';
    public const ACTION_LOGIN_SUCCESS = 'login_success';
    public const ACTION_LOGIN_FAILED = 'login_failed';
    public const ACTION_ACCOUNT_LOCKED = 'account_locked';
}
