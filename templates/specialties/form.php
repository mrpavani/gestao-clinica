<?php
// templates/specialties/form.php
$controller = new SpecialtyController();
$message = '';
$error = '';
$id = $_GET['id'] ?? null;
$specialty = null;

if ($id) {
    $specialty = $controller->getById($id);
    if (!$specialty) {
        echo "Especialidade não encontrada.";
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';

    if ($name) {
        if ($id) {
            if ($controller->update($id, $name)) {
                header("Location: ?page=specialties&success=1");
                exit;
            } else {
                $error = 'Erro ao atualizar especialidade. Pode já existir uma com este nome.';
            }
        } else {
            if ($controller->create($name)) {
                header("Location: ?page=specialties&success=1");
                exit;
            } else {
                $error = 'Erro ao cadastrar especialidade. Pode já existir uma com este nome.';
            }
        }
    } else {
        $error = 'O Nome da especialidade é obrigatório.';
    }
}
?>

<header>
    <h1><?= $id ? 'Editar Especialidade' : 'Nova Especialidade' ?></h1>
    <a href="?page=specialties" class="btn" style="background: #e5e7eb; color: var(--text-primary);">
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
        <div class="form-group">
            <label for="name">Nome da Especialidade *</label>
            <input type="text" id="name" name="name" required placeholder="Ex: Fonoaudiologia, Psicologia" value="<?= $specialty ? htmlspecialchars($specialty['name']) : '' ?>">
        </div>

        <div style="margin-top: 2rem; text-align: right;">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-save"></i> <?= $id ? 'Atualizar Especialidade' : 'Salvar Especialidade' ?>
            </button>
        </div>
    </form>
</div>
