<?php

require_once __DIR__ . '/Notification.php';
require_once __DIR__ . '/DeliveryMan.php';

class Delivery extends Model
{
    // Item title / location can come from items or from a wanted offer (transactions)
    private const ITEM_JOINS = "
        LEFT JOIN items i ON i.item_id = d.item_id
        LEFT JOIN transactions t ON d.source_table = 'transactions' AND t.transaction_id = d.source_request_id
        LEFT JOIN wanted_offers wo ON wo.offer_id = t.request_id
        LEFT JOIN wanted_posts wp ON wp.wanted_id = wo.wanted_id";

    public const HISTORY_STATUSES = ['Charge Pending', 'Charge Proposed', 'Charge Accepted', 'Arrived at Pickup', 'Picked Up', 'Completed'];

    /* =========================
       DELIVERY MAN — READS
       ========================= */

    public function summaryFor(int $deliveryManId): array
    {
        return $this->fetchOne(
            "SELECT COUNT(*) AS assigned_total,
                    COALESCE(SUM(status = 'Charge Pending'), 0) AS awaiting_acceptance,
                    COALESCE(SUM(status IN ('Charge Proposed', 'Charge Accepted', 'Payment Confirmed', 'Arrived at Pickup', 'Picked Up')), 0) AS active_total,
                    COALESCE(SUM(status = 'Completed'), 0) AS completed_total
             FROM deliveries WHERE assigned_delivery_man_id = ?",
            [$deliveryManId]
        ) ?? [];
    }

    public function currentJobsFor(int $deliveryManId, int $limit = 5): array
    {
        return $this->fetchAll(
            "SELECT d.delivery_id, d.status, COALESCE(i.title, wp.title) AS item_name,
                    COALESCE(i.location, wo.location) AS pickup_location, u.name AS receiver_name
             FROM deliveries d " . self::ITEM_JOINS . "
             LEFT JOIN users u ON u.user_id = d.receiver_id
             WHERE d.assigned_delivery_man_id = ? AND d.status <> 'Completed'
             ORDER BY d.updated_at DESC, d.delivery_id DESC LIMIT " . (int)$limit,
            [$deliveryManId]
        );
    }

    // Open jobs this delivery man has not answered yet (only when he can take work)
    public function openJobsFor(int $deliveryManId): array
    {
        return $this->fetchAll(
            "SELECT d.delivery_id, COALESCE(i.title, wp.title) AS item_name, COALESCE(i.location, wo.location) AS pickup_location
             FROM deliveries d " . self::ITEM_JOINS . "
             LEFT JOIN delivery_job_responses r ON r.delivery_id = d.delivery_id AND r.delivery_man_id = ?
             INNER JOIN delivery_man dm ON dm.delivery_id = ?
             WHERE d.status = 'Open' AND r.response_id IS NULL
               AND dm.verification_status = 'Approved' AND dm.availability = 'Available' AND dm.is_blocked = 0
             ORDER BY d.created_at ASC",
            [$deliveryManId, $deliveryManId]
        );
    }

    public function jobsFor(int $deliveryManId): array
    {
        return $this->fetchAll(
            "SELECT d.delivery_id, d.status, d.proposed_charge, d.created_at, i.title AS item_name,
                    i.location AS pickup_location, u.name AS receiver_name
             FROM deliveries d
             LEFT JOIN items i ON i.item_id = d.item_id
             LEFT JOIN users u ON u.user_id = d.receiver_id
             WHERE d.assigned_delivery_man_id = ?
             ORDER BY d.updated_at DESC, d.delivery_id DESC",
            [$deliveryManId]
        );
    }

    public function assignedFor(int $deliveryManId): array
    {
        return $this->fetchAll(
            "SELECT d.delivery_id, d.status, i.title AS item_name, i.location AS pickup_location, u.name AS donor_name
             FROM deliveries d
             LEFT JOIN items i ON i.item_id = d.item_id
             LEFT JOIN users u ON u.user_id = d.receiver_id
             WHERE d.assigned_delivery_man_id = ?
             ORDER BY d.updated_at DESC, d.delivery_id DESC",
            [$deliveryManId]
        );
    }

    public function historyFor(int $deliveryManId, string $status = ''): array
    {
        $params = [$deliveryManId];
        $where = 'WHERE d.assigned_delivery_man_id = ?';

        if (in_array($status, self::HISTORY_STATUSES, true)) {
            $where .= ' AND d.status = ?';
            $params[] = $status;
        }

        return $this->fetchAll(
            "SELECT d.delivery_id, d.status, d.proposed_charge, d.accepted_charge, d.created_at, d.updated_at, d.delivered_at,
                    COALESCE(i.title, wp.title, 'Item') AS item_name, i.location AS pickup_location, u.name AS receiver_name
             FROM deliveries d " . self::ITEM_JOINS . "
             LEFT JOIN users u ON u.user_id = d.receiver_id
             $where
             ORDER BY d.updated_at DESC, d.delivery_id DESC",
            $params
        );
    }

    public function earningTotalsFor(int $deliveryManId): array
    {
        return $this->fetchOne(
            "SELECT COUNT(*) AS assigned_total,
                    COALESCE(SUM(status = 'Completed'), 0) AS completed_total,
                    COALESCE(SUM(status IN ('Charge Pending','Charge Proposed','Charge Accepted','Arrived at Pickup','Picked Up')), 0) AS active_total,
                    COALESCE(SUM(CASE WHEN status = 'Completed' THEN accepted_charge ELSE 0 END), 0) AS total_earning
             FROM deliveries WHERE assigned_delivery_man_id = ?",
            [$deliveryManId]
        ) ?? [];
    }

    public function recentCompletedFor(int $deliveryManId, int $limit = 10): array
    {
        return $this->fetchAll(
            "SELECT d.delivery_id, d.accepted_charge, d.delivered_at, COALESCE(i.title, wp.title, 'Item') AS item_name
             FROM deliveries d " . self::ITEM_JOINS . "
             WHERE d.assigned_delivery_man_id = ? AND d.status = 'Completed'
             ORDER BY d.delivered_at DESC, d.delivery_id DESC LIMIT " . (int)$limit,
            [$deliveryManId]
        );
    }

    public function hasActiveDelivery(int $deliveryManId): bool
    {
        return (bool)$this->fetchOne(
            "SELECT delivery_id FROM deliveries WHERE assigned_delivery_man_id = ? AND status NOT IN ('Completed', 'Open') LIMIT 1",
            [$deliveryManId]
        );
    }

    /* =========================
       RECEIVER (USER) — READS
       ========================= */

    public function forReceiver(int $userId): array
    {
        return $this->fetchAll(
            "SELECT d.delivery_id, d.status, d.proposed_charge, d.accepted_charge, d.charge_status, d.charge_payment_status,
                    d.created_at, d.picked_up_at, d.delivered_at, COALESCE(i.title, wp.title, 'Item') AS item_title,
                    i.location AS pickup_location, dm.name AS delivery_man_name, dm.phone AS delivery_man_phone
             FROM deliveries d " . self::ITEM_JOINS . "
             LEFT JOIN delivery_man dm ON dm.delivery_id = d.assigned_delivery_man_id
             WHERE d.receiver_id = ?
             ORDER BY d.created_at DESC, d.delivery_id DESC",
            [$userId]
        );
    }

    /* =========================
       WORKFLOW — WRITES
       ========================= */

    /*
     * Create an open delivery job for a source request and notify every available delivery man.
     * Returns the existing job id if one already exists for this source.
     */
    public function createJob(string $sourceTable, int $sourceRequestId, int $itemId, int $receiverId): int
    {
        $sourceTable = in_array($sourceTable, ['requests', 'item_requests', 'transactions'], true)
            ? $sourceTable
            : 'item_requests';

        $existing = $this->fetchValue(
            'SELECT delivery_id FROM deliveries WHERE source_table = ? AND source_request_id = ? LIMIT 1',
            [$sourceTable, $sourceRequestId]
        );

        if ($existing) {
            return (int)$existing;
        }

        $staffIds = (new DeliveryMan())->availableIds();

        $this->execute(
            "INSERT INTO deliveries (request_id, source_table, source_request_id, item_id, receiver_id, delivery_type, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, 'delivery', 'Open', NOW(), NOW())",
            [$sourceRequestId, $sourceTable, $sourceRequestId, $itemId, $receiverId]
        );

        $deliveryId = $this->lastInsertId();

        $notifications = new Notification();

        foreach ($staffIds as $deliveryManId) {
            $notifications->create([
                'user_id' => null,
                'delivery_id' => (int)$deliveryManId,
                'notification_type' => 'delivery',
                'message' => "New open delivery job #$deliveryId is available. Accept it to handle this delivery.",
                'status' => 'Unread',
            ]);
        }

        return $deliveryId;
    }

    /*
     * Delivery man accepts or rejects an open / assigned job
     */
    public function respondToJob(int $jobId, int $deliveryManId, string $action): void
    {
        $this->begin();

        try {
            $staff = $this->fetchOne(
                'SELECT availability, verification_status, is_blocked FROM delivery_man WHERE delivery_id = ? FOR UPDATE',
                [$deliveryManId]
            );
            if (!$staff) throw new Exception('Delivery man account could not be found.');

            $job = $this->fetchOne('SELECT status FROM deliveries WHERE delivery_id = ? FOR UPDATE', [$jobId]);
            if (!$job || !in_array($job['status'], ['Open', 'Assigned'], true)) throw new Exception('This job is no longer available.');

            if ($action === 'reject') {
                $this->execute(
                    "INSERT INTO delivery_job_responses (delivery_id, delivery_man_id, response_status) VALUES (?, ?, 'Rejected')
                     ON DUPLICATE KEY UPDATE response_status = 'Rejected', responded_at = NOW()",
                    [$jobId, $deliveryManId]
                );
            } else {
                if ($staff['verification_status'] !== 'Approved') throw new Exception('Only approved delivery men can accept jobs.');
                if ((int)$staff['is_blocked'] !== 0) throw new Exception('Your account is blocked and cannot accept jobs.');
                if ($staff['availability'] !== 'Available') throw new Exception('Set your availability to Available before accepting a job.');

                $active = $this->fetchOne(
                    "SELECT delivery_id FROM deliveries WHERE assigned_delivery_man_id = ? AND status NOT IN ('Completed', 'Open') LIMIT 1 FOR UPDATE",
                    [$deliveryManId]
                );
                if ($active) throw new Exception('Complete your current delivery before accepting another job.');

                $nextStatus = $job['status'] === 'Assigned' ? 'Charge Accepted' : 'Charge Pending';

                $updated = $this->execute(
                    "UPDATE deliveries SET assigned_delivery_man_id = ?, status = ?, updated_at = NOW()
                     WHERE delivery_id = ? AND status IN ('Open', 'Assigned') AND (assigned_delivery_man_id IS NULL OR assigned_delivery_man_id = ?)",
                    [$deliveryManId, $nextStatus, $jobId, $deliveryManId]
                );
                if ($updated !== 1) throw new Exception('Another delivery man already accepted this job.');

                $this->execute(
                    "UPDATE requests r INNER JOIN deliveries d ON d.request_id = r.request_id
                     SET r.status = 'Accepted', r.delivery_id = ?, r.assigned_at = NOW(), r.updated_at = NOW() WHERE d.delivery_id = ?",
                    [$deliveryManId, $jobId]
                );
                $this->execute(
                    "UPDATE pickups p INNER JOIN deliveries d ON d.request_id = p.request_id
                     SET p.delivery_id = ?, p.status = 'Accepted' WHERE d.delivery_id = ?",
                    [$deliveryManId, $jobId]
                );
                $this->execute(
                    "INSERT INTO delivery_job_responses (delivery_id, delivery_man_id, response_status) VALUES (?, ?, 'Accepted')
                     ON DUPLICATE KEY UPDATE response_status = 'Accepted', responded_at = NOW()",
                    [$jobId, $deliveryManId]
                );

                $busy = $this->execute(
                    "UPDATE delivery_man SET availability = 'Busy' WHERE delivery_id = ? AND availability = 'Available'",
                    [$deliveryManId]
                );
                if ($busy !== 1) throw new Exception('Your availability changed before this job could be assigned.');
            }

            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /*
     * Delivery man proposes a charge; receiver (or NGO) is notified
     */
    public function proposeCharge(int $jobId, int $deliveryManId, float $charge): void
    {
        $this->begin();

        try {
            $job = $this->fetchOne(
                "SELECT d.receiver_id, d.request_id, d.status, r.ngo_id
                 FROM deliveries d
                 LEFT JOIN requests r ON d.source_table = 'requests' AND r.request_id = d.request_id
                 WHERE d.delivery_id = ? AND d.assigned_delivery_man_id = ? FOR UPDATE",
                [$jobId, $deliveryManId]
            );
            if (!$job || $job['status'] !== 'Charge Pending') throw new Exception('This delivery cannot be updated.');

            $chargeText = number_format($charge, 2, '.', '');

            $this->execute(
                "UPDATE deliveries SET proposed_charge = ?, charge_status = 'Pending', status = 'Charge Proposed', updated_at = NOW() WHERE delivery_id = ?",
                [$chargeText, $jobId]
            );

            $ngoId = (int)($job['ngo_id'] ?? 0);

            (new Notification())->create([
                'user_id' => $ngoId > 0 ? null : (int)$job['receiver_id'],
                'ngo_id' => $ngoId > 0 ? $ngoId : null,
                'request_id' => (int)$job['request_id'],
                'notification_type' => 'delivery',
                'message' => "Delivery charge proposed for your delivery #$jobId: $chargeText BDT. Please accept or reject it.",
                'status' => 'Unread',
            ]);

            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /*
     * Receiver accepts / rejects the proposed charge.
     * $ngoId > 0 means the NGO owns the request, otherwise the user is the receiver.
     */
    public function respondToCharge(int $jobId, string $action, int $userId, int $ngoId): void
    {
        $this->begin();

        try {
            $where = $ngoId > 0 ? 'r.ngo_id = ?' : 'd.receiver_id = ?';

            $job = $this->fetchOne(
                "SELECT d.assigned_delivery_man_id, d.proposed_charge, d.request_id
                 FROM deliveries d
                 LEFT JOIN requests r ON d.source_table = 'requests' AND r.request_id = d.request_id
                 WHERE d.delivery_id = ? AND $where FOR UPDATE",
                [$jobId, $ngoId > 0 ? $ngoId : $userId]
            );
            if (!$job || $job['proposed_charge'] === null) throw new Exception('Delivery charge is not available.');

            $manId = (int)$job['assigned_delivery_man_id'];

            if ($action === 'accept') {
                $this->execute(
                    "UPDATE deliveries SET accepted_charge = ?, charge_status = 'Accepted', status = 'Charge Accepted',
                            charge_accepted_at = NOW(), updated_at = NOW() WHERE delivery_id = ?",
                    [(float)$job['proposed_charge'], $jobId]
                );

                $requestId = (int)$job['request_id'];

                if ($this->fetchOne('SELECT pickup_id FROM pickups WHERE request_id = ? LIMIT 1', [$requestId])) {
                    $this->execute(
                        "UPDATE pickups SET delivery_id = ?, status = 'Charge Accepted' WHERE request_id = ?",
                        [$manId, $requestId]
                    );
                } else {
                    $this->execute(
                        "INSERT INTO pickups (request_id, date, time, status, delivery_id) VALUES (?, CURDATE(), CURTIME(), 'Charge Accepted', ?)",
                        [$requestId, $manId]
                    );
                }
            } else {
                $this->execute(
                    "UPDATE deliveries SET assigned_delivery_man_id = NULL, proposed_charge = NULL, accepted_charge = NULL,
                            charge_status = 'Rejected', status = 'Open', charge_rejected_at = NOW(), updated_at = NOW()
                     WHERE delivery_id = ?",
                    [$jobId]
                );

                if ($manId > 0) {
                    (new DeliveryMan())->setAvailability($manId, 'Available');
                }
            }

            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /*
     * Move a job one step forward: arrived -> picked_up -> complete
     */
    public function advance(int $jobId, int $deliveryManId, string $action): bool
    {
        $steps = [
            'arrived'   => ['Charge Accepted', 'Arrived at Pickup', 'arrived_at'],
            'picked_up' => ['Arrived at Pickup', 'Picked Up', 'picked_up_at'],
            'complete'  => ['Picked Up', 'Completed', 'delivered_at'],
        ];

        if (!isset($steps[$action])) {
            return false;
        }

        [$from, $to, $timeColumn] = $steps[$action];

        $updated = $this->execute(
            "UPDATE deliveries SET status = ?, updated_at = NOW(), $timeColumn = NOW()
             WHERE delivery_id = ? AND assigned_delivery_man_id = ? AND status = ?",
            [$to, $jobId, $deliveryManId, $from]
        );

        if ($updated !== 1) {
            return false;
        }

        $this->execute(
            'UPDATE pickups p INNER JOIN deliveries d ON d.request_id = p.request_id SET p.status = ? WHERE d.delivery_id = ?',
            [$to, $jobId]
        );
        $this->execute(
            'UPDATE requests r INNER JOIN deliveries d ON d.request_id = r.request_id SET r.status = ?, r.updated_at = NOW() WHERE d.delivery_id = ?',
            [$to, $jobId]
        );

        if ($action === 'complete') {
            (new DeliveryMan())->setAvailability($deliveryManId, 'Available');
        }

        return true;
    }

    /*
     * NGO assigns an accepted donation request to a specific delivery man
     */
    public function assignFromNgo(int $requestId, int $ngoId, int $deliveryManId): void
    {
        $this->begin();

        try {
            $request = $this->fetchOne(
                "SELECT request_id, user_id, item_id FROM requests
                 WHERE request_id = ? AND ngo_id = ? AND request_type = 'Donation' AND status = 'Accepted' FOR UPDATE",
                [$requestId, $ngoId]
            );

            if (!$request || !(new DeliveryMan())->isAssignable($deliveryManId)) {
                throw new Exception('Select an approved and available delivery man.');
            }

            $this->execute(
                'UPDATE requests SET delivery_id = ?, assigned_at = NOW(), updated_at = NOW() WHERE request_id = ?',
                [$deliveryManId, $requestId]
            );

            $existingId = $this->fetchValue('SELECT delivery_id FROM deliveries WHERE request_id = ? LIMIT 1', [$requestId]);

            if ($existingId) {
                $this->execute(
                    "UPDATE deliveries SET assigned_delivery_man_id = ?, status = 'Assigned', updated_at = NOW() WHERE delivery_id = ?",
                    [$deliveryManId, (int)$existingId]
                );
            } else {
                $this->execute(
                    "INSERT INTO deliveries (request_id, delivery_type, status, assigned_delivery_man_id, source_table, source_request_id,
                                             item_id, receiver_id, charge_status, charge_payment_status, updated_at)
                     VALUES (?, 'delivery', 'Assigned', ?, 'requests', ?, ?, ?, 'Not Proposed', 'Unpaid', NOW())",
                    [$requestId, $deliveryManId, $requestId, (int)$request['item_id'], (int)$request['user_id']]
                );
            }

            if ($this->fetchOne('SELECT pickup_id FROM pickups WHERE request_id = ? LIMIT 1', [$requestId])) {
                $this->execute(
                    "UPDATE pickups SET delivery_id = ?, status = 'Assigned' WHERE request_id = ?",
                    [$deliveryManId, $requestId]
                );
            } else {
                $this->execute(
                    "INSERT INTO pickups (request_id, date, time, status, delivery_id) VALUES (?, CURDATE(), CURTIME(), 'Assigned', ?)",
                    [$requestId, $deliveryManId]
                );
            }

            (new Notification())->create([
                'user_id' => null,
                'delivery_id' => $deliveryManId,
                'item_id' => (int)$request['item_id'],
                'notification_type' => 'delivery',
                'message' => 'A donation pickup has been assigned to you.',
                'status' => 'Unread',
            ]);

            $this->commit();
        } catch (Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }
}
