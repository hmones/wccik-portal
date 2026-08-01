<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cheque = 'cheque';
    case PayOrder = 'pay_order';
    case BankTransfer = 'bank_transfer';

    public function label(): string
    {
        return match ($this) {
            self::Cheque => 'Cheque',
            self::PayOrder => 'Pay Order',
            self::BankTransfer => 'Online Bank Transfer',
        };
    }
}
