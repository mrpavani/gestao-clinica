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

$appointments = $apptController->getAppointmentsByRange($startDate, $endDate);
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

// Handle Form Submission
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $res = $apptController->create(
        $_POST['patient_id'],
        $_POST['professional_id'],
        $_POST['therapy_id'],
        $_POST['start_time'],
        $_POST['duration'],
        $_POST['notes'] ?? ''
    );
    
    if ($res['success']) {
        $message = 'Agendamento realizado!';
        // Refresh appointments
        $appointments = $apptController->getAppointmentsByRange($startDate, $endDate);
        $calendarEvents = [];
        foreach ($appointments as $appt) {
            $day = date('j', strtotime($appt['start_time']));
            $calendarEvents[$day][] = $appt;
        }
    } else {
        $error = $res['error'];
    }
}

// Navigation
$prevMonth = date('m', strtotime("$startDate -1 month"));
$prevYear = date('Y', strtotime("$startDate -1 month"));
$nextMonth = date('m', strtotime("$startDate +1 month"));
$nextYear = date('Y', strtotime("$startDate +1 month"));
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
    <h1>Agenda - <?= date('F Y', strtotime($startDate)) ?></h1>
    <div>
        <a href="?page=schedule&month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="btn" style="background: #e5e7eb; color: var(--text-primary);">
            <i class="fa-solid fa-chevron-left"></i> Anterior
        </a>
        <a href="?page=schedule&month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="btn" style="background: #e5e7eb; color: var(--text-primary);">
            Próximo <i class="fa-solid fa-chevron-right"></i>
        </a>
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

<div style="display: grid; grid-template-columns: 3fr 1fr; gap: 2rem;">
    <!-- Calendar Grid -->
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
                
                echo '<div style="background: white; min-height: 120px; padding: 0.5rem; border: 1px solid #f3f4f6; position: relative;">';
                echo '<div style="font-weight: bold; margin-bottom: 0.5rem; ' . ($isToday ? 'color: var(--primary-color);' : '') . '">' . $dayCount . '</div>';
                
                foreach ($events as $evt) {
                    $color = ($evt['status'] == 'completed') ? '#10B981' : 'var(--primary-color)';
                    $title = date('H:i', strtotime($evt['start_time'])) . ' - ' . substr($evt['patient_name'], 0, 10) . '...';
                    
                    // Link to Notes page
                    echo "<a href='?page=appointment_notes&id={$evt['id']}' style='display: block; background: {$color}; color: white; font-size: 0.75rem; padding: 2px 4px; border-radius: 4px; margin-bottom: 2px; text-decoration: none;' title='{$evt['patient_name']} - {$evt['therapy_name']}'>";
                    echo $title;
                    echo "</a>";
                }
                
                echo '</div>';
                $dayCount++;
            }
        }
        ?>
    </div>

    <!-- Quick Add Form -->
    <div class="card">
        <h3>Novo Agendamento</h3>
        <form method="POST">
            <div class="form-group">
                <label>Paciente</label>
                <select name="patient_id" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($patients as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Terapia</label>
                <select name="therapy_id" id="therapySelect" required onchange="filterProfessionals()">
                    <option value="">Selecione...</option>
                    <?php foreach ($therapies as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                    <?php endforeach; ?>
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
                <input type="datetime-local" name="start_time" required value="<?= date('Y-m-d\T08:00') ?>">
            </div>
            
            <div class="form-group">
                <label>Duração (min)</label>
                <input type="number" name="duration" value="60" min="15" step="15">
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">Agendar</button>
        </form>
    </div>
</div>

<script>
// Map of Therapy ID -> [Professional IDs]
const therapyMap = <?= json_encode($therapyProfs) ?>;
const allProfs = <?= json_encode($professionals) ?>;

function filterProfessionals() {
    const therapyId = document.getElementById('therapySelect').value;
    const profSelect = document.getElementById('profSelect');
    
    // Clear current options
    profSelect.innerHTML = '<option value="">Selecione...</option>';
    
    if (!therapyId) return;
    
    const allowedIds = therapyMap[therapyId] || []; // List of IDs allowed
    
    // Filter allProfs
    const filtered = allProfs.filter(p => allowedIds.includes(p.id));
    
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
