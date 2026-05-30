<?php

namespace App\UserService\Models;

use App\AuthService\Models\AuthToken;
use App\AuthService\Models\PasswordResetToken;
use App\BookingService\Models\Booking;
use Shared\Database\Model;

class User extends Model
{
    protected string $table = 'users';

    protected array $fillable = [
        'name',
        'email',
        'password_hash',
        'phone',
        'role',
        'status',
        'email_verified_at',
    ];

    protected array $casts = [
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function vendor(): array
    {
        return $this->hasOne(Vendor::class, 'user_id');
    }

    public function bookings(): array
    {
        return $this->hasMany(Booking::class, 'customer_id');
    }

    public function authTokens(): array
    {
        return $this->hasMany(AuthToken::class, 'user_id');
    }

    public function passwordResetTokens(): array
    {
        return $this->hasMany(PasswordResetToken::class, 'user_id');
    }
}
