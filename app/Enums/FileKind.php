<?php

namespace App\Enums;

enum FileKind: string
{
    case Image = 'image';
    case Avatar = 'avatar';
    case Preview = 'preview';
    case Document = 'document';


    public function label(): string
    {
        return match ($this) {
            self::Image => 'Оригинал',
            self::Avatar => 'Мастер',
            self::Preview => 'Превью',
            self::Document => 'Документ',
        };
    }
}
