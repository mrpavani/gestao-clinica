<?php
// templates/specialties/list.php
$controller = new SpecialtyController();
$specialties = $controller->getAll();
?>

<header>
    <h1>Especialidades</h1>
    <a href="?page=specialties_new" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> Nova Especialidade
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

<div class="card table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome da Especialidade</th>
                <th style="width: 100px;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($specialties)): ?>
                <tr>
                    <td colspan="3" style="text-align: center; color: var(--text-secondary);">Nenhuma especialidade cadastrada.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($specialties as $spec): ?>
                    <tr>
                        <td><?= htmlspecialchars($spec['id']) ?></td>
                        <td style="font-weight: 500;"><?= htmlspecialchars($spec['name']) ?></td>
                        <td>
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="?page=specialties_new&id=<?= $spec['id'] ?>" class="btn" style="color: var(--primary-color); padding: 0.5rem;" title="Editar"><i class="fa-solid fa-pen"></i></a>
                                <a href="#" 
                                   class="btn text-danger" 
                                   style="color: var(--danger-color); padding: 0.5rem;" 
                                   title="Excluir"
                                   onclick="event.preventDefault(); if (window.UI) UI.confirmDelete('Excluir Especialidade', 'Tem certeza que deseja excluir esta especialidade? Ela será removida de todos os profissionais.', () => window.location.href='?page=specialties_delete&id=<?= $spec['id'] ?>'); else if (confirm('Tem certeza?')) window.location.href='?page=specialties_delete&id=<?= $spec['id'] ?>';">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
