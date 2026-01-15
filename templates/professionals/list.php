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
                            <a href="?page=professionals_new&id=<?= $prof['id'] ?>" class="btn" style="color: var(--primary-color); padding: 0.5rem;"><i class="fa-solid fa-pen"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
