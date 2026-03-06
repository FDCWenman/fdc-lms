<?php

namespace App\Jobs;

use App\Models\VerificationToken;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Cleanup Expired Verification Tokens Job
 *
 * Deletes expired verification and password reset tokens from the database.
 * Scheduled to run daily at 2:00 AM.
 */
class CleanupExpiredTokens implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $deleted = VerificationToken::where('expires_at', '<', now())->delete();

        Log::info('Cleaned up expired verification tokens', [
            'deleted_count' => $deleted,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }
}
