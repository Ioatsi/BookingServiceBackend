<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Day;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();
        
        $this->call(DepartmentSeeder::class);
        $this->call(BuildingSeeder::class);
        $this->call(GroupSeeder::class);
        $this->call(UsersSeeder::class);
        $this->call(RoomSeeder::class);
        $this->call(SemesterSeeder::class);
        //$this->call(RecurringSeeder::class);
        $this->call(BookingSeeder::class);
        $this->call(RolesSeeder::class);
        $this->call(User_rolesSeeder::class);
        $this->call(Moderator_roomSeeder::class);
        //$this->call(DaySeeder::class);
        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
