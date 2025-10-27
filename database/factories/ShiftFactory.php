<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class ShiftFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'netid' => fake()->userName() . Str::random(5),
            'proj_id' => 1,
            'date' => now()->subHours(rand(1, 10)),
            'duration' => rand(1, 8) * 60,
            'billed' => fake()->boolean(),
            'entered' => fake()->boolean(),
        ];
    }
}
