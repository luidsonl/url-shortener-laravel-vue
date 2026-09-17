<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\SubscriptionEventType;

class SubscriptionEvent extends Model
{
    /** @use HasFactory<\Database\Factories\SubscriptionEventFactory> */
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'event_type',
        'credit_applied',
    ];

    protected $casts = [
        'credit_applied' => 'integer',
        'event_type' => SubscriptionEventType::class,
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
    
    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }
}
