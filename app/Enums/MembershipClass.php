<?php

namespace App\Enums;

enum MembershipClass: string
{
    case Corporate = 'corporate';
    case Associate = 'associate';

    public function label(): string
    {
        return match ($this) {
            self::Corporate => 'Corporate Member',
            self::Associate => 'Associate Member',
        };
    }
}
