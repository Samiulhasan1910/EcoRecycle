<?php

class Notification extends Model
{
    /*
     * Generic insert: create(['user_id' => 5, 'message' => '...'])
     */
    public function create(array $fields): int
    {
        $columns = implode(', ', array_keys($fields));
        $marks = implode(', ', array_fill(0, count($fields), '?'));

        $this->execute("INSERT INTO notifications ($columns) VALUES ($marks)", array_values($fields));

        return $this->lastInsertId();
    }

    // One notification per approved NGO
    public function notifyApprovedNgos(?int $itemId, string $type, string $message): void
    {
        $this->execute(
            "INSERT INTO notifications (user_id, ngo_id, item_id, notification_type, message, status)
             SELECT NULL, ngo_id, ?, ?, ?, 'Unread'
             FROM ngos
             WHERE LOWER(verification_status) = 'approved'",
            [$itemId, $type, $message]
        );
    }

    /* =========================
       USER
       ========================= */

    public function countUnreadForUser(int $userId): int
    {
        return (int)$this->fetchValue(
            "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND status = 'Unread'",
            [$userId]
        );
    }

    public function latestForUser(int $userId, int $limit = 50): array
    {
        return $this->fetchAll(
            'SELECT notification_id, message, status, item_id, request_id FROM notifications WHERE user_id = ? ORDER BY notification_id DESC LIMIT ' . (int)$limit,
            [$userId]
        );
    }

    public function allForUser(int $userId): array
    {
        return $this->fetchAll(
            'SELECT n.notification_id, n.user_id, n.request_id, n.notification_type, n.message, n.status
             FROM notifications n
             WHERE n.user_id = ?
             ORDER BY n.notification_id DESC',
            [$userId]
        );
    }

    public function markReadForUser(int $notificationId, int $userId): void
    {
        $this->execute(
            "UPDATE notifications SET status = 'Read' WHERE notification_id = ? AND user_id = ?",
            [$notificationId, $userId]
        );
    }

    public function markOneReadForUser(int $notificationId, int $userId): void
    {
        $this->execute(
            "UPDATE notifications SET status = 'Read' WHERE notification_id = ? AND user_id = ? LIMIT 1",
            [$notificationId, $userId]
        );
    }

    public function deleteForUser(int $notificationId, int $userId): void
    {
        $this->execute(
            'DELETE FROM notifications WHERE notification_id = ? AND user_id = ?',
            [$notificationId, $userId]
        );
    }

    /* =========================
       DELIVERY MAN
       ========================= */

    public function latestForDelivery(int $deliveryId, int $limit = 50): array
    {
        return $this->fetchAll(
            'SELECT notification_id, message, status, item_id, pickup_id FROM notifications WHERE delivery_id = ? ORDER BY notification_id DESC LIMIT ' . (int)$limit,
            [$deliveryId]
        );
    }

    public function markReadForDelivery(int $notificationId, int $deliveryId): void
    {
        $this->execute(
            "UPDATE notifications SET status = 'Read' WHERE notification_id = ? AND delivery_id = ?",
            [$notificationId, $deliveryId]
        );
    }
}
