<?php
// filepath: c:\xampp\htdocs\Projeto_PAP\sidebar_admin.php

// Garante que $active_page está definida
if (!isset($active_page)) {
    $active_page = '';
}
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="admin.php" class="logo">MyWellness - Admin</a>
    </div>
    <nav>
        <ul class="sidebar-nav">
            <li>
                <a href="admin.php" class="<?= ($active_page == 'admin.php') ? 'active' : ''; ?>">
                    <i class="fas fa-shield-alt fa-fw"></i><span>Administração</span>
                </a>
            </li>
             <li>
                <a href="profile.php" class="<?= ($active_page == 'profile.php') ? 'active' : ''; ?>">
                    <i class="fas fa-user-cog fa-fw"></i><span>Meu Perfil</span>
                </a>
            </li>
            <!-- Adicione outros links específicos do admin aqui -->
            <!-- Exemplo:
            <li>
                <a href="manage_users.php" class="<?= ($active_page == 'manage_users.php') ? 'active' : ''; ?>">
                    <i class="fas fa-users-cog fa-fw"></i><span>Gerir Utilizadores</span>
                </a>
            </li>
             -->
        </ul>
    </nav>
     <div class="sidebar-footer">
         <a href="logout.php">
            <i class="fas fa-sign-out-alt fa-fw"></i><span>Sair</span>
        </a>
    </div>
</aside>
