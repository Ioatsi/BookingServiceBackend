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
        // Generate a random start date within the last week
        $startDate = $this->faker->dateTimeBetween('-1 week', 'now');

        // Set the start time to 08:00 if it's before 08:00
        if ($startDate->format('H') < 8) {
            $startDate->setTime(8, 0, 0);
        }

        // Set the start time to 20:00 if it's after 20:00
        if ($startDate->format('H') >= 20) {
            $startDate->setTime(20, 0, 0);
        }

        // Round to the nearest hour
        $startDate->setTime($startDate->format('H'), 0, 0);

        // Add exactly 2 hours to the start date to get the end date
        $endDate = clone $startDate;
        $endDate->modify('+2 hours');

        // Make sure end date does not exceed 20:00
        if ($endDate->format('H') >= 20) {
            $endDate->setTime(20, 0, 0);
        }

        // Round to the nearest hour
        $endDate->setTime($endDate->format('H'), 0, 0);
        
        $roomId = Room::pluck('id')->toArray();
        return [
            'booker_id' => $this->faker->randomElement($bookerIds),
            'room_id' => $this->faker->randomElement($roomId),
            'status' => $this->faker->randomElement([0, 1]),
            'publicity' => $this->faker->randomElement([0, 1]),
            'title' => 'booking' . $incrementingName++,
            'start' => $startDate,
            'end' => $endDate,
            'info' => $this->faker->sentence(10),
            'participants' => "Participant1, Participant2, Participant3",
            'group_id' => null,
            'recurring_id' => null,
            'type' => 'normal',
        ];
    }
}
