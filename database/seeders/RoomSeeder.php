<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Room::create([
            'name' => 'Room Name',
            'capacity' => 10,
            'building_id' => 1,
            'department' => 'Department Value',
            'number' => 101,
            'type' => 'Type Value',
        ]);
    }
}
