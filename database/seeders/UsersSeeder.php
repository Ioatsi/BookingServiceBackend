<?php

namespace Database\Seeders;

use App\Models\User;
use GuzzleHttp\Promise\Create;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        User::create([
            'username' => 'Admin',
        ]);
        $faker = Faker::create();

        foreach (range(1, 10) as $index) {
            User::create([
                'username' => 'user' . $index,
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'email' => $faker->email,
                'AM' => $faker->randomNumber(6)
            ]);
        }
    }
}
