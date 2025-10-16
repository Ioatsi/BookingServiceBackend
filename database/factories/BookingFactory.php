<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\User;
use App\Models\Room;
use App\Models\Semester;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        static $incrementingName = 1;

        $bookerIds =  User::pluck('id')->toArray();
        $roomIds = Room::pluck('id')->values()->toArray();

        $bookerId = Arr::random($bookerIds);

        // Generate a random start date within the last year
        $startDate = $this->faker->dateTimeBetween('-1 year', 'now');

        // Force business hours (08:00–20:00)
        if ($startDate->format('H') < 8) {
            $startDate->setTime(8, 0, 0);
        }
        if ($startDate->format('H') >= 20) {
            $startDate->setTime(20, 0, 0);
        }
        $startDate->setTime($startDate->format('H'), 0, 0);

        $semesterId = Semester::where('start', '<=', $startDate)
            ->where('end', '>=', $startDate)
            ->value('id');

        

        // Create random end time (1–6 hours later)
        $hoursToAdd = rand(1, 6);
        $minutesToAdd = rand(0, 59);
        $endDate = clone $startDate;
        $endDate->modify("+$hoursToAdd hours +$minutesToAdd minutes");

        // Clamp to 20:00 max
        if ($endDate->format('H') >= 20) {
            $endDate->setTime(20, 0, 0);
        }
        $endDate->setTime($endDate->format('H'), 0, 0);

        return [
            'booker_id'           => $bookerId,
            'room_id'             => $this->faker->randomElement($roomIds),
            'status'              => $this->faker->randomElement([0, 1]),
            'publicity'           => $this->faker->randomElement([0, 1]),
            'title'               => 'Κράτηση ' . $incrementingName++,
            'start'               => $startDate,
            'end'                 => $endDate,
            'semester_id'         => $semesterId,
            'info'                => $this->faker->sentence(10),
            'participants'        => "Participant1, Participant2, Participant3",
            'group_id'            => null,
            'recurring_id'        => null,
            'type'                => 'normal',
            'lecture_type'        => $this->faker->randomElement(['lecture', 'teleconference', 'seminar', 'other']),
            'expected_attendance' => $this->faker->randomElement(['<20', '20-50', '50-100', '>100']),
        ];
    }
}
