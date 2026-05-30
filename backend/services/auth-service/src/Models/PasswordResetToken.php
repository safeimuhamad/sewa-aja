<?php

namespace App\AuthService\Models;

use App\UserService\Models\User;
use Shared\Database\Model;

class PasswordResetToken extends Model
{
    protected string $table = 'password_reset_tokens';

    protected array $fillable = [
        'user_id',
        'email',
        'token_hash',
        'expires_at',
        'used_at',
    ];

    protected array $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function user(): array
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
