<?php

namespace App\Models\Content;

use App\enums\StatusType;
use App\Models\Content\Comment;
use App\Models\Content\Feedback;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasUuids;

    protected $fillable = [
        "title",
        "text",
        "status",
        "user_id"
    ];

    protected $casts = [
        'status' => StatusType::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comment()
    {
        return $this->hasMany(Comment::class);
    }

    public function feedback()
    {
        return $this->hasMany(Feedback::class);
    }
}
