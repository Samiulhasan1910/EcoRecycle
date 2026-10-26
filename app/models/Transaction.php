<?php

require_once __DIR__ . '/Delivery.php';
require_once __DIR__ . '/Notification.php';

/*
 * transactions + payments tables
 */
class Transaction extends Model
{
    public function forUser(int $userId): array
    {
        return $this->fetchAll(
            'SELECT t.*, i.title, i.type, u.name AS other_party
             FROM transactions t
             LEFT JOIN items i ON i.item_id = t.item_id
             LEFT JOIN users u ON u.user_id = CASE WHEN t.buyer_id = ? THEN t.seller_id ELSE t.buyer_id END
             WHERE t.buyer_id = ? OR t.seller_id = ?
             ORDER BY t.transaction_id DESC',
            [$userId, $userId, $userId]
        );
    }

    private function record(int $buyerId, int $sellerId, ?int $itemId, ?int $offerId, string $method, string $amount, string $receiver): int
    {
        // Never store the payment PIN in transaction history.
        $this->execute(
            "INSERT INTO transactions (buyer_id, seller_id, item_id, request_id, payment_method, amount, receiver_number, pin_last4, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, '****', 'Completed', NOW())",
            [$buyerId, $sellerId, $itemId, $offerId, $method, $amount, $receiver]
        );

        return $this->lastInsertId();
    }

    /*
     * Pay for a Sell item: item -> Sold, transaction saved, delivery job opened.
     * Returns the delivery job id.
     */
    public function payForItem(int $buyerId, int $sellerId, int $itemId, string $itemTitle, string $method, string $amount, string $receiver): int
    {
        $this->begin();

        try {
            $status = $this->fetchValue('SELECT status FROM items WHERE item_id = ? LIMIT 1 FOR UPDATE', [$itemId]);

            if ($status === null) {
                throw new Exception('Item could not be found.');
            }

            if (strtolower(trim($status)) !== 'available') {
                throw new Exception('This item has already been sold.');
            }

            $this->execute(
                "INSERT INTO payments (request_id, amount, method, status, created_at) VALUES (NULL, ?, ?, 'Paid', NOW())",
                [$amount, $method]
            );

            if ($this->execute("UPDATE items SET status = 'Sold' WHERE item_id = ? AND status = 'Available'", [$itemId]) !== 1) {
                throw new Exception('Item could not be marked as sold.');
            }

            $transactionId = $this->record($buyerId, $sellerId, $itemId, null, $method, $amount, $receiver);

            $deliveryJobId = (new Delivery())->createJob('transactions', $transactionId, $itemId, $buyerId);

            (new Notification())->create([
                'user_id' => $sellerId,
                'request_id' => null,
                'notification_type' => 'payment',
                'message' => ($_SESSION['name'] ?? 'A user') . ' has paid for your item: ' . $itemTitle . '. The item is now sold.',
                'status' => 'Unread',
            ]);

            $this->commit();

            return $deliveryJobId;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /*
     * Wanted-post owner pays for an accepted offer: offer -> Paid, delivery job opened.
     * Returns the delivery job id.
     */
    public function payForOffer(int $buyerId, int $offerId, string $itemTitle, string $method, string $amount, string $receiver): int
    {
        $this->begin();

        try {
            $offer = $this->fetchOne(
                'SELECT wo.*, wp.user_id AS wanted_owner
                 FROM wanted_offers wo
                 LEFT JOIN wanted_posts wp ON wp.wanted_id = wo.wanted_id
                 WHERE wo.offer_id = ? LIMIT 1 FOR UPDATE',
                [$offerId]
            );

            if (!$offer) {
                throw new Exception('Offer could not be found.');
            }

            if ((int)($offer['wanted_owner'] ?? 0) !== $buyerId) {
                throw new Exception('You are not authorized to make this payment.');
            }

            $status = strtolower(trim($offer['status'] ?? ''));

            if ($status === 'paid') {
                throw new Exception('This offer has already been paid.');
            }

            if ($status !== 'accepted') {
                throw new Exception('This offer is not accepted.');
            }

            $sellerId = (int)($offer['offerer_user_id'] ?? 0);

            if ($sellerId <= 0) {
                throw new Exception('Offerer could not be found.');
            }

            if (abs(floatval($amount) - floatval($offer['price'] ?? 0)) > 0.001) {
                throw new Exception('Payment amount does not match the offer price.');
            }

            if ($this->fetchOne("SELECT transaction_id FROM transactions WHERE request_id = ? AND status = 'Completed' LIMIT 1", [$offerId])) {
                throw new Exception('Payment has already been completed for this offer.');
            }

            $this->execute(
                "INSERT INTO payments (request_id, offer_id, amount, method, status, created_at) VALUES (NULL, ?, ?, ?, 'Paid', NOW())",
                [$offerId, $amount, $method]
            );

            // Wanted: item_id = NULL, request_id = offer_id
            $transactionId = $this->record($buyerId, $sellerId, null, $offerId, $method, $amount, $receiver);

            // The wanted-post owner is the receiver of this delivery
            $deliveryJobId = (new Delivery())->createJob('transactions', $transactionId, 0, $buyerId);

            if ($this->execute("UPDATE wanted_offers SET status = 'Paid', payment_visible = 1 WHERE offer_id = ? AND status = 'Accepted'", [$offerId]) !== 1) {
                throw new Exception('Offer could not be marked as paid.');
            }

            (new Notification())->create([
                'user_id' => $sellerId,
                'request_id' => $offerId,
                'notification_type' => 'payment',
                'message' => ($_SESSION['name'] ?? 'A user') . ' has completed payment for your offer on "' . $itemTitle
                    . '". Amount: ' . number_format(floatval($amount), 2) . ' BDT.',
                'status' => 'Unread',
            ]);

            $this->commit();

            return $deliveryJobId;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
}
