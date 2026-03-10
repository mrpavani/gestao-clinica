<?php
// templates/settings/index.php
?>
<header>
    <h1>Configurações</h1>
</header>

<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem;">
    <div class="card" style="text-align: center; padding: 2rem;">
        <i class="fa-solid fa-stethoscope" style="font-size: 2.5rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
        <h3 style="margin-bottom: 0.5rem; color: var(--text-primary);">Especialidades</h3>
        <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.5rem;">
            Gerencie as especialidades disponíveis na clínica.
        </p>
        <a href="?page=specialties" class="btn btn-primary">
            Gerenciar Especialidades
        </a>
    </div>
    
    <!-- Future settings cards can go here -->
</div>
