<?php

class Admin extends Model
{
    public function findByEmail(string $email): ?array
    {
        return $this->fetchOne('SELECT * FROM admins WHERE email = ? LIMIT 1', [$email]);
    }

    public function updatePassword(int $adminId, string $hash): void
    {
        $this->execute('UPDATE admins SET password = ? WHERE admin_id = ?', [$hash, $adminId]);
    }
}
