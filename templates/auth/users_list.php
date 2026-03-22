<?php
// templates/auth/users_list.php
if (!AuthController::isAdmin()) {
    echo '<script>window.location.href="?page=dashboard";</script>';
    exit;
}

$controller = new AuthController();
$users = $controller->getAllUsers();

// Handle user creation
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_user') {
    $result = $controller->createUser(
        $_POST['username'],
        $_POST['password'],
        $_POST['role'],
        $_POST['professional_id'] ?? null
    );
}

// Handle user deletion
if (isset($_GET['delete_id'])) {
    $result = $controller->deleteUser($_GET['delete_id']);
    echo '<script>window.location.href="?page=users&success=1";</script>';
    exit;
}
?>

<header>
    <h1>Gerenciar Usuários</h1>
    <button class="btn btn-primary" onclick="document.getElementById('userModal').style.display='flex'">
        <i class="fa-solid fa-plus"></i> Novo Usuário
    </button>
</header>

<script>
document.addEventListener('DOMContentLoaded', () => {
<?php if (!empty($message)): ?>
    if (window.UI) UI.showToast('<?= addslashes(htmlspecialchars($message['message'])) ?>', '<?= $message['success'] ? 'success' : 'error' ?>');
<?php endif; ?>
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
                <th>Usuário</th>
                <th>Função</th>
                <th>Profissional</th>
                <th>Último Login</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: var(--text-secondary);">Nenhum usuário cadastrado.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td style="font-weight: 500;"><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= $user['role'] === 'admin' ? 'Administrador' : 'Profissional' ?></td>
                        <td><?= htmlspecialchars($user['professional_name'] ?? '-') ?></td>
                        <td><?= $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Nunca' ?></td>
                        <td>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <a href="?page=user_edit&id=<?= $user['id'] ?>"
                                   class="btn"
                                   style="color: var(--primary-color); padding: 0.5rem;"
                                   title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <a href="#" 
                                   class="btn" 
                                   style="color: var(--danger-color); padding: 0.5rem;" 
                                   title="Excluir"
                                   onclick="event.preventDefault(); if (window.UI) UI.confirmDelete('Excluir Usuário', 'Tem certeza que deseja excluir este usuário? Esta ação não pode ser desfeita.', () => window.location.href='?page=users&delete_id=<?= $user['id'] ?>'); else if (confirm('Tem certeza?')) window.location.href='?page=users&delete_id=<?= $user['id'] ?>';">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- User Creation Modal -->
<div id="userModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000;">
    <div class="card" style="width: 100%; max-width: 500px; margin: 2rem;">
        <h2 style="margin-bottom: 1.5rem;">Novo Usuário</h2>
        <form method="POST" autocomplete="off">
            <input type="hidden" name="action" value="create_user">
            <input type="hidden" name="role" value="admin">
            
            <div class="form-group">
                <label for="username">Nome de Usuário (Acesso Administrativo)</label>
                <input type="text" id="username" name="username" required autocomplete="new-password">
            </div>
            
            <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" required minlength="6" autocomplete="new-password">
                <small style="color: var(--text-secondary)">Mínimo 6 caracteres</small>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i class="fa-solid fa-save"></i> Criar Usuário
                </button>
                <button type="button" class="btn" style="flex: 1;" onclick="document.getElementById('userModal').style.display='none'">
                    <i class="fa-solid fa-times"></i> Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleProfessionalSelect(role) {
    const professionalGroup = document.getElementById('professionalGroup');
    const professionalSelect = document.getElementById('professional_id');
    
    if (role === 'professional') {
        professionalGroup.style.display = 'block';
        professionalSelect.required = true;
    } else {
        professionalGroup.style.display = 'none';
        professionalSelect.required = false;
        professionalSelect.value = '';
    }
}
</script>
