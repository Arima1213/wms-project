<?php

namespace App\Enums;

enum OutboundStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case PICKING = 'picking';
    case PACKED = 'packed';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING => 'Menunggu',
            self::PICKING => 'Picking',
            self::PACKED => 'Dikemas',
            self::SHIPPED => 'Dikirim',
            self::DELIVERED => 'Terkirim',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PENDING => 'yellow',
            self::PICKING => 'blue',
            self::PACKED => 'indigo',
            self::SHIPPED => 'purple',
            self::DELIVERED => 'green',
            self::CANCELLED => 'red',
        };
    }
}
