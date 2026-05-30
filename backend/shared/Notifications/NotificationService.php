<?php

namespace Shared\Notifications;

use PDO;
use Shared\Support\FileLogger;
use Shared\Support\Uuid;

class NotificationService
{
    public function __construct(private PDO $db)
    {
    }

    public function create(array $data): array
    {
        $id = Uuid::v4();
        $statement = $this->db->prepare(
            'INSERT INTO notifications
             (id, user_id, role_target, type, channel, title, message, action_url, payload, status)
             VALUES
             (:id, :user_id, :role_target, :type, :channel, :title, :message, :action_url, :payload, :status)'
        );
        $statement->execute([
            'id' => $id,
            'user_id' => $data['user_id'] ?? null,
            'role_target' => $data['role_target'] ?? null,
            'type' => $data['type'],
            'channel' => $data['channel'] ?? 'in_app',
            'title' => $data['title'],
            'message' => $data['message'],
            'action_url' => $data['action_url'] ?? null,
            'payload' => isset($data['payload']) ? json_encode($data['payload'], JSON_UNESCAPED_SLASHES) : null,
            'status' => $data['status'] ?? 'queued',
        ]);

        return $this->find($id);
    }

    public function listForUser(string $userId, string $role, int $limit = 20): array
    {
        $statement = $this->db->prepare(
            'SELECT id, type, channel, title, message, action_url, status, read_at, created_at
             FROM notifications
             WHERE deleted_at IS NULL
             AND (user_id = :user_id OR role_target = :role)
             ORDER BY created_at DESC
             LIMIT :limit'
        );
        $statement->bindValue(':user_id', $userId);
        $statement->bindValue(':role', $role);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function markRead(string $userId, string $notificationId): bool
    {
        $statement = $this->db->prepare(
            "UPDATE notifications
             SET status = 'read', read_at = NOW()
             WHERE id = :id
             AND deleted_at IS NULL
             AND (user_id = :user_id OR user_id IS NULL)"
        );
        $statement->execute([
            'id' => $notificationId,
            'user_id' => $userId,
        ]);

        return $statement->rowCount() > 0;
    }

    public function logFailure(string $notificationId, string $message): void
    {
        $this->db->prepare(
            "UPDATE notifications
             SET status = 'failed', failed_at = NOW(), error_message = :error_message
             WHERE id = :id"
        )->execute([
            'id' => $notificationId,
            'error_message' => $message,
        ]);
        FileLogger::error('Notification failed', ['notification_id' => $notificationId, 'error' => $message]);
    }

    private function find(string $id): array
    {
        $statement = $this->db->prepare('SELECT * FROM notifications WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: [];
    }
}
