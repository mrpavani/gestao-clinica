<?php
// templates/professionals/form.php
$controller = new ProfessionalController();
require_once __DIR__ . '/../../src/Controllers/SpecialtyController.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
$specialtyController = new SpecialtyController();
$authController = new AuthController();
$allSpecialties = $specialtyController->getAll();

$message = '';
$error = '';
$id = $_GET['id'] ?? null;
$professional = null;
$skills = [];
$profSpecialties = [];
$schedules = [];

if ($id) {
    $professional = $controller->getById($id);
    $skills = $controller->getSkills($id);
    $schedulesList = $controller->getSchedules($id);
    foreach ($schedulesList as $sched) {
        $schedules[$sched['day_of_week']] = [
            'start' => substr($sched['start_time'], 0, 5),
            'end' => substr($sched['end_time'], 0, 5)
        ];
    }

    if (!$professional) {
        echo "Profissional não encontrado.";
        exit;
    }
    if (!empty($professional['specialties'])) {
        $profSpecialties = array_column($professional['specialties'], 'id');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    // specialty checkboxes
    $selectedSpecialties = $_POST['specialties'] ?? [];
    $newSkills = $_POST['skills'] ?? [];
    $newSchedules = $_POST['schedules'] ?? [];
    
    // user auto-create fields
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($name) {
        if ($id) {
            // Edit
            if ($controller->update($id, $name, $selectedSpecialties)) {
                // Update skills
                foreach ($skills as $skill) {
                    $controller->removeSkill($skill['id']);
                }
                foreach ($newSkills as $skill) {
                    if (!empty($skill['name'])) {
                        $controller->addSkill($id, $skill['name'], $skill['type']);
                    }
                }
                $controller->saveSchedules($id, $newSchedules);
                
                header("Location: ?page=professionals");
                exit;
            } else {
                $error = 'Erro ao atualizar profissional. Pode haver um profissional com este nome já cadastrado.';
            }
        } else {
            // Create
            if (empty($username) || empty($password)) {
                $error = 'Para cadastrar um novo profissional, é necessário informar um Nome de Usuário e Senha de acesso.';
            } else {
                $newId = $controller->create($name, $selectedSpecialties);
                if ($newId) {
                    // Add skills
                    foreach ($newSkills as $skill) {
                        if (!empty($skill['name'])) {
                            $controller->addSkill($newId, $skill['name'], $skill['type']);
                        }
                    }
                    $controller->saveSchedules($newId, $newSchedules);
                    
                    // Add user auto-generation
                    $userRes = $authController->createUser($username, $password, 'professional', $newId);
                    if (!$userRes['success']) {
                        // User creation failed, but professional created. Should handle gracefully or delete prof to rollback.
                        // Setting error and preventing redirect so user can see it
                        $error = 'Profissional cadastrado, mas falha ao criar o acesso do usuário: ' . $userRes['message'];
                    } else {
                        header("Location: ?page=professionals");
                        exit;
                    }
                } else {
                    $error = 'Erro ao cadastrar profissional. Pode haver um profissional com este nome já cadastrado.';
                }
            }
        }
    } else {
        $error = 'Preencha todos os campos obrigatórios.';
    }
}
?>

<header>
    <h1><?= $id ? 'Editar Profissional' : 'Novo Profissional' ?></h1>
    <a href="?page=professionals" class="btn" style="background: #e5e7eb; color: var(--text-primary);">
        <i class="fa-solid fa-arrow-left"></i> Voltar
    </a>
</header>

<div style="max-width: 1000px; margin: 0 auto;">
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

    <form method="POST" id="professionalForm" autocomplete="off">
        
        <div style="display: grid; grid-template-columns: 1fr; gap: 1.5rem;">
            <!-- Adjust to 2 columns on larger screens -->
            <style>
                @media(min-width: 768px) {
                    .prof-grid { grid-template-columns: 1fr 1fr !important; }
                    .prof-full { grid-column: 1 / -1; }
                }
            </style>
            
            <div class="prof-grid" style="display: grid; grid-template-columns: 1fr; gap: 1.5rem;">
                
                <!-- BLOCK: Identificação & Login -->
                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    
                    <div class="card">
                        <h3 style="margin-bottom: 1.5rem; color: var(--primary-color); border-bottom: 1px solid #E5E7EB; padding-bottom: 0.5rem;">Dados Básicos</h3>
                        
                        <div class="form-group">
                            <label for="name">Nome Completo *</label>
                            <input type="text" id="name" name="name" required placeholder="Ex: Dra. Ana Silva" value="<?= $professional ? htmlspecialchars($professional['name']) : '' ?>" autocomplete="new-password">
                        </div>

                        <?php if (!$id): ?>
                        <div style="margin-top: 1.5rem; background: #F9FAFB; padding: 1rem; border-radius: var(--radius-md); border: 1px solid #E5E7EB;">
                            <h4 style="margin-top: 0; margin-bottom: 0.5rem; color: var(--text-primary); font-size: 0.95rem;">Acesso ao Sistema</h4>
                            <p style="color: var(--text-secondary); margin-bottom: 1rem; font-size: 0.85rem;">Credenciais geradas automaticamente para acesso restrito à própria agenda.</p>
                            
                            <div class="form-group">
                                <label for="username">Nome de Usuário *</label>
                                <input type="text" id="username" name="username" placeholder="ex: ana.silva" required autocomplete="new-password">
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="password">Senha de Acesso *</label>
                                <input type="password" id="password" name="password" placeholder="Mínimo 6 caracteres" minlength="6" required autocomplete="new-password">
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card">
                         <h3 style="margin-bottom: 1.5rem; color: var(--primary-color); border-bottom: 1px solid #E5E7EB; padding-bottom: 0.5rem;">Especialidades Oficiais</h3>
                         
                         <div style="background: #F9FAFB; padding: 1rem; border-radius: var(--radius-md); border: 1px solid #E5E7EB; display: grid; gap: 0.5rem; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));">
                            <?php if(empty($allSpecialties)): ?>
                                <p style="color: var(--text-secondary); font-size: 0.9rem; margin: 0; grid-column: 1/-1;">Nenhuma especialidade cadastrada. <a href="?page=specialties_new" style="color: var(--primary-color);">Cadastrar agora</a>.</p>
                            <?php else: ?>
                                <?php foreach($allSpecialties as $spec): ?>
                                    <label style="display: flex; align-items: center; gap: 0.5rem; margin: 0; font-weight: normal; cursor: pointer; font-size: 0.9rem;">
                                        <input type="checkbox" name="specialties[]" value="<?= $spec['id'] ?>" <?= in_array($spec['id'], $profSpecialties) ? 'checked' : '' ?> style="width: auto;">
                                        <?= htmlspecialchars($spec['name']) ?>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- BLOCK: Escala e Conhecimentos -->
                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    
                    <div class="card">
                        <h3 style="margin-bottom: 1.5rem; color: var(--primary-color); border-bottom: 1px solid #E5E7EB; padding-bottom: 0.5rem;">Carga Horária Semanal</h3>
                        <p style="color: var(--text-secondary); margin-bottom: 1rem; font-size: 0.85rem;">Defina a jornada para limitar a agenda na visão geral do sistema.</p>

                        <div style="display: grid; gap: 0.75rem;">
                            <?php 
                            $days = [
                                1 => 'Segunda-feira', 2 => 'Terça-feira', 3 => 'Quarta-feira', 
                                4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado', 0 => 'Domingo'
                            ];
                            foreach ($days as $dayNum => $dayName): 
                                $isActive = isset($schedules[$dayNum]);
                                $start = $isActive ? $schedules[$dayNum]['start'] : '08:00';
                                $end = $isActive ? $schedules[$dayNum]['end'] : '18:00';
                            ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0.75rem; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: var(--radius-md);">
                                <label style="display: flex; align-items: center; gap: 0.5rem; margin: 0; font-weight: 500; cursor: pointer; font-size: 0.9rem;">
                                    <input type="checkbox" name="schedules[<?= $dayNum ?>][active]" value="1" <?= $isActive ? 'checked' : '' ?> onchange="toggleSchedule(this, <?= $dayNum ?>)" style="width: auto; margin:0;">
                                    <?= $dayName ?>
                                </label>
                                
                                <div style="display: flex; gap: 0.25rem; align-items: center; opacity: <?= $isActive ? '1' : '0.4' ?>; pointer-events: <?= $isActive ? 'auto' : 'none' ?>;" id="sched_time_<?= $dayNum ?>">
                                    <input type="time" name="schedules[<?= $dayNum ?>][start]" value="<?= $start ?>" style="width: auto; padding: 0.25rem; font-size: 0.85rem;">
                                    <span style="font-size: 0.8rem; color: #9CA3AF;">às</span>
                                    <input type="time" name="schedules[<?= $dayNum ?>][end]" value="<?= $end ?>" style="width: auto; padding: 0.25rem; font-size: 0.85rem;">
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- BLOCK: Skills Adicionais -->
                <div class="card prof-full">
                    <h3 style="margin-bottom: 1rem; color: var(--primary-color); border-bottom: 1px solid #E5E7EB; padding-bottom: 0.5rem;">Conhecimentos Específicos Adicionais</h3>
                    <p style="color: var(--text-secondary); margin-bottom: 1rem; font-size: 0.85rem;">Certificações extras ou métodos de aplicação pertinentes.</p>

                    <div id="skillsContainer">
                        <?php if (!empty($skills)): ?>
                            <?php foreach ($skills as $index => $skill): ?>
                                <div class="skill-row" style="display: flex; gap: 0.75rem; margin-bottom: 0.75rem; align-items: end;">
                                    <div style="flex: 2;">
                                        <label style="font-size: 0.85rem;">Nome</label>
                                        <input type="text" name="skills[<?= $index ?>][name]" placeholder="Ex: Método ABA" value="<?= htmlspecialchars($skill['skill_name']) ?>" required>
                                    </div>
                                    <div style="flex: 1;">
                                        <label style="font-size: 0.85rem;">Classificação</label>
                                        <select name="skills[<?= $index ?>][type]">
                                            <option value="specialty" <?= $skill['skill_type'] == 'specialty' ? 'selected' : '' ?>>Especialidade</option>
                                            <option value="knowledge" <?= $skill['skill_type'] == 'knowledge' ? 'selected' : '' ?>>Conhecimento</option>
                                            <option value="certification" <?= $skill['skill_type'] == 'certification' ? 'selected' : '' ?>>Certificação</option>
                                        </select>
                                    </div>
                                    <button type="button" class="btn" onclick="removeSkill(this)" style="background: var(--danger-color); color: white; padding: 0.625rem 1rem;">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <button type="button" class="btn" onclick="addSkill()" style="background: var(--secondary-color); color: white; margin-top: 0.5rem; font-size: 0.9rem;">
                        <i class="fa-solid fa-plus"></i> Adicionar Nova Habilidade
                    </button>
                </div>
            </div>

            <div style="text-align: right; margin-top: 1rem;">
                <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem; font-size: 1rem;">
                    <i class="fa-solid fa-save"></i> <?= $id ? 'Salvar Alterações do Profissional' : 'Cadastrar Profissional' ?>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
let skillIndex = <?= count($skills) ?>;

function addSkill() {
    const container = document.getElementById('skillsContainer');
    const skillRow = document.createElement('div');
    skillRow.className = 'skill-row';
    skillRow.style.cssText = 'display: flex; gap: 0.75rem; margin-bottom: 0.75rem; align-items: end;';
    skillRow.innerHTML = `
        <div style="flex: 2;">
            <label>Nome</label>
            <input type="text" name="skills[${skillIndex}][name]" placeholder="Ex: ABA, TEACCH, BCBA" required>
        </div>
        <div style="flex: 1;">
            <label>Tipo</label>
            <select name="skills[${skillIndex}][type]">
                <option value="specialty">Especialidade</option>
                <option value="knowledge" selected>Conhecimento</option>
                <option value="certification">Certificação</option>
            </select>
        </div>
        <button type="button" class="btn" onclick="removeSkill(this)" style="background: var(--danger-color); color: white; padding: 0.625rem 1rem;">
            <i class="fa-solid fa-trash"></i>
        </button>
    `;
    container.appendChild(skillRow);
    skillIndex++;
}

function removeSkill(button) {
    button.closest('.skill-row').remove();
}
</script>

<?php if (!$id): ?>
<script>
document.getElementById('name').addEventListener('input', function() {
    const nameStr = this.value.trim();
    if (nameStr) {
        // remove acentos
        const noAccent = nameStr.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        const parts = noAccent.toLowerCase().split(/\s+/);
        let usernameStr = '';
        if (parts.length > 1) {
            // Primeiro nome . ultimo nome
            usernameStr = parts[0] + '.' + parts[parts.length - 1];
        } else {
            usernameStr = parts[0];
        }
        
        // Only override if user hasn't manually changed the username yet
        const usernameInput = document.getElementById('username');
        if (!usernameInput.dataset.manualEdit) {
            usernameInput.value = usernameStr;
        }
    }
});

document.getElementById('username').addEventListener('input', function() {
    this.dataset.manualEdit = 'true';
});
</script>
<?php endif; ?>
