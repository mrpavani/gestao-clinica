<?php
// templates/reports/patient_report.php
require_once __DIR__ . '/../../src/Controllers/PatientController.php';

$patientController = new PatientController();
$id = $_GET['patient_id'] ?? null;
if (!$id) die("Paciente não selecionado");

$patient = $patientController->getById($id);
$history = $patientController->getHistory($id);
$plannings = $patientController->getAllPlannings($id, date('Y'));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório - <?= htmlspecialchars($patient['name']) ?></title>
    <style>
        body { font-family: sans-serif; line-height: 1.5; padding: 2rem; color: #333; }
        h1, h2, h3 { color: #111; }
        .header { text-align: center; margin-bottom: 2rem; border-bottom: 2px solid #ddd; padding-bottom: 1rem; }
        .section { margin-bottom: 2rem; }
        .field { margin-bottom: 0.5rem; }
        .label { font-weight: bold; }
        .timeline-item { border-bottom: 1px solid #eee; padding: 1rem 0; page-break-inside: avoid; }
        .meta { color: #666; font-size: 0.9rem; margin-bottom: 0.5rem; }
        .content { white-space: pre-wrap; }
        .pei-block { background: #f9f9f9; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; page-break-inside: avoid; }
        
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()" style="padding: 10px 20px; font-size: 16px; margin-bottom: 20px; cursor: pointer;">Imprimir Relatório</button>

    <div class="header">
        <h1>Relatório de Progresso e Evolução</h1>
        <p>Clínica NeuroCare</p>
    </div>

    <div class="section">
        <h2>Identificação</h2>
        <div class="field"><span class="label">Paciente:</span> <?= htmlspecialchars($patient['name']) ?></div>
        <div class="field"><span class="label">Data de Nascimento:</span> <?= date('d/m/Y', strtotime($patient['dob'])) ?></div>
        <div class="field"><span class="label">Responsável:</span> <?= htmlspecialchars($patient['guardian_name']) ?></div>
    </div>

    <?php if (!empty($plannings)): ?>
    <div class="section">
        <h2>Planejamento Anual (PEI) - <?= date('Y') ?></h2>
        <?php foreach ($plannings as $p): ?>
            <div class="pei-block">
                <h3><?= htmlspecialchars($p['therapy_name']) ?></h3>
                <div class="content"><?= nl2br(htmlspecialchars($p['goals'])) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="section">
        <h2>Histórico de Evoluções</h2>
        <?php if (empty($history)): ?>
            <p>Nenhum registro encontrado.</p>
        <?php else: ?>
            <?php foreach ($history as $item): ?>
                <?php if ($item['evolution_content']): ?>
                    <div class="timeline-item">
                        <div class="meta">
                            <strong><?= date('d/m/Y H:i', strtotime($item['start_time'])) ?></strong> - 
                            <?= htmlspecialchars($item['therapy_name']) ?> | 
                            Prof. <?= htmlspecialchars($item['professional_name']) ?>
                            (<?= $item['evolution_type'] ?>)
                        </div>
                        <div class="content"><?= htmlspecialchars($item['evolution_content']) ?></div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="section" style="margin-top: 4rem; text-align: center;">
        <p>___________________________________________________</p>
        <p>Assinatura do Profissional Responsável</p>
        <p><?= date('d/m/Y') ?></p>
    </div>

</body>
</html>
