<?php
// templates/auth/reset_password.php

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

$authController = new AuthController();
$isValidToken = false;

if (empty($token)) {
    $error = "Token não fornecido.";
} else {
    $isValidToken = $authController->validateResetToken($token);
    if (!$isValidToken) {
        $error = "Token inválido ou expirado. Solicite a recuperação novamente.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isValidToken) {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($newPassword !== $confirmPassword) {
        $error = "As senhas não coincidem.";
    } else {
        $result = $authController->resetPasswordWithToken($token, $newPassword);
        if ($result['success']) {
            $success = $result['message'];
            $isValidToken = false;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Nexo System</title>
    <link rel="icon" type="image/png" href="public/assets/img/logo.png">
    <link rel="stylesheet" href="public/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background: linear-gradient(135deg, #2E86AB 0%, #A2D729 100%);
            margin: 0;
            font-family: 'Inter', sans-serif;
        }
        
        .login-container {
            background: var(--surface-color);
            border-radius: var(--radius-lg);
            padding: 3rem;
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 420px;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        
        .login-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .input-group {
            margin-bottom: 1.5rem;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .input-group input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            background: var(--surface-secondary);
            transition: all 0.3s;
        }

        .input-group input:focus {
            border-color: var(--primary-color);
            background: var(--surface-color);
        }

        .btn-reset {
            width: 100%;
            padding: 0.9rem;
            font-weight: 600;
            margin-top: 1rem;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div id="notification-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>

    <div class="login-container">
        <div class="login-header">
            <img src="public/assets/img/logo.png" alt="Nexo Logo" style="max-height: 60px; margin-bottom: 1rem;">
            <h1 class="login-title">Nova Senha</h1>
        </div>

        <?php if ($success): ?>
            <div style="text-align: center; margin-top: 2rem;">
                <p style="color: var(--success-color); font-weight: 500;"><?= $success ?></p>
                <a href="?page=login" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem;">Fazer Login</a>
            </div>
        <?php elseif (!$isValidToken): ?>
            <div style="text-align: center; margin-top: 1.5rem;">
                <a href="?page=forgot_password" class="btn btn-primary" style="width: 100%;">Solicitar Novo Link</a>
                <a href="?page=login" class="back-link">Voltar ao Login</a>
            </div>
        <?php else: ?>
            <form method="POST" autocomplete="off">
                <div class="input-group">
                    <label for="new_password">Nova Senha</label>
                    <input 
                        type="password" 
                        id="new_password" 
                        name="new_password" 
                        required 
                        minlength="6"
                        autofocus
                        placeholder="Mínimo 6 caracteres"
                    >
                </div>

                <div class="input-group" style="margin-bottom: 2rem;">
                    <label for="confirm_password">Confirmar Senha</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        required 
                        minlength="6"
                        placeholder="Repita a nova senha"
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-reset">
                    <i class="fa-solid fa-save"></i> Salvar Senha
                </button>
            </form>
        <?php endif; ?>
    </div>

    <script src="public/assets/js/notifications.js"></script>
    <script>
        <?php if ($error): ?>
            document.addEventListener('DOMContentLoaded', () => {
                showNotification('<?= addslashes($error) ?>', 'error');
            });
        <?php endif; ?>
        
        <?php if ($success): ?>
            document.addEventListener('DOMContentLoaded', () => {
                showNotification('<?= addslashes($success) ?>', 'success');
            });
        <?php endif; ?>
    </script>
</body>
</html>
