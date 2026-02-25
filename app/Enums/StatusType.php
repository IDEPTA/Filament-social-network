<?php

namespace App\enums;

enum StatusType: string
{
    case DRAFT = 'draft';
    case REVIEW = 'review';
    case ACTIVE = 'active';
    case WITHDRAWAL_PENDING = 'withdrawal_pending';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Черновик',
            self::REVIEW => 'На проверке',
            self::ACTIVE => 'Активен',
            self::WITHDRAWAL_PENDING => 'К выбытию',
            self::ARCHIVED => 'Архив',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::REVIEW => 'warning',
            self::ACTIVE => 'success',
            self::WITHDRAWAL_PENDING => 'warning',
            self::ARCHIVED => 'danger',
        };
    }
}