<?php

namespace App\Actions\Auth;

use App\Models\User;

class LogoutUser
{
    /**
     * Logout the user by deleting their current access token.
     */
    public function execute(User $user): void
    {
        // Delete the current access token
        $user->currentAccessToken()?->delete();
        
        // Or if using session-based auth, you might want to invalidate the session
        // This depends on your authentication strategy
    }
}
