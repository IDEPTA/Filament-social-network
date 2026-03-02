<?php

namespace App\Enums;

enum FileKind: string
{
    case Image = 'image';
    case Avatar = 'avatar';
    case Preview = 'preview';
    case Document = 'document';

    /**
     * Возвращает лейблы
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Image => 'Изображение',
            self::Avatar => 'Аватар',
            self::Preview => 'Превью',
            self::Document => 'Документ',
        };
    }
}
