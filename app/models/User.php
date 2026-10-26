<?php

class User extends Model
{
    public function find(int $userId): ?array
    {
        return $this->fetchOne('SELECT * FROM users WHERE user_id = ? LIMIT 1', [$userId]);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->fetchOne('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);
    }

    public function create(string $name, string $email, string $passwordHash, string $phone, string $location): int
    {
        $this->execute(
            "INSERT INTO users (name, email, password, phone, location, role) VALUES (?, ?, ?, ?, ?, 'user')",
            [$name, $email, $passwordHash, $phone, $location]
        );

        return $this->lastInsertId();
    }

    public function emailUsedByOther(string $email, int $userId): bool
    {
        return (bool)$this->fetchOne(
            'SELECT user_id FROM users WHERE email = ? AND user_id != ?',
            [$email, $userId]
        );
    }

    public function updateProfile(int $userId, string $name, string $email, string $phone, string $location): void
    {
        $this->execute(
            'UPDATE users SET name = ?, email = ?, phone = ?, location = ? WHERE user_id = ?',
            [$name, $email, $phone, $location, $userId]
        );
    }

    public function updatePassword(int $userId, string $hash): void
    {
        $this->execute('UPDATE users SET password = ? WHERE user_id = ?', [$hash, $userId]);
    }

    // Is this email used by any account type?
    public function emailTaken(string $email): bool
    {
        return (bool)$this->fetchOne(
            'SELECT 1 FROM users WHERE email = ?
             UNION SELECT 1 FROM ngos WHERE email = ?
             UNION SELECT 1 FROM delivery_man WHERE email = ?
             LIMIT 1',
            [$email, $email, $email]
        );
    }
}
