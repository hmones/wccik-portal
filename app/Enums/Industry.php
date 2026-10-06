<?php

namespace App\Enums;

enum Industry: string
{
    case Trading = 'trading';
    case Services = 'services';
    case Manufacturing = 'manufacturing';

    public function label(): string
    {
        return match ($this) {
            self::Trading => 'Trading',
            self::Services => 'Services',
            self::Manufacturing => 'Manufacturing',
        };
    }
}
