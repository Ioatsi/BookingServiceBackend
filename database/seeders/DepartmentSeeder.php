<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Faker\Factory as Faker;

class DepartmentSeeder extends Seeder
{
    
    protected $faker;
    public function __construct()
    {
        $this->faker = Faker::create();
    }
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Department::create([
            'name' => 'IEE',
        ]);
        Department::create([
            'name' => 'IEM',
        ]);
    }
}
