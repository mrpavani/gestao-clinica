<?php
// templates/patients/package_edit.php
require_once __DIR__ . '/../../src/Controllers/PatientController.php';
require_once __DIR__ . '/../../src/Controllers/TherapyController.php';

$patientController = new PatientController();
$therapyController = new TherapyController();

$packageId = $_GET['id'] ?? null;
if (!$packageId) die("ID Inválido");

$package = $patientController->getPackageById($packageId);
if (!$package) die("Pacote não encontrado");

$patient = $patientController->getById($package['patient_id']);
$therapies = $therapyController->getAll();

// Helper to find existing session count for a therapy
$getSessionCount = function($therapyId) use ($package) {
    foreach ($package['items'] as $item) {
        if ($item['therapy_id'] == $therapyId) return $item['sessions_per_month'];
    }
    return 0;
};

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start = $_POST['start_date'];
    $end = $_POST['end_date'];
    
    $items = [];
    foreach ($therapies as $t) {
        $key = 'therapy_' . $t['id'];
        $val = $_POST[$key] ?? 0;
        if ($val > 0) {
            $items[] = ['therapy_id' => $t['id'], 'sessions' => (int)$val];
        }
    }
    
    if ($patientController->updatePackage($packageId, $start, $end, $items)) {
        // Redirect to patient view
        header("Location: ?page=patients_view&id=" . $package['patient_id']);
        exit;
    } else {
        $error = "Erro ao atualizar pacote.";
    }
}
?>

<header>
    <h1>Editar Pacote</h1>
    <a href="?page=patients_view&id=<?= $package['patient_id'] ?>" class="btn" style="background: #e5e7eb; color: var(--text-primary);">
        <i class="fa-solid fa-arrow-left"></i> Voltar
    </a>
</header>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <h3 style="margin-bottom: 1.5rem;">Pacote de: <?= htmlspecialchars($patient['name']) ?></h3>
    
    <?php if ($error): ?>
        <div style="background: #FEE2E2; color: #991B1B; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label>Data Início</label>
                <input type="date" name="start_date" required value="<?= $package['start_date'] ?>">
            </div>
            <div class="form-group">
                <label>Data Fim / Renovação</label>
                <input type="date" name="end_date" required value="<?= $package['end_date'] ?>">
            </div>
        </div>
        
        <h4 style="margin-top: 1rem; margin-bottom: 0.5rem; color: var(--text-secondary);">Composição do Pacote (Sessões/Mês)</h4>
        <div style="background: #f9fafb; padding: 1rem; border-radius: var(--radius-md); border: 1px solid #e5e7eb;">
            <?php foreach ($therapies as $t): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; border-bottom: 1px dotted #ccc; padding-bottom: 0.5rem;">
                    <label style="margin: 0;"><?= htmlspecialchars($t['name']) ?></label>
                    <input type="number" name="therapy_<?= $t['id'] ?>" min="0" max="20" style="width: 70px; padding: 0.25rem;" value="<?= $getSessionCount($t['id']) ?>">
                </div>
            <?php endforeach; ?>
        </div>
        
        <div style="margin-top: 2rem; text-align: right;">
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        </div>
    </form>
</div>
