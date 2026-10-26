<?php

/*
 * Offers made on wanted posts
 */
class WantedOffer extends Model
{
    public function idForOfferer(int $wantedId, int $userId): int
    {
        return (int)$this->fetchValue(
            'SELECT offer_id FROM wanted_offers WHERE wanted_id = ? AND offerer_user_id = ? LIMIT 1',
            [$wantedId, $userId]
        );
    }

    public function forWanted(int $wantedId): array
    {
        return $this->fetchAll(
            'SELECT wo.*, u.name AS offerer_name, u.phone AS offerer_phone, i.title AS item_title
             FROM wanted_offers wo
             LEFT JOIN users u ON wo.offerer_user_id = u.user_id
             LEFT JOIN items i ON wo.item_id = i.item_id
             WHERE wo.wanted_id = ?
             ORDER BY wo.created_at DESC',
            [$wantedId]
        );
    }

    public function find(int $offerId): ?array
    {
        return $this->fetchOne('SELECT * FROM wanted_offers WHERE offer_id = ? LIMIT 1', [$offerId]);
    }

    /*
     * New Pending offer; free offers have no price / payment info.
     * Returns the new offer id.
     */
    public function create(array $data): int
    {
        $this->execute(
            "INSERT INTO wanted_offers (wanted_id, offerer_user_id, item_id, image, location, price, is_free,
                                        payment_method, payment_number, status, payment_visible)
             VALUES (?, ?, NULL, ?, ?, ?, ?, ?, ?, 'Pending', 0)",
            [
                $data['wanted_id'], $data['offerer_user_id'], $data['image'], $data['location'], $data['price'],
                $data['is_free'], $data['payment_method'], $data['payment_number'],
            ]
        );

        return $this->lastInsertId();
    }

    private function lockPending(int $offerId): void
    {
        $current = $this->fetchOne('SELECT * FROM wanted_offers WHERE offer_id = ? FOR UPDATE', [$offerId]);

        if (!$current) {
            throw new Exception('Offer not found during transaction.');
        }

        if ($current['status'] !== 'Pending') {
            throw new Exception('This offer has already been processed.');
        }
    }

    private function notify(int $userId, int $offerId, string $message): void
    {
        $this->execute(
            "INSERT INTO notifications (user_id, request_id, notification_type, message, status) VALUES (?, ?, 'wanted_offer', ?, 'Unread')",
            [$userId, $offerId, $message]
        );
    }

    /*
     * Wanted owner accepts one offer: all other pending offers are rejected,
     * the wanted post becomes Accepted and every offerer is notified.
     */
    public function accept(array $offer, array $wanted): void
    {
        $offerId = (int)$offer['offer_id'];
        $wantedId = (int)$wanted['wanted_id'];

        $this->begin();

        try {
            $this->lockPending($offerId);

            $this->execute(
                "UPDATE wanted_offers SET status = 'Accepted', payment_visible = 1
                 WHERE offer_id = ? AND wanted_id = ? AND status = 'Pending'",
                [$offerId, $wantedId]
            );

            $others = $this->fetchAll(
                "SELECT offer_id, offerer_user_id FROM wanted_offers WHERE wanted_id = ? AND offer_id != ? AND status = 'Pending'",
                [$wantedId, $offerId]
            );

            $this->execute(
                "UPDATE wanted_offers SET status = 'Rejected', payment_visible = 0
                 WHERE wanted_id = ? AND offer_id != ? AND status = 'Pending'",
                [$wantedId, $offerId]
            );

            // No items table update here
            $this->execute("UPDATE wanted_posts SET status = 'Accepted' WHERE wanted_id = ?", [$wantedId]);

            $this->notify((int)$offer['offerer_user_id'], $offerId, 'Your offer was accepted for wanted post: ' . $wanted['title']);

            foreach ($others as $other) {
                $this->notify((int)$other['offerer_user_id'], (int)$other['offer_id'], 'Your offer was rejected for wanted post: ' . $wanted['title']);
            }

            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function reject(array $offer, array $wanted): void
    {
        $offerId = (int)$offer['offer_id'];

        $this->begin();

        try {
            $this->lockPending($offerId);

            $this->execute(
                "UPDATE wanted_offers SET status = 'Rejected', payment_visible = 0
                 WHERE offer_id = ? AND wanted_id = ? AND status = 'Pending'",
                [$offerId, (int)$wanted['wanted_id']]
            );

            $this->notify((int)$offer['offerer_user_id'], $offerId, 'Your offer was rejected for wanted post: ' . $wanted['title']);

            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    // Offer + the wanted post it belongs to
    public function findWithPost(int $offerId): ?array
    {
        return $this->fetchOne(
            'SELECT wo.*, wp.user_id AS wanted_owner, wp.title AS wanted_title, wp.location AS wanted_location,
                    wp.status AS wanted_post_status
             FROM wanted_offers wo
             LEFT JOIN wanted_posts wp ON wp.wanted_id = wo.wanted_id
             WHERE wo.offer_id = ?
             LIMIT 1',
            [$offerId]
        );
    }
}
