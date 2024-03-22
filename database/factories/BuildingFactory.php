<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\building>
 */
class BuildingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        
        static $incrementingName = 1;
        $departemntId = Department::pluck('id')->toArray();
        return [
            //
            'department_id' => $this->faker->randomElement($departemntId),            
            'info' => $this->faker->text,
            'name' => 'building' . $incrementingName++,
        ];
    }
}
