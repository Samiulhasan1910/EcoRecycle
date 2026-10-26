<?php

/*
 * "requests" table: donation / NGO requests
 */
class Request extends Model
{
    /* =========================
       USER DONATE REQUESTS
       ========================= */

    public function forUser(int $userId): array
    {
        return $this->fetchAll(
            'SELECT r.request_id, r.user_id, r.item_id, r.ngo_id, r.request_type, r.status,
                    i.title, i.category, i.type AS item_type, i.price, i.location, i.status AS item_status, i.image
             FROM requests r
             LEFT JOIN items i ON r.item_id = i.item_id
             WHERE r.user_id = ?
             ORDER BY r.request_id DESC',
            [$userId]
        );
    }

    public function createDonate(int $userId, int $itemId): int
    {
        $this->execute(
            "INSERT INTO requests (user_id, item_id, ngo_id, request_type, status) VALUES (?, ?, NULL, 'Donate', 'Pending')",
            [$userId, $itemId]
        );

        return $this->lastInsertId();
    }

    // Donate request on an item owned by $ownerId
    public function donateRequestForOwner(int $requestId, int $ownerId): ?array
    {
        return $this->fetchOne(
            "SELECT r.request_id, r.user_id AS requester_id, r.item_id, r.request_type, r.status AS request_status,
                    i.user_id AS owner_id, i.title AS item_title, i.type AS item_type, i.status AS item_status
             FROM requests r
             INNER JOIN items i ON i.item_id = r.item_id
             WHERE r.request_id = ? AND r.request_type = 'Donate' AND i.user_id = ?
             LIMIT 1",
            [$requestId, $ownerId]
        );
    }

    /*
     * Owner accepts a Donate request:
     * request -> Accepted, item -> Donated, delivery job opened, other requests rejected.
     */
    public function acceptDonate(array $request, int $ownerId): void
    {
        $requestId = (int)$request['request_id'];
        $itemId = (int)$request['item_id'];
        $requesterId = (int)$request['requester_id'];
        $itemTitle = $request['item_title'];

        $this->begin();

        try {
            // Lock the item so two clicks cannot accept two requests
            $locked = $this->fetchOne(
                "SELECT item_id, status FROM items WHERE item_id = ? AND user_id = ? AND type = 'Donate' FOR UPDATE",
                [$itemId, $ownerId]
            );

            if (!$locked) {
                throw new Exception('Donation item was not found or you are not its owner.');
            }

            if (strtolower(trim($locked['status'])) !== 'available') {
                throw new Exception('This donated item is no longer available.');
            }

            $alreadyAccepted = $this->fetchOne(
                "SELECT request_id FROM requests
                 WHERE item_id = ? AND request_type = 'Donate' AND status = 'Accepted' AND request_id != ?
                 LIMIT 1 FOR UPDATE",
                [$itemId, $requestId]
            );

            if ($alreadyAccepted) {
                throw new Exception('This donated item has already been accepted by another requester.');
            }

            if ($this->execute("UPDATE requests SET status = 'Accepted' WHERE request_id = ? AND status = 'Pending'", [$requestId]) !== 1) {
                throw new Exception('This request is no longer pending.');
            }

            $itemUpdated = $this->execute(
                "UPDATE items SET status = 'Donated'
                 WHERE item_id = ? AND user_id = ? AND type = 'Donate' AND status = 'Available'",
                [$itemId, $ownerId]
            );

            if ($itemUpdated == 0) {
                $debugItem = $this->fetchOne('SELECT item_id, user_id, type, status FROM items WHERE item_id = ? LIMIT 1', [$itemId]);

                if ($debugItem) {
                    throw new Exception(
                        'Item was found, but status was not changed. item_id=' . $debugItem['item_id']
                        . ', owner=' . $debugItem['user_id'] . ', type=' . $debugItem['type'] . ', status=' . $debugItem['status']
                    );
                }

                throw new Exception("Item #$itemId was not found.");
            }

            require_once __DIR__ . '/Delivery.php';
            (new Delivery())->createJob('requests', $requestId, $itemId, $requesterId);

            $this->execute(
                "UPDATE requests SET status = 'Rejected'
                 WHERE item_id = ? AND request_type = 'Donate' AND status = 'Pending' AND request_id != ?",
                [$itemId, $requestId]
            );

            $this->closeOwnerNotification($ownerId, $requestId, 'You accepted the donation request for your item: ' . $itemTitle . '.');
            $this->notifyRequester($requesterId, $requestId, "Your request for the donated item '" . $itemTitle . "' has been accepted.");

            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function rejectDonate(array $request, int $ownerId): void
    {
        $requestId = (int)$request['request_id'];
        $itemTitle = $request['item_title'];

        $this->begin();

        try {
            $this->execute("UPDATE requests SET status = 'Rejected' WHERE request_id = ? AND status = 'Pending'", [$requestId]);

            $this->closeOwnerNotification($ownerId, $requestId, 'You rejected the donation request for your item: ' . $itemTitle . '.');
            $this->notifyRequester((int)$request['requester_id'], $requestId, "Your request for the donated item '" . $itemTitle . "' has been rejected.");

            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /*
     * Donor answers an NGO's request for one of their public donations
     */
    public function answerNgoRequest(int $requestId, int $userId, string $action, int $notificationId): void
    {
        $this->begin();

        try {
            $request = $this->fetchOne(
                "SELECT r.request_id, r.ngo_id, r.item_id, r.status, i.title, i.status AS item_status
                 FROM requests r
                 INNER JOIN items i ON i.item_id = r.item_id
                 WHERE r.request_id = ? AND r.request_type = 'NGO Donation' AND r.user_id = ? AND i.user_id = ?
                 FOR UPDATE",
                [$requestId, $userId, $userId]
            );

            if (!$request || strtolower($request['status'] ?? '') !== 'pending') {
                throw new Exception('This NGO request is no longer pending.');
            }

            $ngoId = (int)$request['ngo_id'];
            $itemId = (int)$request['item_id'];
            $itemTitle = $request['title'] ?? 'item';

            if ($action === 'accept') {
                if (strtolower($request['item_status'] ?? '') !== 'available') {
                    throw new Exception('This donation is no longer available.');
                }

                $this->execute("UPDATE requests SET status = 'Accepted' WHERE request_id = ? AND status = 'Pending'", [$requestId]);
                $this->execute(
                    "UPDATE items SET status = 'Donated', ngo_id = ? WHERE item_id = ? AND user_id = ? AND status = 'Available'",
                    [$ngoId, $itemId, $userId]
                );

                require_once __DIR__ . '/Delivery.php';
                (new Delivery())->createJob('requests', $requestId, $itemId, $userId);

                $message = "The donor accepted your request for '$itemTitle'. A delivery request is now open.";
            } else {
                $this->execute("UPDATE requests SET status = 'Rejected' WHERE request_id = ? AND status = 'Pending'", [$requestId]);

                $message = "The donor rejected your request for '$itemTitle'.";
            }

            $this->execute(
                "INSERT INTO notifications (user_id, ngo_id, item_id, request_id, notification_type, message, status)
                 VALUES (NULL, ?, ?, ?, 'ngo_request_response', ?, 'Unread')",
                [$ngoId, $itemId, $requestId, $message]
            );

            if ($notificationId > 0) {
                $this->execute(
                    "UPDATE notifications SET status = 'Read' WHERE notification_id = ? AND user_id = ?",
                    [$notificationId, $userId]
                );
            }

            $this->commit();
        } catch (Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    private function closeOwnerNotification(int $ownerId, int $requestId, string $message): void
    {
        $this->execute(
            "UPDATE notifications SET message = ?, status = 'Read'
             WHERE user_id = ? AND request_id = ? AND notification_type = 'request'",
            [$message, $ownerId, $requestId]
        );
    }

    private function notifyRequester(int $requesterId, int $requestId, string $message): void
    {
        $this->execute(
            "INSERT INTO notifications (user_id, request_id, notification_type, message, status) VALUES (?, ?, 'request', ?, 'Unread')",
            [$requesterId, $requestId, $message]
        );
    }

    // The user's latest Donate request for an item
    public function latestDonateRequest(int $itemId, int $userId): ?array
    {
        return $this->fetchOne(
            "SELECT request_id, status FROM requests
             WHERE item_id = ? AND user_id = ? AND request_type = 'Donate'
             ORDER BY request_id DESC LIMIT 1",
            [$itemId, $userId]
        );
    }

    // Request + item + requester info shown next to a user notification
    public function notificationContext(int $requestId): ?array
    {
        return $this->fetchOne(
            'SELECT r.request_id, r.user_id AS requester_id, r.item_id, r.request_type, r.status AS request_status,
                    i.user_id AS owner_id, i.title AS item_title, i.category AS item_category, i.type AS item_type,
                    u.name AS requester_name
             FROM requests r
             LEFT JOIN items i ON i.item_id = r.item_id
             LEFT JOIN users u ON u.user_id = r.user_id
             WHERE r.request_id = ?
             LIMIT 1',
            [$requestId]
        );
    }

    /* =========================
       DELIVERY MAN
       ========================= */

    public function assignedToDeliveryMan(int $deliveryManId): array
    {
        return $this->fetchAll(
            'SELECT r.request_id, r.status, r.assigned_at, i.title AS item_name, i.location AS pickup_location, n.name AS ngo_name
             FROM requests r
             LEFT JOIN items i ON i.item_id = r.item_id
             LEFT JOIN ngos n ON n.ngo_id = r.ngo_id
             WHERE r.delivery_id = ?
             ORDER BY r.updated_at DESC, r.request_id DESC',
            [$deliveryManId]
        );
    }

    public function detailForDeliveryMan(int $requestId, int $deliveryManId): ?array
    {
        return $this->fetchOne(
            'SELECT r.request_id, r.request_type, r.status, r.created_at, r.assigned_at,
                    r.delivery_rejection_reason, r.completion_note, r.completed_at,
                    r.arrived_at, r.item_received, r.pickup_note, r.picked_up_at,
                    i.title AS item_name, i.category AS item_category, i.type AS item_condition,
                    i.location AS pickup_location, u.name AS owner_name, u.phone AS owner_phone,
                    n.name AS ngo_name, n.location AS ngo_address, n.phone AS ngo_phone
             FROM requests r
             LEFT JOIN items i ON i.item_id = r.item_id
             LEFT JOIN users u ON u.user_id = r.user_id
             LEFT JOIN ngos n ON n.ngo_id = r.ngo_id
             WHERE r.request_id = ? AND r.delivery_id = ?
             LIMIT 1',
            [$requestId, $deliveryManId]
        );
    }
}
