<?php
// src/Controllers/TherapyController.php

require_once __DIR__ . '/../Database.php';

class TherapyController {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function getAll() {
        // Fetch therapies with a count of linked professionals, filtered by branch
        $branchId = $_SESSION['branch_id'] ?? null;
        $sql = "SELECT t.*, COUNT(pt.professional_id) as professional_count 
                FROM therapies t 
                LEFT JOIN professional_therapies pt ON t.id = pt.therapy_id 
                WHERE t.branch_id = ? OR ? IS NULL
                GROUP BY t.id 
                ORDER BY t.name ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$branchId, $branchId]);
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

    public function getDocuments($therapyId) {
        $stmt = $this->pdo->prepare("SELECT * FROM therapy_documents WHERE therapy_id = ?");
        $stmt->execute([$therapyId]);
        return $stmt->fetchAll();
    }

    private function saveDocuments($therapyId, $documents) {
        // Simple full replacement for now (delete all and recreate)
        // Ideally we would sync by ID to preserve history if needed, but for configuration it's fine.
        // However, if we delete, we might lose links to patient files if we cascade delete? 
        // Schema checks: therapy_documents -> patient_documents (FK set NULL or Cascade?)
        // In my schema: FOREIGN KEY (therapy_document_id) REFERENCES therapy_documents(id) ON DELETE SET NULL
        // So it's safe to delete, but patient files will become "unlinked" (orphan label).
        // Better to try to update existing ones.
        
        $currentDocs = $this->getDocuments($therapyId);
        $currentIds = array_column($currentDocs, 'id');
        
        $keepIds = [];
        
        $sqlInsert = "INSERT INTO therapy_documents (therapy_id, name, is_required) VALUES (?, ?, ?)";
        $stmtInsert = $this->pdo->prepare($sqlInsert);
        
        $sqlUpdate = "UPDATE therapy_documents SET name = ?, is_required = ? WHERE id = ?";
        $stmtUpdate = $this->pdo->prepare($sqlUpdate);
        
        foreach ($documents as $doc) {
            $name = $doc['name'];
            $required = isset($doc['required']) ? 1 : 0;
            
            if (isset($doc['id']) && in_array($doc['id'], $currentIds)) {
                $stmtUpdate->execute([$name, $required, $doc['id']]);
                $keepIds[] = $doc['id'];
            } else {
                if (!empty($name)) {
                    $stmtInsert->execute([$therapyId, $name, $required]);
                    // New IDs are fine
                }
            }
        }
        
        // Remove deleted documents
        $toDelete = array_diff($currentIds, $keepIds);
        if (!empty($toDelete)) {
            $placeholders = implode(',', array_fill(0, count($toDelete), '?'));
            $stmtDelete = $this->pdo->prepare("DELETE FROM therapy_documents WHERE id IN ($placeholders)");
            $stmtDelete->execute(array_values($toDelete));
        }
    }

    public function create($name, $duration, $professionalIds = [], $documents = []) {
        try {
            $this->pdo->beginTransaction();

            $sql = "INSERT INTO therapies (name, default_duration_minutes) VALUES (?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$name, $duration]);
            
            $therapyId = $this->pdo->lastInsertId();
            $this->syncProfessionals($therapyId, $professionalIds);
            $this->saveDocuments($therapyId, $documents);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
    
    public function update($id, $name, $duration, $professionalIds = [], $documents = []) {
        try {
            $this->pdo->beginTransaction();

            $sql = "UPDATE therapies SET name = ?, default_duration_minutes = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$name, $duration, $id]);
            
            // Delete existing links and re-add (simple sync)
            $this->pdo->prepare("DELETE FROM professional_therapies WHERE therapy_id = ?")->execute([$id]);
            $this->syncProfessionals($id, $professionalIds);
            
            $this->saveDocuments($id, $documents);

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
    
    public function delete($id) {
        try {
            // Check for dependencies
            // Check for future appointments only (scheduled)
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM appointments WHERE therapy_id = ? AND start_time >= NOW() AND status = 'scheduled'");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                return ['success' => false, 'error' => 'Não é possível excluir: existem agendamentos futuros vinculados a esta terapia.'];
            }
            
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM package_items WHERE therapy_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                return ['success' => false, 'error' => 'Não é possível excluir: existem pacotes vinculados a esta terapia.'];
            }
            
            // Safe to delete
            $stmt = $this->pdo->prepare("DELETE FROM therapies WHERE id = ?");
            if ($stmt->execute([$id])) {
                return ['success' => true];
            }
            return ['success' => false, 'error' => 'Erro ao excluir terapia.'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
