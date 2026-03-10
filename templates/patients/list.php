<?php
// templates/patients/list.php
$controller = new PatientController();
$patients = $controller->getAll();
require_once __DIR__ . '/../../src/Controllers/BranchController.php';
$branchController = new BranchController();
$branches = $branchController->getAll();
?>

<header>
    <h1>Pacientes</h1>
    <a href="?page=patients_new" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> Novo Paciente
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
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="?page=patients_view&id=<?= $p['id'] ?>" class="btn" style="color: var(--primary-color); padding: 0.5rem;" title="Ver Detalhes/Pacotes">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="?page=patients_new&id=<?= $p['id'] ?>" class="btn" style="color: var(--text-secondary); padding: 0.5rem;" title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <button type="button" class="btn" style="color: #6366F1; padding: 0.5rem;" title="Transferir Filial"
                                    onclick="openTransferModal(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>')">
                                    <i class="fa-solid fa-right-left"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Transfer Branch Modal -->
<div id="transferModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000;">
    <div class="card" style="width: 100%; max-width: 450px; margin: 2rem;">
        <h2 style="margin-bottom: 0.5rem;">Transferir de Filial</h2>
        <p id="transferPatientName" style="color: var(--text-secondary); margin-bottom: 1.5rem; font-size: 0.95rem;"></p>
        <form method="POST" action="?page=patient_transfer">
            <input type="hidden" name="patient_id" id="transferPatientId">
            <div class="form-group">
                <label for="new_branch_id">Nova Filial</label>
                <select name="new_branch_id" id="new_branch_id" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i class="fa-solid fa-right-left"></i> Confirmar Transferência
                </button>
                <button type="button" class="btn" style="flex: 1;" onclick="document.getElementById('transferModal').style.display='none'">
                    <i class="fa-solid fa-times"></i> Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openTransferModal(patientId, patientName) {
    document.getElementById('transferPatientId').value = patientId;
    document.getElementById('transferPatientName').textContent = 'Paciente: ' + patientName;
    document.getElementById('transferModal').style.display = 'flex';
}
</script>
