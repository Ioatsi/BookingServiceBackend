<?php

namespace Database\Factories;

use Faker\Generator as Faker;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\moderator_room>
 */
class Moderator_roomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $modIds = User::pluck('id')->toArray();
        return [
            //
            'booker_id' => $this->faker->randomElement($modIds),
        ];
    }
}
