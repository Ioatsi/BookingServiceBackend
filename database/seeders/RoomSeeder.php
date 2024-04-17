<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //\App\Models\Room::factory(10)->create();
        \App\Models\Room::create([
            'name'=> 'Αλέξανδρος',
            'info'=> 'Κεντρικο αμφιθέατρο',
            'color' => '#4287f5',
            'building_id'=> 1,          
            'department_id' =>1  
        ]);
        \App\Models\Room::create([
            'name'=> 'Φίλιππος',
            'info'=> '',            
            'color' => '#42f55d',
            'building_id'=> 1,
            'department_id' =>1
        ]);
        \App\Models\Room::create([
            'name'=> 'Ολυμπία',
            'info'=> 'Kτήριο-αμφιθέατρο',            
            'color' => '#f5b342',
            'building_id'=> 2,
            'department_id' =>1
        ]);
        \App\Models\Room::create([
            'name'=> 'Αίθουσα Τηλεκπαίδευσης',
            'info'=> '',            
            'color' => '#f5424b',
            'building_id'=> 3,
            'department_id' =>1
        ]);
    }
}
