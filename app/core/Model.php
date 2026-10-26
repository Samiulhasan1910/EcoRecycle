<?php

/*
 * Base model: small helpers around prepared statements.
 * Every "?" in $sql is bound from $params.
 */
abstract class Model
{
    protected mysqli $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    protected function query(string $sql, array $params = []): mysqli_result|bool
    {
        if (!$params) {
            return $this->db->query($sql);
        }

        return $this->db->execute_query($sql, array_values($params));
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        $result = $this->query($sql, $params);

        return $result instanceof mysqli_result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params);

        if (!$result instanceof mysqli_result) {
            return null;
        }

        return $result->fetch_assoc() ?: null;
    }

    protected function fetchValue(string $sql, array $params = []): mixed
    {
        $result = $this->query($sql, $params);

        if (!$result instanceof mysqli_result) {
            return null;
        }

        $row = $result->fetch_row();

        return $row ? $row[0] : null;
    }

    /*
     * Runs INSERT / UPDATE / DELETE, returns affected rows
     */
    protected function execute(string $sql, array $params = []): int
    {
        $this->query($sql, $params);

        return $this->db->affected_rows;
    }

    public function lastInsertId(): int
    {
        return (int)$this->db->insert_id;
    }

    public function begin(): void
    {
        $this->db->begin_transaction();
    }

    public function commit(): void
    {
        $this->db->commit();
    }

    public function rollback(): void
    {
        $this->db->rollback();
    }

    public function error(): string
    {
        return $this->db->error;
    }

    /*
     * Does a column exist? (some pages check schema before using new columns)
     */
    public function columnExists(string $table, string $column): bool
    {
        return (bool)$this->fetchOne(
            'SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1',
            [$table, $column]
        );
    }

    public function tableExists(string $table): bool
    {
        return (bool)$this->fetchOne(
            'SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1',
            [$table]
        );
    }
}
