<?php
// templates/professionals/list.php
$controller = new ProfessionalController();
$professionals = $controller->getAllWithSkills();
?>

<header>
    <h1>Profissionais</h1>
    <a href="?page=professionals_new" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> Novo Profissional
    </a>
</header>

<?php if (isset($_GET['success'])): ?>
    <div style="background: #D1FAE5; color: #065F46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
        Operação realizada com sucesso!
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div style="background: #FEE2E2; color: #991B1B; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
        <?= htmlspecialchars($_GET['error']) ?>
    </div>
<?php endif; ?>

<div class="card table-container">
    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>Especialidade</th>
                <th>Conhecimentos/Habilidades</th>
                <th>Carga Horária</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($professionals)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: var(--text-secondary);">Nenhum profissional cadastrado.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($professionals as $prof): ?>
                    <tr>
                        <td style="font-weight: 500;"><?= htmlspecialchars($prof['name']) ?></td>
                        <td><?= htmlspecialchars($prof['specialty']) ?></td>
                        <td>
                            <?php if (!empty($prof['skills'])): ?>
                                <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                                    <?php foreach ($prof['skills'] as $skill): ?>
                                        <?php
                                        $badgeColor = match($skill['skill_type']) {
                                            'specialty' => 'background: var(--primary-color); color: white;',
                                            'knowledge' => 'background: var(--secondary-color); color: white;',
                                            'certification' => 'background: #8B5CF6; color: white;',
                                            default => 'background: #6B7280; color: white;'
                                        };
                                        ?>
                                        <span style="<?= $badgeColor ?> padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 500;" title="<?= ucfirst($skill['skill_type']) ?>">
                                            <?= htmlspecialchars($skill['skill_name']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span style="color: var(--text-secondary); font-size: 0.85rem;">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($prof['max_weekly_hours']) ?>h</td>
                        <td>
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="?page=professionals_new&id=<?= $prof['id'] ?>" class="btn" style="color: var(--primary-color); padding: 0.5rem;" title="Editar"><i class="fa-solid fa-pen"></i></a>
                                <a href="?page=professionals_delete&id=<?= $prof['id'] ?>" 
                                   class="btn text-danger" 
                                   style="color: var(--danger-color); padding: 0.5rem;" 
                                   title="Excluir"
                                   onclick="return confirm('Tem certeza que deseja excluir este profissional? Esta ação não pode ser desfeita.')">
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
