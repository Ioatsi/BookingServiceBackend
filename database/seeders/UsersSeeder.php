<?php

namespace Database\Seeders;

use App\Models\User;
use GuzzleHttp\Promise\Create;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        User::create([
            'name' => 'Admin',
        ]);
        User::create([
            'name' => 'user1',
        ]);
        User::create([
            'name' => 'user2',
        ]);
        User::create([
            'name' => 'user3',
        ]);
        User::create([
            'name' => 'user4',
        ]);
        User::create([
            'name' => 'user5',
        ]);
        User::create([
            'name' => 'user6',
        ]);
        User::create([
            'name' => 'user7',
        ]);
        User::create([
            'name' => 'user8',
        ]);
        User::create([
            'name' => 'user9',
        ]);
    }
}
