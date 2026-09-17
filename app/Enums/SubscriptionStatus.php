<?php

namespace App\Enums;

enum SubscriptionStatus:string
{
    case ACTIVE = 'active';
    case CANCELED = 'canceled';
    case EXPIRED = 'expired';
    case PENDING = 'pending';
    case PAST_DUE = 'past_due';


    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
