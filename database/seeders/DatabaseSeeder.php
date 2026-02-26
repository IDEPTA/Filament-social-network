<?php

namespace Database\Seeders;

use App\Models\Content\Comment;
use App\Models\Content\Post;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(50)->create();
        Post::factory(500)->create();
        Comment::factory(500)->create();

        User::firstOrCreate(
            ['email' => 'admin@soc.local'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
            ]
        );
    }
}
