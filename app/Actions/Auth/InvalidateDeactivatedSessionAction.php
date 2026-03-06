<?php

namespace App\Actions\Auth;

use Illuminate\Support\Facades\DB;

/**
 * Invalidate sessions for deactivated users
 *
 * Cleans up sessions for users who have been deactivated,
 * ensuring they are logged out at their next request.
 */
class InvalidateDeactivatedSessionAction
{
    /**
     * Execute the session invalidation.
     *
     * @param  int  $userId
     * @return bool
     */
    public function execute(int $userId): bool
    {
        try {
            // Delete all sessions for the user
            DB::table('sessions')
                ->where('user_id', $userId)
                ->delete();

            \Log::info('Sessions invalidated for deactivated user', ['user_id' => $userId]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to invalidate sessions', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Invalidate all sessions for deactivated users.
     *
     * @return int Number of users whose sessions were invalidated
     */
    public function executeForAllDeactivated(): int
    {
        try {
            $deactivatedUserIds = DB::table('users_fdc_leaves')
                ->where('status', 0)
                ->pluck('id');

            if ($deactivatedUserIds->isEmpty()) {
                return 0;
            }

            DB::table('sessions')
                ->whereIn('user_id', $deactivatedUserIds)
                ->delete();

            \Log::info('Bulk session invalidation completed', [
                'count' => $deactivatedUserIds->count(),
            ]);

            return $deactivatedUserIds->count();
        } catch (\Exception $e) {
            \Log::error('Bulk session invalidation failed', [
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }
}
