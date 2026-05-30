<?php

namespace App\NotificationService\Controllers;

use Shared\Http\AuthGuard;
use Shared\Http\Request;
use Shared\Http\Response;
use Shared\Notifications\NotificationService;

class NotificationController
{
    public function __construct(
        private NotificationService $notifications,
        private AuthGuard $auth
    ) {
    }

    public function index(): void
    {
        $user = $this->auth->requireRole(['admin', 'vendor', 'customer']);

        if (!$user) {
            return;
        }

        Response::success([
            'notifications' => $this->notifications->listForUser(
                $user['id'],
                $user['role'],
                min(50, max(5, (int) ($_GET['limit'] ?? 20)))
            ),
        ], 'Notifikasi berhasil diambil.');
    }

    public function store(): void
    {
        $user = $this->auth->requireRole(['admin']);

        if (!$user) {
            return;
        }

        $data = Request::json();
        $required = ['type', 'title', 'message'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                Response::error('Payload notifikasi tidak lengkap.', 422, [$field => ['Field wajib diisi.']]);
                return;
            }
        }

        Response::success([
            'notification' => $this->notifications->create([
                'user_id' => $data['user_id'] ?? null,
                'role_target' => $data['role_target'] ?? null,
                'type' => trim((string) $data['type']),
                'channel' => $data['channel'] ?? 'in_app',
                'title' => trim((string) $data['title']),
                'message' => trim((string) $data['message']),
                'action_url' => $data['action_url'] ?? null,
                'payload' => $data['payload'] ?? null,
            ]),
        ], 'Notifikasi berhasil dibuat.', 201);
    }

    public function markRead(string $id): void
    {
        $user = $this->auth->requireRole(['admin', 'vendor', 'customer']);

        if (!$user) {
            return;
        }

        if (!$this->notifications->markRead($user['id'], $id)) {
            Response::error('Notifikasi tidak ditemukan.', 404);
            return;
        }

        Response::success([], 'Notifikasi ditandai sudah dibaca.');
    }
}
