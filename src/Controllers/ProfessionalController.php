<?php
// src/Controllers/ProfessionalController.php

require_once __DIR__ . '/../Database.php';

class ProfessionalController {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM professionals ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM professionals WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($name, $specialty, $max_weekly_hours) {
        $sql = "INSERT INTO professionals (name, specialty, max_weekly_hours) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$name, $specialty, $max_weekly_hours]);
    }

    public function update($id, $name, $specialty, $max_weekly_hours) {
        $sql = "UPDATE professionals SET name = ?, specialty = ?, max_weekly_hours = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$name, $specialty, $max_weekly_hours, $id]);
    }
    
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM professionals WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
