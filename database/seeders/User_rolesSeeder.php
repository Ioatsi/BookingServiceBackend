<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;


class User_rolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $roles = Role::all();

        // Assign random roles to users
        foreach ($users as $user) {
            $user->roles()->attach(
                $roles->pluck('id')->toArray()
                //$roles->random(rand(1, 3))->pluck('id')->toArray()
            );
        }
    }
}
