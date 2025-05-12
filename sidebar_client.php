<?php
// File: sidebar_client.php
// Sidebar específica para utilizadores com role 'client'.
// IMPORTANTE: A variável $active_page deve ser definida na página PAI ANTES de incluir este ficheiro.
if (!isset($active_page)) { $active_page = ''; } // Garante que $active_page existe
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="logo">MyWellness</a>
    </div>
    <nav>
        <ul class="sidebar-nav">
             <li><a href="dashboard.php" class="<?= ($active_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt fa-fw"></i><span>Dashboard</span></a></li>
            <li><a href="workout_hub.php" class="<?= ($active_page == 'workout_hub.php') ? 'active' : ''; ?>"><i class="fas fa-dumbbell fa-fw"></i><span>Plano de Treino</span></a></li>
            <li><a href="nutrition_hub.php" class="<?= ($active_page == 'nutrition_hub.php') ? 'active' : ''; ?>"><i class="fas fa-utensils fa-fw"></i><span>Nutrição</span></a></li>
            <li><a href="classes.php" class="<?= ($active_page == 'classes.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-check fa-fw"></i><span>Aulas</span></a></li>

            <!-- Item de Perfil com Sub-menu ATUALIZADO -->
            <?php
                // Lista de páginas que pertencem ao submenu Perfil
                $profile_submenu_pages = ['profile.php', 'client_statistics.php', 'ficha_pessoal.php', 'evolution.php']; // <<< ADICIONADO evolution.php
                $is_profile_submenu_active = in_array($active_page, $profile_submenu_pages);
            ?>
            <li class="sidebar-item">
                <a href="#" class="sidebar-link has-arrow <?= $is_profile_submenu_active ? 'active' : ''; ?>" data-bs-toggle="collapse" data-bs-target="#profileSubmenu" aria-expanded="<?= $is_profile_submenu_active ? 'true' : 'false'; ?>" aria-controls="profileSubmenu">
                    <i class="fas fa-user fa-fw"></i>
                    <span>Perfil</span>
                </a>
                <ul id="profileSubmenu" class="sidebar-dropdown list-unstyled collapse <?= $is_profile_submenu_active ? 'show' : ''; ?>" data-bs-parent=".sidebar-nav">
                    <li class="sidebar-item">
                        <a href="profile.php" class="sidebar-link <?= ($active_page == 'profile.php') ? 'active' : ''; ?>">
                            <i class="fas fa-id-card fa-fw ms-3"></i>
                            <span>Ver/Editar Perfil</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a href="client_statistics.php" class="sidebar-link <?= ($active_page == 'client_statistics.php') ? 'active' : ''; ?>">
                            <i class="fas fa-chart-line fa-fw ms-3"></i>
                            <span>Estatísticas</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a href="ficha_pessoal.php" class="sidebar-link <?= ($active_page == 'ficha_pessoal.php') ? 'active' : ''; ?>">
                            <i class="fas fa-address-card fa-fw ms-3"></i>
                            <span>Ficha Pessoal</span>
                        </a>
                    </li>
                    <!-- *** NOVO LINK *** -->
                    <li class="sidebar-item">
                        <a href="evolution.php" class="sidebar-link <?= ($active_page == 'evolution.php') ? 'active' : ''; ?>">
                            <i class="fas fa-arrow-trend-up fa-fw ms-3"></i> <!-- Ícone de exemplo -->
                            <span>Evolução</span>
                        </a>
                    </li>
                    <!-- *************** -->
                </ul>
            </li>
            <!-- Fim Item de Perfil com Sub-menu -->

            <li><a href="chat.php" class="<?= ($active_page == 'chat.php') ? 'active' : ''; ?>"><i class="fas fa-comments fa-fw"></i><span>Chat</span></a></li>
        </ul>
    </nav>
     <div class="sidebar-footer">
         <a href="logout.php">
            <i class="fas fa-sign-out-alt fa-fw"></i>
            <span>Sair</span>
        </a>
    </div>
</aside>
