<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the two specified users
        User::create([
            'name' => 'Matt',
            'email' => 'matt@goteam.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Test User',
            'email' => 'testme@goteam.inc',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
    }
}
