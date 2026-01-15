<?php
// templates/auth/users_list.php
if (!AuthController::isAdmin()) {
    header('Location: ?page=dashboard');
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
    $message = $result;
    header('Location: ?page=users');
    exit;
}
?>

<header>
    <h1>Gerenciar Usuários</h1>
    <button class="btn btn-primary" onclick="document.getElementById('userModal').style.display='flex'">
        <i class="fa-solid fa-plus"></i> Novo Usuário
    </button>
</header>

<?php if (!empty($message)): ?>
<div class="card" style="margin-bottom: 1.5rem; padding: 1rem; background: <?= $message['success'] ? '#d1fae5' : '#fee' ?>; border: 1px solid <?= $message['success'] ? '#6ee7b7' : '#fcc' ?>;">
    <p style="color: <?= $message['success'] ? '#065f46' : '#c33' ?>; margin: 0;">
        <i class="fa-solid fa-<?= $message['success'] ? 'check-circle' : 'exclamation-triangle' ?>"></i>
        <?= htmlspecialchars($message['message']) ?>
    </p>
</div>
<?php endif; ?>

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
                                <a href="?page=users&delete_id=<?= $user['id'] ?>" 
                                   class="btn" 
                                   style="color: var(--danger-color); padding: 0.5rem;" 
                                   title="Excluir"
                                   onclick="return confirm('Tem certeza que deseja excluir este usuário?')">
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
        <form method="POST">
            <input type="hidden" name="action" value="create_user">
            
            <div class="form-group">
                <label for="username">Nome de Usuário</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" required minlength="6">
                <small style="color: var(--text-secondary)">Mínimo 6 caracteres</small>
            </div>
            
            <div class="form-group">
                <label for="role">Função</label>
                <select id="role" name="role" required onchange="toggleProfessionalSelect(this.value)">
                    <option value="professional">Profissional</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
            
            <div class="form-group" id="professionalGroup">
                <label for="professional_id">Profissional *</label>
                <select id="professional_id" name="professional_id">
                    <option value="">Selecione...</option>
                    <?php
                    $profController = new ProfessionalController();
                    $professionals = $profController->getAll();
                    foreach ($professionals as $prof):
                    ?>
                        <option value="<?= $prof['id'] ?>"><?= htmlspecialchars($prof['name']) ?></option>
                    <?php endforeach; ?>
                </select>
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
