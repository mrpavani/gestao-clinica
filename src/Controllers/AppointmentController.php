<?php
// src/Controllers/AppointmentController.php

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/PatientController.php';

class AppointmentController {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function getAppointmentsByRange($startDate, $endDate) {
        $sql = "SELECT a.*, p.name as patient_name, prof.name as professional_name, t.name as therapy_name, sn.id as has_note
                FROM appointments a
                JOIN patients p ON a.patient_id = p.id
                JOIN professionals prof ON a.professional_id = prof.id
                JOIN therapies t ON a.therapy_id = t.id
                LEFT JOIN session_notes sn ON a.id = sn.appointment_id
                WHERE a.start_time BETWEEN ? AND ?
                ORDER BY a.start_time ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
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
        // 1. Check if PEI exists for this therapy
        $patientController = new PatientController();
        if (!$patientController->hasActivePlanning($patientId, $therapyId)) {
            // Need to fetch therapy name for better error message
            $stmt = $this->pdo->prepare("SELECT name FROM therapies WHERE id = ?");
            $stmt->execute([$therapyId]);
            $tName = $stmt->fetchColumn();
            
            return ['success' => false, 'error' => "Bloqueado: O paciente não possui um PEI (Planejamento) ativo para a terapia: $tName."];
        }

        // 2. Conflict Check
        $start = new DateTime($startTime);
        $end = clone $start;
        $end->modify("+$durationMinutes minutes");
        
        $startStr = $start->format('Y-m-d H:i:s');
        $endStr = $end->format('Y-m-d H:i:s');

        if ($this->hasConflict($professionalId, $startStr, $endStr)) {
            return ['success' => false, 'error' => 'Profissional já tem agendamento neste horário.'];
        }

        $sql = "INSERT INTO appointments (patient_id, professional_id, therapy_id, start_time, end_time, notes) 
                VALUES (?, ?, ?, ?, ?, ?)";
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

    private function hasConflict($professionalId, $startTime, $endTime) {
        $sql = "SELECT COUNT(*) FROM appointments 
                WHERE professional_id = ? 
                AND status = 'scheduled'
                AND (
                    (start_time < ? AND end_time > ?) OR
                    (start_time < ? AND end_time > ?) OR
                    (start_time >= ? AND end_time <= ?)
                )";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $professionalId, 
            $endTime, $startTime, 
            $endTime, $startTime, 
            $startTime, $endTime
        ]);
        return $stmt->fetchColumn() > 0;
    }
}
