<?php

namespace App\Enums;

enum InboundStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case PARTIAL = 'partial';
    case RECEIVED = 'received';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING => 'Menunggu',
            self::PARTIAL => 'Sebagian Diterima',
            self::RECEIVED => 'Diterima',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PENDING => 'yellow',
            self::PARTIAL => 'blue',
            self::RECEIVED => 'green',
            self::CANCELLED => 'red',
        };
    }
}
