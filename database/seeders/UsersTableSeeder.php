<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un utilisateur administrateur
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'code_secret' => Hash::make('123456'), // Code secret: 123456 (à changer en production)
            'email_verified_at' => now(),
        ]);

        // Créer un utilisateur normal
        User::create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'code_secret' => Hash::make('654321'), // Code secret: 654321 (à changer en production)
            'email_verified_at' => now(),
        ]);
    }
}
