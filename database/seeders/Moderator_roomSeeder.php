<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use App\Models\User;
use App\Models\Room;
class Moderator_roomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $moderatorIds = User::whereHas('roles', function ($query) {
            $query->where('name', 'Moderator');
        })->pluck('id')->toArray();

        $roomIds = Room::pluck('room_id')->toArray();

        foreach ($moderatorIds as $moderatorId) {
            foreach ($roomIds as $roomId) {
                \DB::table('moderator_room')->insert([
                    'user_id' => $moderatorId,
                    'room_id' => $roomId,
                ]);
            }
        }
    }
}
