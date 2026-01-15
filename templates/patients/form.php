<?php
// templates/patients/form.php
require_once __DIR__ . '/../../src/Controllers/PatientController.php';
require_once __DIR__ . '/../../src/Controllers/TherapyController.php';

$controller = new PatientController();
$therapyController = new TherapyController();
$therapies = $therapyController->getAll();

$message = '';
$error = '';
$id = $_GET['id'] ?? null;
$patient = null;

if ($id) {
    $patient = $controller->getById($id);
    if (!$patient) {
        echo "Paciente não encontrado.";
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Personal Data
    $name = $_POST['name'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $guardian = $_POST['guardian_name'] ?? '';
    $contact = $_POST['contact_info'] ?? '';

    // Validation for Minors (Simple check: if < 18, guardian required)
    $isMinor = false;
    if ($dob) {
        $age = date_diff(date_create($dob), date_create('today'))->y;
        if ($age < 18) $isMinor = true;
    }

    if (!$name || !$dob) {
        $error = 'Nome e Data de Nascimento são obrigatórios.';
    } elseif ($isMinor && (!$guardian || !$contact)) {
        $error = 'Para menores de 18 anos, Nome do Responsável e Contato são obrigatórios.';
    } else {
        if ($id) {
            // Edit Mode - Only updates personal info
            if ($controller->update($id, $name, $dob, $guardian, $contact)) {
                header("Location: ?page=patients_view&id=$id");
                exit;
            } else {
                $error = 'Erro ao atualizar paciente.';
            }
        } else {
            // Create Mode - Full Transaction
            $contractStart = $_POST['start_date'] ?? '';
            $contractEnd = $_POST['end_date'] ?? '';
            $selectedTherapies = [];
            
            if (!$contractStart || !$contractEnd) {
                $error = 'Datas de Início e Fim do contrato são obrigatórias.';
            } else {
                // Collect selected therapies data
                foreach ($therapies as $therapy) {
                    $tid = $therapy['id'];
                    if (isset($_POST['therapy_' . $tid])) {
                        $selectedTherapies[] = [
                            'therapy_id' => $tid,
                            'sessions' => $_POST['sessions_' . $tid] ?? 0,
                            'goals' => $_POST['goals_' . $tid] ?? ''
                        ];
                    }
                }

                $newId = $controller->createFullPatient(
                    ['name' => $name, 'dob' => $dob, 'guardian_name' => $guardian, 'contact_info' => $contact],
                    ['start_date' => $contractStart, 'end_date' => $contractEnd],
                    $selectedTherapies
                );

                if ($newId) {
                    header("Location: ?page=patients_view&id=$newId&new=1");
                    exit;
                } else {
                    $error = 'Erro ao cadastrar paciente. Verifique se já não existe um paciente com este nome e data de nascimento.';
                }
            }
        }
    }
}
?>

<header>
    <h1><?= $id ? 'Editar Dados do Paciente' : 'Novo Paciente' ?></h1>
    <a href="?page=patients" class="btn" style="background: #e5e7eb; color: var(--text-primary);">
        <i class="fa-solid fa-arrow-left"></i> Voltar
    </a>
</header>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <?php if ($error): ?>
        <div style="background: #FEE2E2; color: #991B1B; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST" id="patientForm">
        
        <!-- SECTION 1: Personal Data -->
        <h3 style="color: var(--primary-color); margin-bottom: 1rem; border-bottom: 1px solid #E5E7EB; padding-bottom: 0.5rem;">Dados Pessoais</h3>
        
        <div class="form-group">
            <label for="name">Nome Completo *</label>
            <input type="text" id="name" name="name" required placeholder="Nome do paciente" value="<?= $patient ? htmlspecialchars($patient['name']) : '' ?>">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label for="dob">Data de Nascimento *</label>
                <input type="date" id="dob" name="dob" required value="<?= $patient ? $patient['dob'] : '' ?>" onchange="checkAge()">
            </div>
            <div class="form-group">
                <label for="contact_info">Contato (Telefone/Email)</label>
                <input type="text" id="contact_info" name="contact_info" placeholder="(00) 00000-0000" value="<?= $patient ? htmlspecialchars($patient['contact_info']) : '' ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="guardian_name">Nome do Responsável / Mãe</label>
            <input type="text" id="guardian_name" name="guardian_name" placeholder="Obrigatório para menores de 18 anos" value="<?= $patient ? htmlspecialchars($patient['guardian_name']) : '' ?>">
            <small id="ageWarning" style="color: var(--danger-color); display: none;">* Obrigatório para menor de 18 anos</small>
        </div>

        <?php if (!$id): // Sections below ONLY for new registration ?>
            
            <!-- SECTION 2: Contract Data -->
            <div style="margin-top: 2rem;">
                <h3 style="color: var(--primary-color); margin-bottom: 1rem; border-bottom: 1px solid #E5E7EB; padding-bottom: 0.5rem;">Dados do Contrato</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="start_date">Início do Contrato *</label>
                        <input type="date" id="start_date" name="start_date" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label for="end_date">Fim do Contrato *</label>
                        <input type="date" id="end_date" name="end_date" required value="<?= date('Y-m-d', strtotime('+1 year')) ?>">
                    </div>
                </div>
            </div>

            <!-- SECTION 3: Therapies & PEI -->
            <div style="margin-top: 2rem;">
                <h3 style="color: var(--primary-color); margin-bottom: 1rem; border-bottom: 1px solid #E5E7EB; padding-bottom: 0.5rem;">Terapias e PEI Inicial</h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1rem;">Selecione as terapias para habilitar os campos de quantidade e objetivos.</p>

                <?php foreach ($therapies as $therapy): ?>
                    <div style="background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1rem;">
                        <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                            <input type="checkbox" id="therapy_<?= $therapy['id'] ?>" name="therapy_<?= $therapy['id'] ?>" value="1" onchange="toggleTherapyFields(<?= $therapy['id'] ?>)" style="width: auto; margin-right: 0.75rem;">
                            <label for="therapy_<?= $therapy['id'] ?>" style="margin: 0; font-weight: 600; font-size: 1rem; color: var(--text-primary); cursor: pointer;"><?= htmlspecialchars($therapy['name']) ?></label>
                        </div>

                        <div id="fields_<?= $therapy['id'] ?>" style="display: none; padding-left: 1.75rem; margin-top: 0.5rem;">
                            <div class="form-group">
                                <label>Sessões por Mês</label>
                                <input type="number" name="sessions_<?= $therapy['id'] ?>" min="1" value="4" style="width: 150px;">
                            </div>
                            <div class="form-group">
                                <label>Objetivos Iniciais do PEI (Planejamento)</label>
                                <textarea name="goals_<?= $therapy['id'] ?>" rows="3" placeholder="Descreva os objetivos iniciais para esta terapia..."></textarea>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div style="margin-top: 2rem; background: #FFF7ED; color: #9A3412; padding: 1rem; border-radius: var(--radius-md);">
                <i class="fa-solid fa-circle-info"></i> <strong>Nota:</strong> Para editar Contratos, Terapias e PEI, utilize a página de perfil do paciente.
            </div>
        <?php endif; ?>

        <div style="margin-top: 2rem; text-align: right;">
            <button type="submit" class="btn btn-primary">
                <?= $id ? 'Salvar Alterações' : 'Cadastrar Paciente' ?>
            </button>
        </div>
    </form>
</div>

<script>
function checkAge() {
    const dobInput = document.getElementById('dob');
    if (!dobInput.value) return;

    const dob = new Date(dobInput.value);
    const diff_ms = Date.now() - dob.getTime();
    const age_dt = new Date(diff_ms);
    const age = Math.abs(age_dt.getUTCFullYear() - 1970);

    const guardianInput = document.getElementById('guardian_name');
    const contactInput = document.getElementById('contact_info');
    const warning = document.getElementById('ageWarning');

    if (age < 18) {
        warning.style.display = 'inline';
        guardianInput.required = true;
        contactInput.required = true;
        // Visual indicator
        guardianInput.style.borderColor = '#FCA5A5';
        contactInput.style.borderColor = '#FCA5A5';
    } else {
        warning.style.display = 'none';
        guardianInput.required = false;
        contactInput.required = false; // Could keep generic required if policy demands
        guardianInput.style.borderColor = '#E5E7EB';
        contactInput.style.borderColor = '#E5E7EB';
    }
}

function toggleTherapyFields(id) {
    const checkbox = document.getElementById('therapy_' + id);
    const fields = document.getElementById('fields_' + id);
    if (checkbox.checked) {
        fields.style.display = 'block';
    } else {
        fields.style.display = 'none';
    }
}

// Initial check
checkAge();
</script>
