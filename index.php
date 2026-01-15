<?php
// index.php (Moved to root)
// Simple router/dispatcher

// Error handling wrapper
try {
    // DEBUGGING ENABLED
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    // Start session
    session_start();
    
    require_once __DIR__ . '/src/Database.php';
    require_once __DIR__ . '/src/Controllers/AuthController.php';

    // Quick auto-loader if needed later, for now we will manually include or use spl_autoload
    spl_autoload_register(function ($class_name) {
        if (file_exists(__DIR__ . '/src/Controllers/' . $class_name . '.php')) {
            require_once __DIR__ . '/src/Controllers/' . $class_name . '.php';
        } elseif (file_exists(__DIR__ . '/src/' . $class_name . '.php')) {
            require_once __DIR__ . '/src/' . $class_name . '.php';
        }
    });

    // Basic Routing
    $page = $_GET['page'] ?? 'dashboard';
    
    // Handle login action
    if ($page === 'login_action') {
        $auth = new AuthController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if ($auth->login($username, $password)) {
                header('Location: ?page=dashboard');
                exit;
            } else {
                header('Location: ?page=login&error=invalid');
                exit;
            }
        }
        header('Location: ?page=login');
        exit;
    }
    
    // Handle logout
    if ($page === 'logout') {
        $auth = new AuthController();
        $auth->logout();
        header('Location: ?page=login&error=logout');
        exit;
    }
    
    // Check authentication for all pages except login
    if ($page !== 'login' && !AuthController::isAuthenticated()) {
        header('Location: ?page=login&error=unauthorized');
        exit;
    }
    
    // Get current user for sidebar
    $currentUser = AuthController::getCurrentUser();


?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão Clínica - Autismo</title>
    <link rel="stylesheet" href="public/assets/css/style.css">
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php if ($page !== 'login'): ?>
    <nav class="sidebar">
        <div class="brand">
            <img src="public/assets/img/logo.png" alt="Nexo Logo" class="brand-logo">
            <span>Nexo System</span>
        </div>
        <ul class="nav-links">
            <li class="nav-item">
                <a href="?page=dashboard" class="<?= $page === 'dashboard' ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-pie"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="?page=professionals" class="<?= $page === 'professionals' || $page === 'professionals_new' ? 'active' : '' ?>">
                    <i class="fa-solid fa-user-doctor"></i> Profissionais
                </a>
            </li>
            <li class="nav-item">
                <a href="?page=patients" class="<?= $page === 'patients' ? 'active' : '' ?>">
                    <i class="fa-solid fa-users"></i> Pacientes
                </a>
            </li>
            <li class="nav-item">
                <a href="?page=therapies" class="<?= $page === 'therapies' ? 'active' : '' ?>">
                    <i class="fa-solid fa-hands-holding-child"></i> Terapias
                </a>
            </li>
            <li class="nav-item">
                <a href="?page=schedule" class="<?= $page === 'schedule' ? 'active' : '' ?>">
                    <i class="fa-regular fa-calendar-days"></i> Agenda
                </a>
            </li>
        </ul>
        
        <!-- User Menu --><div class="user-menu">
            <div class="user-info">
                <i class="fa-solid fa-circle-user"></i>
                <span><?= htmlspecialchars($currentUser['username'] ?? '') ?></span>
            </div>
            <?php if (AuthController::isAdmin()): ?>
            <a href="?page=users" class="btn" style="font-size: 0.85rem; padding: 0.5rem 0.75rem; margin-bottom: 0.5rem;">
                <i class="fa-solid fa-users-gear"></i> Gerenciar Usuários
            </a>
            <?php endif; ?>
            <a href="?page=logout" class="btn" style="font-size: 0.85rem; padding: 0.5rem 0.75rem;">
                <i class="fa-solid fa-right-from-bracket"></i> Sair
            </a>
        </div>
    </nav>

    <main class="main-content">
    <?php else: ?>
    <!-- Login page renders its own complete layout -->
    <?php endif; ?>
        <?php
        // Simple View Router
        switch($page) {
            case 'login':
                require_once __DIR__ . '/templates/auth/login.php';
                break;
                
            case 'dashboard':
                require_once __DIR__ . '/templates/dashboard/index.php';
                break;
            
            case 'professionals':
                // We will move this to a controller later, doing inline for speed on first pass
                require_once __DIR__ . '/templates/professionals/list.php';
                break;

            case 'professionals_new':
                require_once __DIR__ . '/templates/professionals/form.php';
                break;
            
            case 'patients':
                require_once __DIR__ . '/templates/patients/list.php';
                break;
            
            case 'patients_new':
                require_once __DIR__ . '/templates/patients/form.php';
                break;

            case 'patients_view':
                require_once __DIR__ . '/templates/patients/view.php';
                break;
            
            case 'therapies':
                require_once __DIR__ . '/templates/therapies/list.php';
                break;
            
            case 'therapies_new':
                require_once __DIR__ . '/templates/therapies/form.php';
                break;
                
            case 'schedule':
                require_once __DIR__ . '/templates/schedule/calendar.php';
                break;
                
            case 'appointment_notes':
                 require_once __DIR__ . '/templates/appointments/notes.php';
                 break;

            case 'patients_record':
                 require_once __DIR__ . '/templates/patients/record.php';
                 break;
            
            case 'report':
                include __DIR__ . '/templates/reports/patient_report.php';
                break;
            case 'patient_package_edit':
                include __DIR__ . '/templates/patients/package_edit.php';
                break;
            case 'users':
                include __DIR__ . '/templates/auth/users_list.php';
                break;
            default:
                echo "<h2>Página não encontrada</h2>";
                break;
        }
        ?>
    <?php if ($page !== 'login'): ?>
    </main>
    <?php endif; ?>
</body>
</html>
<?php
} catch (Exception $e) {
    // Display error in a user-friendly format
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Error</title></head><body>';
    echo '<div style="font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #fee; border: 2px solid #c33; border-radius: 8px;">';
    echo '<h2 style="color: #c33;">⚠️ Erro na Aplicação</h2>';
    echo '<p><strong>Mensagem:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><strong>Arquivo:</strong> ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</p>';
    echo '<pre style="background: #fff; padding: 10px; border-radius: 4px; overflow-x: auto;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</div></body></html>';
}
?>
