<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Transfer = 'transfer';
    case EWallet = 'e_wallet';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Transfer => 'Transfer',
            self::EWallet => 'E-Wallet',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Cash => 'success',
            self::Transfer => 'warning',
            self::EWallet => 'primary',
        };
    }
}

