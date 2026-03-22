<?php
// index.php (VERIFICATION_TAG_12345)
// Simple router/dispatcher

// Error handling wrapper
try {
    // Environment detection: production = anything that is NOT localhost / 127.x
    $serverName = $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
    $isProduction = !in_array($serverName, ['localhost', '127.0.0.1', '::1']);

    if ($isProduction) {
        // Production: hide errors from visitors, log them instead
        ini_set('display_errors', 0);
        ini_set('display_startup_errors', 0);
        error_reporting(E_ALL);
        ini_set('log_errors', 1);
        ini_set('error_log', __DIR__ . '/storage/logs/php_errors.log');
    } else {
        // Local development: show all errors
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
    }


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
                $_SESSION['success_msg'] = 'Bem-vindo ao Nexo System!';
                header('Location: ?page=dashboard');
                exit;
            } else {
                $_SESSION['error_msg'] = 'Usuário ou senha inválidos.';
                header('Location: ?page=login');
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
        $_SESSION['success_msg'] = 'Você saiu do sistema.';
        header('Location: ?page=login');
        exit;
    }
    
    // Check authentication for all pages except public
    $publicPages = ['login', 'login_action', 'select_branch', 'forgot_password', 'reset_password'];
    if (!in_array($page, $publicPages) && !AuthController::isAuthenticated()) {
        $_SESSION['error_msg'] = 'Sua sessão expirou. Por favor, faça login novamente.';
        header('Location: ?page=login');
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


    $hideNavPages = ['login', 'select_branch', 'forgot_password', 'reset_password'];
?>
<?php if (!in_array($page, $hideNavPages)): ?>
<?php require_once 'templates/layout/header.php'; ?>
<?php require_once 'templates/layout/sidebar.php'; ?>

    <main class="main-content">
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

            case 'patients_delete':
                if (!$isAdmin) { header('Location: ?page=patients&error=' . urlencode('Acesso negado.')); exit; }
                $id = intval($_GET['id'] ?? 0);
                if ($id > 0) {
                    $controller = new PatientController();
                    $result = $controller->delete($id);
                    if ($result['success']) {
                        header('Location: ?page=patients&success=deleted');
                    } else {
                        header('Location: ?page=patients&error=' . urlencode($result['error']));
                    }
                } else {
                    header('Location: ?page=patients&error=' . urlencode('ID de paciente inválido.'));
                }
                exit;
            
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
    
    <?php require_once 'templates/layout/footer.php'; ?>
<?php endif; ?>
<?php
} catch (Exception $e) {
    // Log the error regardless of environment
    error_log('[gestao-clinica] Uncaught Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Erro</title></head><body>';
    echo '<div style="font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #fee; border: 2px solid #c33; border-radius: 8px;">';
    echo '<h2 style="color: #c33;">⚠️ Erro na Aplicação</h2>';

    if (isset($isProduction) && !$isProduction) {
        // Development: show full details
        echo '<p><strong>Mensagem:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p><strong>Arquivo:</strong> ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</p>';
        echo '<pre style="background: #fff; padding: 10px; border-radius: 4px; overflow-x: auto;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        // Production: hide technical details
        echo '<p>Ocorreu um erro interno. Por favor, tente novamente ou entre em contato com o suporte.</p>';
        echo '<p><a href="?page=login" style="color: #c33;">← Voltar para o Login</a></p>';
    }

    echo '</div></body></html>';
}
?>
