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

if ($id) {
    $professional = $controller->getById($id);
    $skills = $controller->getSkills($id);
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

<div class="card" style="max-width: 800px; margin: 0 auto;">
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

    <form method="POST" id="professionalForm">
        <h3 style="margin-bottom: 1.5rem; color: var(--primary-color);">Dados Básicos</h3>
        
        <div class="form-group">
            <label for="name">Nome Completo *</label>
            <input type="text" id="name" name="name" required placeholder="Ex: Dra. Ana Silva" value="<?= $professional ? htmlspecialchars($professional['name']) : '' ?>">
        </div>

        <?php if (!$id): ?>
        <hr style="margin: 1.5rem 0; border: none; border-top: 1px dashed #E5E7EB;">
        <h4 style="margin-bottom: 1rem; color: var(--primary-color);">Acesso ao Sistema</h4>
        <p style="color: var(--text-secondary); margin-bottom: 1rem; font-size: 0.85rem;">
            O profissional receberá o perfil "Profissional" e poderá ver apenas os seus próprios agendamentos.
        </p>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label for="username">Nome de Usuário do Profissional *</label>
                <input type="text" id="username" name="username" placeholder="ex: ana.silva" <?= !$id ? 'required' : '' ?>>
            </div>
            
            <div class="form-group">
                <label for="password">Senha de Acesso *</label>
                <input type="text" id="password" name="password" placeholder="Mínimo 6 caracteres" minlength="6" <?= !$id ? 'required' : '' ?>>
            </div>
        </div>
        <hr style="margin: 1.5rem 0; border: none; border-top: 1px dashed #E5E7EB;">
        <?php endif; ?>

        <div class="form-group">
            <label>Especialidades *</label>
            <div style="background: #F9FAFB; padding: 1rem; border-radius: var(--radius-md); border: 1px solid #E5E7EB; display: grid; gap: 0.5rem; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));">
                <?php if(empty($allSpecialties)): ?>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin: 0;">Nenhuma especialidade cadastrada. <a href="?page=specialties_new" style="color: var(--primary-color);">Cadastrar agora</a>.</p>
                <?php else: ?>
                    <?php foreach($allSpecialties as $spec): ?>
                        <label style="display: flex; align-items: center; gap: 0.5rem; margin: 0; font-weight: normal; cursor: pointer;">
                            <input type="checkbox" name="specialties[]" value="<?= $spec['id'] ?>" <?= in_array($spec['id'], $profSpecialties) ? 'checked' : '' ?> style="width: auto;">
                            <?= htmlspecialchars($spec['name']) ?>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <hr style="margin: 2rem 0; border: none; border-top: 1px solid #E5E7EB;">
        
        <h3 style="margin-bottom: 1rem; color: var(--primary-color);">Conhecimentos e Habilidades</h3>
        <p style="color: var(--text-secondary); margin-bottom: 1.5rem; font-size: 0.9rem;">
            Adicione especialidades, conhecimentos técnicos e certificações do profissional
        </p>

        <div id="skillsContainer">
            <?php if (!empty($skills)): ?>
                <?php foreach ($skills as $index => $skill): ?>
                    <div class="skill-row" style="display: flex; gap: 0.75rem; margin-bottom: 0.75rem; align-items: end;">
                        <div style="flex: 2;">
                            <label>Nome</label>
                            <input type="text" name="skills[<?= $index ?>][name]" placeholder="Ex: ABA, TEACCH, BCBA" value="<?= htmlspecialchars($skill['skill_name']) ?>" required>
                        </div>
                        <div style="flex: 1;">
                            <label>Tipo</label>
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

        <button type="button" class="btn" onclick="addSkill()" style="background: var(--secondary-color); color: white; margin-top: 0.5rem;">
            <i class="fa-solid fa-plus"></i> Adicionar Conhecimento/Habilidade
        </button>

        <div style="margin-top: 2rem; text-align: right;">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-save"></i> <?= $id ? 'Atualizar Profissional' : 'Salvar Profissional' ?>
            </button>
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
