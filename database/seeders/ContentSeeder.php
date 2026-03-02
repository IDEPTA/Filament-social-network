<?php

namespace Database\Seeders;

use App\Models\Content\Comment;
use App\Models\Content\Post;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Post::factory(500)->create();
        Comment::factory(2500)->create();
    }
}
