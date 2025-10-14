<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin and mod first (no roles here)
        User::create([
            'username'   => 'admin',
            'last_name'  => 'Administrator',
            'first_name' => 'System',
            'email'      => 'admin@example.com',
            'password'   => Hash::make('admin'), // change for prod
        ]);

        User::create([
            'username'   => 'mod',
            'last_name'  => 'Moderator',
            'first_name' => 'System',
            'email'      => 'mod@example.com',
            'password'   => Hash::make('mod'),
        ]);

        // The rest of your users (same data as before) with placeholder passwords
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
            User::create([
                'username'   => $user['username'],
                'last_name'  => $user['last_name'],
                'first_name' => $user['first_name'],
                'email'      => $user['email'],
                'password'   => Hash::make('password'), // placeholder - change later if needed
            ]);
        }
    }
}
