<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Vinkla\Hashids\Facades\Hashids;

class ShortLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'original_url', 'code', 'clicks', 'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($shortLink) {
            if (empty($shortLink->code)) {
                $shortLink->generateCodeFromId();
            }
        });
    }

    public function generateCodeFromId()
    {
        $this->code = Hashids::connection('short_links')->encode($this->id);
        $this->saveQuietly();
        
        return $this;
    }

    public function decodeCode()
    {
        $decoded = Hashids::connection('short_links')->decode($this->code);
        return $decoded[0] ?? null;
    }

    public function getShortUrlAttribute()
    {
        return url('/' . $this->code);
    }

    public static function findByCode($code)
    {
        return self::where('code', $code)->first();
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return !$this->isExpired();
    }
}