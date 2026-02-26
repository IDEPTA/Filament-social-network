<?php

namespace App\Models\Content;

use App\Enums\FeedbackType;
use App\Models\Content\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasUuids;

    protected $fillable = [
        "user_id",
        "post_id",
        "feedback_type"
    ];

    protected $casts = [
        'feedback_type' => FeedbackType::class
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
