<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Booking;
use App\Models\Group;
use App\Models\Recurring;
use App\Models\User;
use App\Models\Semester;
use App\Models\Room;

use Faker\Factory as Faker;

class BookingSeeder extends Seeder
{   
    protected $faker;

    public function __construct()
    {
        $this->faker = Faker::create();
    }
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Booking::factory(10)->create();
        
        $bookingGroupIds = Group::pluck('id')->toArray();
        $recurringGroupIds = Recurring::pluck('id')->toArray();
        $userIds = User::pluck('id')->toArray();
        $semesterIds = Semester::pluck('id')->toArray();
        $roomIds = Room::pluck('id')->toArray();
        //
        
        for ($i = 0; $i < 3; $i++) {
            Booking::create([
                'group_id' => null,
                'recurring_id' => $this->faker->randomElement($recurringGroupIds),
                'booker_id' => $this->faker->randomElement($userIds),
                'semester_id' => $this->faker->randomElement($semesterIds),
                'room_id' => $this->faker->randomElement($roomIds),
                'status' => $this->faker->numberBetween(0, 1), // Assuming status is either 0 or 1
                'title' => $this->faker->word(),
                'start' => $this->faker->dateTimeBetween('+1 week', '+1 month'),
                'end' => $this->faker->dateTimeBetween('+2 weeks', '+2 months'),
                'color' => 'red',
                'info' => $this->faker->text,
                'participants' => $this->faker->sentence,
                'type' => $this->faker->randomElement(['Group', 'Recurring']),
            ]);
        }
        for ($i = 0; $i < 2; $i++) {
            Booking::create([
                'group_id' =>  $this->faker->randomElement($bookingGroupIds),
                'recurring_id' => null,
                'booker_id' => $this->faker->randomElement($userIds),
                'semester_id' => $this->faker->randomElement($semesterIds),
                'room_id' => $this->faker->randomElement($roomIds),
                'status' => $this->faker->numberBetween(0, 1), // Assuming status is either 0 or 1
                'title' => $this->faker->word(),
                'start' => $this->faker->dateTimeBetween('+1 week', '+1 month'),
                'end' => $this->faker->dateTimeBetween('+2 weeks', '+2 months'),
                'color' => 'blue',
                'info' => $this->faker->text,
                'participants' => $this->faker->sentence,
                'type' => $this->faker->randomElement(['Group', 'Recurring']),
            ]);
        }

    }
}
