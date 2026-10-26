<?php

require_once __DIR__ . '/Notification.php';

/*
 * Queries used by the admin panel.
 * Note: `delivery` is a database VIEW over delivery_man (see database/project.sql).
 */
class AdminPanel extends Model
{
    // COUNT(*) that returns 0 instead of failing (e.g. a missing table on an old database)
    public function count(string $sql, array $params = []): int
    {
        try {
            return (int)$this->fetchValue($sql, $params);
        } catch (mysqli_sql_exception $e) {
            return 0;
        }
    }

    /* =========================
       DASHBOARD
       ========================= */

    public function recentRequests(int $limit = 5): array
    {
        return $this->fetchAll(
            'SELECT requests.request_id, requests.request_type, requests.status,
                    users.name AS user_name, items.title AS item_title, ngos.name AS ngo_name
             FROM requests
             LEFT JOIN users ON requests.user_id = users.user_id
             LEFT JOIN items ON requests.item_id = items.item_id
             LEFT JOIN ngos ON requests.ngo_id = ngos.ngo_id
             ORDER BY requests.request_id DESC
             LIMIT ' . (int)$limit
        );
    }

    public function recentItems(int $limit = 5): array
    {
        return $this->fetchAll(
            'SELECT items.item_id, items.title, items.category, items.type, items.price, items.status, users.name AS user_name
             FROM items
             LEFT JOIN users ON items.user_id = users.user_id
             ORDER BY items.item_id DESC
             LIMIT ' . (int)$limit
        );
    }

    /* =========================
       USERS
       ========================= */

    // Returns [rows, total matching]
    public function users(string $search, int $offset, int $limit): array
    {
        $where = '';
        $params = [];

        if ($search !== '') {
            $where = ' WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?';
            $params = ["%$search%", "%$search%", "%$search%"];
        }

        return [
            $this->fetchAll('SELECT * FROM users' . $where . ' ORDER BY user_id DESC LIMIT ' . (int)$offset . ', ' . (int)$limit, $params),
            $this->count('SELECT COUNT(*) FROM users' . $where, $params),
        ];
    }

    public function deleteUser(int $userId): void
    {
        $this->execute('DELETE FROM users WHERE user_id = ?', [$userId]);
    }

    /* =========================
       ITEMS
       ========================= */

    // Returns [rows, total matching]
    public function items(string $search, string $category, string $status, int $offset, int $limit): array
    {
        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = '(i.title LIKE ? OR u.name LIKE ? OR i.location LIKE ? OR i.category LIKE ?)';
            array_push($params, "%$search%", "%$search%", "%$search%", "%$search%");
        }

        if ($category !== '' && $category !== 'All') {
            $where[] = 'i.category = ?';
            $params[] = $category;
        }

        if ($status !== '' && $status !== 'All') {
            $where[] = 'i.status = ?';
            $params[] = $status;
        }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $from = ' FROM items i LEFT JOIN users u ON u.user_id = i.user_id';

        return [
            $this->fetchAll(
                'SELECT i.item_id, i.title, i.category, i.type, i.price, i.location, i.image, i.status, u.name AS user_name'
                . $from . $whereSql . ' ORDER BY i.item_id DESC LIMIT ' . (int)$offset . ', ' . (int)$limit,
                $params
            ),
            $this->count('SELECT COUNT(*)' . $from . $whereSql, $params),
        ];
    }

    public function itemWithOwner(int $itemId): ?array
    {
        return $this->fetchOne(
            'SELECT i.*, u.name AS user_name FROM items i LEFT JOIN users u ON u.user_id = i.user_id WHERE i.item_id = ? LIMIT 1',
            [$itemId]
        );
    }

    public function setItemStatus(int $itemId, string $status): void
    {
        $this->execute('UPDATE items SET status = ? WHERE item_id = ?', [$status, $itemId]);
    }

    public function deleteItem(int $itemId): void
    {
        $this->execute('DELETE FROM items WHERE item_id = ?', [$itemId]);
    }

    /* =========================
       NGOS
       ========================= */

    // Returns [rows, total matching]
    public function ngos(string $search, string $status, int $offset, int $limit): array
    {
        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ?)';
            array_push($params, "%$search%", "%$search%", "%$search%");
        }

        if ($status === 'Pending') {
            $where[] = "(verification_status = 'Pending' OR verification_status = '')";
        } elseif (in_array($status, ['Approved', 'Rejected'], true)) {
            $where[] = 'verification_status = ?';
            $params[] = $status;
        }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        return [
            $this->fetchAll(
                'SELECT ngo_id, name, email, phone, location, organization_type, established_year, verification_status, verification_document
                 FROM ngos' . $whereSql . ' ORDER BY ngo_id DESC LIMIT ' . (int)$offset . ', ' . (int)$limit,
                $params
            ),
            $this->count('SELECT COUNT(*) FROM ngos' . $whereSql, $params),
        ];
    }

    public function ngo(int $ngoId): ?array
    {
        return $this->fetchOne(
            'SELECT ngo_id, name, email, phone, location, organization_type, established_year, verification_status, verification_document
             FROM ngos WHERE ngo_id = ?',
            [$ngoId]
        );
    }

    public function setNgoStatus(int $ngoId, string $status): void
    {
        $this->execute('UPDATE ngos SET verification_status = ? WHERE ngo_id = ?', [$status, $ngoId]);
    }

    /* =========================
       DELIVERY STAFF
       ========================= */

    public function ensureDeliveryColumns(): void
    {
        $checks = [
            'availability' => "ALTER TABLE delivery ADD COLUMN availability VARCHAR(20) NOT NULL DEFAULT 'Offline'",
            'is_blocked' => 'ALTER TABLE delivery ADD COLUMN is_blocked TINYINT(1) NOT NULL DEFAULT 0',
        ];

        foreach ($checks as $column => $sql) {
            if (!$this->columnExists('delivery', $column)) {
                $this->db->query($sql);
            }
        }
    }

    // Returns [rows, total matching]
    public function deliveryStaff(string $search, string $status, int $offset, int $limit): array
    {
        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ? OR location LIKE ?)';
            array_push($params, "%$search%", "%$search%", "%$search%", "%$search%");
        }

        if ($status === 'Blocked') {
            $where[] = "(verification_status = 'Blocked' OR is_blocked = 1)";
        } elseif ($status !== '' && $status !== 'All') {
            $where[] = 'verification_status = ?';
            $params[] = $status;
        }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        return [
            $this->fetchAll(
                'SELECT delivery_id, name, email, phone, location, verification_status, verification_document, availability, is_blocked
                 FROM delivery' . $whereSql . ' ORDER BY delivery_id DESC LIMIT ' . (int)$offset . ', ' . (int)$limit,
                $params
            ),
            $this->count('SELECT COUNT(*) FROM delivery' . $whereSql, $params),
        ];
    }

    public function deliveryMan(int $deliveryId): ?array
    {
        return $this->fetchOne('SELECT * FROM delivery WHERE delivery_id = ? LIMIT 1', [$deliveryId]);
    }

    public function deliveryEmailExists(string $email): bool
    {
        return (bool)$this->fetchOne('SELECT delivery_id FROM delivery WHERE email = ? LIMIT 1', [$email]);
    }

    public function addDeliveryMan(array $data): void
    {
        $this->execute(
            "INSERT INTO delivery (name, email, password, phone, location, verification_document, verification_status, availability, is_blocked)
             VALUES (?, ?, ?, ?, ?, ?, 'Approved', 'Offline', 0)",
            [$data['name'], $data['email'], $data['password'], $data['phone'], $data['location'], $data['verification_document']]
        );
    }

    public function setDeliveryVerification(int $deliveryId, string $status, int $blocked): void
    {
        $this->execute(
            'UPDATE delivery SET verification_status = ?, is_blocked = ? WHERE delivery_id = ?',
            [$status, $blocked, $deliveryId]
        );
    }

    public function setDeliveryAvailability(int $deliveryId, string $availability): void
    {
        $this->execute('UPDATE delivery SET availability = ? WHERE delivery_id = ?', [$availability, $deliveryId]);
    }

    public function deleteDeliveryMan(int $deliveryId): void
    {
        $this->execute('DELETE FROM delivery WHERE delivery_id = ?', [$deliveryId]);
    }

    public function assignableDeliveryMen(): array
    {
        return $this->fetchAll(
            "SELECT delivery_id, name, email, phone, availability FROM delivery
             WHERE verification_status = 'Approved' AND is_blocked = 0 AND availability = 'Available'
             ORDER BY name ASC"
        );
    }

    /* =========================
       REQUESTS
       ========================= */

    public function allRequests(): array
    {
        return $this->fetchAll(
            'SELECT r.request_id, r.request_type, r.status, r.created_at, r.delivery_id,
                    r.delivery_rejection_reason, r.completion_note, r.completed_at,
                    u.name AS user_name, i.title AS item_title, n.name AS ngo_name, d.name AS delivery_name
             FROM requests r
             LEFT JOIN users u ON u.user_id = r.user_id
             LEFT JOIN items i ON i.item_id = r.item_id
             LEFT JOIN ngos n ON n.ngo_id = r.ngo_id
             LEFT JOIN delivery d ON d.delivery_id = r.delivery_id
             ORDER BY r.request_id DESC'
        );
    }

    public function request(int $requestId): ?array
    {
        return $this->fetchOne('SELECT user_id, status, item_id FROM requests WHERE request_id = ? LIMIT 1', [$requestId]);
    }

    // Updates the request status and the status of its item
    public function setRequestAndItemStatus(int $requestId, string $requestStatus, string $itemStatus): void
    {
        $this->execute('UPDATE requests SET status = ? WHERE request_id = ?', [$requestStatus, $requestId]);
        $this->setRequestItemStatus($requestId, $itemStatus);
    }

    private function setRequestItemStatus(int $requestId, string $itemStatus): void
    {
        $this->execute(
            'UPDATE items SET status = ? WHERE item_id = (SELECT item_id FROM requests WHERE request_id = ?)',
            [$itemStatus, $requestId]
        );
    }

    public function cancelRequest(int $requestId, ?int $itemId): void
    {
        $this->execute("UPDATE requests SET status = 'Cancelled' WHERE request_id = ?", [$requestId]);

        if ($itemId) {
            $this->execute("UPDATE items SET status = 'Available' WHERE item_id = ?", [$itemId]);
        }
    }

    public function assignRequest(int $requestId, int $deliveryId): void
    {
        $this->execute(
            "UPDATE requests SET delivery_id = ?, status = 'Assigned', assigned_at = NOW(), delivery_rejection_reason = NULL,
                    item_received = NULL, pickup_note = NULL
             WHERE request_id = ?",
            [$deliveryId, $requestId]
        );
        $this->setRequestItemStatus($requestId, 'Requested');
        $this->setDeliveryAvailability($deliveryId, 'Busy');

        (new Notification())->create([
            'user_id' => null,
            'delivery_id' => $deliveryId,
            'message' => "A new request #$requestId has been assigned to you. Please review and accept it.",
            'status' => 'Unread',
        ]);
    }

    /* ---------- delivery man side of an assigned request ---------- */

    public function deliveryAccept(int $requestId, int $deliveryId): void
    {
        $this->execute(
            "UPDATE requests SET status = 'Accepted' WHERE request_id = ? AND delivery_id = ? AND status = 'Assigned'",
            [$requestId, $deliveryId]
        );
        $this->execute(
            "UPDATE notifications SET status = 'Read' WHERE delivery_id = ? AND message LIKE ? AND status = 'Unread'",
            [$deliveryId, "%request #$requestId%"]
        );
    }

    public function deliveryReject(int $requestId, int $deliveryId, string $reason): void
    {
        $itemId = $this->fetchValue(
            "SELECT item_id FROM requests WHERE request_id = ? AND delivery_id = ? AND status = 'Assigned' LIMIT 1",
            [$requestId, $deliveryId]
        );

        if ($itemId === null) {
            return;
        }

        $this->execute(
            "UPDATE requests SET status = 'Delivery Rejected', delivery_rejection_reason = ?
             WHERE request_id = ? AND delivery_id = ? AND status = 'Assigned'",
            [$reason, $requestId, $deliveryId]
        );
        $this->setDeliveryAvailability($deliveryId, 'Available');
        $this->setItemStatus((int)$itemId, 'Requested');
    }

    public function deliveryArrived(int $requestId, int $deliveryId): void
    {
        $this->execute(
            "UPDATE requests SET status = 'Arrived at Pickup', arrived_at = NOW() WHERE request_id = ? AND delivery_id = ? AND status = 'Accepted'",
            [$requestId, $deliveryId]
        );
    }

    public function deliveryPickedUp(int $requestId, int $deliveryId, bool $received, string $note): void
    {
        if (!$received) {
            $updated = $this->execute(
                "UPDATE requests SET status = 'Pickup Failed', item_received = 'No', pickup_note = ?, picked_up_at = NOW()
                 WHERE request_id = ? AND delivery_id = ? AND status = 'Arrived at Pickup'",
                [$note, $requestId, $deliveryId]
            );

            if ($updated > 0) {
                $this->setDeliveryAvailability($deliveryId, 'Available');
            }

            return;
        }

        $this->execute(
            "UPDATE requests SET status = 'Picked Up', item_received = 'Yes', pickup_note = ?, picked_up_at = NOW()
             WHERE request_id = ? AND delivery_id = ? AND status = 'Arrived at Pickup'",
            [$note, $requestId, $deliveryId]
        );
        $this->setRequestItemStatus($requestId, 'Collected');
    }

    public function deliveryComplete(int $requestId, int $deliveryId, string $note): void
    {
        $updated = $this->execute(
            "UPDATE requests SET status = 'Completed', completion_note = ?, completed_at = NOW()
             WHERE request_id = ? AND delivery_id = ? AND status = 'Picked Up'",
            [$note, $requestId, $deliveryId]
        );

        if ($updated > 0) {
            $this->setRequestItemStatus($requestId, 'Completed');
            $this->setDeliveryAvailability($deliveryId, 'Available');
        }
    }
}
