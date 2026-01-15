<?php
// templates/professionals/form.php
$controller = new ProfessionalController();
$message = '';
$error = '';
$id = $_GET['id'] ?? null;
$professional = null;
$skills = [];

if ($id) {
    $professional = $controller->getById($id);
    $skills = $controller->getSkills($id);
    if (!$professional) {
        echo "Profissional não encontrado.";
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $specialty = $_POST['specialty'] ?? '';
    $email = $_POST['email'] ?? null;
    $hours = $_POST['max_weekly_hours'] ?? 40;
    $newSkills = $_POST['skills'] ?? [];

    if ($name && $specialty) {
        if ($id) {
            // Edit
            if ($controller->update($id, $name, $specialty, $hours, $email)) {
                // Update skills
                // First, delete existing skills then re-add
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
            $newId = $controller->create($name, $specialty, $hours, $email);
            if ($newId) {
                // Add skills
                foreach ($newSkills as $skill) {
                    if (!empty($skill['name'])) {
                        $controller->addSkill($newId, $skill['name'], $skill['type']);
                    }
                }
                header("Location: ?page=professionals");
                exit;
            } else {
                $error = 'Erro ao cadastrar profissional. Pode haver um profissional com este nome já cadastrado.';
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

        <div class="form-group">
            <label for="specialty">Especialidade Principal *</label>
            <input type="text" id="specialty" name="specialty" required placeholder="Ex: Fonoaudiologia" value="<?= $professional ? htmlspecialchars($professional['specialty']) : '' ?>">
        </div>

        <div class="form-group">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" placeholder="profissional@email.com" value="<?= $professional ? htmlspecialchars($professional['email'] ?? '') : '' ?>">
        </div>

        <div class="form-group">
            <label for="max_weekly_hours">Carga Horária Semanal (Horas) *</label>
            <input type="number" id="max_weekly_hours" name="max_weekly_hours" value="<?= $professional ? $professional['max_weekly_hours'] : 40 ?>" min="1" max="168">
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
