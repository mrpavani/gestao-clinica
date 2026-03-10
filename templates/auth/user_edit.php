<?php
// templates/auth/user_edit.php
if (!AuthController::isAdmin()) {
    header('Location: ?page=dashboard');
    exit;
}

$controller = new AuthController();
$profController = new ProfessionalController();
$professionals = $profController->getAll();

$userId = $_GET['id'] ?? null;
if (!$userId) {
    header('Location: ?page=users');
    exit;
}

// Fetch user
$pdo = Database::getInstance()->getConnection();
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ?page=users&error=' . urlencode('Usuário não encontrado.'));
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $controller->updateUser(
        $userId,
        $_POST['username'],
        $_POST['password'] ?? '',
        $_POST['role'],
        $_POST['professional_id'] ?? null
    );
    if ($result['success']) {
        header('Location: ?page=users&success=1');
        exit;
    } else {
        $error = $result['message'];
    }
}
?>

<header>
    <h1>Editar Usuário</h1>
    <a href="?page=users" class="btn" style="background: #e5e7eb; color: var(--text-primary);">
        <i class="fa-solid fa-arrow-left"></i> Voltar
    </a>
</header>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <?php if ($error): ?>
        <div style="background: #FEE2E2; color: #991B1B; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="username">Nome de Usuário *</label>
            <input type="text" id="username" name="username" required value="<?= htmlspecialchars($user['username']) ?>">
        </div>

        <div class="form-group">
            <label for="password">Nova Senha <small style="color: var(--text-secondary); font-weight: normal;">(deixe em branco para não alterar)</small></label>
            <input type="password" id="password" name="password" placeholder="Mínimo 6 caracteres" minlength="6">
        </div>

        <div class="form-group">
            <label for="role">Função *</label>
            <select id="role" name="role" required onchange="toggleProfessionalSelect(this.value)">
                <option value="professional" <?= $user['role'] === 'professional' ? 'selected' : '' ?>>Profissional</option>
                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
            </select>
        </div>

        <div class="form-group" id="professionalGroup" style="<?= $user['role'] !== 'professional' ? 'display:none;' : '' ?>">
            <label for="professional_id">Vínculo com Profissional</label>
            <select id="professional_id" name="professional_id">
                <option value="">Nenhum</option>
                <?php foreach ($professionals as $prof): ?>
                    <option value="<?= $prof['id'] ?>" <?= ($user['professional_id'] == $prof['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($prof['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
            <button type="submit" class="btn btn-primary" style="flex: 1;">
                <i class="fa-solid fa-save"></i> Salvar Alterações
            </button>
            <a href="?page=users" class="btn" style="flex: 1; text-align: center; background: #e5e7eb; color: var(--text-primary);">
                <i class="fa-solid fa-times"></i> Cancelar
            </a>
        </div>
    </form>
</div>

<script>
function toggleProfessionalSelect(role) {
    const professionalGroup = document.getElementById('professionalGroup');
    professionalGroup.style.display = (role === 'professional') ? 'block' : 'none';
}
</script>
