<?php

namespace App\Enums;

enum WarehouseType: string
{
    case REGULER = 'reguler';
    case COLD_STORAGE = 'cold_storage';
    case BONDED = 'bonded';
    case KONSINYASI = 'konsinyasi';

    public function label(): string
    {
        return match ($this) {
            self::REGULER => 'Reguler',
            self::COLD_STORAGE => 'Cold Storage',
            self::BONDED => 'Bonded Warehouse',
            self::KONSINYASI => 'Konsinyasi',
        };
    }
}
