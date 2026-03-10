<?php
// templates/patients/edit_plan.php
require_once __DIR__ . '/../../src/Controllers/PatientController.php';
require_once __DIR__ . '/../../src/Controllers/TherapyController.php';

$patientController = new PatientController();
$therapyController = new TherapyController();

$patientId = $_GET['id'] ?? null;
if (!$patientId) {
    echo "ID do paciente não fornecido.";
    exit;
}

$patient = $patientController->getById($patientId);
if (!$patient) {
    echo "Paciente não encontrado.";
    exit;
}

$therapies = $therapyController->getAll();

// Get the active/latest package
$packages = $patientController->getPackages($patientId);
$activePackage = !empty($packages) ? $packages[0] : null; // Assume the first is the current/latest

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    
    $packageItems = [];
    $peiGoals = [];

    // Collect data per therapy
    foreach ($therapies as $therapy) {
        $tid = $therapy['id'];
        $sessions = (int)($_POST['sessions_' . $tid] ?? 0);
        if ($sessions > 0) {
            $packageItems[] = [
                'therapy_id' => $tid,
                'sessions' => $sessions
            ];
            
            $goal = trim($_POST['goals_' . $tid] ?? '');
            if ($goal) {
                $peiGoals[$tid] = $goal;
            }
        }
    }

    if ($activePackage) {
        $success = $patientController->updatePackage($activePackage['id'], $startDate, $endDate, $packageItems);
    } else {
        $success = $patientController->createPackage($patientId, $startDate, $endDate, $packageItems);
    }

    if ($success) {
        // Now update PEI
        // Since PatientController doesn't safely have an "update PEI by therapy", we use raw or a new method.
        // Wait, PatientController->addPlanning uses addPlanning($patientId, $therapyId, $goals)
        // I will add a quick loop to update/insert PEI goals
        foreach ($peiGoals as $tid => $goal) {
            // we will create a helper method in PatientController to upsert PEI
            $patientController->upsertPEI($patientId, $tid, $goal);
        }
        
        header("Location: ?page=patients_view&id=$patientId&msg=PlanUpdated");
        exit;
    } else {
        $error = "Erro ao salvar contrato.";
    }
}

// Extract sessions from active package for the form
$currentSessions = [];
if ($activePackage && isset($activePackage['items'])) {
    foreach ($activePackage['items'] as $item) {
        $currentSessions[$item['therapy_id']] = $item['sessions_per_month'];
    }
}

// Extract current PEI plans
$plannings = $patientController->getPlannings($patientId);
$currentGoals = [];
foreach ($plannings as $plan) {
    if ($plan['status'] === 'active') {
        $currentGoals[$plan['therapy_id']] = $plan['goals'];
    }
}

?>

<header>
    <h1>Editar Plano (Contrato e PEI)</h1>
    <a href="?page=patients_view&id=<?= $patientId ?>" class="btn" style="background: #e5e7eb; color: var(--text-primary);">
        <i class="fa-solid fa-arrow-left"></i> Voltar
    </a>
</header>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <h3 style="margin-bottom: 1.5rem; color: var(--primary-color);">Paciente: <?= htmlspecialchars($patient['name']) ?></h3>

    <?php if ($error): ?>
        <div style="background: #FEE2E2; color: #991B1B; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <h4 style="margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid #E5E7EB;">Dados do Contrato Vigente</h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label>Data Início</label>
                <input type="date" name="start_date" required value="<?= $activePackage['start_date'] ?? date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label>Data Fim (Renovação)</label>
                <input type="date" name="end_date" required value="<?= $activePackage['end_date'] ?? date('Y-m-d', strtotime('+1 year')) ?>">
            </div>
        </div>
        
        <h4 style="margin-top: 2rem; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid #E5E7EB;">Terapias e PEI (Sessões Mensais)</h4>
        <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1.5rem;">Defina a quantidade de sessões mensais. Se for 0, a terapia é removida do contrato. Defina as metas do PEI correspondentes.</p>

        <?php foreach ($therapies as $t): 
            $tid = $t['id'];
            $sessions = $currentSessions[$tid] ?? 0;
            $goal = $currentGoals[$tid] ?? '';
            $isChecked = $sessions > 0;
        ?>
            <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: <?= $isChecked ? '1rem' : '0' ?>;" id="header_<?= $tid ?>">
                    <label style="margin: 0; font-weight: 600; font-size: 1.05rem; display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" id="check_<?= $tid ?>" <?= $isChecked ? 'checked' : '' ?> onchange="toggleTherapy(<?= $tid ?>)" style="width: auto;">
                        <?= htmlspecialchars($t['name']) ?>
                    </label>
                </div>
                
                <div id="body_<?= $tid ?>" style="<?= $isChecked ? 'display: block;' : 'display: none;' ?>">
                    <div class="form-group" style="max-width: 250px;">
                        <label>Sessões por Mês</label>
                        <input type="number" name="sessions_<?= $tid ?>" id="sessions_<?= $tid ?>" min="0" value="<?= $sessions ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Limites e Objetivos do PEI</label>
                        <textarea name="goals_<?= $tid ?>" rows="4" placeholder="Descreva os objetivos a serem alcançados..."><?= htmlspecialchars($goal) ?></textarea>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div style="margin-top: 2rem; text-align: right;">
            <button type="submit" class="btn btn-primary" style="font-size: 1.1rem; padding: 0.75rem 2rem;">Salvar Atualização de Plano</button>
        </div>
    </form>
</div>

<script>
function toggleTherapy(tid) {
    const isChecked = document.getElementById('check_' + tid).checked;
    const body = document.getElementById('body_' + tid);
    const sessions = document.getElementById('sessions_' + tid);
    const header = document.getElementById('header_' + tid);
    
    if (isChecked) {
        body.style.display = 'block';
        header.style.marginBottom = '1rem';
        if (sessions.value == 0) sessions.value = 4; // Default to 4
    } else {
        body.style.display = 'none';
        header.style.marginBottom = '0';
        sessions.value = 0; // Clear
    }
}
</script>
