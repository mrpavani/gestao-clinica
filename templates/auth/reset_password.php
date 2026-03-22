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
    <link rel="stylesheet" href="public/assets/css/notifications.css">
    <style>
        :root {
            --surface-secondary: #F3F4F6;
            --border-color: #D1D5DB;
            --text-tertiary: #9CA3AF;
            --success-color: #059669;
        }

        body {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            height: auto;
            background: #ffffff;
            margin: 0;
            font-family: 'Outfit', sans-serif;
        }

        .login-container {
            background: var(--surface-color);
            border-radius: var(--radius-lg);
            padding: 3rem;
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 420px;
            animation: fadeIn 0.4s ease-out;
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 1rem;
                padding: 2rem 1.5rem;
                max-width: calc(100% - 2rem);
                border-radius: var(--radius-md);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
        }

        .login-subtitle {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .input-group {
            margin-bottom: 1.25rem;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .input-group input {
            width: 100%;
            height: 44px;
            padding: 0 1rem;
            border: 1.5px solid var(--border-color);
            border-radius: var(--radius-md);
            background: var(--surface-secondary);
            font-family: 'Outfit', sans-serif;
            font-size: 0.95rem;
            color: var(--text-primary);
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
            box-sizing: border-box;
        }

        .input-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(46, 134, 171, 0.12);
            background: #fff;
        }

        .input-group input:-webkit-autofill,
        .input-group input:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0 50px #F3F4F6 inset !important;
            -webkit-text-fill-color: var(--text-primary) !important;
        }

        .btn-reset {
            width: 100%;
            height: 46px;
            font-weight: 600;
            font-size: 1rem;
            margin-top: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            border-radius: var(--radius-md);
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 1.25rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
            text-decoration: none;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: var(--primary-color);
        }

        .success-box {
            text-align: center;
            padding: 1rem 0;
        }

        .success-box p {
            color: var(--success-color);
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }

        .error-box {
            background: #FEF2F2;
            border-left: 4px solid var(--danger-color);
            padding: 1rem 1.25rem;
            border-radius: var(--radius-md);
            color: var(--danger-color);
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div id="notification-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>

    <div class="login-container">
        <div class="login-header">
            <img src="public/assets/img/logo.png" alt="Nexo Logo" style="max-height: 72px; margin-bottom: 1rem; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));">
            <h1 class="login-title">Redefinir Senha</h1>
            <p class="login-subtitle">Crie uma nova senha para sua conta</p>
        </div>

        <?php if ($error && !$isValidToken): ?>
            <div class="error-box">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
            </div>
            <a href="?page=forgot_password" class="btn btn-primary" style="width:100%; display:flex; align-items:center; justify-content:center; gap:0.5rem; height:46px;">
                <i class="fa-solid fa-rotate-right"></i> Solicitar Novo Link
            </a>
            <a href="?page=login" class="back-link">
                <i class="fa-solid fa-arrow-left"></i> Voltar ao Login
            </a>
        <?php elseif ($success): ?>
            <div class="success-box">
                <i class="fa-solid fa-circle-check" style="font-size:2.5rem; color: var(--success-color); display:block; margin-bottom:0.75rem;"></i>
                <p><?= htmlspecialchars($success) ?></p>
                <a href="?page=login" class="btn btn-primary" style="width:100%; display:flex; align-items:center; justify-content:center; gap:0.5rem; height:46px;">
                    <i class="fa-solid fa-right-to-bracket"></i> Fazer Login
                </a>
            </div>
        <?php else: ?>
            <form method="POST">
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

                <div class="input-group">
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
                    <i class="fa-solid fa-floppy-disk"></i> Salvar Nova Senha
                </button>

                <a href="?page=login" class="back-link">
                    <i class="fa-solid fa-arrow-left"></i> Voltar ao Login
                </a>
            </form>
        <?php endif; ?>
    </div>

    <script src="public/assets/js/ui-helper.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            <?php if ($error && $isValidToken): ?>
            UI.showToast(<?= json_encode($error) ?>, 'error');
            <?php endif; ?>
        });
    </script>
</body>
</html>
