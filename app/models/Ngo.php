<?php

class Ngo extends Model
{
    public function find(int $ngoId): ?array
    {
        return $this->fetchOne('SELECT * FROM ngos WHERE ngo_id = ? LIMIT 1', [$ngoId]);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->fetchOne('SELECT * FROM ngos WHERE email = ? LIMIT 1', [$email]);
    }

    // [ngo_id, name] lists for select boxes
    public function approvedOptions(): array
    {
        return $this->fetchAll("SELECT ngo_id, name FROM ngos WHERE verification_status = 'Approved' ORDER BY name ASC");
    }

    public function allOptions(): array
    {
        return $this->fetchAll('SELECT ngo_id, name FROM ngos ORDER BY name ASC');
    }

    public function create(array $data): int
    {
        $this->execute(
            "INSERT INTO ngos (name, email, password, phone, location, organization_type, established_year, verification_document, verification_status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')",
            [
                $data['name'], $data['email'], $data['password'], $data['phone'], $data['location'],
                $data['organization_type'], (int)$data['established_year'], $data['verification_document'],
            ]
        );

        return $this->lastInsertId();
    }

    public function updatePassword(int $ngoId, string $hash): void
    {
        $this->execute('UPDATE ngos SET password = ? WHERE ngo_id = ?', [$hash, $ngoId]);
    }
}
