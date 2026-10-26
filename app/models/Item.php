<?php

class Item extends Model
{
    public function find(int $itemId): ?array
    {
        return $this->fetchOne('SELECT * FROM items WHERE item_id = ? LIMIT 1', [$itemId]);
    }

    public function findOwned(int $itemId, int $userId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM items WHERE item_id = ? AND user_id = ? LIMIT 1',
            [$itemId, $userId]
        );
    }

    // Latest available Sell / Donate items from other users
    public function recommendedFor(int $userId, int $limit = 6): array
    {
        return $this->fetchAll(
            "SELECT * FROM items
             WHERE status = 'Available'
               AND (user_id IS NULL OR user_id != ?)
               AND type IN ('Sell', 'Donate')
             ORDER BY item_id DESC
             LIMIT " . (int)$limit,
            [$userId]
        );
    }

    public function byOwnerAndType(int $userId, string $type): array
    {
        return $this->fetchAll(
            'SELECT * FROM items WHERE user_id = ? AND type = ? ORDER BY item_id DESC',
            [$userId, $type]
        );
    }

    public function search(int $userId, string $search, string $category, string $location): array
    {
        $where = ["status = 'Available'", '(user_id IS NULL OR user_id != ?)', "type IN ('Sell','Donate')"];
        $params = [$userId];

        if ($search !== '') {
            $where[] = '(title LIKE ? OR description LIKE ? OR category LIKE ? OR location LIKE ?)';
            array_push($params, "%$search%", "%$search%", "%$search%", "%$search%");
        }

        if ($category !== '') {
            $where[] = 'category = ?';
            $params[] = $category;
        }

        if ($location !== '') {
            $where[] = 'location LIKE ?';
            $params[] = "%$location%";
        }

        return $this->fetchAll(
            'SELECT * FROM items WHERE ' . implode(' AND ', $where) . ' ORDER BY item_id DESC',
            $params
        );
    }

    public function deleteOwned(int $itemId, int $userId, ?string $type = null): bool
    {
        $sql = 'DELETE FROM items WHERE item_id = ? AND user_id = ?';
        $params = [$itemId, $userId];

        if ($type !== null) {
            $sql .= ' AND type = ?';
            $params[] = $type;
        }

        $this->execute($sql, $params);

        return true;
    }

    // Older databases do not have items.ngo_id yet
    public function ensureNgoColumn(): void
    {
        if (!$this->columnExists('items', 'ngo_id')) {
            $this->db->query('ALTER TABLE items ADD COLUMN ngo_id INT NULL AFTER user_id');
        }
    }

    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO items (user_id, ngo_id, title, category, type, price, location, image, status, payment_method, payment_number)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['user_id'], $data['ngo_id'], $data['title'], $data['category'], $data['type'], $data['price'],
                $data['location'], $data['image'], $data['status'], $data['payment_method'], $data['payment_number'],
            ]
        );

        return $this->lastInsertId();
    }

    /*
     * Owner edit. Sold / donated / accepted-donation items are never changed.
     */
    public function updateOwned(int $itemId, int $userId, array $data): void
    {
        $this->execute(
            "UPDATE items SET title = ?, category = ?, price = ?, location = ?, image = ?, ngo_id = ?,
                    payment_method = ?, payment_number = ?
             WHERE item_id = ? AND user_id = ?
               AND LOWER(TRIM(status)) NOT IN ('sold', 'donated')
               AND NOT (LOWER(TRIM(type)) = 'donate' AND LOWER(TRIM(status)) = 'accepted')
             LIMIT 1",
            [
                $data['title'], $data['category'], $data['price'], $data['location'], $data['image'], $data['ngo_id'],
                $data['payment_method'], $data['payment_number'], $itemId, $userId,
            ]
        );
    }

    public function statusOwned(int $itemId, int $userId): ?string
    {
        $status = $this->fetchValue(
            'SELECT status FROM items WHERE item_id = ? AND user_id = ? LIMIT 1',
            [$itemId, $userId]
        );

        return $status === null ? null : (string)$status;
    }
}
