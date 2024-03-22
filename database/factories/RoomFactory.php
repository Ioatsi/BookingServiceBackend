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
        $building = Building::inRandomOrder()->first(); // Fetch a random building
        $departmentId = $building->department_id;
        return [
            //
            'name' => $this->faker->firstName() . ' room',
            'capacity' => $this->faker->numberBetween(30, 150),
            'building_id' => $building->id,
            'department_id' => $departmentId,
            'info' => $this->faker->text,
            'number' => $this->faker->numberBetween(100, 300),
            'type' => 'normal',
            'color' => $this->faker->hexColor(),
            'status' => 1
        ];
    }
}
