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
        User::firstOrCreate(
            ['email' => 'admin@soc.local'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
            ]
        );
        User::factory(300)->create();
    }
}
