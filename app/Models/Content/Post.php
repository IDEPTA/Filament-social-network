<?php

namespace App\Models\Content;

use App\Enums\StatusType;
use App\Models\Content\Comment;
use App\Models\Content\Feedback;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasUuids;
    use HasFactory;

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
