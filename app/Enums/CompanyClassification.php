<?php

namespace App\Enums;

enum CompanyClassification: string
{
    case Proprietorship = 'proprietorship';
    case Partnership = 'partnership';
    case PrivateLtd = 'private_ltd';
    case PublicLtd = 'public_ltd';
    case Aop = 'aop';

    public function label(): string
    {
        return match ($this) {
            self::Proprietorship => 'Proprietorship',
            self::Partnership => 'Partnership',
            self::PrivateLtd => 'Private Ltd. Co.',
            self::PublicLtd => 'Public Ltd. Co.',
            self::Aop => 'AOP',
        };
    }
}
