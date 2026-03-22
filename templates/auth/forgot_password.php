<?php
// templates/auth/forgot_password.php

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    
    if (empty($username)) {
        $error = 'Por favor, informe seu usuário.';
    } else {
        $authController = new AuthController();
        $result = $authController->generateResetToken($username);
        
        if ($result['success']) {
            $token = $result['token'];
            $recoverLink = "http://" . $_SERVER['HTTP_HOST'] . "/?page=reset_password&token=" . $token;
            // Link is shown for simulation purposes as per original file
            $success = "Instruções geradas! (Simulação)<br><br><a href='{$recoverLink}' style='color: var(--primary-color); word-break: break-all;'>Clique aqui para redefinir sua senha</a>";
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
    <title>Recuperar Senha - Nexo System</title>
    <link rel="icon" type="image/png" href="public/assets/img/logo.png">
    <link rel="stylesheet" href="public/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="public/assets/css/notifications.css">
    <style>
        :root {
            --surface-secondary: #F3F4F6;
            --border-color: #D1D5DB;
            --text-tertiary: #9CA3AF;
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

        .login-logo {
            max-height: 72px;
            margin-bottom: 1rem;
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
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.4rem;
        }

        .input-group .input-icon {
            position: absolute;
            left: 0.9rem;
            bottom: 0;
            height: 44px;
            display: flex;
            align-items: center;
            color: var(--text-tertiary);
            pointer-events: none;
            transition: color 0.2s;
        }

        .input-group input {
            width: 100%;
            height: 44px;
            padding: 0 1rem 0 2.6rem;
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

        .input-group:focus-within .input-icon {
            color: var(--primary-color);
        }

        .input-group input:-webkit-autofill,
        .input-group input:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0 50px #F3F4F6 inset !important;
            -webkit-text-fill-color: var(--text-primary) !important;
        }

        .btn-recover {
            width: 100%;
            height: 46px;
            font-weight: 600;
            font-size: 1rem;
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

        .simulation-box {
            background: #EFF6FF;
            border-left: 4px solid var(--primary-color);
            padding: 1.25rem 1.5rem;
            border-radius: var(--radius-md);
            margin-top: 1.5rem;
            font-size: 0.9rem;
            line-height: 1.6;
            color: var(--text-primary);
        }
    </style>
</head>
<body>
    <div id="notification-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>

    <div class="login-container">
        <div class="login-header">
            <img src="public/assets/img/logo.png" alt="Nexo Logo" class="login-logo">
            <h1 class="login-title">Recuperação</h1>
            <p class="login-subtitle">Informe seu usuário para obter instruções</p>
        </div>

        <?php if ($success): ?>
            <div class="simulation-box">
                <i class="fa-solid fa-circle-info" style="color: var(--primary-color); margin-bottom: 0.5rem;"></i><br>
                <?= $success ?>
            </div>
            
            <a href="?page=login" class="back-link" style="margin-top: 2rem; color: var(--primary-color); font-weight: 600;">
                <i class="fa-solid fa-arrow-left"></i> Voltar ao Login
            </a>
        <?php else: ?>

            <form method="POST">
                <div class="input-group">
                    <label for="username">Usuário</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        required
                        autofocus
                        placeholder="Nome de usuário"
                        autocomplete="username"
                    >
                    <span class="input-icon"><i class="fa-solid fa-user"></i></span>
                </div>

                <button type="submit" class="btn btn-primary btn-recover">
                    <i class="fa-solid fa-paper-plane"></i> Enviar Instruções
                </button>

                <a href="?page=login" class="back-link">
                    <i class="fa-solid fa-arrow-left"></i> Voltar para o Login
                </a>
            </form>
            
        <?php endif; ?>
    </div>

    <script src="public/assets/js/ui-helper.js"></script>
    <script>
        <?php if ($error): ?>
            document.addEventListener('DOMContentLoaded', () => {
                UI.showToast(<?= json_encode($error) ?>, 'error');
            });
        <?php endif; ?>
    </script>
</body>
</html>
