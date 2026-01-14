<?php
// templates/patients/view.php
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
$packages = $patientController->getPackages($patientId);

$message = '';
if (isset($_GET['new'])) {
    $message = "Paciente cadastrado com sucesso! Agora crie um pacote de terapias.";
}

// Handle Add Package
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_package'])) {
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    $items = [];
    
    foreach ($_POST['sessions'] as $therapyId => $count) {
        if ($count > 0) {
            $items[] = ['therapy_id' => $therapyId, 'sessions' => $count];
        }
    }

    if (!empty($items) && $startDate && $endDate) {
        if ($patientController->createPackage($patientId, $startDate, $endDate, $items)) {
            $message = "Pacote criado com sucesso!";
            // Refresh packages list
            $packages = $patientController->getPackages($patientId);
        } else {
            $message = "Erro ao criar pacote."; // Should be error style, but keeping simple
        }
    } else {
        $message = "Preencha as datas e selecione pelo menos uma terapia.";
    }
}
?>

<header>
    <h1>Perfil do Paciente</h1>
    <a href="?page=patients" class="btn" style="background: #e5e7eb; color: var(--text-primary);">
        <i class="fa-solid fa-arrow-left"></i> Voltar
    </a>
</header>

<?php if ($message): ?>
    <div style="background: #D1FAE5; color: #065F46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 2rem;">
        <?= $message ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; align-items: start;">
    
    <!-- Left Column: Details -->
    <div class="card">
        <h2 style="font-size: 1.25rem; margin-bottom: 1.5rem; color: var(--primary-color);">Dados Pessoais</h2>
        
        <div style="margin-bottom: 1rem;">
            <label>Nome</label>
            <div style="font-weight: 600; font-size: 1.1rem;"><?= htmlspecialchars($patient['name']) ?></div>
        </div>
        
        <div style="margin-bottom: 1rem;">
             <a href="?page=patients_record&id=<?= $patientId ?>" class="btn" style="width: 100%; justify-content: center; background: var(--secondary-color); color: white;">
                <i class="fa-solid fa-file-medical"></i> Acessar Prontuário / PEI
            </a>
        </div>
        
        <div style="margin-bottom: 1rem;">
            <label>Data de Nascimento</label>
            <div><?= date('d/m/Y', strtotime($patient['dob'])) ?></div>
        </div>

        <div style="margin-bottom: 1rem;">
            <label>Responsável</label>
            <div><?= htmlspecialchars($patient['guardian_name']) ?></div>
        </div>

        <div style="margin-bottom: 1rem;">
            <label>Contato</label>
            <div><?= htmlspecialchars($patient['contact_info']) ?></div>
        </div>
    </div>

    <!-- Right Column: Packages -->
    <div>
        <!-- New Package Form -->
        <div class="card" style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.25rem; margin-bottom: 1rem; color: var(--primary-color);">Novo Contrato/Pacote</h2>
            <form method="POST">
                <input type="hidden" name="create_package" value="1">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label>Data Início</label>
                        <input type="date" name="start_date" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div>
                        <label>Data Fim</label>
                        <input type="date" name="end_date" required value="<?= date('Y-m-d', strtotime('+1 year')) ?>">
                    </div>
                </div>

                <label>Terapias (Sessões Mensais)</label>
                <div style="background: #F9FAFB; padding: 1rem; border-radius: var(--radius-md); border: 1px solid #E5E7EB; display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
                    <?php foreach ($therapies as $therapy): ?>
                        <div style="background: white; padding: 0.75rem; border-radius: var(--radius-md); border: 1px solid #E5E7EB;">
                            <label for="t_<?= $therapy['id'] ?>" style="margin-bottom: 0.25rem;"><?= htmlspecialchars($therapy['name']) ?></label>
                            <input type="number" id="t_<?= $therapy['id'] ?>" name="sessions[<?= $therapy['id'] ?>]" min="0" value="0" style="width: 100%;">
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="margin-top: 1rem; text-align: right;">
                    <button type="submit" class="btn btn-primary">Gerar Pacote</button>
                </div>
            </form>
        </div>

        <!-- Previous Packages -->
        <h2 style="font-size: 1.25rem; margin-bottom: 1rem;">Histórico de Pacotes</h2>
        <?php if (empty($packages)): ?>
            <div class="card"><p style="color: var(--text-secondary);">Nenhum pacote atribuído.</p></div>
        <?php else: ?>
            <?php foreach ($packages as $pkg): ?>
                <div style="border: 1px solid #E5E7EB; border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <strong><?= date('d/m/Y', strtotime($pkg['start_date'])) ?> até <?= date('d/m/Y', strtotime($pkg['end_date'])) ?></strong>
                        <div>
                             <a href="?page=patient_package_edit&id=<?= $pkg['id'] ?>" style="color: var(--primary-color); font-size: 0.9rem; margin-right: 0.5rem;"><i class="fa-solid fa-pen"></i> Editar</a>
                             <span style="font-size: 0.85rem; background: #DEF7EC; color: #03543F; padding: 2px 6px; border-radius: 4px;">Ativo</span>
                        </div>
                    </div>
                    <ul style="margin: 0; padding-left: 1.25rem; color: var(--text-secondary);">
                        <?php foreach ($pkg['items'] as $item): ?>
                            <li><?= htmlspecialchars($item['therapy_name']) ?>: <?= $item['sessions_per_month'] ?> sessões/mês</li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>
