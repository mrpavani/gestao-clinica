<?php
// ajax/get_patient_therapies.php

session_start();

require_once __DIR__ . '/../src/Controllers/AuthController.php';
require_once __DIR__ . '/../src/Controllers/PatientController.php';

header('Content-Type: application/json');

if (!AuthController::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado.']);
    exit;
}

$patientId = $_GET['patient_id'] ?? null;
$date = $_GET['date'] ?? date('Y-m-d');

if (!$patientId) {
    echo json_encode(['error' => 'ID do paciente não fornecido.']);
    exit;
}

$controller = new PatientController();
$items = $controller->getActivePackageTherapies($patientId, $date);

echo json_encode($items);
