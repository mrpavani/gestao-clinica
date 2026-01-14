<?php
// index.php (Moved to root)
// Simple router/dispatcher

require_once __DIR__ . '/src/Database.php';

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
    </nav>

    <main class="main-content">
        <?php
        // Simple View Router
        switch($page) {
            case 'dashboard':
                echo '<header><h1>Dashboard</h1><button class="btn btn-primary"><i class="fa-solid fa-plus"></i> Novo Agendamento</button></header>';
                echo '<div class="card"><p>Bem-vindo ao sistema de gestão da clínica.</p></div>';
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
            default:
                echo "<h2>Página não encontrada</h2>";
                break;
        }
        ?>
    </main>
</body>
</html>
