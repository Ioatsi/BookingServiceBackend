<?php

namespace Database\Seeders;

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
        $roles = Role::all();

        if ($roles->isEmpty()) {
            $this->command->error('No roles found. Please seed roles first.');
            return;
        }

        // Build an array of role IDs that are allowed for "random assignment".
        // We exclude role_id = 1 (admin) from random assignment so only 'admin' gets it.
        $randomRoleIds = $roles->whereNotIn('id', [1, 2])->pluck('id')->toArray();

        // Get users
        $users = User::all();

        foreach ($users as $user) {
            // Force admin -> role 1 only
            if ($user->username === 'admin') {
                $user->roles()->sync([1]);
                continue;
            }

            // Force mod -> role 2 only
            if ($user->username === 'mod') {
                $user->roles()->sync([2]);
                continue;
            }
            $num = rand(1, min(2, count($randomRoleIds)));
            $assign = array_slice($randomRoleIds, 0, 1);

            $user->roles()->sync($assign);
        }
    }
}
