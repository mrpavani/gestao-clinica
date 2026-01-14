<?php
// templates/patients/form.php
$controller = new PatientController();
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
    $name = $_POST['name'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $guardian = $_POST['guardian_name'] ?? '';
    $contact = $_POST['contact_info'] ?? '';

    if ($name && $dob && $guardian) {
        if ($id) {
            // Update
             if ($controller->update($id, $name, $dob, $guardian, $contact)) {
                 header("Location: ?page=patients");
                 exit;
             } else {
                 $error = "Erro ao atualizar paciente.";
             }
        } else {
            // Create
            $newId = $controller->create($name, $dob, $guardian, $contact);
            if ($newId) {
                header("Location: ?page=patients");
                exit;
            } else {
                $error = 'Erro ao cadastrar paciente.';
            }
        }
    } else {
        $error = 'Preencha todos os campos obrigatórios.';
    }
}
?>

<header>
    <h1><?= $id ? 'Editar Paciente' : 'Novo Paciente' ?></h1>
    <a href="?page=patients" class="btn" style="background: #e5e7eb; color: var(--text-primary);">
        <i class="fa-solid fa-arrow-left"></i> Voltar
    </a>
</header>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <?php if ($message): ?>
        <div style="background: #D1FAE5; color: #065F46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
            <?= $message ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div style="background: #FEE2E2; color: #991B1B; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="name">Nome do Paciente</label>
            <input type="text" id="name" name="name" required placeholder="Nome da criança/paciente" value="<?= $patient ? htmlspecialchars($patient['name']) : '' ?>">
        </div>

        <div class="form-group">
            <label for="dob">Data de Nascimento</label>
            <input type="date" id="dob" name="dob" required value="<?= $patient ? $patient['dob'] : '' ?>">
        </div>

        <div class="form-group">
            <label for="guardian_name">Nome do Responsável</label>
            <input type="text" id="guardian_name" name="guardian_name" required placeholder="Pai, Mãe ou Responsável legal" value="<?= $patient ? htmlspecialchars($patient['guardian_name']) : '' ?>">
        </div>
        
        <div class="form-group">
            <label for="contact_info">Contato (Telefone/Email)</label>
            <input type="text" id="contact_info" name="contact_info" required placeholder="(XX) XXXXX-XXXX" value="<?= $patient ? htmlspecialchars($patient['contact_info']) : '' ?>">
        </div>

        <div style="margin-top: 2rem; text-align: right;">
            <button type="submit" class="btn btn-primary">
                <?= $id ? 'Salvar Alterações' : 'Salvar e Criar Pacote' ?>
            </button>
        </div>
    </form>
</div>
