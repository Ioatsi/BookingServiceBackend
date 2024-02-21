<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'username' => 'Moderator User'
        ])->roles()->attach(1); // Attach role with ID 1 (Admin) to this user

        User::create([
            'username' => 'Faculty User'
        ])->roles()->attach(2);
    }
}
