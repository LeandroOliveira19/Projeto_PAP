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
            <!-- Item de Plano de Treino com Sub-menu ATUALIZADO -->
            <?php
                $workout_submenu_pages = ['workout_hub.php', 'workout_plan_history.php', 'workout_plan_details.php'];
                $is_workout_submenu_active = in_array($active_page, $workout_submenu_pages);
            ?>
            <li class="sidebar-item">
                <a href="#" class="sidebar-link has-arrow <?= $is_workout_submenu_active ? 'active' : ''; ?>" data-bs-toggle="collapse" data-bs-target="#workoutSubmenu" aria-expanded="<?= $is_workout_submenu_active ? 'true' : 'false'; ?>" aria-controls="workoutSubmenu">
                    <i class="fas fa-dumbbell fa-fw"></i>
                    <span>Plano de Treino</span>
                </a>
                <ul id="workoutSubmenu" class="sidebar-dropdown list-unstyled collapse <?= $is_workout_submenu_active ? 'show' : ''; ?>" data-bs-parent=".sidebar-nav">
                    <li class="sidebar-item">
                        <a href="workout_hub.php" class="sidebar-link <?= ($active_page == 'workout_hub.php') ? 'active' : ''; ?>">
                            <i class="fas fa-eye fa-fw ms-3"></i> <span>Ver Plano Ativo</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a href="workout_plan_details.php" class="sidebar-link <?= ($active_page == 'workout_plan_details.php') ? 'active' : ''; ?>">
                            <i class="fas fa-list-alt fa-fw ms-3"></i> <span>Detalhes dos Exercícios</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a href="workout_plan_history.php" class="sidebar-link <?= ($active_page == 'workout_plan_history.php') ? 'active' : ''; ?>">
                            <i class="fas fa-history fa-fw ms-3"></i> <span>Histórico de Planos</span>
                        </a>
                    </li>
                </ul>
            </li>
            <!-- Fim Item de Plano de Treino com Sub-menu -->

            <!-- Item de Nutrição com Sub-menu ATUALIZADO -->
            <?php
                // Define quais páginas pertencem ao submenu de Nutrição
                $nutrition_submenu_pages = ['nutrition_hub.php', 'recommendations_client.php', 'recipes_client.php'];
                $is_nutrition_submenu_active = in_array($active_page, $nutrition_submenu_pages);
            ?>
            <li class="sidebar-item">
                <a href="#" class="sidebar-link has-arrow <?= $is_nutrition_submenu_active ? 'active' : ''; ?>" data-bs-toggle="collapse" data-bs-target="#nutritionSubmenu" aria-expanded="<?= $is_nutrition_submenu_active ? 'true' : 'false'; ?>" aria-controls="nutritionSubmenu">
                    <i class="fas fa-utensils fa-fw"></i>
                    <span>Nutrição</span>
                </a>
                <ul id="nutritionSubmenu" class="sidebar-dropdown list-unstyled collapse <?= $is_nutrition_submenu_active ? 'show' : ''; ?>" data-bs-parent=".sidebar-nav">
                    <li class="sidebar-item">
                        <a href="nutrition_hub.php" class="sidebar-link <?= ($active_page == 'nutrition_hub.php') ? 'active' : ''; ?>"><i class="fas fa-concierge-bell fa-fw ms-3"></i> <span>Plano Alimentar</span></a>
                    </li>
                    <li class="sidebar-item">
                        <a href="recommendations_client.php" class="sidebar-link <?= ($active_page == 'recommendations_client.php') ? 'active' : ''; ?>"><i class="fas fa-lightbulb fa-fw ms-3"></i> <span>Recomendações</span></a>
                    </li>
                    <li class="sidebar-item">
                        <a href="recipes_client.php" class="sidebar-link <?= ($active_page == 'recipes_client.php') ? 'active' : ''; ?>"><i class="fas fa-utensil-spoon fa-fw ms-3"></i> <span>Receitas</span></a>
                    </li>
                </ul>
            </li>
            <!-- Fim Item de Nutrição com Sub-menu -->
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
