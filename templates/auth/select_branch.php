<?php
// templates/auth/select_branch.php
require_once __DIR__ . '/../../src/Controllers/BranchController.php';

$branchController = new BranchController();
$branches = $branchController->getAll();

// Handle branch selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['branch_id'])) {
    if ($branchController->selectBranch($_POST['branch_id'])) {
        header('Location: ?page=dashboard');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selecionar Filial - Nexo System</title>
    <link rel="stylesheet" href="public/assets/css/style.css?v=<?=time()?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .branch-selection-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #2E86AB 0%, #56B4D3 100%);
        }
        .branch-card {
            background: white;
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            max-width: 500px;
            width: 90%;
            text-align: center;
        }
        .branch-card h1 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        .branch-card p {
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }
        .branch-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .branch-btn {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border: 2px solid #E5E7EB;
            border-radius: var(--radius-md);
            background: white;
            cursor: pointer;
            transition: all 0.2s;
            text-align: left;
        }
        .branch-btn:hover {
            border-color: var(--primary-color);
            background: #F0F9FF;
        }
        .branch-btn i {
            font-size: 1.5rem;
            color: var(--primary-color);
        }
        .branch-btn .branch-name {
            font-weight: 600;
            color: var(--text-primary);
        }
        .branch-btn .branch-address {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <div class="branch-selection-container">
        <div class="branch-card">
            <i class="fa-solid fa-building" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
            <h1>Selecionar Filial</h1>
            <p>Escolha a unidade que deseja acessar</p>

            <?php if (empty($branches)): ?>
                <p style="color: var(--danger-color);">Nenhuma filial cadastrada. Entre em contato com o administrador.</p>
            <?php else: ?>
                <form method="POST" class="branch-list">
                    <?php foreach ($branches as $branch): ?>
                        <button type="submit" name="branch_id" value="<?= $branch['id'] ?>" class="branch-btn">
                            <i class="fa-solid fa-location-dot"></i>
                            <div>
                                <div class="branch-name"><?= htmlspecialchars($branch['name']) ?></div>
                                <?php if (!empty($branch['address'])): ?>
                                    <div class="branch-address"><?= htmlspecialchars($branch['address']) ?></div>
                                <?php endif; ?>
                            </div>
                        </button>
                    <?php endforeach; ?>
                </form>
            <?php endif; ?>

            <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #E5E7EB;">
                <a href="?page=logout" style="color: var(--text-secondary); font-size: 0.9rem;">
                    <i class="fa-solid fa-right-from-bracket"></i> Sair
                </a>
            </div>
        </div>
    </div>
</body>
</html>
