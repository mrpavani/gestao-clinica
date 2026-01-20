<?php
// src/Controllers/BranchController.php

require_once __DIR__ . '/../Database.php';

class BranchController {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM branches WHERE active = 1 ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM branches WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($name, $address = '', $phone = '') {
        $sql = "INSERT INTO branches (name, address, phone) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        if ($stmt->execute([$name, $address, $phone])) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }

    public function update($id, $name, $address = '', $phone = '') {
        $sql = "UPDATE branches SET name = ?, address = ?, phone = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$name, $address, $phone, $id]);
    }

    public function delete($id) {
        // Check for dependencies
        $tables = ['therapies', 'patients', 'professionals', 'appointments'];
        foreach ($tables as $table) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM $table WHERE branch_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                return ['success' => false, 'error' => "Não é possível excluir: existem registros vinculados a esta filial na tabela $table."];
            }
        }

        // Safe to delete (or deactivate)
        $stmt = $this->pdo->prepare("UPDATE branches SET active = 0 WHERE id = ?");
        if ($stmt->execute([$id])) {
            return ['success' => true];
        }
        return ['success' => false, 'error' => 'Erro ao excluir filial.'];
    }

    /**
     * Get current branch from session
     */
    public static function getCurrentBranchId() {
        return $_SESSION['branch_id'] ?? null;
    }

    /**
     * Get current branch name from session
     */
    public static function getCurrentBranchName() {
        return $_SESSION['branch_name'] ?? null;
    }

    /**
     * Set current branch in session
     */
    public function selectBranch($branchId) {
        $branch = $this->getById($branchId);
        if ($branch) {
            $_SESSION['branch_id'] = $branch['id'];
            $_SESSION['branch_name'] = $branch['name'];
            return true;
        }
        return false;
    }

    /**
     * Check if branch is selected
     */
    public static function hasBranchSelected() {
        return isset($_SESSION['branch_id']) && $_SESSION['branch_id'] !== null;
    }
}
