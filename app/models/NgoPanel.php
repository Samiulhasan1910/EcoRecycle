<?php

require_once __DIR__ . '/Delivery.php';
require_once __DIR__ . '/Notification.php';

/*
 * Queries and workflows used by the NGO panel
 */
class NgoPanel extends Model
{
    /* =========================
       SCHEMA COMPATIBILITY
       Older databases miss some notification / item columns.
       ========================= */

    public function ensureNotificationColumns(): void
    {
        $columns = [
            'wanted_id' => 'INT NULL',
            'ngo_id' => 'INT NULL AFTER user_id',
            'item_id' => 'INT NULL',
            'pickup_id' => 'INT NULL',
        ];

        foreach ($columns as $column => $definition) {
            if (!$this->columnExists('notifications', $column)) {
                $this->db->query("ALTER TABLE notifications ADD COLUMN $column $definition");
            }
        }
    }

    public function ensureItemDescriptionColumn(): void
    {
        if (!$this->columnExists('items', 'description')) {
            $this->db->query('ALTER TABLE items ADD COLUMN description TEXT NULL AFTER category');
        }
    }

    /* =========================
       NOTIFICATIONS
       ========================= */

    // Add delivery updates to the NGO inbox without duplicates
    public function syncPickupNotifications(int $ngoId, bool $withItem = true): void
    {
        $itemColumn = $withItem ? ', item_id' : '';
        $itemValue = $withItem ? ', r.item_id' : '';

        $this->execute(
            "INSERT INTO notifications (user_id, ngo_id$itemColumn, message, status, pickup_id)
             SELECT NULL, r.ngo_id$itemValue, CONCAT('Delivery update for pickup #', p.pickup_id, ': ', p.status), 'Unread', p.pickup_id
             FROM pickups p
             INNER JOIN requests r ON r.request_id = p.request_id
             WHERE r.ngo_id = ?
               AND NOT EXISTS (SELECT 1 FROM notifications n WHERE n.ngo_id = r.ngo_id AND n.pickup_id = p.pickup_id)",
            [$ngoId]
        );
    }

    // Recover user donation posts created before NGO notifications existed
    public function syncDonationNotifications(int $ngoId): void
    {
        $this->execute(
            "INSERT INTO notifications (user_id, ngo_id, item_id, notification_type, message, status)
             SELECT NULL, ?, i.item_id, 'ngo_donation', CONCAT('User donation post available: ', i.title), 'Unread'
             FROM items i
             WHERE i.type = 'Donate' AND i.user_id IS NOT NULL AND (i.ngo_id = ? OR i.ngo_id IS NULL)
               AND NOT EXISTS (
                   SELECT 1 FROM notifications n
                   WHERE n.ngo_id = ? AND n.item_id = i.item_id AND n.notification_type = 'ngo_donation'
               )",
            [$ngoId, $ngoId, $ngoId]
        );
    }

    public function notifications(int $ngoId, ?int $limit = null): array
    {
        return $this->fetchAll(
            'SELECT notification_id, message, status, request_id, pickup_id, item_id, wanted_id
             FROM notifications WHERE ngo_id = ? ORDER BY notification_id DESC'
            . ($limit ? ' LIMIT ' . (int)$limit : ''),
            [$ngoId]
        );
    }

    public function unreadCount(int $ngoId): int
    {
        return (int)$this->fetchValue(
            "SELECT COUNT(*) FROM notifications WHERE ngo_id = ? AND LOWER(status) = 'unread'",
            [$ngoId]
        );
    }

    public function notification(int $notificationId, int $ngoId): ?array
    {
        return $this->fetchOne(
            'SELECT notification_id, notification_type, message, status, request_id, pickup_id, item_id, wanted_id
             FROM notifications WHERE notification_id = ? AND ngo_id = ?',
            [$notificationId, $ngoId]
        );
    }

    public function setNotificationStatus(int $notificationId, int $ngoId, string $status, ?int $requestId = null): void
    {
        if ($requestId !== null) {
            $this->execute(
                'UPDATE notifications SET status = ?, request_id = ? WHERE notification_id = ? AND ngo_id = ?',
                [$status, $requestId, $notificationId, $ngoId]
            );
        } else {
            $this->execute(
                'UPDATE notifications SET status = ? WHERE notification_id = ? AND ngo_id = ?',
                [$status, $notificationId, $ngoId]
            );
        }
    }

    /* =========================
       DASHBOARD
       ========================= */

    public function donationCount(int $ngoId, ?string $status = null): int
    {
        $sql = "SELECT COUNT(*) FROM items WHERE type = 'Donate' AND ngo_id = ?";
        $params = [$ngoId];

        if ($status !== null) {
            $sql .= ' AND status = ?';
            $params[] = $status;
        }

        return (int)$this->fetchValue($sql, $params);
    }

    public function acceptWantedPost(int $wantedId, int $ngoId): void
    {
        $this->execute(
            "UPDATE wanted_posts SET status = 'Accepted', ngo_id = ?
             WHERE wanted_id = ? AND (ngo_id IS NULL OR ngo_id = ?) AND status != 'Completed'",
            [$ngoId, $wantedId, $ngoId]
        );
    }

    // Dashboard "accept": item -> Accepted and a Pending Donation request is created
    public function acceptItemFromDashboard(int $itemId, int $ngoId): void
    {
        $this->begin();

        $updated = $this->execute(
            "UPDATE items SET status = 'Accepted', ngo_id = ? WHERE item_id = ? AND ngo_id = ? AND status = 'Pending'",
            [$ngoId, $itemId, $ngoId]
        );

        if ($updated === 1) {
            $userId = (int)$this->fetchValue('SELECT user_id FROM items WHERE item_id = ? LIMIT 1', [$itemId]);

            if ($userId && !$this->fetchOne('SELECT request_id FROM requests WHERE item_id = ? LIMIT 1', [$itemId])) {
                $this->execute(
                    "INSERT INTO requests (user_id, item_id, ngo_id, request_type, status, created_at) VALUES (?, ?, ?, 'Donation', 'Pending', NOW())",
                    [$userId, $itemId, $ngoId]
                );
            }
        }

        $this->commit();
    }

    /* =========================
       DONATION REQUESTS
       ========================= */

    public function pendingDonations(int $ngoId): array
    {
        return $this->fetchAll(
            "SELECT * FROM items WHERE type = 'Donate' AND ngo_id = ? AND status = 'Pending' ORDER BY item_id DESC",
            [$ngoId]
        );
    }

    // Accept a private donation: request Accepted, delivery job opened, donor notified
    public function acceptPrivateDonation(int $itemId, int $ngoId): void
    {
        $this->begin();

        try {
            $updated = $this->execute(
                "UPDATE items SET status = 'Accepted', ngo_id = ? WHERE item_id = ? AND ngo_id = ? AND status = 'Pending'",
                [$ngoId, $itemId, $ngoId]
            );

            $userId = $updated === 1
                ? (int)$this->fetchValue('SELECT user_id FROM items WHERE item_id = ? LIMIT 1', [$itemId])
                : 0;

            if ($userId) {
                $requestId = (int)$this->fetchValue('SELECT request_id FROM requests WHERE item_id = ? LIMIT 1', [$itemId]);

                if ($requestId) {
                    $this->execute(
                        "UPDATE requests SET ngo_id = ?, request_type = 'Donation', status = 'Accepted', assigned_at = NOW(), updated_at = NOW()
                         WHERE request_id = ?",
                        [$ngoId, $requestId]
                    );
                } else {
                    $this->execute(
                        "INSERT INTO requests (user_id, item_id, ngo_id, request_type, status, created_at, updated_at)
                         VALUES (?, ?, ?, 'Donation', 'Accepted', NOW(), NOW())",
                        [$userId, $itemId, $ngoId]
                    );
                    $requestId = $this->lastInsertId();
                }

                if ($requestId > 0) {
                    (new Delivery())->createJob('requests', $requestId, $itemId, $userId);

                    (new Notification())->create([
                        'user_id' => $userId,
                        'item_id' => $itemId,
                        'request_id' => $requestId,
                        'notification_type' => 'request',
                        'message' => 'Your donation has been accepted. The delivery request is open for available delivery men.',
                        'status' => 'Unread',
                    ]);
                }
            }

            $this->commit();
        } catch (Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function acceptedDonations(int $ngoId): array
    {
        return $this->fetchAll(
            "SELECT r.request_id, r.user_id, r.item_id, r.ngo_id, r.request_type, r.status,
                    i.title, i.category, i.price, i.location,
                    d.status AS delivery_status, d.assigned_delivery_man_id, dm.name AS delivery_name
             FROM requests r
             INNER JOIN items i ON r.item_id = i.item_id
             LEFT JOIN deliveries d ON d.request_id = r.request_id
             LEFT JOIN delivery_man dm ON dm.delivery_id = d.assigned_delivery_man_id
             WHERE r.ngo_id = ? AND r.request_type = 'Donation' AND r.status = 'Accepted'
             ORDER BY r.request_id DESC",
            [$ngoId]
        );
    }

    public function availableDeliveryMen(): array
    {
        return $this->fetchAll(
            "SELECT delivery_id, name, phone FROM delivery_man
             WHERE verification_status = 'Approved' AND availability = 'Available' AND is_blocked = 0
             ORDER BY name ASC"
        );
    }

    public function pickups(int $ngoId): array
    {
        return $this->fetchAll(
            'SELECT p.pickup_id, p.request_id, p.delivery_id, p.date, p.time, p.status, r.ngo_id, r.item_id,
                    i.title, i.category, i.location,
                    d.name AS delivery_name, d.phone AS delivery_phone, d.location AS delivery_location
             FROM pickups p
             INNER JOIN requests r ON p.request_id = r.request_id
             INNER JOIN items i ON r.item_id = i.item_id
             INNER JOIN delivery_man d ON p.delivery_id = d.delivery_id
             WHERE r.ngo_id = ?
             ORDER BY p.pickup_id DESC',
            [$ngoId]
        );
    }

    /* =========================
       NOTIFICATION DETAIL
       ========================= */

    public function wantedSummary(int $wantedId): ?array
    {
        return $this->fetchOne(
            'SELECT wanted_id, title, category, location, description, status FROM wanted_posts WHERE wanted_id = ?',
            [$wantedId]
        );
    }

    public function itemForNgo(int $itemId, int $ngoId): ?array
    {
        return $this->fetchOne(
            "SELECT user_id, ngo_id, title, category, type, price, location, status
             FROM items WHERE item_id = ? AND (ngo_id = ? OR type = 'Donate')",
            [$itemId, $ngoId]
        );
    }

    public function requestForNgo(int $requestId, int $ngoId): ?array
    {
        return $this->fetchOne(
            'SELECT r.request_id, r.user_id, r.item_id, r.request_type, r.status, i.title, i.category, i.price, i.location
             FROM requests r
             INNER JOIN items i ON r.item_id = i.item_id
             WHERE r.request_id = ? AND r.ngo_id = ?',
            [$requestId, $ngoId]
        );
    }

    public function proposedCharge(int $requestId): ?array
    {
        return $this->fetchOne(
            "SELECT d.delivery_id, d.status, d.proposed_charge, d.charge_status, dm.name AS delivery_name
             FROM deliveries d
             LEFT JOIN delivery_man dm ON dm.delivery_id = d.assigned_delivery_man_id
             WHERE d.request_id = ? AND d.status = 'Charge Proposed'
             LIMIT 1",
            [$requestId]
        );
    }

    public function pickupForNgo(int $pickupId, int $ngoId): ?array
    {
        return $this->fetchOne(
            'SELECT p.pickup_id, p.request_id, p.delivery_id, p.date, p.time, p.status,
                    d.name AS delivery_name, d.phone AS delivery_phone, d.location AS delivery_location
             FROM pickups p
             INNER JOIN delivery_man d ON p.delivery_id = d.delivery_id
             INNER JOIN requests r ON p.request_id = r.request_id
             WHERE p.pickup_id = ? AND r.ngo_id = ?',
            [$pickupId, $ngoId]
        );
    }

    // NGO asks the donor of a public donation to give it to them
    public function requestFromDonor(int $itemId, int $ngoId, int $notificationId): void
    {
        $this->begin();

        try {
            $item = $this->fetchOne('SELECT user_id, title, type, status, ngo_id FROM items WHERE item_id = ? FOR UPDATE', [$itemId]);

            if (!$item
                || strtolower(trim($item['type'] ?? '')) !== 'donate'
                || strtolower(trim($item['status'] ?? '')) !== 'available'
                || !empty($item['ngo_id'])) {
                throw new Exception('This public donation is no longer available.');
            }

            $donorId = (int)$item['user_id'];

            $existing = $this->fetchOne(
                "SELECT request_id, status FROM requests WHERE item_id = ? AND ngo_id = ? AND request_type = 'NGO Donation'
                 ORDER BY request_id DESC LIMIT 1",
                [$itemId, $ngoId]
            );

            if ($existing && strtolower($existing['status'] ?? '') === 'pending') {
                throw new Exception('You have already sent a request to this donor.');
            }

            $this->execute(
                "INSERT INTO requests (user_id, item_id, ngo_id, request_type, status) VALUES (?, ?, ?, 'NGO Donation', 'Pending')",
                [$donorId, $itemId, $ngoId]
            );
            $requestId = $this->lastInsertId();

            $itemTitle = $item['title'] ?? 'item';

            (new Notification())->create([
                'user_id' => $donorId,
                'item_id' => $itemId,
                'request_id' => $requestId,
                'notification_type' => 'ngo_request',
                'message' => "An NGO requested your public donation '$itemTitle'. Please accept or reject the request.",
                'status' => 'Unread',
            ]);

            $this->setNotificationStatus($notificationId, $ngoId, 'Read', $requestId);

            $this->commit();
        } catch (Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    // NGO accepts / rejects a donation addressed to it
    public function answerDonation(int $itemId, int $ngoId, int $notificationId, string $action): void
    {
        $this->begin();

        try {
            $item = $this->fetchOne(
                "SELECT item_id, user_id, ngo_id, title, type, status FROM items
                 WHERE item_id = ? AND type = 'Donate' AND (ngo_id IS NULL OR ngo_id = ?) FOR UPDATE",
                [$itemId, $ngoId]
            );

            if (!$item || !in_array(strtolower(trim($item['status'] ?? '')), ['available', 'pending', 'accepted'], true)) {
                throw new Exception('This donation request is no longer available.');
            }

            if ((int)($item['ngo_id'] ?? 0) !== $ngoId) {
                throw new Exception('Public donations must be requested from the donor first.');
            }

            $donorId = (int)$item['user_id'];
            $itemTitle = $item['title'] ?? 'item';
            $notifications = new Notification();

            if ($action === 'accept') {
                $this->execute("UPDATE items SET status = 'Accepted', ngo_id = ? WHERE item_id = ?", [$ngoId, $itemId]);

                $requestId = (int)$this->fetchValue(
                    "SELECT request_id FROM requests WHERE item_id = ? AND ngo_id = ? AND LOWER(request_type) IN ('donate', 'donation') LIMIT 1",
                    [$itemId, $ngoId]
                );

                if ($requestId) {
                    $this->execute("UPDATE requests SET status = 'Accepted' WHERE request_id = ?", [$requestId]);
                } else {
                    $this->execute(
                        "INSERT INTO requests (user_id, item_id, ngo_id, request_type, status) VALUES (?, ?, ?, 'Donation', 'Accepted')",
                        [$donorId, $itemId, $ngoId]
                    );
                    $requestId = $this->lastInsertId();
                }

                (new Delivery())->createJob('requests', $requestId, $itemId, $donorId);

                $notifications->create([
                    'user_id' => $donorId,
                    'item_id' => $itemId,
                    'request_id' => $requestId,
                    'notification_type' => 'request',
                    'message' => "Your donation '$itemTitle' has been accepted by the NGO. A delivery request is now open.",
                    'status' => 'Unread',
                ]);

                $this->setNotificationStatus($notificationId, $ngoId, 'Accepted', $requestId);
            } else {
                $this->execute("UPDATE items SET status = 'Available', ngo_id = NULL WHERE item_id = ?", [$itemId]);

                $notifications->create([
                    'user_id' => $donorId,
                    'item_id' => $itemId,
                    'notification_type' => 'request',
                    'message' => "Your donation '$itemTitle' was rejected by the NGO.",
                    'status' => 'Unread',
                ]);

                $this->setNotificationStatus($notificationId, $ngoId, 'Rejected');
            }

            $this->commit();
        } catch (Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    /* =========================
       NGO DONATION POSTS
       ========================= */

    public function post(int $itemId, int $ngoId): ?array
    {
        return $this->fetchOne(
            "SELECT item_id, title, category, description, location, image, status
             FROM items WHERE item_id = ? AND ngo_id = ? AND type = 'Donate' LIMIT 1",
            [$itemId, $ngoId]
        );
    }

    public function posts(int $ngoId): array
    {
        return $this->fetchAll(
            "SELECT item_id, title, category, description, location, image, status
             FROM items WHERE ngo_id = ? AND type = 'Donate' ORDER BY item_id DESC",
            [$ngoId]
        );
    }

    public function deletePost(int $itemId, int $ngoId): void
    {
        $this->execute("DELETE FROM items WHERE item_id = ? AND ngo_id = ? AND type = 'Donate'", [$itemId, $ngoId]);
    }

    public function createPost(int $ngoId, string $title, string $category, string $description, string $location, string $image): int
    {
        $this->execute(
            "INSERT INTO items (user_id, ngo_id, title, category, description, type, price, location, image, status, payment_method, payment_number)
             VALUES (NULL, ?, ?, ?, ?, 'Donate', '0', ?, ?, 'Available', '', '')",
            [$ngoId, $title, $category, $description, $location, $image]
        );

        $itemId = $this->lastInsertId();

        // Notify every user, this NGO and every delivery man; failures must not undo the post
        try {
            $message = "New donation post available: $title";

            $this->execute(
                "INSERT INTO notifications (user_id, item_id, notification_type, message, status)
                 SELECT user_id, ?, 'new_post', ?, 'Unread' FROM users WHERE user_id IS NOT NULL",
                [$itemId, $message]
            );
            $this->execute(
                "INSERT INTO notifications (user_id, ngo_id, item_id, notification_type, message, status) VALUES (NULL, ?, ?, 'new_post', ?, 'Unread')",
                [$ngoId, $itemId, $message]
            );
            $this->execute(
                "INSERT INTO notifications (user_id, delivery_id, item_id, notification_type, message, status)
                 SELECT NULL, delivery_id, ?, 'new_post', ?, 'Unread' FROM delivery_man WHERE delivery_id IS NOT NULL",
                [$itemId, $message]
            );
        } catch (mysqli_sql_exception $e) {
        }

        return $itemId;
    }

    // $image = '' keeps the current image
    public function updatePost(int $itemId, int $ngoId, string $title, string $category, string $description, string $location, string $image): void
    {
        $this->execute(
            "UPDATE items SET title = ?, category = ?, description = ?, location = ?, image = IF(? = '', image, ?)
             WHERE item_id = ? AND ngo_id = ? AND type = 'Donate'",
            [$title, $category, $description, $location, $image, $image, $itemId, $ngoId]
        );
    }

    /* =========================
       BROWSE WANTED POSTS
       ========================= */

    public function browse(string $search, string $category, string $location): array
    {
        $sql = "SELECT wanted_id, title, category, location, description, status, ngo_name
                FROM (
                    SELECT wp.wanted_id, wp.title, wp.category, wp.location, wp.description, wp.status, NULL AS ngo_name
                    FROM wanted_posts AS wp
                    WHERE LOWER(wp.status) = 'active'
                    UNION ALL
                    SELECT i.item_id AS wanted_id, i.title, i.category, i.location, NULL AS description, 'Active' AS status, n.name AS ngo_name
                    FROM items AS i
                    LEFT JOIN ngos AS n ON n.ngo_id = i.ngo_id
                    WHERE i.type = 'Donate' AND LOWER(i.status) = 'available'
                ) AS all_posts
                WHERE 1";
        $params = [];

        if ($search !== '') {
            $sql .= ' AND (title LIKE ? OR description LIKE ? OR category LIKE ? OR location LIKE ?)';
            array_push($params, "%$search%", "%$search%", "%$search%", "%$search%");
        }

        if ($category !== '') {
            $sql .= ' AND LOWER(TRIM(category)) = LOWER(TRIM(?))';
            $params[] = $category;
        }

        if ($location !== '') {
            $sql .= ' AND location = ?';
            $params[] = $location;
        }

        return $this->fetchAll($sql . ' ORDER BY wanted_id DESC', $params);
    }

    public function wantedLocations(): array
    {
        return $this->fetchAll(
            "SELECT DISTINCT location FROM wanted_posts WHERE location IS NOT NULL AND location != '' ORDER BY location ASC"
        );
    }

    /* =========================
       PROFILE
       ========================= */

    public function profile(int $ngoId): ?array
    {
        return $this->fetchOne(
            'SELECT name, email, phone, location, organization_type, established_year FROM ngos WHERE ngo_id = ? LIMIT 1',
            [$ngoId]
        );
    }

    public function updateProfile(int $ngoId, array $data, ?string $documentPath): void
    {
        $sql = 'UPDATE ngos SET name = ?, email = ?, phone = ?, location = ?, organization_type = ?, established_year = ?';
        $params = [$data['name'], $data['email'], $data['phone'], $data['location'], $data['organization_type'], $data['established_year']];

        if ($documentPath !== null) {
            $sql .= ', verification_document = ?';
            $params[] = $documentPath;
        }

        $params[] = $ngoId;

        $this->execute($sql . ' WHERE ngo_id = ?', $params);
    }

    // Delete the NGO and detach / remove everything that points to it
    public function deleteAccount(int $ngoId): bool
    {
        $this->begin();

        try {
            $this->execute('DELETE p FROM pickups p INNER JOIN requests r ON r.request_id = p.request_id WHERE r.ngo_id = ?', [$ngoId]);
            $this->execute('DELETE FROM requests WHERE ngo_id = ?', [$ngoId]);
            $this->execute('DELETE FROM notifications WHERE ngo_id = ?', [$ngoId]);
            $this->execute('UPDATE items SET ngo_id = NULL WHERE ngo_id = ?', [$ngoId]);
            $this->execute('UPDATE wanted_posts SET ngo_id = NULL WHERE ngo_id = ?', [$ngoId]);
            $this->execute('DELETE FROM ngos WHERE ngo_id = ?', [$ngoId]);

            $this->commit();

            return true;
        } catch (mysqli_sql_exception $e) {
            $this->rollback();

            return false;
        }
    }
}
