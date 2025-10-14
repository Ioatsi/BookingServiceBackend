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
        $randomRoleIds = $roles->where('id', '!=', 1)->pluck('id')->toArray();

        // Get users
        $users = User::all();

        foreach ($users as $user) {
            // Force admin -> role 1 only
            if ($user->username === 'admin') {
                $user->roles()->sync([1]);
                continue;
            }

            // Force mod -> role 2 (if exists)
            if ($user->username === 'mod') {
                if (in_array(2, $roles->pluck('id')->toArray())) {
                    $user->roles()->sync([2]);
                } else {
                    // fallback: give the mod the first available non-admin role
                    $fallback = collect($randomRoleIds)->first();
                    $user->roles()->sync([$fallback]);
                }
                continue;
            }

            // For everyone else: assign 1 or 2 random roles (but not role 1)
            // Choose randomly between 1 or 2 roles to attach
            $num = rand(1, min(2, count($randomRoleIds)));
            // shuffle and take $num roles
            shuffle($randomRoleIds);
            $assign = array_slice($randomRoleIds, 0, $num);

            $user->roles()->sync($assign);
        }
    }
}
