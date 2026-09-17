<?php

namespace App\Enums;

enum SubscriptionEventType: string
{
    case CREATED = 'created';
    case RENEWED = 'renewed';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';
}
