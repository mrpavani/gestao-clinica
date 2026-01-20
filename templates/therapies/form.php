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
    $existingDocs = $controller->getDocuments($id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $duration = $_POST['duration'] ?? 60;
    $selectedProfessionals = $_POST['professionals'] ?? [];

    // Process Documents
    $documents = [];
    if (isset($_POST['doc_names'])) {
        foreach ($_POST['doc_names'] as $index => $docName) {
            if (trim($docName) !== '') {
                $documents[] = [
                    'id' => $_POST['doc_ids'][$index] ?? null,
                    'name' => trim($docName),
                    'required' => isset($_POST['doc_required'][$index])
                ];
            }
        }
    }

    if ($name) {
        if ($id) {
            if ($controller->update($id, $name, $duration, $selectedProfessionals, $documents)) {
                // Redirect to list
                header("Location: ?page=therapies");
                exit;
            } else {
                $error = 'Erro ao atualizar terapia.';
            }
        } else {
            if ($controller->create($name, $duration, $selectedProfessionals, $documents)) {
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
            <input type="text" id="name" name="name" required placeholder="Ex: Fonoaudiologia" value="<?= $therapy ? htmlspecialchars($therapy['name']) : '' ?>" autocomplete="off">
        </div>

        <div class="form-group">
            <label for="duration">Duração Padrão (minutos - máx 60)</label>
            <input type="number" id="duration" name="duration" min="15" max="60" value="<?= $therapy ? $therapy['default_duration_minutes'] : 45 ?>">
        </div>
        
        <!-- DOCUMENTS SECTION -->
        <div class="form-group">
            <label style="margin-bottom: 0.75rem; display: block;">Documentos Necessários (Opcionais ou Obrigatórios)</label>
            <div id="docs-container" style="border: 1px solid #E5E7EB; border-radius: var(--radius-md); padding: 1rem; background: #f9fafb;">
                <?php 
                $docs = $existingDocs ?? [];
                if (empty($docs) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
                    // Add one empty row by default if new? No, let user add.
                }
                ?>
                
                <div id="doc-list">
                    <?php foreach ($docs as $doc): ?>
                        <div class="doc-row" style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem; align-items: center;">
                            <input type="hidden" name="doc_ids[]" value="<?= $doc['id'] ?>">
                            <input type="text" name="doc_names[]" placeholder="Nome do Documento (Ex: Laudo)" value="<?= htmlspecialchars($doc['name']) ?>" style="flex: 1;" autocomplete="off">
                            <label style="display: flex; align-items: center; gap: 0.25rem; font-size: 0.9rem; margin: 0;">
                                <input type="checkbox" name="doc_required[]" <?= $doc['is_required'] ? 'checked' : '' ?>>
                                Obrigatório
                            </label>
                            <button type="button" onclick="removeDoc(this)" style="background: none; border: none; color: #DC2626; cursor: pointer;">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="button" onclick="addDoc()" style="margin-top: 0.5rem; font-size: 0.9rem; color: var(--primary-color); background: none; border: none; cursor: pointer; display: flex; align-items: center; gap: 0.25rem;">
                    <i class="fa-solid fa-plus"></i> Adicionar Documento
                </button>
            </div>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.5rem;">Defina quais documentos podem ser anexados ao paciente para esta terapia.</p>
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

<script>
function addDoc() {
    const container = document.getElementById('doc-list');
    const div = document.createElement('div');
    div.className = 'doc-row';
    div.style = 'display: flex; gap: 0.5rem; margin-bottom: 0.5rem; align-items: center;';
    div.innerHTML = `
        <input type="hidden" name="doc_ids[]" value="">
        <input type="text" name="doc_names[]" placeholder="Nome do Documento (Ex: Laudo)" style="flex: 1;" autocomplete="off">
        <label style="display: flex; align-items: center; gap: 0.25rem; font-size: 0.9rem; margin: 0;">
            <input type="checkbox" name="doc_required[]">
            Obrigatório
        </label>
        <button type="button" onclick="removeDoc(this)" style="background: none; border: none; color: #DC2626; cursor: pointer;">
            <i class="fa-solid fa-trash"></i>
        </button>
    `;
    container.appendChild(div);
}

function removeDoc(btn) {
    btn.closest('.doc-row').remove();
}
</script>

        <div style="margin-top: 2rem; text-align: right;">
            <button type="submit" class="btn btn-primary">
                <?= $id ? 'Salvar Alterações' : 'Salvar Terapia' ?>
            </button>
        </div>
    </form>
</div>
