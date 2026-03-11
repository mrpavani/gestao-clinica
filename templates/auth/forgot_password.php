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
        
        .login-logo {
            max-height: 80px;
            margin-bottom: 1rem;
        }
        
        .login-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .login-subtitle {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .input-group {
            position: relative;
            margin-bottom: 2rem;
        }

        .input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-tertiary);
        }

        .input-group input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.8rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            background: var(--surface-secondary);
            transition: all 0.3s;
        }

        .input-group input:focus {
            border-color: var(--primary-color);
            background: var(--surface-color);
        }

        .btn-recover {
            width: 100%;
            padding: 0.9rem;
            font-weight: 600;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
            text-decoration: none;
        }

        .simulation-box {
            background: var(--surface-secondary);
            border-left: 4px solid var(--primary-color);
            padding: 1.5rem;
            border-radius: var(--radius-md);
            margin-top: 1.5rem;
            font-size: 0.9rem;
            line-height: 1.5;
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

            <form method="POST" autocomplete="off">
                <div class="input-group">
                    <i class="fa-solid fa-user"></i>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required 
                        autofocus
                        placeholder="Nome de Usuário"
                    >
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

    <script src="public/assets/js/notifications.js"></script>
    <script>
        <?php if ($error): ?>
            document.addEventListener('DOMContentLoaded', () => {
                showNotification('<?= addslashes($error) ?>', 'error');
            });
        <?php endif; ?>
    </script>
</body>
</html>
