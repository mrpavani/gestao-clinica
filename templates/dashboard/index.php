<?php
// templates/dashboard/index.php
require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../src/Controllers/BranchController.php';

$pdo = Database::getInstance()->getConnection();
$branchController = new BranchController();
$branches = $branchController->getAll();

// Handle branch filter
$filterBranch = $_GET['branch'] ?? 'current';
$currentBranchId = $_SESSION['branch_id'] ?? null;

// Handle quick branch switch
if (isset($_GET['switch_branch']) && AuthController::isAdmin()) {
    $branchController->selectBranch($_GET['switch_branch']);
    header('Location: ?page=dashboard');
    exit;
}

// Build branch condition for queries
if ($filterBranch === 'all') {
    $branchCondition = "1=1"; // No filter
    $patientBranchCondition = "1=1";
} else {
    $branchId = $currentBranchId ?? 0;
    $branchCondition = "a.branch_id = $branchId";
    $patientBranchCondition = "p.branch_id = $branchId";
}

// 1. Total Active Patients
$activePatients = $pdo->query("
    SELECT COUNT(DISTINCT p.id) 
    FROM patients p 
    JOIN patient_packages pp ON p.id = pp.patient_id 
    WHERE p.status = 'active' 
    AND pp.end_date >= CURRENT_DATE
    AND $patientBranchCondition
")->fetchColumn();

// 2. Appointments Today
$today = date('Y-m-d');
$todayStats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled
    FROM appointments a
    WHERE DATE(start_time) = '$today'
    AND $branchCondition
")->fetch(PDO::FETCH_ASSOC);

// 3. Appointments This Month
$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');
$monthStats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled
    FROM appointments a
    WHERE DATE(start_time) BETWEEN '$monthStart' AND '$monthEnd'
    AND $branchCondition
")->fetch(PDO::FETCH_ASSOC);

// Stats per branch (for "all" view)
$branchStats = [];
if ($filterBranch === 'all') {
    foreach ($branches as $branch) {
        $bid = $branch['id'];
        $bPatients = $pdo->query("
            SELECT COUNT(DISTINCT p.id) 
            FROM patients p 
            JOIN patient_packages pp ON p.id = pp.patient_id 
            WHERE p.status = 'active' 
            AND pp.end_date >= CURRENT_DATE
            AND p.branch_id = $bid
        ")->fetchColumn();
        
        $bToday = $pdo->query("
            SELECT COUNT(*) FROM appointments 
            WHERE DATE(start_time) = '$today' AND branch_id = $bid
        ")->fetchColumn();
        
        $branchStats[$bid] = [
            'name' => $branch['name'],
            'patients' => $bPatients,
            'today' => $bToday
        ];
    }
}
?>

<header style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1>Dashboard Gerencial</h1>
            <p style="color: var(--text-secondary);">Visão geral da clínica</p>
        </div>
        
        <!-- Branch Filter & Quick Switch -->
        <div style="display: flex; gap: 1rem; align-items: center;">
            <?php if (AuthController::isAdmin()): ?>
            <!-- Quick Branch Switcher -->
            <div style="position: relative;">
                <select onchange="if(this.value) window.location='?page=dashboard&switch_branch='+this.value" style="padding: 0.5rem 1rem; border-radius: var(--radius-md); border: 1px solid #E5E7EB; background: white; font-size: 0.9rem;">
                    <option value="">🔄 Trocar Filial</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= $b['id'] == $currentBranchId ? 'disabled' : '' ?>>
                            <?= htmlspecialchars($b['name']) ?> <?= $b['id'] == $currentBranchId ? '(atual)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- View Filter -->
            <div style="display: flex; gap: 0.5rem;">
                <a href="?page=dashboard&branch=current" class="btn" style="<?= $filterBranch !== 'all' ? 'background: var(--primary-color); color: white;' : 'background: #F3F4F6; color: var(--text-primary);' ?>">
                    Filial Atual
                </a>
                <a href="?page=dashboard&branch=all" class="btn" style="<?= $filterBranch === 'all' ? 'background: var(--primary-color); color: white;' : 'background: #F3F4F6; color: var(--text-primary);' ?>">
                    Todas as Filiais
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if ($filterBranch === 'all' && !empty($branchStats)): ?>
<!-- Per-Branch Summary -->
<div style="margin-bottom: 2rem;">
    <h3 style="margin-bottom: 1rem; color: var(--text-secondary);">Resumo por Filial</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
        <?php foreach ($branchStats as $bid => $stats): ?>
        <div class="card" style="padding: 1rem; border-left: 4px solid var(--primary-color);">
            <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem;">
                <i class="fa-solid fa-building"></i> <?= htmlspecialchars($stats['name']) ?>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 0.9rem; color: var(--text-secondary);">
                <span><i class="fa-solid fa-users"></i> <?= $stats['patients'] ?> pacientes</span>
                <span><i class="fa-solid fa-calendar"></i> <?= $stats['today'] ?> hoje</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
    
    <!-- Active Patients Card -->
    <div class="card" style="border-left: 5px solid var(--primary-color);">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <p style="color: var(--text-secondary); font-size: 0.9rem; font-weight: 500;">Pacientes Ativos</p>
                <h2 style="font-size: 2.5rem; color: var(--text-primary); margin: 0.5rem 0;"><?= $activePatients ?></h2>
            </div>
            <div style="background: #E0F2FE; color: var(--primary-color); padding: 1rem; border-radius: 50%;">
                <i class="fa-solid fa-users" style="font-size: 1.5rem;"></i>
            </div>
        </div>
        <p style="font-size: 0.85rem; color: var(--text-secondary);">
            <?= $filterBranch === 'all' ? 'Todas as filiais' : 'Filial atual' ?> - Com contrato vigente
        </p>
    </div>

    <!-- Today's Stats -->
    <div class="card" style="border-left: 5px solid var(--secondary-color);">
        <div style="display: flex; justify-content: space-between; align-items: start;">
            <div>
                <p style="color: var(--text-secondary); font-size: 0.9rem; font-weight: 500;">Atendimentos Hoje</p>
                <h2 style="font-size: 2.5rem; color: var(--text-primary); margin: 0.5rem 0;"><?= $todayStats['total'] ?></h2>
            </div>
            <div style="background: #DEF7EC; color: #03543F; padding: 1rem; border-radius: 50%;">
                <i class="fa-regular fa-calendar-check" style="font-size: 1.5rem;"></i>
            </div>
        </div>
        <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
            <span style="font-size: 0.9rem; color: #03543F; font-weight: 500;">
                <i class="fa-solid fa-check"></i> <?= $todayStats['completed'] ?? 0 ?> Realizados
            </span>
            <span style="font-size: 0.9rem; color: var(--primary-color); font-weight: 500;">
                <i class="fa-regular fa-clock"></i> <?= $todayStats['scheduled'] ?? 0 ?> Agendados
            </span>
        </div>
    </div>

    <!-- Month's Stats -->
    <div class="card" style="border-left: 5px solid #8B5CF6;">
        <div style="display: flex; justify-content: space-between; align-items: start;">
            <div>
                <p style="color: var(--text-secondary); font-size: 0.9rem; font-weight: 500;">Atendimentos Mês (<?= date('M/Y') ?>)</p>
                <h2 style="font-size: 2.5rem; color: var(--text-primary); margin: 0.5rem 0;"><?= $monthStats['total'] ?></h2>
            </div>
            <div style="background: #F3F0FF; color: #7C3AED; padding: 1rem; border-radius: 50%;">
                <i class="fa-solid fa-chart-line" style="font-size: 1.5rem;"></i>
            </div>
        </div>
        <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
            <span style="font-size: 0.9rem; color: #03543F; font-weight: 500;">
                <i class="fa-solid fa-check"></i> <?= $monthStats['completed'] ?? 0 ?> Realizados
            </span>
             <span style="font-size: 0.9rem; color: var(--primary-color); font-weight: 500;">
                <i class="fa-regular fa-clock"></i> <?= $monthStats['scheduled'] ?? 0 ?> Agendados
            </span>
        </div>
    </div>
</div>

<div style="margin-top: 2rem; display: grid; grid-template-columns: 1fr; gap: 2rem;">
   <!-- Can add charts or detailed tables here later -->
   <div class="card">
       <h3>Acesso Rápido</h3>
       <div style="display: flex; gap: 1rem; margin-top: 1rem; flex-wrap: wrap;">
           <a href="?page=patients_new" class="btn" style="background: #F3F4F6; color: var(--text-primary);">
               <i class="fa-solid fa-user-plus"></i> Novo Paciente
           </a>
           <a href="?page=schedule" class="btn" style="background: #F3F4F6; color: var(--text-primary);">
               <i class="fa-solid fa-calendar-days"></i> Agenda Completa
           </a>
           <?php if (AuthController::isAdmin()): ?>
           <a href="?page=branches" class="btn" style="background: #F3F4F6; color: var(--text-primary);">
               <i class="fa-solid fa-building"></i> Gerenciar Filiais
           </a>
           <?php endif; ?>
       </div>
   </div>
</div>
