<?php

namespace App\Models\Content;

use App\Enums\StatusType;
use App\Models\Content\Comment;
use App\Models\Content\Feedback;
use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function feedback()
    {
        return $this->hasMany(Feedback::class);
    }

    /**
     * Файлы, прикреплённые к объекту (полиморфная связь).
     */
    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'model');
    }
}
