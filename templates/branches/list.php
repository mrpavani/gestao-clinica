<?php
// templates/branches/list.php
require_once __DIR__ . '/../../src/Controllers/BranchController.php';

if (!AuthController::isAdmin()) {
    echo "<p style='color: red;'>Acesso negado. Apenas administradores podem acessar esta página.</p>";
    return;
}

$controller = new BranchController();

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $result = $controller->delete($_GET['id']);
    if ($result['success']) {
        header('Location: ?page=branches&success=deleted');
    } else {
        header('Location: ?page=branches&error=' . urlencode($result['error']));
    }
    exit;
}

$branches = $controller->getAll();
?>

<header>
    <h1>Gerenciar Filiais</h1>
    <a href="?page=branches_new" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> Nova Filial
    </a>
</header>

<script>
document.addEventListener('DOMContentLoaded', () => {
<?php if (isset($_GET['success'])): ?>
    if (window.UI) UI.showToast('Operação realizada com sucesso!', 'success');
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
    if (window.UI) UI.showToast('<?= addslashes(htmlspecialchars($_GET['error'])) ?>', 'error');
<?php endif; ?>
});
</script>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>Endereço</th>
                <th>Telefone</th>
                <th style="width: 150px;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($branches)): ?>
                <tr><td colspan="4" style="text-align: center; color: var(--text-secondary);">Nenhuma filial cadastrada.</td></tr>
            <?php else: ?>
                <?php foreach ($branches as $branch): ?>
                    <tr>
                        <td style="font-weight: 500;"><?= htmlspecialchars($branch['name']) ?></td>
                        <td><?= htmlspecialchars($branch['address'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($branch['phone'] ?? '-') ?></td>
                        <td>
                            <a href="?page=branches_new&id=<?= $branch['id'] ?>" class="btn" style="padding: 0.5rem; background: #E0F2FE; color: var(--primary-color);">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <a href="#" class="btn" style="padding: 0.5rem; background: #FEE2E2; color: #DC2626;" onclick="event.preventDefault(); if (window.UI) UI.confirmDelete('Excluir Filial', 'Deseja realmente excluir esta filial?', () => window.location.href='?page=branches&action=delete&id=<?= $branch['id'] ?>'); else if (confirm('Deseja realmente excluir esta filial?')) window.location.href='?page=branches&action=delete&id=<?= $branch['id'] ?>';">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
