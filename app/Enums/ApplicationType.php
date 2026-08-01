<?php

namespace App\Enums;

enum ApplicationType: string
{
    case NewMember = 'new_member';
    case Renewal = 'renewal';

    public function label(): string
    {
        return match ($this) {
            self::NewMember => 'New Member',
            self::Renewal => 'Renewal',
        };
    }
}
