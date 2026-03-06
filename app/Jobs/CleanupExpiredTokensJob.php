<?php

namespace App\Jobs;

use App\Models\VerificationToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanupExpiredTokensJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     * Deletes expired verification tokens (older than 24 hours).
     */
    public function handle(): void
    {
        $cutoffTime = now()->subHours(24);

        $deletedCount = VerificationToken::where('created_at', '<', $cutoffTime)->delete();

        Log::info("Cleaned up {$deletedCount} expired verification tokens", [
            'cutoff_time' => $cutoffTime->toDateTimeString(),
            'deleted_count' => $deletedCount,
        ]);
    }
}
