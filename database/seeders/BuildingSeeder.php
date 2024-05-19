<?php

namespace Database\Seeders;

use App\Models\Building;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BuildingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //\App\Models\Building::factory(5)->create();
        \App\Models\Building::create([
            'name' => 'Κεντρικο κτήριο Αλεξάνδρειας Πανειστημιούπολης'
        ]);
        \App\Models\Building::create([
            'name' => 'Κτήριο-αμφιθέατρο Αλεξάνδρειας Πανειστημιούπολης'
        ]);
        \App\Models\Building::create([
            'name' => 'Κτήριο Τμήματος Μηχανικών Παραγωγής και Διοίκησης'
        ]);
    }

}
