<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    protected $connection = 'mysql';

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Developer',
            'email' => 'ronald.1999.mercado@gmail.com',
            'username' => 'Admin',
            'role' => 'Administrator',
            'password' => Hash::make('Aa@1234!'),
        ]);
    }
}
