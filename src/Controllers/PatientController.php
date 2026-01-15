<?php
// src/Controllers/PatientController.php

require_once __DIR__ . '/../Database.php';

class PatientController {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM patients ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM patients WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($name, $dob, $guardian, $contact) {
        $sql = "INSERT INTO patients (name, dob, guardian_name, contact_info) VALUES (?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        if ($stmt->execute([$name, $dob, $guardian, $contact])) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }
    
    public function update($id, $name, $dob, $guardian, $contact) {
        $sql = "UPDATE patients SET name = ?, dob = ?, guardian_name = ?, contact_info = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$name, $dob, $guardian, $contact, $id]);
    }

    public function updateStatus($id, $status, $reason = null) {
        $sql = "UPDATE patients SET status = ?, pause_reason = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$status, $reason, $id]);
    }

    /**
     * Creates a patient with contract and PEI in a single transaction
     */
    public function createFullPatient($patientData, $contractData, $therapiesData) {
        try {
            $this->pdo->beginTransaction();

            // 1. Create Patient
            $sql = "INSERT INTO patients (name, dob, guardian_name, contact_info) VALUES (?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $patientData['name'], 
                $patientData['dob'], 
                $patientData['guardian_name'], 
                $patientData['contact_info']
            ]);
            $patientId = $this->pdo->lastInsertId();

            // 2. Create Patient Package (Contract)
            $sqlPkg = "INSERT INTO patient_packages (patient_id, start_date, end_date) VALUES (?, ?, ?)";
            $stmtPkg = $this->pdo->prepare($sqlPkg);
            $stmtPkg->execute([
                $patientId, 
                $contractData['start_date'], 
                $contractData['end_date']
            ]);
            $packageId = $this->pdo->lastInsertId();

            // 3. Process Therapies (Package Items & PEI)
            if (!empty($therapiesData)) {
                $sqlItem = "INSERT INTO package_items (package_id, therapy_id, sessions_per_month) VALUES (?, ?, ?)";
                $stmtItem = $this->pdo->prepare($sqlItem);

                $sqlPei = "INSERT INTO patient_planning (patient_id, year, therapy_id, goals) VALUES (?, ?, ?, ?)";
                $stmtPei = $this->pdo->prepare($sqlPei);
                $currentYear = date('Y');

                foreach ($therapiesData as $item) {
                    // Add to Package
                    if ($item['sessions'] > 0) {
                        $stmtItem->execute([$packageId, $item['therapy_id'], $item['sessions']]);
                    }

                    // Add PEI (if goals provided or just to initialize)
                    if (!empty($item['goals'])) {
                        $stmtPei->execute([$patientId, $currentYear, $item['therapy_id'], $item['goals']]);
                    }
                }
            }

            $this->pdo->commit();
            return $patientId;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            // Re-throw or return false depending on how we want to handle errors
            // If it's a duplicate entry, existing logic elsewhere might want to know
            if ($e->getCode() == 23000) {
                 // Duplicate entry
                 return false;
            }
            throw $e;
        }
    }

    // Packages Logic
    public function createPackage($patientId, $startDate, $endDate, $items = []) {
        try {
            $this->pdo->beginTransaction();

            $sql = "INSERT INTO patient_packages (patient_id, start_date, end_date) VALUES (?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$patientId, $startDate, $endDate]);
            $packageId = $this->pdo->lastInsertId();

            if (!empty($items)) {
                $sqlItem = "INSERT INTO package_items (package_id, therapy_id, sessions_per_month) VALUES (?, ?, ?)";
                $stmtItem = $this->pdo->prepare($sqlItem);
                foreach ($items as $item) {
                    if ($item['sessions'] > 0) {
                        $stmtItem->execute([$packageId, $item['therapy_id'], $item['sessions']]);
                    }
                }
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
    
    public function getPackageById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM patient_packages WHERE id = ?");
        $stmt->execute([$id]);
        $pkg = $stmt->fetch();
        if ($pkg) {
             $sqlItems = "SELECT pi.*, t.name as therapy_name 
                         FROM package_items pi 
                         JOIN therapies t ON pi.therapy_id = t.id 
                         WHERE pi.package_id = ?";
            $stmtItems = $this->pdo->prepare($sqlItems);
            $stmtItems->execute([$id]);
            $pkg['items'] = $stmtItems->fetchAll();
        }
        return $pkg;
    }

    public function updatePackage($id, $startDate, $endDate, $items = []) {
        try {
            $this->pdo->beginTransaction();
            
            // Update Package Dates
            $stmt = $this->pdo->prepare("UPDATE patient_packages SET start_date = ?, end_date = ? WHERE id = ?");
            $stmt->execute([$startDate, $endDate, $id]);
            
            // Update Items (simplest is delete all and re-insert, but that changes IDs. 
            // Better to upsert or just update 'sessions_per_month' if exists)
            // For now, let's delete and re-insert to handle removed items easily.
            $this->pdo->prepare("DELETE FROM package_items WHERE package_id = ?")->execute([$id]);
            
            if (!empty($items)) {
                $sqlItem = "INSERT INTO package_items (package_id, therapy_id, sessions_per_month) VALUES (?, ?, ?)";
                $stmtItem = $this->pdo->prepare($sqlItem);
                foreach ($items as $item) {
                    if ($item['sessions'] > 0) {
                        $stmtItem->execute([$id, $item['therapy_id'], $item['sessions']]);
                    }
                }
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function getPackages($patientId) {
        $sql = "SELECT * FROM patient_packages WHERE patient_id = ? ORDER BY start_date DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$patientId]);
        $packages = $stmt->fetchAll();

        foreach ($packages as &$pkg) {
            $sqlItems = "SELECT pi.*, t.name as therapy_name 
                         FROM package_items pi 
                         JOIN therapies t ON pi.therapy_id = t.id 
                         WHERE pi.package_id = ?";
            $stmtItems = $this->pdo->prepare($sqlItems);
            $stmtItems->execute([$pkg['id']]);
            $pkg['items'] = $stmtItems->fetchAll();
        }

        return $packages;
    }
    
    // Planning Logic (PEI)
    public function getActivePlanning($patientId, $year, $therapyId) {
        $stmt = $this->pdo->prepare("SELECT * FROM patient_planning WHERE patient_id = ? AND year = ? AND therapy_id = ? AND status = 'active'");
        $stmt->execute([$patientId, $year, $therapyId]);
        return $stmt->fetch();
    }
    
    public function getAllPlannings($patientId, $year) {
        // Fetch all active PEIs for the year, linked to therapies
        $sql = "SELECT pp.*, t.name as therapy_name 
                FROM patient_planning pp
                JOIN therapies t ON pp.therapy_id = t.id
                WHERE pp.patient_id = ? AND pp.year = ? AND pp.status = 'active'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$patientId, $year]);
        return $stmt->fetchAll();
    }
    
    public function savePlanning($patientId, $year, $therapyId, $goals) {
        // Simple upsert logic
        $existing = $this->getActivePlanning($patientId, $year, $therapyId);
        if ($existing) {
            $stmt = $this->pdo->prepare("UPDATE patient_planning SET goals = ? WHERE id = ?");
            return $stmt->execute([$goals, $existing['id']]);
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO patient_planning (patient_id, year, therapy_id, goals) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$patientId, $year, $therapyId, $goals]);
        }
    }
    
    public function hasActivePlanning($patientId, $therapyId, $year = null) {
        if (!$year) $year = date('Y');
        $plan = $this->getActivePlanning($patientId, $year, $therapyId);
        return !empty($plan);
    }
    
    // Medical Record / History
    public function getHistory($patientId) {
        $sql = "SELECT a.*, t.name as therapy_name, prof.name as professional_name, sn.content as evolution_content, sn.evolution_type
                FROM appointments a
                JOIN therapies t ON a.therapy_id = t.id
                JOIN professionals prof ON a.professional_id = prof.id
                LEFT JOIN session_notes sn ON a.id = sn.appointment_id
                WHERE a.patient_id = ?
                ORDER BY a.start_time DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$patientId]);
        return $stmt->fetchAll();
    }
}
