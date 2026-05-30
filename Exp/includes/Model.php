<?php
// Exp/includes/Model.php

class Model {
    protected $pdo;
    protected $table;
    protected $userId;

    public function __construct($pdo, $table, $userId) {
        $this->pdo = $pdo;
        $this->table = $table;
        $this->userId = $userId;
    }

    public function getAll($conditions = [], $orderBy = '') {
        $query = "SELECT * FROM {$this->table} WHERE user_id = ?";
        $params = [$this->userId];

        foreach ($conditions as $col => $val) {
            $query .= " AND {$col} = ?";
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
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $query = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(array_values($data));
        
        return $this->pdo->lastInsertId();
    }

    public function update($id, $data) {
        $setParts = [];
        $params = [];
        
        foreach ($data as $col => $val) {
            $setParts[] = "{$col} = ?";
            $params[] = $val;
        }
        
        $params[] = $id;
        $params[] = $this->userId;
        
        $setString = implode(', ', $setParts);
        $query = "UPDATE {$this->table} SET {$setString} WHERE id = ? AND user_id = ?";
        
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute($params);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $this->userId]);
    }

    public function deleteWhere($conditions = []) {
        $query = "DELETE FROM {$this->table} WHERE user_id = ?";
        $params = [$this->userId];
        
        foreach ($conditions as $col => $val) {
            $query .= " AND {$col} = ?";
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
