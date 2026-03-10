<?php
// templates/professionals/list.php
$controller = new ProfessionalController();
$professionals = $controller->getAllWithSkills();
require_once __DIR__ . '/../../src/Controllers/BranchController.php';
$branchController = new BranchController();
$branches = $branchController->getAll();
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
                <th>Especialidades</th>
                <th>Conhecimentos/Habilidades</th>
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
                        <td style="font-weight: 500;"><?= htmlspecialchars($prof['name']) ?></td>
                        <td>
                            <?php if (!empty($prof['specialties'])): ?>
                                <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                                    <?php foreach ($prof['specialties'] as $spec): ?>
                                        <span style="background: var(--primary-color); color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 500;">
                                            <?= htmlspecialchars($spec['name']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span style="color: var(--text-secondary); font-size: 0.85rem;">-</span>
                            <?php endif; ?>
                        </td>
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
                        <td>
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="?page=professionals_new&id=<?= $prof['id'] ?>" class="btn" style="color: var(--primary-color); padding: 0.5rem;" title="Editar"><i class="fa-solid fa-pen"></i></a>
                                <button type="button" class="btn" style="color: #6366F1; padding: 0.5rem;" title="Transferir Filial"
                                    onclick="openTransferModal(<?= $prof['id'] ?>, '<?= htmlspecialchars(addslashes($prof['name'])) ?>')">
                                    <i class="fa-solid fa-right-left"></i>
                                </button>
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

<!-- Transfer Branch Modal -->
<div id="transferModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000;">
    <div class="card" style="width: 100%; max-width: 450px; margin: 2rem;">
        <h2 style="margin-bottom: 0.5rem;">Transferir de Filial</h2>
        <p id="transferProfName" style="color: var(--text-secondary); margin-bottom: 1.5rem; font-size: 0.95rem;"></p>
        <form method="POST" action="?page=professional_transfer">
            <input type="hidden" name="professional_id" id="transferProfId">
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
function openTransferModal(profId, profName) {
    document.getElementById('transferProfId').value = profId;
    document.getElementById('transferProfName').textContent = 'Profissional: ' + profName;
    document.getElementById('transferModal').style.display = 'flex';
}
</script>
