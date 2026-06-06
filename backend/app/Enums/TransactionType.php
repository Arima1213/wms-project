<?php

namespace App\Enums;

enum TransactionType: string
{
    case GOODS_RECEIPT = 'GR';
    case GOODS_ISSUE = 'GI';
    case TRANSFER = 'TR';
    case LOCATION_TRANSFER = 'LT';
    case STOCK_OPNAME = 'SO';
    case ADJUSTMENT_PLUS = 'ADJ+';
    case ADJUSTMENT_MINUS = 'ADJ-';
    case RESERVE = 'RS';
    case RELEASE = 'RC';

    public function label(): string
    {
        return match ($this) {
            self::GOODS_RECEIPT => 'Goods Receipt',
            self::GOODS_ISSUE => 'Goods Issue',
            self::TRANSFER => 'Transfer',
            self::LOCATION_TRANSFER => 'Location Transfer',
            self::STOCK_OPNAME => 'Stock Opname',
            self::ADJUSTMENT_PLUS => 'Adjustment (+)',
            self::ADJUSTMENT_MINUS => 'Adjustment (-)',
            self::RESERVE => 'Reserve',
            self::RELEASE => 'Release',
        };
    }
}
