<?php
// templates/patients/record.php
require_once __DIR__ . '/../../src/Controllers/PatientController.php';
require_once __DIR__ . '/../../src/Controllers/TherapyController.php';

$patientController = new PatientController();
$therapyController = new TherapyController();

$id = $_GET['id'] ?? null;
if (!$id) die("ID Inválido");

$patient = $patientController->getById($id);
$history = $patientController->getHistory($id);
$plannings = $patientController->getAllPlannings($id, date('Y'));
$therapies = $therapyController->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_planning'])) {
    $goals = $_POST['goals'];
    $year = $_POST['year'];
    $therapyId = $_POST['therapy_id'];
    
    if ($therapyId && $goals) {
        $patientController->savePlanning($id, $year, $therapyId, $goals);
        // Refresh
        $plannings = $patientController->getAllPlannings($id, date('Y'));
    }
}
?>

<header>
    <h1>Prontuário: <?= htmlspecialchars($patient['name']) ?></h1>
    <a href="?page=patients_view&id=<?= $id ?>" class="btn" style="background: #e5e7eb; color: var(--text-primary);">
        <i class="fa-solid fa-arrow-left"></i> Voltar ao Perfil
    </a>
</header>
    
<div style="display: grid; grid-template-columns: 3fr 2fr; gap: 2rem;">
    
    <!-- Timeline -->
    <div>
        <h2>Histórico de Atendimentos</h2>
        <?php if (empty($history)): ?>
            <p>Nenhum atendimento registrado.</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($history as $item): ?>
                    <div class="card" style="border-left: 4px solid <?= $item['evolution_content'] ? 'var(--secondary-color)' : '#9CA3AF' ?>;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <strong><?= date('d/m/Y H:i', strtotime($item['start_time'])) ?> - <?= htmlspecialchars($item['therapy_name']) ?></strong>
                            <span style="font-size: 0.85rem; color: var(--text-secondary);"><?= htmlspecialchars($item['professional_name']) ?></span>
                        </div>
                        
                        <?php if ($item['evolution_content']): ?>
                            <div style="background: #F9FAFB; padding: 1rem; border-radius: 8px; font-size: 0.95rem; white-space: pre-wrap;"><?= htmlspecialchars($item['evolution_content']) ?></div>
                            <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase; font-weight: bold;">
                                Tipo: <?= $item['evolution_type'] ?>
                            </div>
                        <?php else: ?>
                            <div style="color: var(--text-secondary); font-style: italic;">Sem evolução registrada. Status: <?= $item['status'] ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Planning (PEI) -->
    <div>
        <h2 style="margin-bottom: 1rem;">Planejamentos (PEI) - <?= date('Y') ?></h2>
        
        <!-- List Existing Plans -->
        <?php if (!empty($plannings)): ?>
            <?php foreach ($plannings as $p): ?>
                <div class="card" style="margin-bottom: 1rem; border: 1px solid var(--primary-light);">
                    <h3 style="color: var(--primary-color); font-size: 1rem; margin-bottom: 0.5rem;">
                        <i class="fa-solid fa-book"></i> PEI - <?= htmlspecialchars($p['therapy_name']) ?>
                    </h3>
                    <div style="white-space: pre-wrap; font-size: 0.9rem; color: var(--text-secondary); max-height: 100px; overflow-y: auto;">
                        <?= htmlspecialchars($p['goals']) ?>
                    </div>
                    <button onclick="editPlan(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['goals'])) ?>', <?= $p['therapy_id'] ?>)" class="btn btn-sm" style="margin-top: 0.5rem; background: #eee; font-size: 0.8rem;">Editar</button>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
             <p style="color: var(--text-secondary); font-style: italic; margin-bottom: 1rem;">Nenhum PEI cadastrado para este ano.</p>
        <?php endif; ?>

        <div class="card" style="margin-top: 2rem; border-top: 4px solid var(--primary-color);">
            <h3 id="formTitle">Novo/Editar PEI</h3>
            <form method="POST">
                <input type="hidden" name="save_planning" value="1">
                <input type="hidden" name="year" value="<?= date('Y') ?>">
                
                <div class="form-group">
                    <label>Terapia</label>
                    <select name="therapy_id" id="therapySelect" required>
                        <option value="">Selecione a terapia...</option>
                        <?php foreach ($therapies as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Metas e Objetivos</label>
                    <textarea name="goals" id="goalsArea" rows="10" style="width: 100%;" placeholder="Defina as metas terapêuticas..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Salvar Planejamento</button>
            </form>
        </div>
        
        <script>
        function editPlan(id, content, therapyId) {
            document.getElementById('formTitle').innerText = 'Editar PEI';
            document.getElementById('therapySelect').value = therapyId;
            document.getElementById('goalsArea').value = content;
            // Scroll to form
            document.getElementById('formTitle').scrollIntoView({behavior: 'smooth'});
        }
        </script>
        
        <div style="margin-top: 2rem;">
            <a href="?page=report&patient_id=<?= $id ?>" target="_blank" class="btn" style="width: 100%; background: #4B5563; color: white;">
                <i class="fa-solid fa-print"></i> Gerar Relatório de Impressão
            </a>
        </div>
    </div>

</div>
