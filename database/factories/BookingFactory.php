<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\User;
use App\Models\Room;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\booking>
 */
class BookingFactory extends Factory
{

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $incrementingName = 1;
        $bookerIds = User::pluck('id')->toArray();
        $roomIds = Room::pluck('id')->toArray();
        $startDate = $this->faker->dateTimeBetween('-1 week', 'now');
        $endDate = $this->faker->dateTimeBetween($startDate, strtotime('+1 week'));
        return [
            'booker_id' => $this->faker->randomElement($bookerIds),
            'semester_id' => 1,
            'room_id' => $this->faker->randomElement($roomIds),
            'status' => $this->faker->randomElement([0,1]),
            'title' => 'booking' . $incrementingName++,
            'start' => $startDate,
            'end' => $endDate,
            'color' => $this->faker->randomElement(['red', 'green', 'blue', 'orange', 'purple', 'pink', 'yellow']),
            'info' => $this->faker->sentence(10),
            'participants' => "Participant1, Participant2, Participant3",     
            'group_id' => null,
            'recurring_id' => null,
            'type' => 'normal',
        ];
    }
}
