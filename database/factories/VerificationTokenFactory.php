<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VerificationToken>
 */
class VerificationTokenFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token' => bin2hex(random_bytes(32)),
            'created_at' => now(),
        ];
    }

    /**
     * Indicate that the token is expired (older than 24 hours).
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => now()->subHours(25),
        ]);
    }

    /**
     * Indicate that the token was created recently.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => now()->subHours(12),
        ]);
    }

    /**
     * Indicate that the token is about to expire (23 hours old).
     */
    public function aboutToExpire(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => now()->subHours(23)->subMinutes(55),
        ]);
    }
}
