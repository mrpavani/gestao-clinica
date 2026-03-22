    <nav class="sidebar">
        <div class="brand">
            <img src="public/assets/img/logo.png" alt="Nexo Logo" class="brand-logo">
            <span>Nexo System</span>
        </div>
        
        <!-- Current Branch Badge -->
        <?php if ($currentBranchName): ?>
        <div style="background: #E0F2FE; border-radius: var(--radius-md); padding: 0.5rem 0.75rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fa-solid fa-building" style="color: var(--primary-color);"></i>
            <span style="font-weight: 500; color: var(--primary-color); font-size: 0.9rem;"><?= htmlspecialchars($currentBranchName) ?></span>
            <a href="?page=select_branch" style="margin-left: auto; font-size: 0.8rem; color: var(--text-secondary);" title="Trocar Filial">
                <i class="fa-solid fa-arrows-rotate"></i>
            </a>
        </div>
        <?php endif; ?>
        
        <ul class="nav-links">
            <?php if (AuthController::isAdmin()): ?>
            <li class="nav-item">
                <a href="?page=dashboard" class="<?= $page === 'dashboard' ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-pie"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="?page=professionals" class="<?= $page === 'professionals' || $page === 'professionals_new' ? 'active' : '' ?>">
                    <i class="fa-solid fa-user-doctor"></i> Profissionais
                </a>
            </li>
            <li class="nav-item">
                <a href="?page=patients" class="<?= strpos($page, 'patient') === 0 ? 'active' : '' ?>">
                    <i class="fa-solid fa-users"></i> Pacientes
                </a>
            </li>
            <li class="nav-item">
                <a href="?page=therapies" class="<?= $page === 'therapies' ? 'active' : '' ?>">
                    <i class="fa-solid fa-hands-holding-child"></i> Terapias
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a href="?page=schedule" class="<?= $page === 'schedule' ? 'active' : '' ?>">
                    <i class="fa-regular fa-calendar-days"></i> Agenda
                </a>
            </li>
            <?php if (AuthController::isAdmin()): ?>
            <li class="nav-item" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255, 255, 255, 0.1);">
                <a href="?page=settings" class="<?= in_array($page, ['settings', 'specialties', 'specialties_new']) ? 'active' : '' ?>">
                    <i class="fa-solid fa-gear"></i> Configurações
                </a>
            </li>
            <?php endif; ?>
        </ul>
        
        <!-- User Menu -->
        <div class="sidebar-footer-menu">
            <div class="user-info">
                <i class="fa-solid fa-circle-user"></i>
                <span><?= htmlspecialchars($currentUser['username'] ?? '') ?></span>
            </div>
            
            <a href="?page=change_password" class="btn" style="margin-bottom: 0.5rem; background: transparent; border: 1px solid rgba(255,255,255,0.2); color: white;">
                <i class="fa-solid fa-key"></i> Alterar Senha
            </a>
            
            <?php if (AuthController::isAdmin()): ?>
            <a href="?page=branches" class="btn">
                <i class="fa-solid fa-building"></i> Gerenciar Filiais
            </a>
            <a href="?page=users" class="btn">
                <i class="fa-solid fa-users-gear"></i> Gerenciar Usuários
            </a>
            <?php endif; ?>
            <a href="?page=logout" class="btn" style="background: #ef4444; color: white;">
                <i class="fa-solid fa-right-from-bracket"></i> Sair
            </a>
        </div>
    </nav>
