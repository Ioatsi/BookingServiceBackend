<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BookingGroup;

class BookingGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        BookingGroup::create([
            'group_title' => 'Group Title Value',
            'start' => '2024-02-15',
            'end' => '2024-02-20',
        ]);
    }
}
