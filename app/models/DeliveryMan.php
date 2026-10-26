<?php

class DeliveryMan extends Model
{
    public function find(int $deliveryId): ?array
    {
        return $this->fetchOne(
            'SELECT name, email, phone, location, verification_status, availability FROM delivery_man WHERE delivery_id = ? LIMIT 1',
            [$deliveryId]
        );
    }

    public function findByEmail(string $email): ?array
    {
        return $this->fetchOne('SELECT * FROM delivery_man WHERE email = ? LIMIT 1', [$email]);
    }

    public function create(array $data): int
    {
        $this->execute(
            "INSERT INTO delivery_man (name, email, password, phone, location, verification_document, verification_status, status)
             VALUES (?, ?, ?, ?, ?, ?, 'Pending', 'Available')",
            [$data['name'], $data['email'], $data['password'], $data['phone'], $data['location'], $data['verification_document']]
        );

        return $this->lastInsertId();
    }

    public function updatePassword(int $deliveryId, string $hash): void
    {
        $this->execute('UPDATE delivery_man SET password = ? WHERE delivery_id = ?', [$hash, $deliveryId]);
    }

    public function updateProfile(int $deliveryId, string $name, string $phone, string $location, ?string $passwordHash = null): bool
    {
        if ($passwordHash !== null) {
            $this->execute(
                'UPDATE delivery_man SET name = ?, phone = ?, location = ?, password = ? WHERE delivery_id = ?',
                [$name, $phone, $location, $passwordHash, $deliveryId]
            );
        } else {
            $this->execute(
                'UPDATE delivery_man SET name = ?, phone = ?, location = ? WHERE delivery_id = ?',
                [$name, $phone, $location, $deliveryId]
            );
        }

        return true;
    }

    public function availability(int $deliveryId): string
    {
        return (string)($this->fetchValue(
            'SELECT availability FROM delivery_man WHERE delivery_id = ? LIMIT 1',
            [$deliveryId]
        ) ?? 'Offline');
    }

    public function setAvailability(int $deliveryId, string $availability): void
    {
        $this->execute(
            'UPDATE delivery_man SET availability = ? WHERE delivery_id = ?',
            [$availability, $deliveryId]
        );
    }

    // Approved, available and not blocked — can take a job
    public function isAssignable(int $deliveryId): bool
    {
        return (bool)$this->fetchOne(
            "SELECT delivery_id FROM delivery_man WHERE delivery_id = ? AND verification_status = 'Approved' AND availability = 'Available' AND is_blocked = 0 LIMIT 1",
            [$deliveryId]
        );
    }

    public function availableIds(): array
    {
        return array_column($this->fetchAll(
            "SELECT delivery_id FROM delivery_man WHERE verification_status = 'Approved' AND availability = 'Available' AND is_blocked = 0 ORDER BY delivery_id ASC"
        ), 'delivery_id');
    }
}
