<?php
// templates/patients/list.php
$controller = new PatientController();
$patients = $controller->getAll();
?>

<header>
    <h1>Pacientes</h1>
    <a href="?page=patients_new" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> Novo Paciente
    </a>
</header>

<div class="card table-container">
    <table>
        <thead>
            <tr>
                <th>Nome do Paciente</th>
                <th>Responsável</th>
                <th>Contato</th>
                <th>Idade</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($patients)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-secondary);">Nenhum paciente cadastrado.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($patients as $p): ?>
                    <?php 
                        $age = date_diff(date_create($p['dob']), date_create('today'))->y;
                        $status = $p['status'] ?? 'active';
                        $statusLabel = match($status) {
                            'active' => 'Ativo',
                            'inactive' => 'Inativo',
                            'paused' => 'Pausado',
                            default => 'Ativo'
                        };
                        $statusColor = match($status) {
                            'active' => 'background: #DEF7EC; color: #03543F;',
                            'inactive' => 'background: #FEE2E2; color: #991B1B;',
                            'paused' => 'background: #FEF3C7; color: #92400E;',
                            default => 'background: #DEF7EC; color: #03543F;'
                        };
                    ?>
                    <tr>
                        <td style="font-weight: 500;"><?= htmlspecialchars($p['name']) ?></td>
                        <td><?= htmlspecialchars($p['guardian_name']) ?></td>
                        <td><?= htmlspecialchars($p['contact_info']) ?></td>
                        <td><?= $age ?> anos</td>
                        <td>
                            <span style="<?= $statusColor ?> padding: 0.25rem 0.6rem; border-radius: 99px; font-size: 0.85rem; font-weight: 500;">
                                <?= $statusLabel ?>
                            </span>
                        </td>
                        <td>
                            <a href="?page=patients_view&id=<?= $p['id'] ?>" class="btn" style="color: var(--primary-color); padding: 0.5rem;" title="Ver Detalhes/Pacotes">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="?page=patients_new&id=<?= $p['id'] ?>" class="btn" style="color: var(--text-secondary); padding: 0.5rem;" title="Editar">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
