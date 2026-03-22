<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Nexo System</title>
    <link rel="icon" type="image/png" href="public/assets/img/logo.png">
    <link rel="stylesheet" href="public/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="public/assets/css/notifications.css">
    <style>
        /* Variáveis locais complementares ao style.css */
        :root {
            --surface-secondary: #F3F4F6;
            --border-color: #D1D5DB;
            --text-tertiary: #9CA3AF;
        }

        /* Reset do body para o layout de login (sobrescreve o display:flex do app) */
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
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));
        }

        .login-title {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
            letter-spacing: -0.5px;
        }

        .login-subtitle {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .input-group {
            position: relative;
            margin-bottom: 1.25rem;
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
            padding: 0 2.6rem;
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

        .input-group input:focus + .input-icon,
        .input-group:focus-within .input-icon {
            color: var(--primary-color);
        }

        /* Autofill fix */
        .input-group input:-webkit-autofill,
        .input-group input:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0 50px #F3F4F6 inset !important;
            -webkit-text-fill-color: var(--text-primary) !important;
        }

        .toggle-password {
            position: absolute;
            right: 0.75rem;
            bottom: 0;
            height: 44px;
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-tertiary);
            padding: 0 0.25rem;
            display: flex;
            align-items: center;
            transition: color 0.2s;
            z-index: 2;
        }

        .toggle-password:hover {
            color: var(--primary-color);
        }

        .btn-login {
            width: 100%;
            height: 46px;
            padding: 0 1.5rem;
            font-weight: 600;
            font-size: 1rem;
            margin-top: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            border-radius: var(--radius-md);
            cursor: pointer;
        }

        .forgot-link {
            display: block;
            text-align: center;
            margin-top: 1.25rem;
            color: var(--primary-color);
            font-size: 0.875rem;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .forgot-link:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }

        .form-footer {
            margin-top: 2rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border-color);
            text-align: center;
            color: var(--text-tertiary);
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <?php
    // Flash Messages handling for notifications.js
    if (isset($_SESSION['error_msg'])) {
        echo '<div id="php-flash-error" data-flash-error="' . htmlspecialchars($_SESSION['error_msg']) . '" style="display:none;"></div>';
        unset($_SESSION['error_msg']);
    }
    if (isset($_SESSION['success_msg'])) {
        echo '<div id="php-flash-success" data-flash-success="' . htmlspecialchars($_SESSION['success_msg']) . '" style="display:none;"></div>';
        unset($_SESSION['success_msg']);
    }
    ?>
    <div id="notification-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>

    <div class="login-container">
        <div class="login-header">
            <img src="public/assets/img/logo.png" alt="Nexo Logo" class="login-logo">
            <h1 class="login-title">Nexo System</h1>
            <p class="login-subtitle">Acesso Restrito ao Sistema</p>
        </div>

        <form method="POST" action="?page=login_action">
            <div class="input-group">
                <label for="username">Usuário</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    required
                    autofocus
                    placeholder="Digite seu usuário"
                    autocomplete="username"
                >
                <span class="input-icon"><i class="fa-solid fa-user"></i></span>
            </div>

            <div class="input-group">
                <label for="password">Senha</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    placeholder="Digite sua senha"
                    autocomplete="current-password"
                >
                <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                <button type="button" class="toggle-password" id="togglePassword" title="Mostrar/ocultar senha" aria-label="Mostrar senha">
                    <i class="fa-solid fa-eye" id="togglePasswordIcon"></i>
                </button>
            </div>

            <button type="submit" class="btn btn-primary btn-login">
                <i class="fa-solid fa-right-to-bracket"></i> Entrar no Sistema
            </button>

            <a href="?page=forgot_password" class="forgot-link">
                <i class="fa-solid fa-key" style="font-size:0.8rem;"></i> Esqueceu sua senha?
            </a>
        </form>

        <div class="form-footer">
            <p>&copy; 2026 Nexo System. Eficiência em Gestão.</p>
        </div>
    </div>

    <script src="public/assets/js/ui-helper.js"></script>
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            <?php if (isset($_SESSION['error_msg'])): ?>
            UI.showToast(<?= json_encode($_SESSION['error_msg']) ?>, 'error');
            <?php unset($_SESSION['error_msg']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['success_msg'])): ?>
            UI.showToast(<?= json_encode($_SESSION['success_msg']) ?>, 'success');
            <?php unset($_SESSION['success_msg']); ?>
            <?php endif; ?>

            // Toggle password visibility
            const toggleBtn = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('togglePasswordIcon');

            if (toggleBtn && passwordInput) {
                toggleBtn.addEventListener('click', () => {
                    const isPassword = passwordInput.type === 'password';
                    passwordInput.type = isPassword ? 'text' : 'password';
                    toggleIcon.className = isPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
                    toggleBtn.setAttribute('aria-label', isPassword ? 'Ocultar senha' : 'Mostrar senha');
                });
            }
        });
    </script>
</body>
</html>
