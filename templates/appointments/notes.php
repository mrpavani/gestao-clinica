<?php
// templates/appointments/notes.php
require_once __DIR__ . '/../../src/Controllers/AppointmentController.php';
require_once __DIR__ . '/../../src/Controllers/PatientController.php';

$apptController = new AppointmentController();
$patientController = new PatientController();

$id = $_GET['id'] ?? null;
if (!$id) die("ID Inválido");

$appt = $apptController->getById($id);
if (!$appt) die("Agendamento não encontrado");

// Get Patient Planning for THIS therapy
$planning = $patientController->getActivePlanning($appt['patient_id'], date('Y'), $appt['therapy_id']);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = $_POST['content'];
    $type = $_POST['type'];
    
    if ($apptController->saveEvolution($id, $content, $type)) {
        // Redirect back to calendar or stay
        header("Location: ?page=schedule&success=1");
        exit;
    } else {
        $message = "Erro ao salvar evolução.";
    }
}
?>

<header>
    <h1>Registrar Evolução</h1>
    <a href="?page=schedule" class="btn" style="background: #e5e7eb; color: var(--text-primary);">
        <i class="fa-solid fa-arrow-left"></i> Voltar
    </a>
</header>
    
<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
    <!-- Context Column -->
    <div>
        <div class="card" style="margin-bottom: 1rem;">
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <h3>Detalhes do Atendimento</h3>
                <a href="?page=appointment_edit&id=<?= $id ?>" class="btn" style="padding: 0.25rem 0.5rem; font-size: 0.85rem; color: var(--primary-color);">
                    <i class="fa-solid fa-pen"></i> Editar
                </a>
            </div>
            <p><strong>Paciente:</strong> <?= htmlspecialchars($appt['patient_name']) ?></p>
            <p><strong>Profissional:</strong> <?= htmlspecialchars($appt['professional_name']) ?></p>
            <p><strong>Terapia:</strong> <?= htmlspecialchars($appt['therapy_name']) ?></p>
            <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($appt['start_time'])) ?></p>
        </div>
        
        <div class="card" style="border-left: 4px solid var(--primary-color);">
            <h3>PEI - <?= htmlspecialchars($appt['therapy_name']) ?></h3>
            <?php if ($planning): ?>
                <div style="white-space: pre-wrap; font-size: 0.9rem; color: var(--text-secondary);"><?= htmlspecialchars($planning['goals']) ?></div>
            <?php else: ?>
                <p style="color: var(--text-secondary); font-style: italic;">Nenhum PEI encontrado para esta terapia.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Form Column -->
    <div class="card">
        <form method="POST">
            <div class="form-group">
                <label>Tipo de Evolução</label>
                <select name="type">
                    <option value="routine">Rotina</option>
                    <option value="evaluation">Avaliação</option>
                    <option value="incident">Incidente / Ocorrência</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Descrição do Atendimento / Evolução</label>
                <textarea name="content" rows="12" required placeholder="Descreva como foi o atendimento, progressos observados, etc."></textarea>
            </div>
            
            <div style="text-align: right;">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-check"></i> Salvar e Finalizar Atendimento
                </button>
            </div>
        </form>
    </div>
</div>
