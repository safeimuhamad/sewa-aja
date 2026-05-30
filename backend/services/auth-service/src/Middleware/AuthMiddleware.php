<?php

namespace App\AuthService\Middleware;

use App\AuthService\Repositories\AuthTokenRepository;
use App\AuthService\Repositories\UserRepository;
use Shared\Http\Request;
use Shared\Http\Response;
use Shared\Support\Jwt;

class AuthMiddleware
{
    public function __construct(
        private UserRepository $users,
        private AuthTokenRepository $tokens
    ) {
    }

    public function requireAuth(array $roles = []): ?array
    {
        $token = Request::bearerToken();

        if (!$token) {
            Response::error('Token autentikasi wajib dikirim.', 401);
            return null;
        }

        $payload = Jwt::decode($token, getenv('JWT_SECRET') ?: 'change-this-to-a-long-random-secret');

        if (!$payload || empty($payload['sub']) || empty($payload['jti'])) {
            Response::error('Token autentikasi tidak valid atau sudah kedaluwarsa.', 401);
            return null;
        }

        if (!$this->tokens->isActive($payload['jti'])) {
            Response::error('Token autentikasi sudah tidak aktif.', 401);
            return null;
        }

        $user = $this->users->findById($payload['sub']);

        if (!$user || $user['status'] !== 'active') {
            Response::error('Akun tidak aktif atau tidak ditemukan.', 401);
            return null;
        }

        if ($roles !== [] && !in_array($user['role'], $roles, true)) {
            Response::error('Anda tidak memiliki akses untuk aksi ini.', 403);
            return null;
        }

        return [
            'user' => $user,
            'payload' => $payload,
            'token' => $token,
        ];
    }
}
