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

    /**
     * Creates recurring appointments on the same weekday from startTime until repeatEndDate.
     * Each appointment is validated individually (conflicts, session limits).
     * Returns summary with count of created and skipped sessions.
     */
    public function createRecurrent($patientId, $professionalId, $therapyId, $startTime, $durationMinutes, $repeatEndDate, $notes = '') {
        // Generate a shared UUID to group all recurring sessions
        $groupId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $start     = new DateTime($startTime);
        $endLimit  = new DateTime($repeatEndDate . ' 23:59:59');
        $timeOfDay = $start->format('H:i:s');
        $dayOfWeek = (int)$start->format('N'); // 1=Monday ... 7=Sunday

        // Collect all future dates on the same weekday
        $dates = [];
        $cursor = clone $start;
        // Align cursor to next occurrence of the same weekday (start date itself if it matches)
        // $start already is the right weekday (user picked the date+time)
        while ($cursor <= $endLimit) {
            $dates[] = clone $cursor;
            $cursor->modify('+7 days');
        }

        if (empty($dates)) {
            return ['success' => false, 'error' => 'Nenhuma data gerada. Verifique a data final.'];
        }

        $created  = 0;
        $skipped  = [];

        foreach ($dates as $date) {
            $sessionStart = $date->format('Y-m-d') . ' ' . $timeOfDay;
            $sessionStartDt = new DateTime($sessionStart);
            $sessionEnd   = clone $sessionStartDt;
            $sessionEnd->modify("+$durationMinutes minutes");
            $startStr = $sessionStartDt->format('Y-m-d H:i:s');
            $endStr   = $sessionEnd->format('Y-m-d H:i:s');
            $dateOnly = $sessionStartDt->format('Y-m-d');
            $month    = $sessionStartDt->format('m');
            $year     = $sessionStartDt->format('Y');

            // Check branch isolation
            $branchId = $_SESSION['branch_id'] ?? null;
            if ($branchId) {
                $prof    = (new ProfessionalController())->getById($professionalId);
                $patient = (new PatientController())->getById($patientId);
                if ($prof && $prof['branch_id'] != $branchId) {
                    $skipped[] = $dateOnly . ' (profissional de outra unidade)';
                    continue;
                }
                if ($patient && $patient['branch_id'] != $branchId) {
                    $skipped[] = $dateOnly . ' (paciente de outra unidade)';
                    continue;
                }
            }

            // Check active package
            $allowedTherapies = (new PatientController())->getActivePackageTherapies($patientId, $dateOnly);
            $matched = null;
            foreach ($allowedTherapies as $at) {
                if ($at['therapy_id'] == $therapyId) { $matched = $at; break; }
            }
            if (!$matched) {
                $skipped[] = $dateOnly . ' (terapia sem pacote ativo)';
                continue;
            }

            // Check monthly session limit
            $limit = $matched['sessions_per_month'];
            $stmtCount = $this->pdo->prepare("
                SELECT COUNT(*) FROM appointments 
                WHERE patient_id = ? AND therapy_id = ?
                AND MONTH(start_time) = ? AND YEAR(start_time) = ?
                AND status IN ('scheduled', 'completed')
            ");
            $stmtCount->execute([$patientId, $therapyId, $month, $year]);
            if ($stmtCount->fetchColumn() >= $limit) {
                $skipped[] = $dateOnly . " (limite $limit sessões/mês atingido)";
                continue;
            }

            // Check professional conflict
            if ($this->hasConflict($professionalId, $startStr, $endStr)) {
                $skipped[] = $dateOnly . ' (conflito de horário do profissional)';
                continue;
            }

            // Check patient conflict
            if ($this->hasPatientConflict($patientId, $startStr, $endStr)) {
                $skipped[] = $dateOnly . ' (conflito de horário do paciente)';
                continue;
            }

            // Insert with recurrence_group_id
            $sql = "INSERT INTO appointments (patient_id, professional_id, therapy_id, start_time, end_time, status, notes, recurrence_group_id) 
                    VALUES (?, ?, ?, ?, ?, 'scheduled', ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            if ($stmt->execute([$patientId, $professionalId, $therapyId, $startStr, $endStr, $notes, $groupId])) {
                $created++;
            } else {
                $skipped[] = $dateOnly . ' (erro no banco)';
            }
        }

        $total = count($dates);
        if ($created === 0) {
            return [
                'success' => false,
                'error'   => 'Nenhuma sessão pôde ser criada. Motivos: ' . implode('; ', $skipped)
            ];
        }

        $msg = "$created de $total sessões agendadas com sucesso.";
        if (!empty($skipped)) {
            $msg .= ' Sessões não criadas: ' . implode('; ', $skipped);
        }
        return ['success' => true, 'message' => $msg, 'created' => $created, 'skipped' => $skipped];
    }
}

