<?php
// c:\xampp\htdocs\Projeto_PAP\sidebar_admin.php
// This sidebar is included in admin-related pages.
// The $active_page variable should be defined in the file that includes this sidebar.
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="admin.php" class="logo">MyWellness - Admin</a>
    </div>
    <nav>
        <ul class="sidebar-nav">
            <li>
                <a href="admin.php" class="<?= ($active_page == 'admin.php') ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line fa-fw"></i>
                    <span>Estatísticas</span>
                </a>
            </li>
            <li>
                <a href="manage_classes_admin.php" class="<?= ($active_page == 'manage_classes_admin.php') ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check fa-fw"></i>
                    <span>Gerir Aulas</span>
                </a>
            </li>
            <li>
                <a href="manage_trainers_admin.php" class="<?= ($active_page == 'manage_trainers_admin.php') ? 'active' : ''; ?>">
                    <i class="fas fa-dumbbell fa-fw"></i>
                    <span>Gerir Treinadores</span>
                </a>
            </li>
            <li>
                <a href="manage_nutritionists_admin.php" class="<?= ($active_page == 'manage_nutritionists_admin.php') ? 'active' : ''; ?>">
                    <i class="fas fa-apple-alt fa-fw"></i>
                    <span>Gerir Nutricionistas</span>
                </a>
            </li>
            <li>
                <a href="manage_receptionists_admin.php" class="<?= ($active_page == 'manage_receptionists_admin.php') ? 'active' : ''; ?>">
                    <i class="fas fa-concierge-bell fa-fw"></i>
                    <span>Gerir Recepcionistas</span>
                </a>
            </li>
            <li>
                <a href="manage_clients_admin.php" class="<?= ($active_page == 'manage_clients_admin.php') ? 'active' : ''; ?>">
                    <i class="fas fa-users fa-fw"></i>
                    <span>Gerir Clientes</span>
                </a>
            </li>
        </ul>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php">
            <i class="fas fa-sign-out-alt fa-fw"></i>
            <span>Sair</span>
        </a>
    </div>
</aside>
