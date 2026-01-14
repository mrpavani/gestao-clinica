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
                            <a href="?page=therapies_new&id=<?= $therapy['id'] ?>" class="btn" style="color: var(--primary-color); padding: 0.5rem;"><i class="fa-solid fa-pen"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
