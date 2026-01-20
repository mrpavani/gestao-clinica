<?php
// templates/branches/form.php
require_once __DIR__ . '/../../src/Controllers/BranchController.php';

if (!AuthController::isAdmin()) {
    echo "<p style='color: red;'>Acesso negado. Apenas administradores podem acessar esta página.</p>";
    return;
}

$controller = new BranchController();
$error = '';
$id = $_GET['id'] ?? null;
$branch = null;

if ($id) {
    $branch = $controller->getById($id);
    if (!$branch) {
        echo "Filial não encontrada.";
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($name)) {
        $error = 'O nome da filial é obrigatório.';
    } else {
        if ($id) {
            if ($controller->update($id, $name, $address, $phone)) {
                header('Location: ?page=branches&success=updated');
                exit;
            } else {
                $error = 'Erro ao atualizar filial.';
            }
        } else {
            if ($controller->create($name, $address, $phone)) {
                header('Location: ?page=branches&success=created');
                exit;
            } else {
                $error = 'Erro ao cadastrar filial.';
            }
        }
    }
}
?>

<header>
    <h1><?= $id ? 'Editar Filial' : 'Nova Filial' ?></h1>
    <a href="?page=branches" class="btn" style="background: #e5e7eb; color: var(--text-primary);">
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
            <label for="name">Nome da Filial *</label>
            <input type="text" id="name" name="name" required placeholder="Ex: Unidade Centro" value="<?= $branch ? htmlspecialchars($branch['name']) : '' ?>">
        </div>

        <div class="form-group">
            <label for="address">Endereço</label>
            <input type="text" id="address" name="address" placeholder="Ex: Rua das Flores, 123" value="<?= $branch ? htmlspecialchars($branch['address']) : '' ?>">
        </div>

        <div class="form-group">
            <label for="phone">Telefone</label>
            <input type="text" id="phone" name="phone" placeholder="(00) 0000-0000" value="<?= $branch ? htmlspecialchars($branch['phone']) : '' ?>">
        </div>

        <div style="margin-top: 2rem; text-align: right;">
            <button type="submit" class="btn btn-primary">
                <?= $id ? 'Salvar Alterações' : 'Cadastrar Filial' ?>
            </button>
        </div>
    </form>
</div>
