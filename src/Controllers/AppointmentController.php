<?php
// src/Controllers/AppointmentController.php

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/PatientController.php';

class AppointmentController {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function getAppointmentsByRange($startDate, $endDate, $professionalId = null, $therapyId = null) {
        $branchId = $_SESSION['branch_id'] ?? null;

        $sql = "SELECT a.*, p.name as patient_name, prof.name as professional_name, t.name as therapy_name, sn.id as has_note
                FROM appointments a
                JOIN patients p ON a.patient_id = p.id
                JOIN professionals prof ON a.professional_id = prof.id
                JOIN therapies t ON a.therapy_id = t.id
                LEFT JOIN session_notes sn ON a.id = sn.appointment_id
                WHERE a.start_time BETWEEN ? AND ?";
        
        $params = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];
        
        // Isolate by branch: filter by patient's branch_id
        if ($branchId) {
            $sql .= " AND p.branch_id = ?";
            $params[] = $branchId;
        }

        if (!empty($professionalId)) {
            $sql .= " AND a.professional_id = ?";
            $params[] = $professionalId;
        }
        
        if (!empty($therapyId)) {
            $sql .= " AND a.therapy_id = ?";
            $params[] = $therapyId;
        }
        
        $sql .= " ORDER BY a.start_time ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getPatientAppointments($patientId, $startDate, $endDate) {
        $sql = "SELECT a.*, prof.name as professional_name, t.name as therapy_name
                FROM appointments a
                JOIN professionals prof ON a.professional_id = prof.id
                JOIN therapies t ON a.therapy_id = t.id
                WHERE a.patient_id = ? 
                AND a.start_time BETWEEN ? AND ?
                ORDER BY a.start_time ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$patientId, $startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $sql = "SELECT a.*, p.name as patient_name, p.id as patient_id, prof.name as professional_name, prof.id as professional_id, t.name as therapy_name, t.id as therapy_id
                FROM appointments a
                JOIN patients p ON a.patient_id = p.id
                JOIN professionals prof ON a.professional_id = prof.id
                JOIN therapies t ON a.therapy_id = t.id
                WHERE a.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($patientId, $professionalId, $therapyId, $startTime, $durationMinutes, $notes = '') {
        $patientController = new PatientController();
        
        $start = new DateTime($startTime);
        $end = clone $start;
        $end->modify("+$durationMinutes minutes");
        
        $startStr = $start->format('Y-m-d H:i:s');
        $endStr = $end->format('Y-m-d H:i:s');
        $dateOnly = $start->format('Y-m-d');
        $month = $start->format('m');
        $year = $start->format('Y');

        $branchId = $_SESSION['branch_id'] ?? null;
        
        // 0. Check Branch Isolation
        if ($branchId) {
            $prof = (new ProfessionalController())->getById($professionalId);
            $patient = $patientController->getById($patientId);
            
            if ($prof && $prof['branch_id'] != $branchId) {
                return ['success' => false, 'error' => "Este profissional pertence a outra unidade. Selecione sua unidade atual para agendar."];
            }
            if ($patient && $patient['branch_id'] != $branchId) {
                return ['success' => false, 'error' => "Este paciente pertence a outra unidade. Selecione sua unidade atual para agendar."];
            }
        }

        // 1. Check if therapy is in the active package for this date
        $allowedTherapies = $patientController->getActivePackageTherapies($patientId, $dateOnly);
        $matchedTherapy = null;
        foreach ($allowedTherapies as $at) {
            if ($at['therapy_id'] == $therapyId) {
                $matchedTherapy = $at;
                break;
            }
        }
        
        if (!$matchedTherapy) {
            return ['success' => false, 'error' => 'Terapia não autorizada no pacote ativo do paciente para esta data.'];
        }

        // 2. Check Session Limits for the month
        $limit = $matchedTherapy['sessions_per_month'];
        $stmtCount = $this->pdo->prepare("
            SELECT COUNT(*) FROM appointments 
            WHERE patient_id = ? 
            AND therapy_id = ? 
            AND MONTH(start_time) = ? 
            AND YEAR(start_time) = ?
            AND status IN ('scheduled', 'completed')
        ");
        $stmtCount->execute([$patientId, $therapyId, $month, $year]);
        $currentCount = $stmtCount->fetchColumn();

        if ($currentCount >= $limit) {
            return ['success' => false, 'error' => "Limite de sessões ($limit/mês) atingido para esta terapia neste mês."];
        }

        // 3. (Removed PEI checkout requirement here, it is now required for evaluation notes)

        // 4. Conflict Check (Professional)
        if ($this->hasConflict($professionalId, $startStr, $endStr)) {
            return ['success' => false, 'error' => 'Profissional já tem agendamento neste horário.'];
        }

        // 5. Conflict Check (Patient)
        if ($this->hasPatientConflict($patientId, $startStr, $endStr)) {
            return ['success' => false, 'error' => 'Paciente já tem agendamento neste horário.'];
        }

        $sql = "INSERT INTO appointments (patient_id, professional_id, therapy_id, start_time, end_time, status, notes) 
                VALUES (?, ?, ?, ?, ?, 'scheduled', ?)";
        $stmt = $this->pdo->prepare($sql);
        
        if ($stmt->execute([$patientId, $professionalId, $therapyId, $startStr, $endStr, $notes])) {
            return ['success' => true];
        }
        return ['success' => false, 'error' => 'Erro no banco de dados.'];
    }
    
    public function saveEvolution($appointmentId, $content, $type) {
        try {
            $this->pdo->beginTransaction();
            
            // Get Appointment details for IDs
            $appt = $this->getById($appointmentId);
            
            $sql = "INSERT INTO session_notes (appointment_id, professional_id, patient_id, content, evolution_type) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$appointmentId, $appt['professional_id'], $appt['patient_id'], $content, $type]);
            
            // Auto complete appointment
            $stmtUpd = $this->pdo->prepare("UPDATE appointments SET status = 'completed' WHERE id = ?");
            $stmtUpd->execute([$appointmentId]);
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function update($id, $professionalId, $startTime, $durationMinutes, $notes, $status) {
        // Calculate End Time
        $start = new DateTime($startTime);
        $end = clone $start;
        $end->modify("+$durationMinutes minutes");
        
        $startStr = $start->format('Y-m-d H:i:s');
        $endStr = $end->format('Y-m-d H:i:s');
        
        // Conflict Check (Professional excluding self)
        if ($this->hasConflict($professionalId, $startStr, $endStr, $id)) {
             return ['success' => false, 'error' => 'Profissional já tem agendamento neste horário.'];
        }
        
        // Conflict Check (Patient excluding self)
        // Need to find patientId first from the current appointment to check against
        $appt = $this->getById($id);
        if ($this->hasPatientConflict($appt['patient_id'], $startStr, $endStr, $id)) {
             return ['success' => false, 'error' => 'Paciente já tem agendamento neste horário.'];
        }
        
        $sql = "UPDATE appointments SET professional_id = ?, start_time = ?, end_time = ?, notes = ?, status = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        
        if ($stmt->execute([$professionalId, $startStr, $endStr, $notes, $status, $id])) {
            return ['success' => true];
        }
        return ['success' => false, 'error' => 'Erro ao atualizar agendamento.'];
    }

    private function hasConflict($professionalId, $startTime, $endTime, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM appointments 
                WHERE professional_id = ? 
                AND status = 'scheduled'
                AND (
                    (start_time < ? AND end_time > ?) OR
                    (start_time < ? AND end_time > ?) OR
                    (start_time >= ? AND end_time <= ?)
                )";
        
        $params = [
            $professionalId, 
            $endTime, $startTime, 
            $endTime, $startTime, 
            $startTime, $endTime
        ];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    private function hasPatientConflict($patientId, $startTime, $endTime, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM appointments 
                WHERE patient_id = ? 
                AND status = 'scheduled'
                AND (
                    (start_time < ? AND end_time > ?) OR
                    (start_time < ? AND end_time > ?) OR
                    (start_time >= ? AND end_time <= ?)
                )";
        
        $params = [
            $patientId, 
            $endTime, $startTime, 
            $endTime, $startTime, 
            $startTime, $endTime
        ];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
    * Cancel an appointment by setting its status to 'canceled'.
    * Returns array with success flag and optional error.
    */
    public function cancel($id) {
        try {
            $stmt = $this->pdo->prepare("UPDATE appointments SET status = 'canceled' WHERE id = ? AND status = 'scheduled'");
            if ($stmt->execute([$id])) {
                if ($stmt->rowCount() > 0) {
                    return ['success' => true];
                } else {
                    return ['success' => false, 'error' => 'Agendamento não encontrado ou já está cancelado.'];
                }
            }
            return ['success' => false, 'error' => 'Erro ao cancelar agendamento.'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
    * Reschedule an appointment to a new datetime and optionally a new professional.
    * Calculates new end_time based on existing duration.
    */
    public function reschedule($id, $newStartTime, $newProfessionalId = null) {
        try {
            // Fetch current appointment to get duration
            $appt = $this->getById($id);
            if (!$appt) {
                return ['success' => false, 'error' => 'Agendamento não encontrado.'];
            }
            $durationMinutes = (new DateTime($appt['end_time']))->diff(new DateTime($appt['start_time']))->i;
            $start = new DateTime($newStartTime);
            $end = clone $start;
            $end->modify("+$durationMinutes minutes");
            $startStr = $start->format('Y-m-d H:i:s');
            $endStr = $end->format('Y-m-d H:i:s');
            // Determine professional
            $professionalId = $newProfessionalId ?? $appt['professional_id'];
            // Conflict check
            if ($this->hasConflict($professionalId, $startStr, $endStr, $id)) {
                return ['success' => false, 'error' => 'Profissional já tem agendamento neste horário.'];
            }
            if ($this->hasPatientConflict($appt['patient_id'], $startStr, $endStr, $id)) {
                return ['success' => false, 'error' => 'Paciente já tem agendamento neste horário.'];
            }
            $sql = "UPDATE appointments SET professional_id = ?, start_time = ?, end_time = ?, status = 'scheduled' WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            if ($stmt->execute([$professionalId, $startStr, $endStr, $id])) {
                return ['success' => true];
            }
            return ['success' => false, 'error' => 'Erro ao reagendar agendamento.'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function delete($id) {
        try {
            // Check if has notes
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM session_notes WHERE appointment_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                return ['success' => false, 'error' => 'Não é possível excluir: já existem anotações de evolução para este agendamento.'];
            }
            
            $stmt = $this->pdo->prepare("DELETE FROM appointments WHERE id = ?");
            if ($stmt->execute([$id])) {
                return ['success' => true];
            }
            return ['success' => false, 'error' => 'Erro ao excluir agendamento.'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
