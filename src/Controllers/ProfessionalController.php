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

    public function create($name, $specialty, $max_weekly_hours, $email = null) {
        try {
            $sql = "INSERT INTO professionals (name, specialty, email, max_weekly_hours) VALUES (?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$name, $specialty, $email, $max_weekly_hours]);
            return $result ? $this->pdo->lastInsertId() : false;
        } catch (PDOException $e) {
            // Check for duplicate entry
            if ($e->getCode() == 23000) {
                return false;
            }
            throw $e;
        }
    }

    public function update($id, $name, $specialty, $max_weekly_hours, $email = null) {
        try {
            $sql = "UPDATE professionals SET name = ?, specialty = ?, email = ?, max_weekly_hours = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$name, $specialty, $email, $max_weekly_hours, $id]);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return false;
            }
            throw $e;
        }
    }
    
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM professionals WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    // Skills Management
    public function addSkill($professional_id, $skill_name, $skill_type = 'specialty') {
        try {
            $sql = "INSERT INTO professional_skills (professional_id, skill_name, skill_type) VALUES (?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$professional_id, $skill_name, $skill_type]);
        } catch (PDOException $e) {
            // Duplicate skill, ignore
            if ($e->getCode() == 23000) {
                return true;
            }
            throw $e;
        }
    }
    
    public function removeSkill($skill_id) {
        $stmt = $this->pdo->prepare("DELETE FROM professional_skills WHERE id = ?");
        return $stmt->execute([$skill_id]);
    }
    
    public function getSkills($professional_id) {
        $sql = "SELECT * FROM professional_skills WHERE professional_id = ? ORDER BY skill_type, skill_name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$professional_id]);
        return $stmt->fetchAll();
    }
    
    public function getAllWithSkills() {
        $professionals = $this->getAll();
        foreach ($professionals as &$prof) {
            $prof['skills'] = $this->getSkills($prof['id']);
        }
        return $professionals;
    }
}
