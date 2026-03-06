<?php

namespace Tests\Unit\Jobs;

use App\Jobs\CleanupExpiredTokensJob;
use App\Models\User;
use App\Models\VerificationToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CleanupExpiredTokensJobTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_deletes_expired_tokens_older_than_24_hours(): void
    {
        // T085: Test expired token deletion
        $user = User::factory()->create(['status' => 2]);

        // Create expired token (25 hours old)
        $expiredToken = VerificationToken::create([
            'user_id' => $user->id,
            'token' => 'expired_token_123',
            'created_at' => now()->subHours(25),
        ]);

        // Create recent token (23 hours old)
        $recentToken = VerificationToken::create([
            'user_id' => $user->id,
            'token' => 'recent_token_456',
            'created_at' => now()->subHours(23),
        ]);

        $job = new CleanupExpiredTokensJob();
        $job->handle();

        // Expired token should be deleted
        $this->assertDatabaseMissing('verification_tokens', [
            'id' => $expiredToken->id,
        ]);

        // Recent token should remain
        $this->assertDatabaseHas('verification_tokens', [
            'id' => $recentToken->id,
        ]);
    }

    /** @test */
    public function it_deletes_multiple_expired_tokens_at_once(): void
    {
        // T085: Test bulk deletion
        $user1 = User::factory()->create(['status' => 2]);
        $user2 = User::factory()->create(['status' => 2]);

        // Create 3 expired tokens
        for ($i = 0; $i < 3; $i++) {
            VerificationToken::create([
                'user_id' => $user1->id,
                'token' => 'expired_token_' . $i,
                'created_at' => now()->subHours(25 + $i),
            ]);
        }

        // Create 2 recent tokens
        for ($i = 0; $i < 2; $i++) {
            VerificationToken::create([
                'user_id' => $user2->id,
                'token' => 'recent_token_' . $i,
                'created_at' => now()->subHours(20 + $i),
            ]);
        }

        $job = new CleanupExpiredTokensJob();
        $job->handle();

        // Should have deleted 3 expired, kept 2 recent
        $this->assertEquals(2, VerificationToken::count());
    }

    /** @test */
    public function it_handles_empty_token_table_gracefully(): void
    {
        // T085: Test job runs without errors when no tokens exist
        $job = new CleanupExpiredTokensJob();

        try {
            $job->handle();
            $this->assertTrue(true); // Job completed without exception
        } catch (\Exception $e) {
            $this->fail('Job should handle empty table without errors');
        }

        $this->assertEquals(0, VerificationToken::count());
    }

    /** @test */
    public function it_logs_cleanup_activity(): void
    {
        // T085: Test logging of cleanup operations
        Log::shouldReceive('info')
            ->once()
            ->with(
                \Mockery::on(function ($message) {
                    return str_contains($message, 'Cleaned up') && str_contains($message, 'expired verification tokens');
                }),
                \Mockery::on(function ($context) {
                    return isset($context['cutoff_time']) && isset($context['deleted_count']);
                })
            );

        $user = User::factory()->create(['status' => 2]);
        VerificationToken::create([
            'user_id' => $user->id,
            'token' => 'expired_token',
            'created_at' => now()->subHours(30),
        ]);

        $job = new CleanupExpiredTokensJob();
        $job->handle();
    }

    /** @test */
    public function it_only_deletes_tokens_created_before_cutoff_time(): void
    {
        // T085: Test precise cutoff time boundary
        $user = User::factory()->create(['status' => 2]);

        // Token exactly at 24 hours (should NOT be deleted)
        $boundaryToken = VerificationToken::create([
            'user_id' => $user->id,
            'token' => 'boundary_token',
            'created_at' => now()->subHours(24),
        ]);

        // Token at 24 hours + 1 minute (should be deleted)
        $expiredToken = VerificationToken::create([
            'user_id' => $user->id,
            'token' => 'expired_token',
            'created_at' => now()->subHours(24)->subMinute(),
        ]);

        $job = new CleanupExpiredTokensJob();
        $job->handle();

        // Boundary token should remain (exactly 24 hours is not "older than")
        $this->assertDatabaseHas('verification_tokens', [
            'id' => $boundaryToken->id,
        ]);

        // Expired token should be deleted
        $this->assertDatabaseMissing('verification_tokens', [
            'id' => $expiredToken->id,
        ]);
    }

    /** @test */
    public function it_preserves_tokens_for_different_users(): void
    {
        // T085: Test that only expired tokens are deleted regardless of user
        $user1 = User::factory()->create(['status' => 2]);
        $user2 = User::factory()->create(['status' => 2]);
        $user3 = User::factory()->create(['status' => 2]);

        // User 1: expired token
        VerificationToken::create([
            'user_id' => $user1->id,
            'token' => 'user1_expired',
            'created_at' => now()->subHours(30),
        ]);

        // User 2: recent token
        $user2Token = VerificationToken::create([
            'user_id' => $user2->id,
            'token' => 'user2_recent',
            'created_at' => now()->subHours(12),
        ]);

        // User 3: recent token
        $user3Token = VerificationToken::create([
            'user_id' => $user3->id,
            'token' => 'user3_recent',
            'created_at' => now()->subHours(18),
        ]);

        $job = new CleanupExpiredTokensJob();
        $job->handle();

        // Only user2 and user3 tokens should remain
        $this->assertEquals(2, VerificationToken::count());
        $this->assertDatabaseHas('verification_tokens', ['id' => $user2Token->id]);
        $this->assertDatabaseHas('verification_tokens', ['id' => $user3Token->id]);
    }
}
