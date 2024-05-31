<?php

namespace Database\Seeders;

use App\Models\User;
use GuzzleHttp\Promise\Create;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            ['username' => 'dmarina', 'last_name' => 'Δελιανίδη', 'first_name' => 'Μαρίνα', 'email' => 'No email1'],
            ['username' => 'karag', 'last_name' => 'Καραγεώργος', 'first_name' => 'Αντώνης', 'email' => 'No email2'],
            ['username' => 'thgera', 'last_name' => 'Γερανίδης', 'first_name' => 'Θεόδωρος', 'email' => 'No email3'],
            ['username' => 'pnikolaidis', 'last_name' => 'Νικολαϊδης', 'first_name' => 'Πολυχρόνης', 'email' => 'No email4'],
            ['username' => 'ialexandris', 'last_name' => 'Αλεξανρδής', 'first_name' => 'Ιάκωβος', 'email' => 'No email5'],
            ['username' => 'asidirop', 'last_name' => 'Σιδηρόπουλος', 'first_name' => 'Αντώνης', 'email' => 'No email6'],
            ['username' => 'kdiamant', 'last_name' => 'Διαμαντάρας', 'first_name' => 'Κωνσταντίνος', 'email' => 'No email7'],
            ['username' => 'antoniou', 'last_name' => 'Αντωνίου', 'first_name' => 'Ευστάθιος', 'email' => 'No email8'],
            ['username' => 'e.grigoropoulos', 'last_name' => 'ΓΡΗΓΟΡΟΠΟΥΛΟΣ', 'first_name' => 'ΕΥΑΓΓΕΛΟΣ', 'email' => 'No email9'],
            ['username' => 'c.kapetanidis', 'last_name' => 'ΚΑΠΕΤΑΝΙΔΗΣ', 'first_name' => 'ΧΑΡΑΛΑΜΠΟΣ', 'email' => 'No email10'],
            ['username' => 'vbanos', 'last_name' => 'ΜΠΑΝΟΣ', 'first_name' => 'ΕΥΑΓΓΕΛΟΣ', 'email' => 'No email11'],
            ['username' => 'i.nitsos', 'last_name' => 'ΝΙΤΣΟΣ', 'first_name' => 'ΗΛΙΑΣ', 'email' => 'No email12']
        ];

        foreach ($users as $user) {
            User::create($user);
        }

        $usernames = array_column($users, 'username');
        $userIds = DB::table('users')
            ->whereIn('username', $usernames)
            ->pluck('id');

        foreach ($userIds as $userId) {
            DB::table('role_user')->insert([
                'user_id' => $userId,
                'role_id' => 3
            ]);
            DB::table('role_user')->insert([
                'user_id' => $userId,
                'role_id' => 1
            ]);
        }
    }
}
