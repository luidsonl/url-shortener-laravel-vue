<?php

namespace App\Models;

use App\Enums\Currency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionFactory> */
    use HasFactory;

        protected $table = 'transactions';

        protected $fillable = [
            'subscription_event_id',
            'processed_at',
            'amount',
            'currency',
        ];

        protected $casts = [
        'currency' => Currency::class,
        'processed_at' => 'datetime',
    ];

    public function subscriptionEvent()
    {
        return $this->belongsTo(SubscriptionEvent::class);
    }
}
