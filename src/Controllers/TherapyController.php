<?php
// src/Controllers/TherapyController.php

require_once __DIR__ . '/../Database.php';

class TherapyController {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function getAll() {
        // Fetch therapies with a count of linked professionals
        $sql = "SELECT t.*, COUNT(pt.professional_id) as professional_count 
                FROM therapies t 
                LEFT JOIN professional_therapies pt ON t.id = pt.therapy_id 
                GROUP BY t.id 
                ORDER BY t.name ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM therapies WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getLinkedProfessionals($therapyId) {
        $stmt = $this->pdo->prepare("SELECT professional_id FROM professional_therapies WHERE therapy_id = ?");
        $stmt->execute([$therapyId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN); // Returns array of IDs
    }

    public function create($name, $duration, $professionalIds = []) {
        try {
            $this->pdo->beginTransaction();

            $sql = "INSERT INTO therapies (name, default_duration_minutes) VALUES (?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$name, $duration]);
            
            $therapyId = $this->pdo->lastInsertId();
            $this->syncProfessionals($therapyId, $professionalIds);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
    
    public function update($id, $name, $duration, $professionalIds = []) {
        try {
            $this->pdo->beginTransaction();

            $sql = "UPDATE therapies SET name = ?, default_duration_minutes = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$name, $duration, $id]);
            
            // Delete existing links and re-add (simple sync)
            $this->pdo->prepare("DELETE FROM professional_therapies WHERE therapy_id = ?")->execute([$id]);
            $this->syncProfessionals($id, $professionalIds);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
    
    private function syncProfessionals($therapyId, $professionalIds) {
        if (!empty($professionalIds)) {
            $sqlLink = "INSERT INTO professional_therapies (professional_id, therapy_id) VALUES (?, ?)";
            $stmtLink = $this->pdo->prepare($sqlLink);
            foreach ($professionalIds as $profId) {
                $stmtLink->execute([$profId, $therapyId]);
            }
        }
    }
    
    public function getAvailableProfessionals() {
        $stmt = $this->pdo->query("SELECT id, name, specialty FROM professionals ORDER BY name ASC");
        return $stmt->fetchAll();
    }
}
