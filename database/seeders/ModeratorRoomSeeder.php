<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\ModeratorRoom;
class ModeratorRoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ModeratorRoom::create([
            'user_id' => 1,
            'room_id' => 1,
        ]);

        ModeratorRoom::create([
            'user_id' => 2,
            'room_id' => 2,
        ]);
    }
}
