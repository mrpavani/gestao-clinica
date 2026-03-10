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
        
        // Em um sistema real, aqui enviariamos um e-mail. 
        // Como não temos SMTP, vamos apenas simular para o admin poder copiar o link ou mostrar direto se deu certo.
        if ($result['success']) {
            $token = $result['token'];
            $recoverLink = "http://" . $_SERVER['HTTP_HOST'] . "/?page=reset_password&token=" . $token;
            // Para fim de demonstração/teste local, vamos mostrar o link direto na mensagem de sucesso. 
            // O ideal seria enviar por e-mail silenciosamente.
            $success = "Instruções geradas! (Simulação de Envio de E-mail) <br><br> Acesse o link para redefinir: <br> <a href='{$recoverLink}' style='color: var(--primary-color); font-weight: bold; word-break: break-all;'>{$recoverLink}</a>";
        } else {
            // Mensagem genérica para não vazar se o usuário existe ou não, ou mostra erro
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
        
        .login-subtitle {
            color: var(--text-secondary);
            font-size: 0.9rem;
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
            <h1 class="login-title">Recuperação de Senha</h1>
            <p class="login-subtitle">Informe seu usuário para obter instruções</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-triangle-exclamation"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-check-circle"></i> <?= $success ?>
            </div>
            
            <div style="text-align: center; margin-top: 2rem;">
                <a href="?page=login" style="color: var(--primary-color); font-weight: 500; text-decoration: none;">Voltar ao Login</a>
            </div>
        <?php else: ?>

            <form method="POST" autocomplete="off">
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="username">
                        <i class="fa-solid fa-user"></i> Nome de Usuário
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required 
                        autofocus
                        placeholder="Digite seu nome de usuário"
                        style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: var(--radius-md);"
                    >
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fa-solid fa-paper-plane"></i> Enviar Instruções
                </button>
                
                <div style="text-align: center; margin-top: 1.5rem;">
                    <a href="?page=login" style="color: var(--text-secondary); font-size: 0.9rem; text-decoration: none;"><i class="fa-solid fa-arrow-left"></i> Voltar ao Login</a>
                </div>
            </form>
            
        <?php endif; ?>
    </div>
</body>
</html>
