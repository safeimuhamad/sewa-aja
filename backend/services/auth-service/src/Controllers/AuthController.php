<?php

namespace App\AuthService\Controllers;

use App\AuthService\Middleware\AuthMiddleware;
use App\AuthService\Repositories\AuthTokenRepository;
use App\AuthService\Repositories\PasswordResetRepository;
use App\AuthService\Repositories\UserRepository;
use App\AuthService\Repositories\VendorRepository;
use PDO;
use Shared\Http\Request;
use Shared\Http\Response;
use Shared\Support\Jwt;
use Shared\Support\Uuid;
use Shared\Validation\Validator;

class AuthController
{
    public function __construct(
        private PDO $db,
        private UserRepository $users,
        private VendorRepository $vendors,
        private AuthTokenRepository $tokens,
        private PasswordResetRepository $passwordResets,
        private AuthMiddleware $auth
    ) {
    }

    public function registerCustomer(): void
    {
        $data = Request::json();
        $errors = Validator::make($data, [
            'name' => ['required', 'min:3', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'password' => ['required', 'min:8'],
            'phone' => ['max:30'],
        ]);

        if ($errors) {
            Response::error('Validasi gagal.', 422, $errors);
            return;
        }

        if ($this->users->findByEmail($data['email'])) {
            Response::error('Email sudah terdaftar.', 409, ['email' => ['Email sudah digunakan.']]);
            return;
        }

        $user = $this->users->create([
            'name' => trim($data['name']),
            'email' => trim($data['email']),
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'phone' => $data['phone'] ?? null,
            'role' => 'customer',
            'status' => 'active',
        ]);

        Response::success([
            'user' => $this->users->publicUser($user),
            'auth' => $this->issueToken($user),
        ], 'Registrasi customer berhasil.', 201);
    }

    public function registerVendor(): void
    {
        $data = Request::json();
        $errors = Validator::make($data, [
            'name' => ['required', 'min:3', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'password' => ['required', 'min:8'],
            'phone' => ['max:30'],
            'store_name' => ['required', 'min:3', 'max:140'],
            'city' => ['max:100'],
            'province' => ['max:100'],
        ]);

        if ($errors) {
            Response::error('Validasi gagal.', 422, $errors);
            return;
        }

        if ($this->users->findByEmail($data['email'])) {
            Response::error('Email sudah terdaftar.', 409, ['email' => ['Email sudah digunakan.']]);
            return;
        }

        try {
            $this->db->beginTransaction();
            $user = $this->users->create([
                'name' => trim($data['name']),
                'email' => trim($data['email']),
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'phone' => $data['phone'] ?? null,
                'role' => 'vendor',
                'status' => 'active',
            ]);

            $vendor = $this->vendors->create([
                'user_id' => $user['id'],
                'store_name' => trim($data['store_name']),
                'slug' => $this->slugify($data['store_name']) . '-' . substr($user['id'], 0, 8),
                'description' => $data['description'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'province' => $data['province'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'status' => 'active',
            ]);
            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            Response::error('Registrasi vendor gagal.', 500, ['server' => [$exception->getMessage()]]);
            return;
        }

        Response::success([
            'user' => $this->users->publicUser($user),
            'vendor' => $vendor,
            'auth' => $this->issueToken($user),
        ], 'Registrasi vendor berhasil.', 201);
    }

    public function login(): void
    {
        $data = Request::json();
        $errors = Validator::make($data, [
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if ($errors) {
            Response::error('Validasi gagal.', 422, $errors);
            return;
        }

        $user = $this->users->findByEmail($data['email']);

        if (!$user || !password_verify($data['password'], $user['password_hash'])) {
            Response::error('Email atau password salah.', 401);
            return;
        }

        if ($user['status'] !== 'active') {
            Response::error('Akun tidak aktif.', 403);
            return;
        }

        $payload = ['user' => $this->users->publicUser($user)];

        if ($user['role'] === 'vendor') {
            $payload['vendor'] = $this->vendors->findByUserId($user['id']);
        }

        $payload['auth'] = $this->issueToken($user);

        Response::success($payload, 'Login berhasil.');
    }

    public function forgotPassword(): void
    {
        $data = Request::json();
        $errors = Validator::make($data, [
            'email' => ['required', 'email'],
        ]);

        if ($errors) {
            Response::error('Validasi gagal.', 422, $errors);
            return;
        }

        $user = $this->users->findByEmail($data['email']);
        $response = ['sent' => true];

        if ($user) {
            $plainToken = bin2hex(random_bytes(32));
            $this->passwordResets->create($user['id'], $user['email'], $plainToken);

            if ((getenv('APP_DEBUG') ?: 'false') === 'true') {
                $response['demo_reset_token'] = $plainToken;
            }
        }

        Response::success($response, 'Jika email terdaftar, instruksi reset password akan dikirim.');
    }

    public function profile(): void
    {
        $context = $this->auth->requireAuth();

        if (!$context) {
            return;
        }

        $user = $context['user'];
        $data = ['user' => $this->users->publicUser($user)];

        if ($user['role'] === 'vendor') {
            $data['vendor'] = $this->vendors->findByUserId($user['id']);
        }

        Response::success($data, 'Profile berhasil diambil.');
    }

    public function updateProfile(): void
    {
        $context = $this->auth->requireAuth();

        if (!$context) {
            return;
        }

        $data = Request::json();
        $errors = Validator::make($data, [
            'name' => ['required', 'min:3', 'max:120'],
            'phone' => ['max:30'],
        ]);

        if ($errors !== []) {
            Response::error('Validasi profile gagal.', 422, $errors);
            return;
        }

        $user = $this->users->updateProfile($context['user']['id'], [
            'name' => trim((string) $data['name']),
            'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
        ]);

        Response::success([
            'user' => $this->users->publicUser($user),
        ], 'Profile berhasil diperbarui.');
    }

    public function logout(): void
    {
        $context = $this->auth->requireAuth();

        if (!$context) {
            return;
        }

        $this->tokens->revoke($context['payload']['jti']);
        Response::success([], 'Logout berhasil.');
    }

    public function vendorOnlyExample(): void
    {
        $context = $this->auth->requireAuth(['vendor']);

        if (!$context) {
            return;
        }

        Response::success([], 'Role vendor terverifikasi.');
    }

    private function issueToken(array $user): array
    {
        $now = time();
        $ttl = (int) (getenv('JWT_TTL_MINUTES') ?: 1440);
        $expiresAt = $now + ($ttl * 60);
        $tokenId = Uuid::v4();

        $token = Jwt::encode([
            'iss' => getenv('APP_URL') ?: 'http://localhost/sewaaja',
            'sub' => $user['id'],
            'jti' => $tokenId,
            'role' => $user['role'],
            'email' => $user['email'],
            'iat' => $now,
            'exp' => $expiresAt,
        ], getenv('JWT_SECRET') ?: 'change-this-to-a-long-random-secret');

        $this->tokens->create($user['id'], $tokenId, $expiresAt);

        return [
            'token_type' => 'Bearer',
            'access_token' => $token,
            'expires_at' => date(DATE_ATOM, $expiresAt),
        ];
    }

    private function slugify(string $value): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $value), '-'));

        return $slug ?: 'vendor';
    }
}
