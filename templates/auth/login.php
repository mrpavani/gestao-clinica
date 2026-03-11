<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Nexo System</title>
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
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));
        }
        
        .login-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }
        
        .login-subtitle {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .form-footer {
            margin-top: 2rem;
            text-align: center;
            color: var(--text-tertiary);
            font-size: 0.85rem;
        }

        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-tertiary);
            transition: color 0.3s;
        }

        .input-group input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.8rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            background: var(--surface-secondary);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .input-group input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(46, 134, 171, 0.1);
            background: var(--surface-color);
        }

        .input-group input:focus + i {
            color: var(--primary-color);
        }

        .btn-login {
            width: 100%;
            padding: 0.9rem;
            font-weight: 600;
            font-size: 1rem;
            margin-top: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .forgot-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: var(--primary-color);
            font-size: 0.9rem;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .forgot-link:hover {
            color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <!-- Notifications will be handled by showNotification JS if redirected with errors -->
    <div id="notification-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>

    <div class="login-container">
        <div class="login-header">
            <img src="public/assets/img/logo.png" alt="Nexo Logo" class="login-logo">
            <h1 class="login-title">Nexo System</h1>
            <p class="login-subtitle">Acesso Restrito ao Sistema</p>
        </div>

        <form method="POST" action="?page=login_action" autocomplete="off">
            <div class="input-group">
                <i class="fa-solid fa-user"></i>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    required 
                    autofocus
                    placeholder="Usuário"
                    autocomplete="off"
                >
            </div>

            <div class="input-group">
                <i class="fa-solid fa-lock"></i>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required
                    placeholder="Senha"
                    autocomplete="new-password"
                >
            </div>

            <button type="submit" class="btn btn-primary btn-login">
                <i class="fa-solid fa-right-to-bracket"></i> Entrar no Sistema
            </button>
            
            <a href="?page=forgot_password" class="forgot-link">Esqueceu sua senha?</a>
        </form>

        <div class="form-footer">
            <p>&copy; 2026 Nexo System. Eficiência em Gestão.</p>
        </div>
    </div>

    <script src="public/assets/js/notifications.js"></script>
    <script>
        // Check for error parameters in URL and show notifications
        window.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);
            const error = params.get('error');
            
            if (error === 'invalid') {
                showNotification('Usuário ou senha inválidos.', 'error');
            } else if (error === 'logout') {
                showNotification('Você foi desconectado com sucesso.', 'success');
            } else if (error === 'unauthorized') {
                showNotification('Acesso restrito. Por favor, faça login.', 'warning');
            }
            
            // Auto-clear for some browsers
            setTimeout(() => {
                document.getElementById('username') && (document.getElementById('username').value = '');
                document.getElementById('password') && (document.getElementById('password').value = '');
            }, 150);
        });
    </script>
</body>
</html>
