<?php

namespace App\Enums;

enum InventoryMovementType: string
{
    case IN = 'IN';      // barang masuk (receive PO, stock in)
    case OUT = 'OUT';     // barang keluar (POS sale, return, dll)
    case ADJUST = 'ADJUST';  // koreksi stok manual / stock opname

    // optional: label buat UI
    public function label(): string
    {
        return match ($this) {
            self::IN => 'Stock In',
            self::OUT => 'Stock Out',
            self::ADJUST => 'Stock Adjustment',
        };
    }
}
