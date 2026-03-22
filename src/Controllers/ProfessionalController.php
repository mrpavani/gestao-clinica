<?php
// src/Controllers/ProfessionalController.php

require_once __DIR__ . '/../Database.php';

class ProfessionalController {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function getAll() {
        $branchId = $_SESSION['branch_id'] ?? null;
        if ($branchId) {
            $stmt = $this->pdo->prepare("SELECT * FROM professionals WHERE branch_id = ? ORDER BY name ASC");
            $stmt->execute([$branchId]);
        } else {
            $stmt = $this->pdo->query("SELECT * FROM professionals ORDER BY name ASC");
        }
        $professionals = $stmt->fetchAll();

        if (empty($professionals)) {
            return $professionals;
        }

        // Load all specialties in a single query (avoids N+1)
        $ids = array_column($professionals, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmtSpec = $this->pdo->prepare("
            SELECT s.*, ps.professional_id
            FROM specialties s
            JOIN professional_specialties ps ON s.id = ps.specialty_id
            WHERE ps.professional_id IN ($placeholders)
        ");
        $stmtSpec->execute($ids);
        $allSpecialties = $stmtSpec->fetchAll();

        // Index specialties by professional_id
        $specialtiesByProf = [];
        foreach ($allSpecialties as $spec) {
            $specialtiesByProf[$spec['professional_id']][] = $spec;
        }

        foreach ($professionals as &$prof) {
            $prof['specialties'] = $specialtiesByProf[$prof['id']] ?? [];
        }
        return $professionals;
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM professionals WHERE id = ?");
        $stmt->execute([$id]);
        $prof = $stmt->fetch();
        
        if ($prof) {
            $stmtSpec = $this->pdo->prepare("
                SELECT s.* 
                FROM specialties s 
                JOIN professional_specialties ps ON s.id = ps.specialty_id 
                WHERE ps.professional_id = ?
            ");
            $stmtSpec->execute([$id]);
            $prof['specialties'] = $stmtSpec->fetchAll();
        }
        
        return $prof;
    }

    public function create($name, $specialtiesIds = []) {
        try {
            $this->pdo->beginTransaction();
            $branchId = $_SESSION['branch_id'] ?? null;
            $sql = "INSERT INTO professionals (name, branch_id) VALUES (?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$name, $branchId]);
            $profId = $this->pdo->lastInsertId();
            
            if (!empty($specialtiesIds)) {
                $sqlSpec = "INSERT INTO professional_specialties (professional_id, specialty_id) VALUES (?, ?)";
                $stmtSpec = $this->pdo->prepare($sqlSpec);
                foreach ($specialtiesIds as $sid) {
                    $stmtSpec->execute([$profId, $sid]);
                }
            }
            
            $this->pdo->commit();
            return $profId;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            if ($e->getCode() == 23000) {
                return false;
            }
            throw $e;
        }
    }

    public function update($id, $name, $specialtiesIds = []) {
        try {
            $this->pdo->beginTransaction();
            $sql = "UPDATE professionals SET name = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$name, $id]);

            // Replace specialties
            $this->pdo->prepare("DELETE FROM professional_specialties WHERE professional_id = ?")->execute([$id]);
            
            if (!empty($specialtiesIds)) {
                $sqlSpec = "INSERT INTO professional_specialties (professional_id, specialty_id) VALUES (?, ?)";
                $stmtSpec = $this->pdo->prepare($sqlSpec);
                foreach ($specialtiesIds as $sid) {
                    $stmtSpec->execute([$id, $sid]);
                }
            }

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            if ($e->getCode() == 23000) {
                return false;
            }
            throw $e;
        }
    }

    public function changeBranch($id, $newBranchId) {
        $sql = "UPDATE professionals SET branch_id = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$newBranchId, $id]);
    }
    
    
    public function delete($id) {
        try {
            // Check for future appointments only (scheduled)
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM appointments WHERE professional_id = ? AND start_time >= NOW() AND status = 'scheduled'");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                return ['success' => false, 'error' => 'Não é possível excluir: o profissional possui agendamentos futuros.'];
            }
            
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE professional_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                return ['success' => false, 'error' => 'Não é possível excluir: existe um usuário vinculado a este profissional.'];
            }
            
            // Safe to delete (CASCADE will handle professional_skills and professional_therapies)
            $stmt = $this->pdo->prepare("DELETE FROM professionals WHERE id = ?");
            if ($stmt->execute([$id])) {
                return ['success' => true];
            }
            return ['success' => false, 'error' => 'Erro ao excluir profissional.'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
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

        if (empty($professionals)) {
            return $professionals;
        }

        // Load all skills in a single query (avoids N+1)
        $ids = array_column($professionals, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmtSkills = $this->pdo->prepare(
            "SELECT * FROM professional_skills WHERE professional_id IN ($placeholders) ORDER BY skill_type, skill_name"
        );
        $stmtSkills->execute($ids);
        $allSkills = $stmtSkills->fetchAll();

        $skillsByProf = [];
        foreach ($allSkills as $skill) {
            $skillsByProf[$skill['professional_id']][] = $skill;
        }

        foreach ($professionals as &$prof) {
            $prof['skills'] = $skillsByProf[$prof['id']] ?? [];
        }
        return $professionals;
    }

    // Schedule Management
    public function getSchedules($professional_id) {
        $sql = "SELECT * FROM professional_schedules WHERE professional_id = ? ORDER BY day_of_week ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$professional_id]);
        return $stmt->fetchAll();
    }

    public function saveSchedules($professional_id, $schedules) {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("DELETE FROM professional_schedules WHERE professional_id = ?");
            $stmt->execute([$professional_id]);

            $sql = "INSERT INTO professional_schedules (professional_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($schedules as $day => $time) {
                if (!empty($time['active']) && !empty($time['start']) && !empty($time['end'])) {
                    $stmt->execute([$professional_id, $day, $time['start'], $time['end']]);
                }
            }
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
}
