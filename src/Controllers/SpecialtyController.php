<?php
// src/Controllers/SpecialtyController.php

require_once __DIR__ . '/../Database.php';

class SpecialtyController {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM specialties ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM specialties WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($name) {
        $sql = "INSERT INTO specialties (name) VALUES (?)";
        $stmt = $this->pdo->prepare($sql);
        try {
            return $stmt->execute([$name]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function update($id, $name) {
        $sql = "UPDATE specialties SET name = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        try {
            return $stmt->execute([$name, $id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function delete($id) {
        // Dependencies in professional_specialties will be CASCADE deleted
        $stmt = $this->pdo->prepare("DELETE FROM specialties WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
