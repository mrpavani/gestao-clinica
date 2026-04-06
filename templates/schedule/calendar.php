<?php
// templates/schedule/calendar.php
require_once __DIR__ . '/../../src/Controllers/AppointmentController.php';
require_once __DIR__ . '/../../src/Controllers/ProfessionalController.php';
require_once __DIR__ . '/../../src/Controllers/TherapyController.php';
require_once __DIR__ . '/../../src/Controllers/PatientController.php';

$apptController = new AppointmentController();
$profController = new ProfessionalController();
$therapyController = new TherapyController();
$patientController = new PatientController();

// Determine date range (default current month)
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');
$startDate = "$year-$month-01";
$endDate = date('Y-m-t', strtotime($startDate));

$startDay = date('w', strtotime($startDate));
$daysInMonth = date('t', strtotime($startDate));
$weeks = ceil(($startDay + $daysInMonth) / 7);

$view = $_GET['view'] ?? 'month';
$currentDate = $_GET['date'] ?? date('Y-m-d');
$profFilter = $_GET['prof_filter'] ?? '';
$therapyFilter = $_GET['therapy_filter'] ?? '';

if (!AuthController::isAdmin() && AuthController::isProfessional()) {
    $profFilter = $_SESSION['professional_id'];
}

if ($view === 'day' || $view === 'list') {
    if ($view === 'day') {
        $startDate = "$currentDate";
        $endDate = "$currentDate";
    } else {
        // List from today onwards (limit to 3 months)
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+3 months'));
    }
}

$appointments = $apptController->getAppointmentsByRange($startDate, $endDate, $profFilter, $therapyFilter);
$professionals = $profController->getAll();
$therapies = $therapyController->getAll();
$patients = $patientController->getAll();

// Create a map of therapy_id => [prof_id, prof_id...]
$therapyProfs = [];
foreach ($therapies as $t) {
    // We don't have a direct method to get linked profs for ALL therapies efficiently in one query yet,
    // so we might need to iterate or fetch a join.
    // For now, let's use a new method I'll add to TherapyController or just do a raw query here for speed.
    // A better way is: TherapyController->getAllWithProfessionals()
    // But let's assume I can call getLinkedProfessionals for each for now (lazy).
    $linked = $therapyController->getLinkedProfessionals($t['id']);
    $therapyProfs[$t['id']] = $linked; // This is an array of IDs
}

// Group appointments by day and time for easier rendering
$calendarEvents = [];
foreach ($appointments as $appt) {
    $day = date('j', strtotime($appt['start_time']));
    $calendarEvents[$day][] = $appt;
}

// Form submission is handled via AJAX (ajax/save_appointment.php)
// No server-side POST processing needed here to avoid white screen issues
$message = '';
$error = '';

// Navigation
$prevMonth = date('m', strtotime("$startDate -1 month"));
$prevYear = date('Y', strtotime("$startDate -1 month"));
$nextMonth = date('m', strtotime("$startDate +1 month"));
$nextYear = date('Y', strtotime("$startDate +1 month"));
// Navigation for Day View
$prevDay = date('Y-m-d', strtotime("$currentDate -1 day"));
$nextDay = date('Y-m-d', strtotime("$currentDate +1 day"));

$monthsPt = [
    '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril',
    '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto',
    '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
];
$monthName = $monthsPt[$month] ?? date('F', strtotime($startDate));
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
    <h1 style="margin: 0;">
        <?php if ($view === 'day'): ?>
            Agenda - <?= date('d/m/Y', strtotime($currentDate)) ?>
        <?php elseif ($view === 'list'): ?>
            Agenda (Lista)
        <?php else: ?>
            Agenda - <?= $monthName ?> <?= $year ?>
        <?php endif; ?>
    </h1>
    <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
        <?php if (AuthController::isAdmin()): ?>
        <form method="GET" style="display: flex; gap: 0.5rem; align-items: center; margin: 0; flex-wrap: wrap;">
            <input type="hidden" name="page" value="schedule">
            <input type="hidden" name="view" value="<?= $view ?>">
            <?php if ($view === 'month'): ?>
                <input type="hidden" name="year" value="<?= $year ?>">
                <input type="hidden" name="month" value="<?= $month ?>">
            <?php elseif ($view === 'day'): ?>
                <input type="hidden" name="date" value="<?= $currentDate ?>">
            <?php endif; ?>
            
            <select name="therapy_filter" style="padding: 0.4rem 0.5rem; border: 1px solid #d1d5db; border-radius: var(--radius-md);" onchange="this.form.submit()">
                <option value="">Todas as Terapias</option>
                <?php foreach ($therapies as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= $therapyFilter == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
                <?php endforeach; ?>
            </select>
            
            <select name="prof_filter" style="padding: 0.4rem 0.5rem; border: 1px solid #d1d5db; border-radius: var(--radius-md);" onchange="this.form.submit()">
                <option value="">Todos os Profissionais</option>
                <?php foreach ($professionals as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $profFilter == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php endif; ?>

        <div style="background: #e5e7eb; border-radius: var(--radius-md); padding: 0.25rem; display: flex;">
            <a href="?page=schedule&view=month&month=<?= $month ?>&year=<?= $year ?>&prof_filter=<?= $profFilter ?>&therapy_filter=<?= $therapyFilter ?>" class="btn <?= $view === 'month' ? 'btn-primary' : '' ?>" style="<?= $view !== 'month' ? 'background:transparent; color:#4B5563; border:none;' : 'padding: 0.4rem 0.75rem; border:none;' ?>">Mês</a>
            <a href="?page=schedule&view=day&date=<?= $view === 'day' ? $currentDate : date('Y-m-d') ?>&prof_filter=<?= $profFilter ?>&therapy_filter=<?= $therapyFilter ?>" class="btn <?= $view === 'day' ? 'btn-primary' : '' ?>" style="<?= $view !== 'day' ? 'background:transparent; color:#4B5563; border:none;' : 'padding: 0.4rem 0.75rem; border:none;' ?>">Dia</a>
            <a href="?page=schedule&view=list&prof_filter=<?= $profFilter ?>&therapy_filter=<?= $therapyFilter ?>" class="btn <?= $view === 'list' ? 'btn-primary' : '' ?>" style="<?= $view !== 'list' ? 'background:transparent; color:#4B5563; border:none;' : 'padding: 0.4rem 0.75rem; border:none;' ?>">Lista</a>
        </div>

        <?php if ($view === 'day'): ?>
            <div>
                <a href="?page=schedule&view=day&date=<?= $prevDay ?>&prof_filter=<?= $profFilter ?>&therapy_filter=<?= $therapyFilter ?>" class="btn" style="background: #e5e7eb; color: var(--text-primary);">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
                <a href="?page=schedule&view=day&date=<?= date('Y-m-d') ?>&prof_filter=<?= $profFilter ?>&therapy_filter=<?= $therapyFilter ?>" class="btn" style="background: #f3f4f6; color: var(--text-primary); margin: 0 0.25rem;">Hoje</a>
                <a href="?page=schedule&view=day&date=<?= $nextDay ?>&prof_filter=<?= $profFilter ?>&therapy_filter=<?= $therapyFilter ?>" class="btn" style="background: #e5e7eb; color: var(--text-primary);">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            </div>
        <?php elseif ($view === 'month'): ?>
            <div>
                <a href="?page=schedule&view=month&month=<?= $prevMonth ?>&year=<?= $prevYear ?>&prof_filter=<?= $profFilter ?>&therapy_filter=<?= $therapyFilter ?>" class="btn" style="background: #e5e7eb; color: var(--text-primary);">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
                <a href="?page=schedule&view=month&month=<?= date('m') ?>&year=<?= date('Y') ?>&prof_filter=<?= $profFilter ?>&therapy_filter=<?= $therapyFilter ?>" class="btn" style="background: #f3f4f6; color: var(--text-primary); margin: 0 0.25rem;">Hoje</a>
                <a href="?page=schedule&view=month&month=<?= $nextMonth ?>&year=<?= $nextYear ?>&prof_filter=<?= $profFilter ?>&therapy_filter=<?= $therapyFilter ?>" class="btn" style="background: #e5e7eb; color: var(--text-primary);">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($message): ?>
    <div style="background: #D1FAE5; color: #065F46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
        <?= $message ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="background: #FEE2E2; color: #991B1B; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
        <?= $error ?>
    </div>
<?php endif; ?>

<style>
    .schedule-grid {
        display: grid !important;
        grid-template-columns: 1fr !important;
        gap: 2rem;
        align-items: start;
    }
    @media (min-width: 1024px) {
        .schedule-grid.has-sidebar {
            grid-template-columns: 1fr 300px !important;
        }
    }
    .sticky-sidebar {
        position: sticky !important;
        top: 80px !important;
        /* Need align-self start so it doesn't stretch to full grid height */
        align-self: start;
    }
</style>
<?php $gridClass = AuthController::isAdmin() ? 'schedule-grid has-sidebar' : 'schedule-grid'; ?>
<div class="<?= $gridClass ?>">
    <!-- Calendar Grid Content -->
    <div class="calendar-item-wrapper">
    <?php if ($view === 'month'): ?>
        <?php
        // Fetch the active professional's schedule if filtered
        $activeProfSchedule = [];
        if ($profFilter) {
            $scheds = $profController->getSchedules($profFilter);
            foreach ($scheds as $s) {
                // If the day is scheduled, map it as active
                $activeProfSchedule[$s['day_of_week']] = true;
            }
        } elseif ($therapyFilter) {
            // Aggregate all professionals schedules attached to this therapy
            $allowedProfs = $therapyProfs[$therapyFilter] ?? [];
            foreach ($allowedProfs as $pId) {
                $scheds = $profController->getSchedules($pId);
                foreach ($scheds as $s) {
                    $activeProfSchedule[$s['day_of_week']] = true;
                }
            }
        }
        
        $hasFilterActive = $profFilter || $therapyFilter;
        ?>
        <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 1px; background: #e5e7eb; border: 1px solid #e5e7eb; border-radius: var(--radius-md); overflow: hidden;">
            <!-- Headers -->
            <?php foreach (['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'] as $h): ?>
                <div style="background: #f9fafb; padding: 0.5rem; text-align: center; font-weight: bold; color: var(--text-secondary);">
                    <?= $h ?>
                </div>
            <?php endforeach; ?>

            <!-- Days -->
            <?php
            $dayCount = 1;
            for ($i = 0; $i < $weeks * 7; $i++) {
                if ($i < $startDay || $dayCount > $daysInMonth) {
                    echo '<div style="background: white; min-height: 120px;"></div>';
                } else {
                    $isToday = ($year == date('Y') && $month == date('m') && $dayCount == date('j'));
                    $events = $calendarEvents[$dayCount] ?? [];
                    
                    $dayFormat = str_pad($dayCount, 2, '0', STR_PAD_LEFT);
                    $linkDate = "$year-$month-$dayFormat";
                    
                    // Determine if the day is faded out (professional filter active but doesn't work this day)
                    $dayOfWeekMapped = date('w', strtotime($linkDate)); // 0 = Sunday, 1 = Monday
                    $isFaded = false;
                    $dayBg = "white";
                    if ($hasFilterActive && !isset($activeProfSchedule[$dayOfWeekMapped])) {
                        $isFaded = true;
                        $dayBg = "#f9fafb"; // subtle gray
                    }
                    
                    echo "<div style='background: {$dayBg}; min-height: 120px; padding: 0.5rem; border: 1px solid #f3f4f6; position: relative; " . ($isFaded ? "opacity: 0.5;" : "") . "'>";
                    echo "<div style='font-weight: bold; margin-bottom: 0.5rem; cursor: pointer; " . ($isToday ? 'color: var(--primary-color);' : '') . "' onclick=\"window.location.href='?page=schedule&view=day&date=$linkDate&prof_filter=$profFilter&therapy_filter=$therapyFilter'\">" . $dayCount . "</div>";
                    
                    foreach ($events as $evt) {
                        $color = $evt['therapy_color'] ?? 'var(--primary-color)';
                        if ($evt['status'] == 'completed') {
                            $color = '#10B981'; // Override completed to green
                        }
                        $title = date('H:i', strtotime($evt['start_time'])) . ' - ' . substr($evt['patient_name'], 0, 10);
                        $tooltip = htmlspecialchars("Profissional: {$evt['professional_name']} | Paciente: {$evt['patient_name']} | {$evt['therapy_name']}");
                        
                        echo "<a href='?page=appointment_notes&id={$evt['id']}' style='display: block; background: {$color}; color: white; font-size: 0.75rem; padding: 2px 4px; border-radius: 4px; margin-bottom: 2px; text-decoration: none;' title='{$tooltip}'>";
                        echo $title;
                        echo "</a>";
                    }
                    
                    echo '</div>';
                    $dayCount++;
                }
            }
            ?>
        </div>
    <?php elseif ($view === 'list'): ?>
        <!-- List View -->
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: var(--radius-md); padding: 1.5rem; min-height: 500px;">
            <?php if (empty($appointments)): ?>
                <div style="text-align: center; color: var(--text-secondary); margin-top: 2rem;">Nenhum agendamento futuro encontrado na base.</div>
            <?php else: ?>
                <?php
                // Group by Day sequentially
                $listEvents = [];
                foreach ($appointments as $appt) {
                    $dayStr = date('Y-m-d', strtotime($appt['start_time']));
                    $listEvents[$dayStr][] = $appt;
                }
                ksort($listEvents);
                ?>

                <?php foreach ($listEvents as $dateStr => $dayEvents): 
                    $dateLabel = date('d/m/Y', strtotime($dateStr));
                    $dayOfWeekLabel = $daysPt[date('w', strtotime($dateStr))] ?? date('l', strtotime($dateStr));
                ?>
                    <h2 style="font-size: 1.25rem; border-bottom: 2px solid #f3f4f6; padding-bottom: 0.5rem; margin-top: <?= $dateStr === array_key_first($listEvents) ? '0' : '2rem' ?>; margin-bottom: 1rem; color: var(--text-primary); display: flex; align-items: baseline; gap: 0.5rem;">
                        <span><?= $dateLabel ?></span>
                        <span style="font-size: 0.9rem; font-weight: normal; color: var(--text-secondary);"><?= $dayOfWeekLabel ?></span>
                    </h2>
                    
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <?php foreach ($dayEvents as $evt): 
                            $color = $evt['therapy_color'] ?? 'var(--primary-color)';
                            $isCompleted = $evt['status'] === 'completed';
                            if ($isCompleted) $color = '#10B981';
                        ?>
                            <a href="?page=appointment_notes&id=<?= $evt['id'] ?>" style="display: flex; align-items: stretch; border: 1px solid #e5e7eb; border-radius: var(--radius-md); overflow: hidden; text-decoration: none; color: inherit; transition: transform 0.1s;" onmouseover="this.style.transform='translateX(4px)'" onmouseout="this.style.transform=''">
                                <div style="background: <?= $color ?>; width: 6px;"></div>
                                <div style="flex: 1; padding: 1rem; display: flex; justify-content: space-between; align-items: center; background: #fafafa;">
                                    <div>
                                        <h3 style="margin: 0 0 0.25rem 0; font-size: 1rem;"><?= date('H:i', strtotime($evt['start_time'])) ?> - <?= date('H:i', strtotime($evt['end_time'])) ?></h3>
                                        <div style="font-weight: 600; color: var(--text-primary); font-size: 0.95rem;"><?= htmlspecialchars($evt['patient_name']) ?></div>
                                        <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.25rem;">
                                            <span style="display: inline-block; padding: 0.1rem 0.5rem; border-radius: 4px; background: #e5e7eb; margin-right: 0.5rem;"><?= htmlspecialchars($evt['therapy_name']) ?></span>
                                            <?= htmlspecialchars($evt['professional_name']) ?>
                                        </div>
                                    </div>
                                    <?php if ($isCompleted): ?>
                                        <span style="background: #DEF7EC; color: #03543F; padding: 0.25rem 0.75rem; border-radius: var(--radius-md); font-size: 0.8rem; font-weight: 600;"><i class="fa-solid fa-check"></i> Concluído</span>
                                    <?php else: ?>
                                        <span style="background: #FEF3C7; color: #D97706; padding: 0.25rem 0.75rem; border-radius: var(--radius-md); font-size: 0.8rem; font-weight: 600;">Agendado</span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Day View -->
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: var(--radius-md); padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; min-height: 500px;">
            <?php 
            if (empty($appointments)): 
            ?>
                <div style="text-align: center; color: var(--text-secondary); margin-top: 2rem;">Nenhum agendamento para este dia.</div>
            <?php else: ?>
                <?php foreach ($appointments as $evt): 
                    $color = $evt['therapy_color'] ?? 'var(--primary-color)';
                    $isCompleted = $evt['status'] === 'completed';
                    if ($isCompleted) $color = '#10B981';
                ?>
                    <a href="?page=appointment_notes&id=<?= $evt['id'] ?>" style="display: flex; align-items: stretch; border: 1px solid #e5e7eb; border-radius: var(--radius-md); overflow: hidden; text-decoration: none; color: inherit; transition: transform 0.1s;" onmouseover="this.style.transform='translateX(4px)'" onmouseout="this.style.transform=''">
                        <div style="background: <?= $color ?>; width: 6px;"></div>
                        <div style="flex: 1; padding: 1rem; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h3 style="margin: 0 0 0.25rem 0;"><?= date('H:i', strtotime($evt['start_time'])) ?> - <?= date('H:i', strtotime($evt['end_time'])) ?></h3>
                                <div style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($evt['patient_name']) ?></div>
                                <div style="font-size: 0.85rem; color: var(--text-secondary);"><?= htmlspecialchars($evt['therapy_name']) ?>  <span style="margin: 0 0.5rem;">|</span>  <?= htmlspecialchars($evt['professional_name']) ?></div>
                            </div>
                            <?php if ($isCompleted): ?>
                                <span style="background: #DEF7EC; color: #03543F; padding: 0.25rem 0.75rem; border-radius: var(--radius-md); font-size: 0.8rem; font-weight: 600;"><i class="fa-solid fa-check"></i> Concluído</span>
                            <?php else: ?>
                                <span style="background: #FEF3C7; color: #D97706; padding: 0.25rem 0.75rem; border-radius: var(--radius-md); font-size: 0.8rem; font-weight: 600;">Agendado</span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    </div>
    <!-- End Calendar Grid Content -->

    <!-- Quick Add Form (Only Admins) -->
    <?php if (AuthController::isAdmin()): ?>
    <div class="card sticky-sidebar">
        <h3 style="margin-bottom: 1rem;">Novo Agendamento</h3>

        <!-- Feedback Area -->
        <div id="apptFeedback" style="display:none; padding: 0.75rem; border-radius: var(--radius-md); margin-bottom: 1rem; font-size: 0.9rem;"></div>

        <form id="appointmentForm" autocomplete="off">
            <div class="form-group">
                <label>Paciente</label>
                <select name="patient_id" id="patientSelect" required onchange="fetchPatientTherapies()">
                    <option value="">Selecione...</option>
                    <?php foreach ($patients as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Terapia</label>
                <select name="therapy_id" id="therapySelect" required onchange="filterProfessionals()">
                    <option value="">Selecione o paciente primeiro...</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Profissional</label>
                <select name="professional_id" id="profSelect" required>
                    <option value="">Selecione a terapia primeiro...</option>
                    <?php foreach ($professionals as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Data e Hora</label>
                <input type="datetime-local" id="startTimeInput" name="start_time" required
                       value="<?= date('Y-m-d\T08:00') ?>"
                       onchange="fetchPatientTherapies(); updateRecurrencePreview()">
            </div>
            
            <div class="form-group">
                <label>Duração (min)</label>
                <input type="number" name="duration" id="durationInput" value="60" min="15" step="15" onchange="updateRecurrencePreview()">
            </div>

            <!-- Recurrence Toggle -->
            <div style="background: #F0F9FF; border: 1px solid #BAE6FD; border-radius: var(--radius-md); padding: 0.75rem; margin-bottom: 1rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; margin: 0; cursor: pointer; font-weight: 600; font-size: 0.9rem; color: #0369A1;">
                    <input type="checkbox" id="recurrenceToggle" name="is_recurrent" value="1"
                           style="width: auto; margin: 0;" onchange="toggleRecurrence()">
                    <i class="fa-solid fa-rotate"></i> Agendar por Recorrência
                </label>
                <p style="margin: 0.4rem 0 0 1.4rem; font-size: 0.8rem; color: #0369A1;">
                    Repete em datas selecionadas
                </p>
            </div>

            <!-- Recurrence Fields (hidden by default) -->
            <div id="recurrenceFields" style="display: none;">
                <div class="form-group">
                    <label style="font-weight: 600; margin-bottom: 0.5rem; display: block;">Dias da Semana e Horários *</label>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem; background: #fff; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: var(--radius-md);">
                        <?php
                            $weekdays = [
                                1 => 'Segunda-feira',
                                2 => 'Terça-feira',
                                3 => 'Quarta-feira',
                                4 => 'Quinta-feira',
                                5 => 'Sexta-feira',
                                6 => 'Sábado',
                                0 => 'Domingo'
                            ];
                            foreach ($weekdays as $val => $name):
                        ?>
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <label style="font-weight: normal; margin: 0; display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" name="recurrence_days[]" value="<?= $val ?>" class="recurrence-day-cb" onchange="toggleRecurrenceTime(<?= $val ?>); updateRecurrencePreview();"> <?= $name ?>
                            </label>
                            <input type="time" name="recurrence_times[<?= $val ?>]" id="recurrence_time_<?= $val ?>" style="width: auto; display: none;" onchange="updateRecurrencePreview()">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label style="font-weight: 600; margin-bottom: 0.5rem; display: block;">Finalizar Recorrência *</label>
                    <div style="display: flex; gap: 1rem; margin-bottom: 0.5rem;">
                        <label style="font-weight: normal; margin:0;"><input type="radio" name="recurrence_end_type" value="date" checked onchange="toggleRecurrenceEndType()"> Em uma data</label>
                        <label style="font-weight: normal; margin:0;"><input type="radio" name="recurrence_end_type" value="occurrences" onchange="toggleRecurrenceEndType()"> Após X sessões</label>
                    </div>
                    
                    <input type="date" id="repeatEndDate" name="repeat_end_date"
                           min="<?= date('Y-m-d') ?>"
                           onchange="updateRecurrencePreview()"
                           style="width: 100%;">
                    
                    <input type="number" id="occurrencesCount" name="occurrences_count"
                           min="1" max="100" placeholder="Ex: 8 sessões" value="8"
                           onchange="updateRecurrencePreview()"
                           style="width: 100%; display: none;">
                </div>

                <!-- Preview -->
                <div id="recurrencePreview" style="background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: var(--radius-md); padding: 0.5rem 0.75rem; margin-bottom: 1rem; font-size: 0.85rem; color: #1D4ED8; display: none;">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span id="previewText"></span>
                </div>
            </div>

            <button type="submit" id="apptSubmitBtn" class="btn btn-primary" style="width: 100%;">
                <i class="fa-solid fa-calendar-plus"></i> <span id="submitBtnText">Agendar</span>
            </button>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
// Map of Therapy ID -> [Professional IDs]
const therapyMap = <?= json_encode($therapyProfs) ?>;
const allProfs = <?= json_encode($professionals) ?>;

async function fetchPatientTherapies() {
    const patientId = document.getElementById('patientSelect').value;
    const startTimeRaw = document.getElementById('startTimeInput').value;
    const therapySelect = document.getElementById('therapySelect');
    const profSelect = document.getElementById('profSelect');
    
    // Reset dropdowns
    therapySelect.innerHTML = '<option value="">Carregando...</option>';
    profSelect.innerHTML = '<option value="">Selecione a terapia primeiro...</option>';
    
    if (!patientId) {
        therapySelect.innerHTML = '<option value="">Selecione o paciente primeiro...</option>';
        return;
    }
    
    const date = startTimeRaw ? startTimeRaw.split('T')[0] : new Date().toISOString().split('T')[0];
    
    try {
        const response = await fetch(`/ajax/get_patient_therapies.php?patient_id=${patientId}&date=${date}`);
        const data = await response.json();
        
        therapySelect.innerHTML = '<option value="">Selecione...</option>';
        
        if (data.error) {
            alert(data.error);
            return;
        }
        
        if (data.length === 0) {
            therapySelect.innerHTML = '<option value="">Nenhum pacote ativo para esta data.</option>';
            return;
        }
        
        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item.therapy_id;
            // Mostra o limite de sessões no dropdown
            option.text = `${item.therapy_name} (Limite: ${item.sessions_per_month}/mês)`;
            if (item.default_duration_minutes) {
                option.dataset.duration = item.default_duration_minutes;
            }
            therapySelect.add(option);
        });
        
    } catch (e) {
        console.error("Erro ao buscar terapias:", e);
        therapySelect.innerHTML = '<option value="">Erro ao carregar terapias.</option>';
    }
}

function filterProfessionals() {
    const therapyId = document.getElementById('therapySelect').value;
    const profSelect = document.getElementById('profSelect');
    
    // Clear current options
    profSelect.innerHTML = '<option value="">Selecione...</option>';
    
    if (!therapyId) return;
    
    const selectedOption = therapySelect.options[therapySelect.selectedIndex];
    if (selectedOption && selectedOption.dataset.duration) {
        document.getElementById('durationInput').value = selectedOption.dataset.duration;
        if(typeof updateRecurrencePreview === 'function') {
            updateRecurrencePreview();
        }
    }
    
    // Normalize allowedIds to strings to avoid int vs string comparison issues
    const rawIds = therapyMap[therapyId] || therapyMap[Number(therapyId)] || [];
    const allowedIds = rawIds.map(id => String(id));
    
    // Filter allProfs (normalize p.id to string as well)
    const filtered = allProfs.filter(p => allowedIds.includes(String(p.id)));
    
    if (filtered.length === 0) {
         const option = document.createElement('option');
         option.text = "Nenhum profissional vinculado";
         profSelect.add(option);
    } else {
        filtered.forEach(p => {
            const option = document.createElement('option');
            option.value = p.id;
            // Decode HTML entities if any in name
            const txt = document.createElement('textarea');
            txt.innerHTML = p.name;
            option.text = txt.value;
            profSelect.add(option);
        });
    }
}
</script>

<script>
// ─── AJAX Form Submit ────────────────────────────────────────────
const apptForm = document.getElementById('appointmentForm');
if (apptForm) {
    apptForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const btn     = document.getElementById('apptSubmitBtn');
        const btnText = document.getElementById('submitBtnText');
        const feedback = document.getElementById('apptFeedback');

        // Loading state
        btn.disabled = true;
        btnText.textContent = 'Agendando...';
        feedback.style.display = 'none';

        const formData = new FormData(apptForm);
        const isRecurrent = document.getElementById('recurrenceToggle').checked;
        formData.set('recurrence_mode', isRecurrent ? 'recurrent' : 'single');

        try {
            const response = await fetch('/ajax/save_appointment.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                showFeedback(data.message || 'Agendamento realizado com sucesso!', 'success');
                apptForm.reset();
                document.getElementById('therapySelect').innerHTML = '<option value="">Selecione o paciente primeiro...</option>';
                document.getElementById('profSelect').innerHTML   = '<option value="">Selecione a terapia primeiro...</option>';
                document.getElementById('recurrenceFields').style.display = 'none';
                document.getElementById('recurrencePreview').style.display = 'none';
                document.getElementById('startTimeInput').value = new Date().toISOString().slice(0, 16).replace('T', 'T').substring(0, 11) + '08:00';
                // Soft reload calendar after short delay so user reads the success message
                setTimeout(() => { window.location.reload(); }, 2200);
            } else {
                showFeedback(data.error || 'Erro ao agendar.', 'error');
            }
        } catch (err) {
            console.error(err);
            showFeedback('Erro de conexão. Tente novamente.', 'error');
        } finally {
            btn.disabled = false;
            btnText.textContent = 'Agendar';
        }
    });
}

function showFeedback(msg, type) {
    const el = document.getElementById('apptFeedback');
    if (!el) return;
    el.style.display = 'block';
    if (type === 'success') {
        el.style.background = '#D1FAE5';
        el.style.color      = '#065F46';
        el.style.border     = '1px solid #6EE7B7';
        el.innerHTML = '<i class="fa-solid fa-check-circle"></i> ' + msg;
    } else {
        el.style.background = '#FEE2E2';
        el.style.color      = '#991B1B';
        el.style.border     = '1px solid #FECACA';
        el.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> ' + msg;
    }
    // Auto-hide success after 4s
    if (type === 'success') {
        setTimeout(() => { el.style.display = 'none'; }, 4000);
    }
}

// ─── Recurrence Toggle ───────────────────────────────────────────
function toggleRecurrenceTime(dayVal) {
    const cb = document.querySelector(`input[name="recurrence_days[]"][value="${dayVal}"]`);
    const timeInput = document.getElementById(`recurrence_time_${dayVal}`);
    if (cb.checked) {
        timeInput.style.display = 'block';
        // Auto-fill time from start_time if empty
        if (!timeInput.value) {
            const startRaw = document.getElementById('startTimeInput').value;
            if (startRaw) {
                timeInput.value = startRaw.split('T')[1];
            }
        }
    } else {
        timeInput.style.display = 'none';
        timeInput.value = '';
    }
}

function toggleRecurrenceEndType() {
    const endType = document.querySelector('input[name="recurrence_end_type"]:checked').value;
    const dateInput = document.getElementById('repeatEndDate');
    const countInput = document.getElementById('occurrencesCount');
    
    if (endType === 'date') {
        dateInput.style.display = 'block';
        countInput.style.display = 'none';
    } else {
        dateInput.style.display = 'none';
        countInput.style.display = 'block';
    }
    updateRecurrencePreview();
}

function toggleRecurrence() {
    const isOn  = document.getElementById('recurrenceToggle').checked;
    const fields = document.getElementById('recurrenceFields');
    fields.style.display = isOn ? 'block' : 'none';
    const btn = document.getElementById('submitBtnText');
    btn.textContent = isOn ? 'Agendar Recorrências' : 'Agendar';
    
    if (isOn) {
        // Automatically check the day of the week from start_time
        setTimeout(() => {
            const startRaw = document.getElementById('startTimeInput').value;
            if (startRaw) {
                const start = new Date(startRaw); // This parses as local if format is 'YYYY-MM-DDTHH:mm'
                const dayVal = start.getDay();
                const cb = document.querySelector(`input[name="recurrence_days[]"][value="${dayVal}"]`);
                if (cb && !cb.checked) {
                    cb.checked = true;
                    toggleRecurrenceTime(dayVal);
                }
            }
            updateRecurrencePreview();
        }, 50);
    }
}

function updateRecurrencePreview() {
    const isOn = document.getElementById('recurrenceToggle').checked;
    if (!isOn) return;

    const endType = document.querySelector('input[name="recurrence_end_type"]:checked').value;
    const text = document.getElementById('previewText');
    const preview = document.getElementById('recurrencePreview');
    
    const checkboxes = document.querySelectorAll('.recurrence-day-cb:checked');
    if (checkboxes.length === 0) {
        preview.style.display = 'none';
        return;
    }
    
    const daysSelected = Array.from(checkboxes).map(cb => cb.parentNode.textContent.trim()).join(', ');
    
    if (endType === 'date') {
        const endRaw = document.getElementById('repeatEndDate').value;
        if (!endRaw) {
            preview.style.display = 'none';
            return;
        }
        text.textContent = `Aos dias: ${daysSelected}, começando da data definida, até ${endRaw.split('-').reverse().join('/')}`;
    } else {
        const count = document.getElementById('occurrencesCount').value;
        if (!count || count <= 0) {
            preview.style.display = 'none';
            return;
        }
        text.textContent = `Aos dias: ${daysSelected}. Um total de ${count} sessões serão agendadas.`;
    }
    
    preview.style.display = 'block';
}
</script>
