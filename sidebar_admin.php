<?php
// c:\xampp\htdocs\Projeto_PAP\sidebar_admin.php
// This sidebar is included in admin-related pages.
// The $active_page variable should be defined in the file that includes this sidebar.
// Ensure $active_page is defined before including this file.
// Example: $active_page = 'admin.php';
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="admin.php" class="logo">MyWellness - Admin</a>
    </div>
    <nav>
        <ul class="sidebar-nav">
            <li class="sidebar-item <?= ($active_page === 'admin.php') ? 'active' : ''; ?>">
                <a href="admin.php" class="sidebar-link">
                    <i class="fas fa-chart-line fa-fw"></i>
                    <span class="align-middle">Estatísticas</span>
                </a>
            </li>
            <li class="sidebar-item <?= ($active_page === 'manage_classes_admin.php') ? 'active' : ''; ?>">
                <a href="manage_classes_admin.php" class="sidebar-link">
                    <i class="fas fa-calendar-check fa-fw"></i>
                    <span class="align-middle">Gerir Aulas</span>
                </a>
            </li>
            <li class="sidebar-item <?= ($active_page === 'manage_trainers_admin.php') ? 'active' : ''; ?>">
                <a href="manage_trainers_admin.php" class="sidebar-link">
                    <i class="fas fa-dumbbell fa-fw"></i>
                    <span class="align-middle">Gerir Treinadores</span>
                </a>
            </li>
            <li class="sidebar-item <?= ($active_page === 'manage_nutritionists_admin.php') ? 'active' : ''; ?>">
                <a href="manage_nutritionists_admin.php" class="sidebar-link">
                    <i class="fas fa-apple-alt fa-fw"></i>
                    <span class="align-middle">Gerir Nutricionistas</span>
                </a>
            </li>
            <li class="sidebar-item <?= ($active_page === 'manage_receptionists_admin.php') ? 'active' : ''; ?>">
                <a href="manage_receptionists_admin.php" class="sidebar-link">
                    <i class="fas fa-concierge-bell fa-fw"></i>
                    <span class="align-middle">Gerir Recepcionistas</span>
                </a>
            </li>
            <li class="sidebar-item <?= ($active_page === 'manage_clients_admin.php') ? 'active' : ''; ?>">
                <a href="manage_clients_admin.php" class="sidebar-link">
                    <i class="fas fa-users fa-fw"></i>
                    <span class="align-middle">Gerir Clientes</span>
                </a>
            </li>
            
            <li class="sidebar-item <?= ($active_page === 'sms_admin.php') ? 'active' : ''; ?>">
                <a href="sms_admin.php" class="sidebar-link">
                    <i class="fas fa-sms fa-fw"></i>
                    <span class="align-middle">Notificações SMS</span>
                </a>
            </li>
            
            <li class="sidebar-item <?= ($active_page === 'delegate_access_admin.php' || $active_page === 'my_delegations_admin.php') ? 'active' : ''; ?>">
                <a class="sidebar-link has-arrow" href="#" data-bs-toggle="collapse" data-bs-target="#delegationSubmenu" aria-expanded="<?= ($active_page === 'delegate_access_admin.php' || $active_page === 'my_delegations_admin.php') ? 'true' : 'false'; ?>" aria-controls="delegationSubmenu">
                    <i class="fas fa-user-cog fa-fw"></i> <span class="align-middle">Delegação de Acesso</span>
                </a>
                <ul id="delegationSubmenu" class="collapse first-level <?= ($active_page === 'delegate_access_admin.php' || $active_page === 'my_delegations_admin.php') ? 'show' : ''; ?>">
                    <li class="sidebar-item <?= ($active_page === 'delegate_access_admin.php') ? 'active' : ''; ?>">
                        <a href="delegate_access_admin.php" class="sidebar-link">
                            <i class="fas fa-user-plus fa-fw"></i> <span class="align-middle">Conceder Delegação</span>
                        </a>
                    </li>
                    <li class="sidebar-item <?= ($active_page === 'my_delegations_admin.php') ? 'active' : ''; ?>">
                        <a href="my_delegations_admin.php" class="sidebar-link">
                            <i class="fas fa-list-ul fa-fw"></i> <span class="align-middle">Minhas Delegações</span>
                        </a>
                    </li>
                </ul>
            </li>
            <li class="sidebar-item <?= ($active_page === 'settings_admin.php') ? 'active' : ''; // Exemplo, se tiver uma página de configurações ?>">
                <a href="settings_admin.php" class="sidebar-link">
                    <i class="fas fa-cog fa-fw"></i> <span class="align-middle">Configurações</span>
                </a>
            </li>
        </ul>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php" class="sidebar-link">
            <i class="fas fa-sign-out-alt fa-fw"></i>
            <span class="align-middle">Sair</span>
        </a>
    </div>
</aside>