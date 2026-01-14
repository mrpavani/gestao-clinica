<?php
// templates/therapies/form.php
$controller = new TherapyController();
$professionals = $controller->getAvailableProfessionals();
$message = '';
$error = '';
$id = $_GET['id'] ?? null;
$therapy = null;
$linkedProfs = [];

if ($id) {
    $therapy = $controller->getById($id);
    if (!$therapy) {
        echo "Terapia não encontrada.";
        exit;
    }
    $linkedProfs = $controller->getLinkedProfessionals($id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $duration = $_POST['duration'] ?? 60;
    $selectedProfessionals = $_POST['professionals'] ?? [];

    if ($name) {
        if ($id) {
            if ($controller->update($id, $name, $duration, $selectedProfessionals)) {
                // Redirect to list
                header("Location: ?page=therapies");
                exit;
            } else {
                $error = 'Erro ao atualizar terapia.';
            }
        } else {
            if ($controller->create($name, $duration, $selectedProfessionals)) {
                // Redirect to list
                header("Location: ?page=therapies");
                exit;
            } else {
                $error = 'Erro ao cadastrar terapia.';
            }
        }
    } else {
        $error = 'O nome da terapia é obrigatório.';
    }
}
?>

<header>
    <h1><?= $id ? 'Editar Terapia' : 'Nova Terapia' ?></h1>
    <a href="?page=therapies" class="btn" style="background: #e5e7eb; color: var(--text-primary);">
        <i class="fa-solid fa-arrow-left"></i> Voltar
    </a>
</header>

<div class="card" style="max-width: 600px; margin: 0 auto;">
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

    <form method="POST">
        <div class="form-group">
            <label for="name">Nome da Terapia</label>
            <input type="text" id="name" name="name" required placeholder="Ex: Fonoaudiologia" value="<?= $therapy ? htmlspecialchars($therapy['name']) : '' ?>">
        </div>

        <div class="form-group">
            <label for="duration">Duração Padrão (minutos - máx 60)</label>
            <input type="number" id="duration" name="duration" min="15" max="60" value="<?= $therapy ? $therapy['default_duration_minutes'] : 45 ?>">
        </div>

        <div class="form-group">
            <label style="margin-bottom: 0.75rem; display: block;">Vincular Profissionais</label>
            <div style="max-height: 300px; overflow-y: auto; border: 1px solid #E5E7EB; border-radius: var(--radius-md);">
                <?php if (empty($professionals)): ?>
                    <p style="padding: 1rem; color: var(--text-secondary); text-align: center;">Nenhum profissional cadastrado.</p>
                <?php else: ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tbody>
                            <?php foreach ($professionals as $prof): ?>
                                <tr style="border-bottom: 1px solid #f3f4f6;">
                                    <td style="padding: 0.75rem 1rem; width: 40px;">
                                        <input type="checkbox" id="prof_<?= $prof['id'] ?>" name="professionals[]" value="<?= $prof['id'] ?>" <?= in_array($prof['id'], $linkedProfs) ? 'checked' : '' ?> style="width: 1.25rem; height: 1.25rem; cursor: pointer;">
                                    </td>
                                    <td style="padding: 0.75rem 1rem;">
                                        <label for="prof_<?= $prof['id'] ?>" style="margin: 0; cursor: pointer; display: block;">
                                            <div style="font-weight: 500; color: var(--text-primary);"><?= htmlspecialchars($prof['name']) ?></div>
                                            <div style="font-size: 0.85rem; color: var(--text-secondary);"><?= htmlspecialchars($prof['specialty']) ?></div>
                                        </label>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.5rem;">Selecione os profissionais que podem realizar esta terapia.</p>
        </div>

        <div style="margin-top: 2rem; text-align: right;">
            <button type="submit" class="btn btn-primary">
                <?= $id ? 'Salvar Alterações' : 'Salvar Terapia' ?>
            </button>
        </div>
    </form>
</div>
