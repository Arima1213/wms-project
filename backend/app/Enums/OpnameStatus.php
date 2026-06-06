<?php

namespace App\Enums;

enum OpnameStatus: string
{
    case DRAFT = 'draft';
    case IN_PROGRESS = 'in_progress';
    case SUBMITTED = 'submitted';
    case APPROVED = 'approved';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::IN_PROGRESS => 'Sedang Berjalan',
            self::SUBMITTED => 'Menunggu Approval',
            self::APPROVED => 'Disetujui',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::IN_PROGRESS => 'blue',
            self::SUBMITTED => 'yellow',
            self::APPROVED => 'green',
            self::CANCELLED => 'red',
        };
    }
}
