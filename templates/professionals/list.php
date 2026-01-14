<?php
// templates/professionals/list.php
$controller = new ProfessionalController();
$professionals = $controller->getAll();
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
                <th>Carga Horária (Semanal)</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($professionals)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: var(--text-secondary);">Nenhum profissional cadastrado.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($professionals as $prof): ?>
                    <tr>
                        <td><?= htmlspecialchars($prof['name']) ?></td>
                        <td><?= htmlspecialchars($prof['specialty']) ?></td>
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
