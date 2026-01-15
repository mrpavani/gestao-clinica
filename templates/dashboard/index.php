<?php
// templates/dashboard/index.php
require_once __DIR__ . '/../../src/Database.php';

$pdo = Database::getInstance()->getConnection();

// 1. Total Active Patients
// Active = Status 'active' AND has at least one valid contract
$activePatients = $pdo->query("
    SELECT COUNT(DISTINCT p.id) 
    FROM patients p 
    JOIN patient_packages pp ON p.id = pp.patient_id 
    WHERE p.status = 'active' 
    AND pp.end_date >= CURRENT_DATE
")->fetchColumn();

// 2. Appointments Today
$today = date('Y-m-d');
$todayStats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled
    FROM appointments 
    WHERE DATE(start_time) = '$today'
")->fetch(PDO::FETCH_ASSOC);

// 3. Appointments This Month
$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');
$monthStats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled
    FROM appointments 
    WHERE DATE(start_time) BETWEEN '$monthStart' AND '$monthEnd'
")->fetch(PDO::FETCH_ASSOC);

?>

<header style="margin-bottom: 2rem;">
    <h1>Dashboard Gerencial</h1>
    <p style="color: var(--text-secondary);">Visão geral da clínica</p>
</header>

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
        <p style="font-size: 0.85rem; color: var(--text-secondary);">Com contrato vigente e status ativo</p>
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
       <div style="display: flex; gap: 1rem; margin-top: 1rem;">
           <a href="?page=patients_new" class="btn" style="background: #F3F4F6; color: var(--text-primary);">
               <i class="fa-solid fa-user-plus"></i> Novo Paciente
           </a>
           <a href="?page=schedule" class="btn" style="background: #F3F4F6; color: var(--text-primary);">
               <i class="fa-solid fa-calendar-days"></i> Agenda Completa
           </a>
       </div>
   </div>
</div>
