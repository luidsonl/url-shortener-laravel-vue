<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    /** @use HasFactory<\Database\Factories\PlanFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'price_monthly',
        'price_yearly',
        'description',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_monthly' => 'integer',
            'price_yearly' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get monthly price (decimal)
     */
    public function getMonthlyPriceAttribute(): float
    {
        return $this->price_monthly / 100;
    }

    /**
     * Get yearly price (decimal)
     */
    public function getYearlyPriceAttribute(): float
    {
        return $this->price_yearly / 100;
    }
}