<?php

namespace App\Enums;

enum FeedbackType: string
{
    case Like = 'like';
    case Dislike = 'dislike';

    /**
     * Возвращает лейблы
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Like => 'Нравится',
            self::Dislike => 'Не нравится',
        };
    }


    /**
     * Возвращает цвет
     *
     * @return string
     */
    public function color(): string
    {
        return match ($this) {
            self::Like => 'success',
            self::Dislike => 'warning',
        };
    }
}
