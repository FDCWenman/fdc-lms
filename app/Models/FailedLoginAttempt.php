<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FailedLoginAttempt extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'ip_address',
        'attempted_at',
        'locked_until',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'attempted_at' => 'datetime',
        'locked_until' => 'datetime',
    ];

    /**
     * Check if the account is currently locked.
     */
    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    /**
     * Get the number of failed attempts within the specified time window.
     */
    public static function countRecentAttempts(string $email, int $minutes = 15): int
    {
        return static::where('email', $email)
            ->where('attempted_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    /**
     * Check if an email is currently locked out.
     */
    public static function isEmailLocked(string $email): bool
    {
        return static::where('email', $email)
            ->where('locked_until', '>', now())
            ->exists();
    }

    /**
     * Lock an account for the specified duration.
     */
    public static function lockAccount(string $email, string $ipAddress, int $minutes = 30): void
    {
        static::create([
            'email' => $email,
            'ip_address' => $ipAddress,
            'attempted_at' => now(),
            'locked_until' => now()->addMinutes($minutes),
        ]);
    }

    /**
     * Clear failed attempts for an email after successful login.
     */
    public static function clearAttempts(string $email): void
    {
        static::where('email', $email)
            ->where('locked_until', null)
            ->delete();
    }
}
