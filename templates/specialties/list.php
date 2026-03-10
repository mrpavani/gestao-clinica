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

<?php if (isset($_GET['success'])): ?>
    <div style="background: #D1FAE5; color: #065F46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
        Operação realizada com sucesso!
    </div>
<?php endif; ?>

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
                                <a href="?page=specialties_delete&id=<?= $spec['id'] ?>" 
                                   class="btn text-danger" 
                                   style="color: var(--danger-color); padding: 0.5rem;" 
                                   title="Excluir"
                                   onclick="return confirm('Tem certeza que deseja excluir esta especialidade? Ela será removida de todos os profissionais.')">
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
