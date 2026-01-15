<?php
// templates/appointments/edit.php
require_once __DIR__ . '/../../src/Controllers/AppointmentController.php';
require_once __DIR__ . '/../../src/Controllers/ProfessionalController.php';

$apptController = new AppointmentController();
$profController = new ProfessionalController();

$id = $_GET['id'] ?? null;
if (!$id) die("ID Inválido");

$appt = $apptController->getById($id);
if (!$appt) die("Agendamento não encontrado");

$professionals = $profController->getAll();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $professionalId = $_POST['professional_id'];
    $startTime = $_POST['start_time'];
    $duration = $_POST['duration'];
    $notes = $_POST['notes'];
    $status = $_POST['status'];
    
    $res = $apptController->update($id, $professionalId, $startTime, $duration, $notes, $status);
    
    if ($res['success']) {
        // Redirect to calendar or notes
        header("Location: ?page=appointment_notes&id=$id&success=updated");
        exit;
    } else {
        $error = $res['error'];
    }
}

// Calculate duration for pre-fill
$start = new DateTime($appt['start_time']);
$end = new DateTime($appt['end_time']);
$duration = ($end->getTimestamp() - $start->getTimestamp()) / 60;
?>

<header>
    <h1>Editar Agendamento</h1>
    <a href="?page=appointment_notes&id=<?= $id ?>" class="btn" style="background: #e5e7eb; color: var(--text-primary);">
        <i class="fa-solid fa-arrow-left"></i> Voltar
    </a>
</header>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <?php if ($error): ?>
        <div style="background: #FEE2E2; color: #991B1B; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <!-- Read-only info -->
        <div style="background: #F3F4F6; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
            <p><strong>Paciente:</strong> <?= htmlspecialchars($appt['patient_name']) ?></p>
            <p><strong>Terapia:</strong> <?= htmlspecialchars($appt['therapy_name']) ?></p>
        </div>

        <div class="form-group">
            <label>Profissional</label>
            <select name="professional_id" required>
                <?php foreach ($professionals as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $p['id'] == $appt['professional_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['name']) ?> - <?= htmlspecialchars($p['specialty']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Data e Hora</label>
            <input type="datetime-local" name="start_time" required value="<?= date('Y-m-d\TH:i', strtotime($appt['start_time'])) ?>">
        </div>
        
        <div class="form-group">
            <label>Duração (min)</label>
            <input type="number" name="duration" value="<?= $duration ?>" min="15" step="15" required>
        </div>
        
         <div class="form-group">
            <label>Status</label>
            <select name="status">
                <option value="scheduled" <?= $appt['status'] == 'scheduled' ? 'selected' : '' ?>>Agendado</option>
                <option value="completed" <?= $appt['status'] == 'completed' ? 'selected' : '' ?>>Realizado</option>
                <option value="cancelled" <?= $appt['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelado</option>
                <option value="noshow" <?= $appt['status'] == 'noshow' ? 'selected' : '' ?>>Não Compareceu</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Notas Internas (Admin)</label>
            <textarea name="notes" rows="3"><?= htmlspecialchars($appt['notes']) ?></textarea>
        </div>
        
        <div style="text-align: right; margin-top: 2rem;">
            <button type="submit" class="btn btn-primary">
                Salvar Alterações
            </button>
        </div>
    </form>
</div>
