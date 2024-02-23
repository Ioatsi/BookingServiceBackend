<?php

namespace Database\Factories;
use App\Models\Building;
use Illuminate\Database\Eloquent\Factories\Factory;

use Faker\Generator as Faker;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\room>
 */
class RoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $builingIds = Building::pluck('id')->toArray();
        return [
            //
            'name' => $this->faker->name().' room',
            'capacity' => $this->faker->numberBetween(30, 150),
            'building_id' => $this->faker->randomElement($builingIds),
            'department_id' => 1,
            'number' => $this->faker->numberBetween(100, 300),
            'type' => 'normal',
        ];
    }
}
