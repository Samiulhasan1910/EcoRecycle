<?php

class WantedPost extends Model
{
    public function find(int $wantedId): ?array
    {
        return $this->fetchOne('SELECT * FROM wanted_posts WHERE wanted_id = ? LIMIT 1', [$wantedId]);
    }

    // Active wanted posts from other users matching the search filters
    public function search(int $userId, string $search, string $category, string $location): array
    {
        $where = ["status = 'Active'", 'user_id != ?'];
        $params = [$userId];

        if ($search !== '') {
            $where[] = '(title LIKE ? OR category LIKE ? OR location LIKE ? OR description LIKE ?)';
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
            'SELECT * FROM wanted_posts WHERE ' . implode(' AND ', $where) . ' ORDER BY wanted_id DESC',
            $params
        );
    }

    public function findOwned(int $wantedId, int $userId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM wanted_posts WHERE wanted_id = ? AND user_id = ? LIMIT 1',
            [$wantedId, $userId]
        );
    }

    public function byOwner(int $userId): array
    {
        return $this->fetchAll('SELECT * FROM wanted_posts WHERE user_id = ? ORDER BY wanted_id DESC', [$userId]);
    }

    public function create(int $userId, string $title, string $category, string $location, string $description, string $image): int
    {
        $this->execute(
            "INSERT INTO wanted_posts (user_id, title, category, location, description, status, image)
             VALUES (?, ?, ?, ?, ?, 'Active', ?)",
            [$userId, $title, $category, $location, $description, $image]
        );

        return $this->lastInsertId();
    }

    /*
     * Owner edit; accepted / paid posts are never changed.
     * $image = null keeps the current image.
     */
    public function updateOwned(int $wantedId, int $userId, array $data, ?string $image = null): void
    {
        $set = 'title = ?, category = ?, location = ?, description = ?, status = ?';
        $params = [$data['title'], $data['category'], $data['location'], $data['description'], $data['status']];

        if ($image !== null) {
            $set .= ', image = ?';
            $params[] = $image;
        }

        array_push($params, $wantedId, $userId);

        $this->execute(
            "UPDATE wanted_posts SET $set
             WHERE wanted_id = ? AND user_id = ? AND LOWER(TRIM(status)) NOT IN ('accepted', 'paid')
             LIMIT 1",
            $params
        );
    }

    /*
     * Public list: active wanted posts + available donate items, optional category filter
     */
    public function publicPosts(string $category = ''): array
    {
        $sql = "SELECT post_id, title, category, location, description, status, ngo_name
                FROM (
                    SELECT wanted_posts.wanted_id AS post_id, wanted_posts.title, wanted_posts.category,
                           wanted_posts.location, wanted_posts.description, wanted_posts.status, NULL AS ngo_name
                    FROM wanted_posts
                    WHERE LOWER(wanted_posts.status) = 'active'

                    UNION ALL

                    SELECT items.item_id AS post_id, items.title, items.category, items.location,
                           items.description, 'Active' AS status, ngos.name AS ngo_name
                    FROM items
                    LEFT JOIN ngos ON ngos.ngo_id = items.ngo_id
                    WHERE items.type = 'Donate' AND LOWER(items.status) = 'available'
                ) AS all_posts
                WHERE 1";
        $params = [];

        if ($category !== '') {
            $sql .= ' AND LOWER(TRIM(category)) = LOWER(TRIM(?))';
            $params[] = $category;
        }

        return $this->fetchAll($sql . ' ORDER BY post_id DESC', $params);
    }

    public function publicCategories(): array
    {
        return $this->fetchAll(
            "SELECT category FROM wanted_posts WHERE category IS NOT NULL AND category != ''
             UNION
             SELECT category FROM items WHERE type = 'Donate' AND status = 'Available' AND category IS NOT NULL AND category != ''
             ORDER BY category ASC"
        );
    }

    public function deleteOwned(int $wantedId, int $userId): void
    {
        $this->execute('DELETE FROM wanted_posts WHERE wanted_id = ? AND user_id = ?', [$wantedId, $userId]);
    }
}
