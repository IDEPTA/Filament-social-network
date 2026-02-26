<?php

namespace App\Models;

use App\enums\FileKind;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class File extends Model
{
    use HasUuids;

    protected $fillable = [
        'disk',
        'path',
        'original_name',
        'mime',
        'size',
        'hash',
        'kind',
        'model_type',
        'model_id',
        'created_by',
    ];

    protected $casts = [
        'kind' => FileKind::class,
    ];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }
}
