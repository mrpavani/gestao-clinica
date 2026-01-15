<?php
// templates/patients/view.php
require_once __DIR__ . '/../../src/Controllers/PatientController.php';
require_once __DIR__ . '/../../src/Controllers/TherapyController.php';
require_once __DIR__ . '/../../src/Controllers/AppointmentController.php';

$patientController = new PatientController();
$therapyController = new TherapyController();
$apptController = new AppointmentController();

$patientId = $_GET['id'] ?? null;
if (!$patientId) {
    echo "ID do paciente não fornecido.";
    exit;
}

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = $_POST['status'];
    $reason = $_POST['pause_reason'] ?? null;
    $patientController->updateStatus($patientId, $newStatus, $reason);
    header("Location: ?page=patients_view&id=$patientId");
    exit;
}

$patient = $patientController->getById($patientId);
if (!$patient) {
    echo "Paciente não encontrado.";
    exit;
}

$therapies = $therapyController->getAll();
$packages = $patientController->getPackages($patientId);

// Determine Effective Status
$dbStatus = $patient['status'] ?? 'active';
$statusLabel = 'Ativo';
$statusColor = 'var(--secondary-color)'; // Green
$statusBg = '#DCFCE7';

// Check Contract Validity
$hasActiveContract = false;
foreach($packages as $pkg) {
    if ($pkg['end_date'] >= date('Y-m-d')) {
        $hasActiveContract = true;
        break;
    }
}

if ($dbStatus === 'paused') {
    $statusLabel = 'Pausado';
    $statusColor = '#D97706'; // Amber
    $statusBg = '#FEF3C7';
} elseif ($dbStatus === 'inactive') {
    $statusLabel = 'Inativo';
    $statusColor = '#DC2626'; // Red
    $statusBg = '#FEE2E2';
} elseif (!$hasActiveContract) {
    // If active in DB but no contract, warn user
    $statusLabel = 'Inativo (Contrato Vencido)';
    $statusColor = '#DC2626';
    $statusBg = '#FEE2E2';
    // Optionally force update DB? For now, just display.
}

// Date Logic for Weekly Calendar
$weekStart = $_GET['week_start'] ?? date('Y-m-d', strtotime('monday this week'));
$weekEnd = date('Y-m-d', strtotime($weekStart . ' +5 days')); // Monday to Saturday
$prevWeek = date('Y-m-d', strtotime($weekStart . ' -1 week'));
$nextWeek = date('Y-m-d', strtotime($weekStart . ' +1 week'));

// Fetch Appointments
$appointments = $apptController->getPatientAppointments($patientId, $weekStart, $weekEnd);

// Organize appointments by date
$appointmentsByDate = [];
foreach ($appointments as $appt) {
    $date = date('Y-m-d', strtotime($appt['start_time']));
    $appointmentsByDate[$date][] = $appt;
}

// Handle Add Package Logic
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_package'])) {
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    $items = [];
    foreach ($_POST['sessions'] as $therapyId => $count) {
        if ($count > 0) $items[] = ['therapy_id' => $therapyId, 'sessions' => $count];
    }
    if (!empty($items) && $startDate && $endDate) {
        if ($patientController->createPackage($patientId, $startDate, $endDate, $items)) {
            $message = "Pacote criado com sucesso!";
            $packages = $patientController->getPackages($patientId); // Refresh
        } else {
            $message = "Erro ao criar pacote.";
        }
    } else {
        $message = "Preencha as datas e selecione pelo menos uma terapia.";
    }
}
?>

<header>
    <h1>Perfil do Paciente</h1>
    <div>
        <a href="?page=patients" class="btn" style="background: #e5e7eb; color: var(--text-primary); margin-right: 0.5rem;">
            <i class="fa-solid fa-arrow-left"></i> Voltar
        </a>
        <button class="btn" onclick="openModal('historyModal')" style="background: var(--primary-color); color: white;">
            <i class="fa-solid fa-clock-rotate-left"></i> Histórico de Pacotes
        </button>
    </div>
</header>

<?php if ($message): ?>
    <div style="background: #D1FAE5; color: #065F46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 2rem;">
        <?= $message ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 300px 1fr; gap: 2rem; align-items: start;">
    
    <!-- Left Column: Patient Details -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
            <h2 style="font-size: 1.25rem; color: var(--primary-color);">Dados Pessoais</h2>
            <span style="background: <?= $statusBg ?>; color: <?= $statusColor ?>; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 700;">
                <?= $statusLabel ?>
            </span>
        </div>
        
        <?php if ($dbStatus === 'paused' && !empty($patient['pause_reason'])): ?>
            <div style="background: #FEF3C7; color: #D97706; padding: 0.5rem; border-radius: 4px; font-size: 0.85rem; margin-bottom: 1rem;">
                <strong>Motivo Pausa:</strong> <?= htmlspecialchars($patient['pause_reason']) ?>
            </div>
        <?php endif; ?>
        
        <div style="margin-bottom: 1rem;">
            <label>Nome</label>
            <div style="font-weight: 600; font-size: 1.1rem;"><?= htmlspecialchars($patient['name']) ?></div>
        </div>
        
        <div style="margin-bottom: 1rem;">
             <a href="?page=patients_record&id=<?= $patientId ?>" class="btn" style="width: 100%; justify-content: center; background: var(--primary-color); color: white;">
                <i class="fa-solid fa-file-medical"></i> Prontuário / PEI
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
        
        <button onclick="openModal('statusModal')" class="btn" style="width: 100%; font-size: 0.85rem; padding: 0.5rem; margin-bottom: 1rem; border: 1px solid #E5E7EB; color: var(--text-secondary);">
            <i class="fa-solid fa-user-gear"></i> Alterar Status
        </button>

        <hr style="margin: 1rem 0; border-top: 1px solid #E5E7EB;">
        
        <h3 style="font-size: 1rem; margin-bottom: 1rem;">Ações Rápidas</h3>
        <a href="?page=schedule" class="btn" style="width: 100%; justify-content: center; background: #e0f2fe; color: var(--primary-color); border: 1px solid #bae6fd; margin-bottom: 0.5rem;">
            <i class="fa-regular fa-calendar-plus"></i> Agendar Consulta
        </a>
        <button onclick="openModal('newPackageModal')" class="btn" style="width: 100%; justify-content: center; background: #f3f4f6; color: var(--text-primary); border: 1px solid #d1d5db;">
            <i class="fa-solid fa-file-contract"></i> Novo Contrato
        </button>
    </div>

    <!-- Right Column: Weekly Calendar -->
    <div>
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="font-size: 1.25rem; color: var(--primary-color);">Mapa Semanal de Consultas</h2>
                <div style="display: flex; gap: 0.5rem;">
                    <a href="?page=patients_view&id=<?= $patientId ?>&week_start=<?= $prevWeek ?>" class="btn" style="padding: 0.5rem 0.75rem;"><i class="fa-solid fa-chevron-left"></i></a>
                    <span style="display: flex; align-items: center; font-weight: 500; background: #F3F4F6; padding: 0 1rem; border-radius: var(--radius-md);">
                        <?= date('d/m', strtotime($weekStart)) ?> - <?= date('d/m', strtotime($weekEnd)) ?>
                    </span>
                    <a href="?page=patients_view&id=<?= $patientId ?>&week_start=<?= $nextWeek ?>" class="btn" style="padding: 0.5rem 0.75rem;"><i class="fa-solid fa-chevron-right"></i></a>
                </div>
            </div>

            <div class="calendar-grid">
                <?php
                $days = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
                for ($i = 0; $i < 6; $i++):
                    $currentDate = date('Y-m-d', strtotime($weekStart . " +$i days"));
                    $dayAppts = $appointmentsByDate[$currentDate] ?? [];
                ?>
                    <div class="calendar-day">
                        <div class="calendar-header">
                            <div><?= $days[$i] ?></div>
                            <small><?= date('d/m', strtotime($currentDate)) ?></small>
                        </div>
                        <div class="calendar-body">
                            <?php if (empty($dayAppts)): ?>
                                <div style="text-align: center; color: #9CA3AF; font-size: 0.85rem; padding-top: 1rem;">-</div>
                            <?php else: ?>
                                <?php foreach ($dayAppts as $appt): ?>
                                    <div class="appointment-card">
                                        <div class="appointment-time">
                                            <?= date('H:i', strtotime($appt['start_time'])) ?> - <?= date('H:i', strtotime($appt['end_time'])) ?>
                                        </div>
                                        <div class="appointment-info">
                                            <strong><?= htmlspecialchars($appt['therapy_name']) ?></strong><br>
                                            <span style="font-size: 0.8rem;"><?= htmlspecialchars($appt['professional_name']) ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <!-- Add button for specific day -->
                            <a href="?page=schedule&patient_id=<?= $patientId ?>&date=<?= $currentDate ?>" 
                               style="display: block; text-align: center; margin-top: auto; padding-top: 0.5rem; color: var(--primary-color); font-size: 0.85rem; text-decoration: none;">
                                <i class="fa-solid fa-plus-circle"></i>
                            </a>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Status Update -->
<div id="statusModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <button class="modal-close" onclick="closeModal('statusModal')">&times;</button>
        <h2 style="margin-bottom: 1.5rem;">Alterar Status do Paciente</h2>
        <form method="POST">
            <input type="hidden" name="update_status" value="1">
            
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="statusSelect" onchange="toggleReasonField()" style="width: 100%; padding: 0.75rem;">
                    <option value="active" <?= $dbStatus === 'active' ? 'selected' : '' ?>>Ativo</option>
                    <option value="paused" <?= $dbStatus === 'paused' ? 'selected' : '' ?>>Pausado</option>
                    <option value="inactive" <?= $dbStatus === 'inactive' ? 'selected' : '' ?>>Inativo</option>
                </select>
            </div>
            
            <div class="form-group" id="reasonField" style="display: none;">
                <label>Motivo da Pausa/Inativação</label>
                <textarea name="pause_reason" rows="3" placeholder="Ex: Viagem, Tratamento externo..." style="width: 100%;"><?= htmlspecialchars($patient['pause_reason'] ?? '') ?></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">Salvar Status</button>
        </form>
    </div>
</div>

<!-- Modal: Package History -->
<div id="historyModal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('historyModal')">&times;</button>
        <h2 style="margin-bottom: 1.5rem;">Histórico de Pacotes</h2>
        
        <?php if (empty($packages)): ?>
            <p style="color: var(--text-secondary);">Nenhum pacote atribuído.</p>
        <?php else: ?>
            <?php foreach ($packages as $pkg): ?>
                <div style="border: 1px solid #E5E7EB; border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <strong><?= date('d/m/Y', strtotime($pkg['start_date'])) ?> até <?= date('d/m/Y', strtotime($pkg['end_date'])) ?></strong>
                        <div>
                             <a href="?page=patient_package_edit&id=<?= $pkg['id'] ?>" style="color: var(--primary-color); font-size: 0.9rem; margin-right: 0.5rem;"><i class="fa-solid fa-pen"></i> Editar/Renovar</a>
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

<!-- Modal: New Package -->
<div id="newPackageModal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('newPackageModal')">&times;</button>
        <h2 style="margin-bottom: 1.5rem; color: var(--primary-color);">Novo Contrato/Pacote</h2>
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
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.add('active');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}
function toggleReasonField() {
    const status = document.getElementById('statusSelect').value;
    const reasonField = document.getElementById('reasonField');
    if (status === 'paused' || status === 'inactive') {
        reasonField.style.display = 'block';
    } else {
        reasonField.style.display = 'none';
    }
}
// Initialize state
toggleReasonField();

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
    }
}
</script>
