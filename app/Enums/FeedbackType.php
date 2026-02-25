<?php

namespace App\enums;

enum FeedbackType: string
{
    case Like = 'like';
    case Dislike = 'dislike';

    public function label(): string
    {
        return match ($this) {
            self::Like => 'Нравится',
            self::Dislike => 'Не нравится',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Like => 'success',
            self::Dislike => 'warning',
        };
    }
}