<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Enums\UserRole;
use Tymon\JWTAuth\Contracts\JWTSubject;

use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $mailer = config('mail.default');
            $host = config('mail.mailers.smtp.host');

            // If SMTP is not configured or specifically set to log, auto-verify the email
            if ($mailer === 'log' || empty($host)) {
                $user->email_verified_at = now();
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role'
    ];

    protected $attributes = [
        'role' => 'user',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    /**
     * Check if user email is verified
     */
    public function isVerified(): bool
    {
        return $this->hasVerifiedEmail();
    }

    /**
     * Check if user is a regular user
     */
    public function isUser(): bool
    {
        return $this->role === UserRole::USER;
    }

    /**
     * Check if user can manage other users
     */
    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Scope to get only admin users
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', UserRole::ADMIN->value);
    }

    /**
     * Scope to get only regular users
     */
    public function scopeUsers($query)
    {
        return $query->where('role', UserRole::USER->value);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
