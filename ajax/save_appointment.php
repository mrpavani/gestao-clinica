<?php
// ajax/save_appointment.php
// AJAX endpoint for creating appointments (single or recurrent)
header('Content-Type: application/json');

session_start();

require_once __DIR__ . '/../src/Controllers/AppointmentController.php';
require_once __DIR__ . '/../src/Controllers/AuthController.php';

// Must be logged in and admin
if (!isset($_SESSION['user_id']) || !AuthController::isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acesso negado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método inválido.']);
    exit;
}

$patientId      = $_POST['patient_id']      ?? '';
$professionalId = $_POST['professional_id'] ?? '';
$therapyId      = $_POST['therapy_id']      ?? '';
$startTime      = $_POST['start_time']      ?? '';
$duration       = (int)($_POST['duration']  ?? 60);
$notes          = $_POST['notes']           ?? '';
$mode              = $_POST['recurrence_mode'] ?? 'single'; // 'single' or 'recurrent'
$repeatEnd         = $_POST['repeat_end_date'] ?? '';
$recurrenceDays    = $_POST['recurrence_days'] ?? [];
$recurrenceTimes   = $_POST['recurrence_times'] ?? [];
$recurrenceEndType = $_POST['recurrence_end_type'] ?? 'date';
$occurrencesCount  = (int)($_POST['occurrences_count'] ?? 0);

if (!$patientId || !$professionalId || !$therapyId || !$startTime) {
    echo json_encode(['success' => false, 'error' => 'Preencha todos os campos obrigatórios.']);
    exit;
}

$controller = new AppointmentController();

if ($mode === 'recurrent') {
    if (empty($recurrenceDays)) {
        echo json_encode(['success' => false, 'error' => 'Selecione pelo menos um dia da semana para a recorrência.']);
        exit;
    }

    if ($recurrenceEndType === 'date' && empty($repeatEnd)) {
        echo json_encode(['success' => false, 'error' => 'Informe a data final da recorrência.']);
        exit;
    }
    
    if ($recurrenceEndType === 'occurrences' && $occurrencesCount <= 0) {
        echo json_encode(['success' => false, 'error' => 'Informe o número de sessões para a recorrência.']);
        exit;
    }

    $result = $controller->createRecurrent(
        $patientId,
        $professionalId,
        $therapyId,
        $startTime,
        $duration,
        $recurrenceDays,
        $recurrenceTimes,
        $recurrenceEndType,
        $repeatEnd,
        $occurrencesCount,
        $notes
    );
    echo json_encode($result);
} else {
    $result = $controller->create(
        $patientId,
        $professionalId,
        $therapyId,
        $startTime,
        $duration,
        $notes
    );
    echo json_encode($result);
}
