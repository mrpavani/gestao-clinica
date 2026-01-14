<?php
// templates/professionals/form.php
$controller = new ProfessionalController();
$message = '';
$error = '';
$id = $_GET['id'] ?? null;
$professional = null;

if ($id) {
    $professional = $controller->getById($id);
    if (!$professional) {
        echo "Profissional não encontrado.";
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $specialty = $_POST['specialty'] ?? '';
    $hours = $_POST['max_weekly_hours'] ?? 40;

    if ($name && $specialty) {
        if ($id) {
            // Edit
            if ($controller->update($id, $name, $specialty, $hours)) {
                header("Location: ?page=professionals");
                exit;
            } else {
                $error = 'Erro ao atualizar profissional.';
            }
        } else {
            // Create
            if ($controller->create($name, $specialty, $hours)) {
                header("Location: ?page=professionals");
                exit;
            } else {
                $error = 'Erro ao cadastrar profissional.';
            }
        }
    } else {
        $error = 'Preencha todos os campos obrigatórios.';
    }
}
?>

<header>
    <h1><?= $id ? 'Editar Profissional' : 'Novo Profissional' ?></h1>
    <a href="?page=professionals" class="btn" style="background: #e5e7eb; color: var(--text-primary);">
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
            <label for="name">Nome Completo</label>
            <input type="text" id="name" name="name" required placeholder="Ex: Dra. Ana Silva" value="<?= $professional ? htmlspecialchars($professional['name']) : '' ?>">
        </div>

        <div class="form-group">
            <label for="specialty">Especialidade</label>
            <input type="text" id="specialty" name="specialty" required placeholder="Ex: Fonoaudiologia" value="<?= $professional ? htmlspecialchars($professional['specialty']) : '' ?>">
        </div>

        <div class="form-group">
            <label for="max_weekly_hours">Carga Horária Semanal (Horas)</label>
            <input type="number" id="max_weekly_hours" name="max_weekly_hours" value="<?= $professional ? $professional['max_weekly_hours'] : 40 ?>" min="1" max="168">
        </div>

        <div style="margin-top: 2rem; text-align: right;">
            <button type="submit" class="btn btn-primary">
                <?= $id ? 'Atualizar Profissional' : 'Salvar Profissional' ?>
            </button>
        </div>
    </form>
</div>
