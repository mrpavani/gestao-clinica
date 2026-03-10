<?php
// templates/auth/change_password.php

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($newPassword !== $confirmPassword) {
        $error = "As novas senhas não coincidem.";
    } else {
        $authController = new AuthController();
        $result = $authController->changePassword($_SESSION['user_id'], $currentPassword, $newPassword);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}
?>

<div style="max-width: 600px; margin: 0 auto; background: white; padding: 2rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid #e5e7eb;">
    <h2 style="margin-top: 0; margin-bottom: 1.5rem; color: var(--text-primary);">Alterar Senha</h2>

    <?php if ($error): ?>
        <div style="background: #FEE2E2; color: #991B1B; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div style="background: #D1FAE5; color: #065F46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group" style="margin-bottom: 1rem;">
            <label for="current_password" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Senha Atual</label>
            <input type="password" id="current_password" name="current_password" class="form-control" required style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: var(--radius-md);">
        </div>
        
        <div class="form-group" style="margin-bottom: 1rem;">
            <label for="new_password" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Nova Senha</label>
            <input type="password" id="new_password" name="new_password" class="form-control" required minlength="6" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: var(--radius-md);">
            <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.25rem;">Mínimo 6 caracteres</div>
        </div>
        
        <div class="form-group" style="margin-bottom: 2rem;">
            <label for="confirm_password" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Confirmar Nova Senha</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="6" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: var(--radius-md);">
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 1rem;">
            <a href="?page=dashboard" class="btn" style="background: #f3f4f6; color: var(--text-primary); text-decoration: none; padding: 0.75rem 1.5rem; border-radius: var(--radius-md);">Cancelar</a>
            <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.5rem;">Alterar Senha</button>
        </div>
    </form>
</div>
