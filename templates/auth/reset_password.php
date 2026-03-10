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
        $error = "Token inválido ou expirado. Solicite a recuperação de senha novamente.";
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
            $isValidToken = false; // Hide form on success
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
        }
        
        .login-container {
            background: var(--surface-color);
            border-radius: var(--radius-lg);
            padding: 3rem;
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 420px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        
        .login-logo {
            max-height: 60px;
            margin-bottom: 1rem;
        }
        
        .login-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .alert {
            padding: 0.75rem 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        .alert-error {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
        }
        .alert-success {
            background: #D1FAE5;
            border: 1px solid #A7F3D0;
            color: #065F46;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="public/assets/img/logo.png" alt="Nexo Logo" class="login-logo">
            <h1 class="login-title">Criar Nova Senha</h1>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-triangle-exclamation"></i> <?= $error ?>
            </div>
            <?php if (!$isValidToken && !$success): ?>
                <div style="text-align: center; margin-top: 1.5rem;">
                    <a href="?page=forgot_password" class="btn btn-primary" style="width: 100%;"><i class="fa-solid fa-rotate-left"></i> Tentar Novamente</a>
                    <a href="?page=login" style="display: block; margin-top: 1rem; color: var(--text-secondary); text-decoration: none;">Ir para o Login</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-check-circle"></i> <?= $success ?>
            </div>
            
            <div style="text-align: center; margin-top: 2rem;">
                <a href="?page=login" class="btn btn-primary" style="width: 100%;">Fazer Login</a>
            </div>
        <?php endif; ?>

        <?php if ($isValidToken && !$success): ?>
            <form method="POST" autocomplete="off">
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label for="new_password">Nova Senha</label>
                    <input 
                        type="password" 
                        id="new_password" 
                        name="new_password" 
                        required 
                        minlength="6"
                        autofocus
                        style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: var(--radius-md);"
                    >
                    <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.25rem;">Mínimo 6 caracteres</div>
                </div>

                <div class="form-group" style="margin-bottom: 2rem;">
                    <label for="confirm_password">Confirmar Senha</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        required 
                        minlength="6"
                        style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: var(--radius-md);"
                    >
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fa-solid fa-save"></i> Salvar Senha
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
