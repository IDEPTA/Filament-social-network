<?php

namespace Database\Factories\Content;

use App\enums\StatusType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $users = User::pluck("id")->toArray();

        return [
            "title" => fake()->text(10),
            "text" => fake()->text(50),
            "user_id" => fake()->randomElement($users),
            "status" => fake()->randomElement(array_map(fn($case) => $case->value, StatusType::cases())),
            "created_at" => Carbon::today()->subDay(rand(1, 30)),
        ];
    }
}
