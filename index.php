<?php
// index.php (VERIFICATION_TAG_12345)
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
    $page = $_GET['page'] ?? (AuthController::isProfessional() ? 'schedule' : 'dashboard');
    
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
    
    // Check authentication for all pages except public
    $publicPages = ['login', 'login_action', 'select_branch', 'forgot_password', 'reset_password'];
    if (!in_array($page, $publicPages) && !AuthController::isAuthenticated()) {
        header('Location: ?page=login&error=unauthorized');
        exit;
    }
    
    // Require branch selection for authenticated users (except on select_branch page)
    require_once __DIR__ . '/src/Controllers/BranchController.php';
    if (AuthController::isAuthenticated() && $page !== 'select_branch' && $page !== 'login' && $page !== 'logout') {
        if (!BranchController::hasBranchSelected()) {
            header('Location: ?page=select_branch');
            exit;
        }
    }
    
    // Get current user for sidebar
    $currentUser = AuthController::getCurrentUser();
    $currentBranchName = BranchController::getCurrentBranchName();


?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexo System</title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link rel="icon" type="image/png" href="public/assets/img/logo.png">
    <link rel="stylesheet" href="public/assets/css/style.css?v=<?=time()?>">
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php $hideNavPages = ['login', 'select_branch', 'forgot_password', 'reset_password']; ?>
    <?php if (!in_array($page, $hideNavPages)): ?>
    <nav class="sidebar">
        <div class="brand">
            <img src="public/assets/img/logo.png" alt="Nexo Logo" class="brand-logo">
            <span>Nexo System</span>
        </div>
        
        <!-- Current Branch Badge -->
        <?php if ($currentBranchName): ?>
        <div style="background: #E0F2FE; border-radius: var(--radius-md); padding: 0.5rem 0.75rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fa-solid fa-building" style="color: var(--primary-color);"></i>
            <span style="font-weight: 500; color: var(--primary-color); font-size: 0.9rem;"><?= htmlspecialchars($currentBranchName) ?></span>
            <a href="?page=select_branch" style="margin-left: auto; font-size: 0.8rem; color: var(--text-secondary);" title="Trocar Filial">
                <i class="fa-solid fa-arrows-rotate"></i>
            </a>
        </div>
        <?php endif; ?>
        
        <ul class="nav-links">
            <?php if (AuthController::isAdmin()): ?>
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
                <a href="?page=patients" class="<?= strpos($page, 'patient') === 0 ? 'active' : '' ?>">
                    <i class="fa-solid fa-users"></i> Pacientes
                </a>
            </li>
            <li class="nav-item">
                <a href="?page=therapies" class="<?= $page === 'therapies' ? 'active' : '' ?>">
                    <i class="fa-solid fa-hands-holding-child"></i> Terapias
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a href="?page=schedule" class="<?= $page === 'schedule' ? 'active' : '' ?>">
                    <i class="fa-regular fa-calendar-days"></i> Agenda
                </a>
            </li>
            <?php if (AuthController::isAdmin()): ?>
            <li class="nav-item" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255, 255, 255, 0.1);">
                <a href="?page=settings" class="<?= in_array($page, ['settings', 'specialties', 'specialties_new']) ? 'active' : '' ?>">
                    <i class="fa-solid fa-gear"></i> Configurações
                </a>
            </li>
            <?php endif; ?>
        </ul>
        
        <!-- User Menu --><div class="sidebar-footer-menu">
            <div class="user-info">
                <i class="fa-solid fa-circle-user"></i>
                <span><?= htmlspecialchars($currentUser['username'] ?? '') ?></span>
            </div>
            
            <a href="?page=change_password" class="btn" style="margin-bottom: 0.5rem; background: transparent; border: 1px solid rgba(255,255,255,0.2); color: white;">
                <i class="fa-solid fa-key"></i> Alterar Senha
            </a>
            
            <?php if (AuthController::isAdmin()): ?>
            <a href="?page=branches" class="btn">
                <i class="fa-solid fa-building"></i> Gerenciar Filiais
            </a>
            <a href="?page=users" class="btn">
                <i class="fa-solid fa-users-gear"></i> Gerenciar Usuários
            </a>
            <?php endif; ?>
            <a href="?page=logout" class="btn" style="background: #ef4444; color: white;">
                <i class="fa-solid fa-right-from-bracket"></i> Sair
            </a>
        </div>
    </nav>

    <main class="main-content">
    <?php else: ?>
    <!-- Full screen page renders its own complete layout -->
    <?php endif; ?>
        <?php
        // Simple View Router
        
        $isAdmin = AuthController::isAdmin();
        
        switch($page) {
            case 'login':
                require_once __DIR__ . '/templates/auth/login.php';
                break;

            case 'forgot_password':
                require_once __DIR__ . '/templates/auth/forgot_password.php';
                break;

            case 'reset_password':
                require_once __DIR__ . '/templates/auth/reset_password.php';
                break;
            
            case 'select_branch':
                require_once __DIR__ . '/templates/auth/select_branch.php';
                break;
                
            case 'dashboard':
                if (!$isAdmin) { header('Location: ?page=schedule'); exit; }
                require_once __DIR__ . '/templates/dashboard/index.php';
                break;
            
            case 'branches':
                if (!$isAdmin) { header('Location: ?page=schedule'); exit; }
                require_once __DIR__ . '/templates/branches/list.php';
                break;
            
            case 'branches_new':
                if (!$isAdmin) { header('Location: ?page=schedule'); exit; }
                require_once __DIR__ . '/templates/branches/form.php';
                break;
            
            case 'settings':
                if (!$isAdmin) { header('Location: ?page=schedule'); exit; }
                require_once __DIR__ . '/templates/settings/index.php';
                break;
            
            case 'professionals':
                if (!$isAdmin) { header('Location: ?page=schedule'); exit; }
                require_once __DIR__ . '/templates/professionals/list.php';
                break;

            case 'professionals_new':
                if (!$isAdmin) { header('Location: ?page=schedule'); exit; }
                require_once __DIR__ . '/templates/professionals/form.php';
                break;
            
            case 'professionals_delete':
                if (AuthController::isAdmin()) {
                    $id = $_GET['id'] ?? null;
                    if ($id) {
                        $controller = new ProfessionalController();
                        $result = $controller->delete($id);
                        if ($result['success']) {
                            header('Location: ?page=professionals&success=deleted');
                        } else {
                            header('Location: ?page=professionals&error=' . urlencode($result['error']));
                        }
                    } else {
                        header('Location: ?page=professionals');
                    }
                } else {
                    header('Location: ?page=professionals&error=' . urlencode('Acesso negado.'));
                }
                exit;
            
            case 'patients':
                if (!$isAdmin) { header('Location: ?page=schedule'); exit; }
                require_once __DIR__ . '/templates/patients/list.php';
                break;
            
            case 'patients_new':
                if (!$isAdmin) { header('Location: ?page=schedule'); exit; }
                require_once __DIR__ . '/templates/patients/form.php';
                break;

            case 'patients_view':
                if (!$isAdmin) { header('Location: ?page=schedule'); exit; }
                require_once __DIR__ . '/templates/patients/view.php';
                break;
            
            case 'patient_edit_plan':
                if (!$isAdmin) { header('Location: ?page=schedule'); exit; }
                require_once __DIR__ . '/templates/patients/edit_plan.php';
                break;
                
            case 'patient_package_edit':
                if (!$isAdmin) { header('Location: ?page=schedule'); exit; }
                include __DIR__ . '/templates/patients/package_edit.php';
                break;
            
            case 'therapies':
                if (!$isAdmin) { header('Location: ?page=schedule'); exit; }
                require_once __DIR__ . '/templates/therapies/list.php';
                break;
            
            case 'therapies_new':
                if (!$isAdmin) { header('Location: ?page=schedule'); exit; }
                require_once __DIR__ . '/templates/therapies/form.php';
                break;
                
            case 'therapies_delete':
                if (!$isAdmin) { header('Location: ?page=schedule'); exit; }
                if (AuthController::isAdmin()) {
                    $id = $_GET['id'] ?? null;
                    if ($id) {
                        $controller = new TherapyController();
                        $result = $controller->delete($id);
                        if ($result['success']) {
                            header('Location: ?page=therapies&success=deleted');
                        } else {
                            header('Location: ?page=therapies&error=' . urlencode($result['error']));
                        }
                    } else {
                        header('Location: ?page=therapies');
                    }
                } else {
                    header('Location: ?page=therapies&error=' . urlencode('Acesso negado.'));
                }
                exit;

            case 'specialties':
                if (!$isAdmin) { header('Location: ?page=schedule'); exit; }
                require_once __DIR__ . '/templates/specialties/list.php';
                break;
            
            case 'specialties_new':
                if (!$isAdmin) { header('Location: ?page=schedule'); exit; }
                require_once __DIR__ . '/templates/specialties/form.php';
                break;

            case 'specialties_delete':
                if (!$isAdmin) { header('Location: ?page=schedule'); exit; }
                if (AuthController::isAdmin()) {
                    $id = $_GET['id'] ?? null;
                    if ($id) {
                        $controller = new SpecialtyController();
                        if ($controller->delete($id)) {
                            header('Location: ?page=specialties&success=deleted');
                        } else {
                            header('Location: ?page=specialties&error=' . urlencode('Erro ao excluir especialidade.'));
                        }
                    } else {
                        header('Location: ?page=specialties');
                    }
                } else {
                    header('Location: ?page=specialties&error=' . urlencode('Acesso negado.'));
                }
                exit;
                
                
            case 'schedule':
                require_once __DIR__ . '/templates/schedule/calendar.php';
                break;
                
            case 'appointment_notes':
                 require_once __DIR__ . '/templates/appointments/notes.php';
                 break;

            case 'appointment_edit':
                 require_once __DIR__ . '/templates/appointments/edit.php';
                 break;

            case 'patients_record':
                 require_once __DIR__ . '/templates/patients/record.php';
                 break;
            
            case 'report':
                if (!$isAdmin) { header('Location: ?page=schedule'); exit; }
                include __DIR__ . '/templates/reports/patient_report.php';
                break;
            case 'users':
                if (!$isAdmin) { header('Location: ?page=schedule'); exit; }
                include __DIR__ . '/templates/auth/users_list.php';
                break;

            case 'user_edit':
                if (!$isAdmin) { header('Location: ?page=schedule'); exit; }
                include __DIR__ . '/templates/auth/user_edit.php';
                break;
                
            case 'change_password':
                include __DIR__ . '/templates/auth/change_password.php';
                break;

            case 'professional_transfer':
                if (!$isAdmin) { header('Location: ?page=professionals'); exit; }
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $profId = $_POST['professional_id'] ?? null;
                    $newBranchId = $_POST['new_branch_id'] ?? null;
                    if ($profId && $newBranchId) {
                        $transferController = new ProfessionalController();
                        if ($transferController->changeBranch($profId, $newBranchId)) {
                            header('Location: ?page=professionals&success=transferred');
                        } else {
                            header('Location: ?page=professionals&error=' . urlencode('Erro ao transferir profissional.'));
                        }
                    } else {
                        header('Location: ?page=professionals&error=' . urlencode('Dados inválidos.'));
                    }
                } else {
                    header('Location: ?page=professionals');
                }
                exit;

            case 'patient_transfer':
                if (!$isAdmin) { header('Location: ?page=patients'); exit; }
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $patientId = $_POST['patient_id'] ?? null;
                    $newBranchId = $_POST['new_branch_id'] ?? null;
                    if ($patientId && $newBranchId) {
                        $transferController = new PatientController();
                        if ($transferController->changeBranch($patientId, $newBranchId)) {
                            header('Location: ?page=patients&success=transferred');
                        } else {
                            header('Location: ?page=patients&error=' . urlencode('Erro ao transferir paciente.'));
                        }
                    } else {
                        header('Location: ?page=patients&error=' . urlencode('Dados inválidos.'));
                    }
                } else {
                    header('Location: ?page=patients');
                }
                exit;
            default:
                echo "<h2>Página não encontrada</h2>";
                break;
        }
        ?>
    <?php if (!in_array($page, $hideNavPages)): ?>
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
