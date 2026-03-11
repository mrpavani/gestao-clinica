<?php
// templates/therapies/list.php
$controller = new TherapyController();
$therapies = $controller->getAll();
?>

<header>
    <h1>Terapias</h1>
    <a href="?page=therapies_new" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> Nova Terapia
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
                <th>Nome da Terapia</th>
                <th>Duração Padrão</th>
                <th>Profissionais Habilitados</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($therapies)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: var(--text-secondary);">Nenhuma terapia cadastrada.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($therapies as $therapy): ?>
                    <tr>
                        <td><?= htmlspecialchars($therapy['name']) ?></td>
                        <td><?= htmlspecialchars($therapy['default_duration_minutes']) ?> min</td>
                        <td>
                            <span style="background: #EEF2FF; color: var(--primary-color); padding: 0.25rem 0.5rem; border-radius: 99px; font-size: 0.875rem;">
                                <?= $therapy['professional_count'] ?> profissionais
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="?page=therapies_new&id=<?= $therapy['id'] ?>" class="btn" style="color: var(--primary-color); padding: 0.5rem;" title="Editar"><i class="fa-solid fa-pen"></i></a>
                                <a href="#" 
                                   class="btn text-danger" 
                                   style="color: var(--danger-color); padding: 0.5rem;" 
                                   title="Excluir"
                                   onclick="event.preventDefault(); if (window.UI) UI.confirmDelete('Excluir Terapia', 'Tem certeza que deseja excluir esta terapia? Esta ação não pode ser desfeita.', () => window.location.href='?page=therapies_delete&id=<?= $therapy['id'] ?>'); else if (confirm('Tem certeza?')) window.location.href='?page=therapies_delete&id=<?= $therapy['id'] ?>';">
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
