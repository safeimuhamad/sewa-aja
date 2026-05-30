<?php

namespace App\AuthService\Models;

use App\UserService\Models\User;
use Shared\Database\Model;

class AuthToken extends Model
{
    protected string $table = 'auth_tokens';

    protected array $fillable = [
        'user_id',
        'token_id',
        'name',
        'expires_at',
        'revoked_at',
    ];

    protected array $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function user(): array
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
