<?php

namespace Database\Seeders;

use App\Models\Recurring;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RecurringSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Recurring::create([
            'semester_id' => 1,
            'title' => 'Reccuring 1',
            'status' => 1
        ]);
    }
}
