<?php

class Model {
    protected $pdo;
    protected $table;
    protected $userId;
    private static $allowed_tables = [
        'expenses',
        'user_categories',
        'user_notes',
        'savings_goals',
        'savings_transactions',
        'category_monthly_budgets',
        'monthly_overall_budgets',
        'rate_limits',
        'security_logs'
    ];

    public function __construct($pdo, $table, $userId) {
        if (!in_array($table, self::$allowed_tables, true)) {
            throw new \InvalidArgumentException("Model: disallowed table name '{$table}'.");
        }
        $this->pdo    = $pdo;
        $this->table  = $table;
        $this->userId = $userId;
    }

    public function getAll($conditions = [], $orderBy = '') {
        $query = "SELECT * FROM `{$this->table}` WHERE user_id = ?";
        $params = [$this->userId];

        foreach ($conditions as $col => $val) {
            $safeCol = "`" . str_replace("`", "``", $col) . "`";
            $query .= " AND {$safeCol} = ?";
            $params[] = $val;
        }

        if ($orderBy) {
            $query .= " ORDER BY {$orderBy}";
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $this->userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function insert($data) {
        $data['user_id'] = $this->userId;
        $safeColumns = array_map(function($col) {
            return "`" . str_replace("`", "``", $col) . "`";
        }, array_keys($data));

        $columns      = implode(', ', $safeColumns);
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $query = "INSERT INTO `{$this->table}` ({$columns}) VALUES ({$placeholders})";
        $stmt  = $this->pdo->prepare($query);
        $stmt->execute(array_values($data));

        return $this->pdo->lastInsertId();
    }

    public function update($id, $data) {
        $setParts = [];
        $params   = [];

        foreach ($data as $col => $val) {
            $safeCol    = "`" . str_replace("`", "``", $col) . "`";
            $setParts[] = "{$safeCol} = ?";
            $params[]   = $val;
        }

        $params[] = $id;
        $params[] = $this->userId;

        $setString = implode(', ', $setParts);
        $query     = "UPDATE `{$this->table}` SET {$setString} WHERE id = ? AND user_id = ?";

        $stmt = $this->pdo->prepare($query);
        return $stmt->execute($params);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $this->userId]);
    }

    public function deleteWhere($conditions = []) {
        $query  = "DELETE FROM `{$this->table}` WHERE user_id = ?";
        $params = [$this->userId];

        foreach ($conditions as $col => $val) {
            $safeCol = "`" . str_replace("`", "``", $col) . "`";
            $query  .= " AND {$safeCol} = ?";
            $params[] = $val;
        }

        $stmt = $this->pdo->prepare($query);
        return $stmt->execute($params);
    }

    public function customQuery($query, $params = []) {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function executeQuery($query, $params = []) {
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute($params);
    }
}
