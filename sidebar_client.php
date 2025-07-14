<?php
// File: sidebar_client.php
// Sidebar específica para utilizadores com role 'client'.
// IMPORTANTE: A variável $active_page deve ser definida na página PAI ANTES de incluir este ficheiro.
if (!isset($active_page)) {
    $active_page = '';
} // Garante que $active_page existe
?>
<style>
    .sidebar {
        /* Pode ajustar a largura se desejar uma barra lateral mais estreita */
        /* width: 80px; */
    }
    .sidebar-nav > li > a { /* Seleciona apenas os links de nível superior */
        padding: 0; /* REMOVIDO padding, o flex fará o alinhamento */
        display: flex;
        justify-content: center; /* Centraliza o ícone */
        align-items: center;
        height: 55px; /* Altura fixa para cada item principal */
        width: 100%; /* Garante que o link ocupa toda a largura do <li> */
        /* text-align: center; Removido, pois display:flex cuida disso */
    }
    .sidebar-nav > li > a i.fa-fw {
        font-size: 1.8em; /* Ícones maiores */
        margin: 0;
        padding: 0;
        display: inline-flex; /* Para usar align-items e justify-content no próprio ícone */
        align-items: center;
        justify-content: center;
        width: 1.8em;  /* Tenta criar um "quadrado" para o ícone, baseado no font-size */
        height: 1.8em; /* Tenta criar um "quadrado" para o ícone, baseado no font-size */
        /* border: 1px dashed red; /* Para debug visual */
    }

    /* Estilos para os itens de submenu (AGORA TAMBÉM MINIMALISTAS) */
    .sidebar-dropdown .sidebar-link {
        padding: 0; /* REMOVIDO padding */
        justify-content: center; /* Centraliza o ícone no submenu */
        height: 50px; /* Altura similar aos principais, pode ajustar */
        /* text-align: center; Removido */
        width: 100%;
        display: flex; /* Necessário para justify-content e align-items */
        align-items: center; /* Necessário para centralização vertical */
    }
    .sidebar-dropdown .sidebar-link i.fa-fw {
        font-size: 1.6em; /* Ícones de submenu maiores */
        margin: 0;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.6em;
        height: 1.6em;
        /* border: 1px dashed blue; /* Para debug visual */
    }
    .sidebar-dropdown .sidebar-link span {
        /* Esconde o texto do submenu */
        display: none !important;
        opacity: 0;
        width: 0;
        overflow: hidden;
        pointer-events: none;
    }

    .sidebar-footer a {
        justify-content: center; /* Centraliza o ícone do link Sair */
        padding: 0; /* REMOVIDO padding */
        display: flex; /* Necessário para justify-content e align-items */
        align-items: center; /* Necessário para centralização vertical */
        height: 55px; /* Altura similar aos itens principais */
        width: 100%;
    }
     .sidebar-footer a i.fa-fw {
        font-size: 1.8em; /* Ícone Sair maior */
        margin: 0;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.8em;
        height: 1.8em;
        /* border: 1px dashed green; /* Para debug visual */
    }
</style>

<aside class="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="logo">MyWellness</a>
    </div>
    <nav>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php" class="<?= ($active_page == 'dashboard.php') ? 'active' : ''; ?>" title="Dashboard"><i class="fas fa-tachometer-alt fa-fw"></i></a></li>
            <!-- Item de Plano de Treino com Sub-menu ATUALIZADO -->
            <?php
            $workout_submenu_pages = ['workout_hub.php', 'workout_plan_history.php', 'workout_plan_details.php'];
            $is_workout_submenu_active = in_array($active_page, $workout_submenu_pages);
            ?>
            <li class="sidebar-item">
                <a href="#" class="sidebar-link has-arrow <?= $is_workout_submenu_active ? 'active' : ''; ?>" data-bs-toggle="collapse" data-bs-target="#workoutSubmenu" aria-expanded="<?= $is_workout_submenu_active ? 'true' : 'false'; ?>" aria-controls="workoutSubmenu">
                    <i class="fas fa-dumbbell fa-fw" title="Plano de Treino"></i>
                    <!-- Texto removido -->
                </a>
                <ul id="workoutSubmenu" class="sidebar-dropdown list-unstyled collapse <?= $is_workout_submenu_active ? 'show' : ''; ?>" data-bs-parent=".sidebar-nav">
                    <li class="sidebar-item">
                        <a href="workout_hub.php" class="sidebar-link <?= ($active_page == 'workout_hub.php') ? 'active' : ''; ?>" title="Ver Plano Ativo">
                            <i class="fas fa-eye fa-fw"></i> <!-- ms-3 removido -->
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a href="workout_plan_details.php" class="sidebar-link <?= ($active_page == 'workout_plan_details.php') ? 'active' : ''; ?>" title="Detalhes dos Exercícios">
                            <i class="fas fa-list-alt fa-fw"></i> <!-- ms-3 removido -->
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a href="workout_plan_history.php" class="sidebar-link <?= ($active_page == 'workout_plan_history.php') ? 'active' : ''; ?>" title="Histórico de Planos">
                            <i class="fas fa-history fa-fw"></i> <!-- ms-3 removido -->
                        </a>
                    </li>
                </ul>
            </li>
            <!-- Fim Item de Plano de Treino com Sub-menu -->

            <!-- Item de Nutrição com Sub-menu ATUALIZADO -->
            <?php
            // Define quais páginas pertencem ao submenu de Nutrição
            $nutrition_submenu_pages = ['nutrition_hub.php', 'recommendations_client.php', 'recipes_client.php', 'diet_plan_history.php'];
            $is_nutrition_submenu_active = in_array($active_page, $nutrition_submenu_pages);
            ?>
            <li class="sidebar-item">
                <a href="#" class="sidebar-link has-arrow <?= $is_nutrition_submenu_active ? 'active' : ''; ?>" data-bs-toggle="collapse" data-bs-target="#nutritionSubmenu" aria-expanded="<?= $is_nutrition_submenu_active ? 'true' : 'false'; ?>" aria-controls="nutritionSubmenu">
                    <i class="fas fa-utensils fa-fw" title="Nutrição"></i>
                    <!-- Texto removido -->
                </a>
                <ul id="nutritionSubmenu" class="sidebar-dropdown list-unstyled collapse <?= $is_nutrition_submenu_active ? 'show' : ''; ?>" data-bs-parent=".sidebar-nav">
                    <li class="sidebar-item">
                        <a href="nutrition_hub.php" class="sidebar-link <?= ($active_page == 'nutrition_hub.php') ? 'active' : ''; ?>" title="Plano Alimentar"><i class="fas fa-concierge-bell fa-fw"></i></a>
                    </li>
                    <li class="sidebar-item">
                        <a href="recommendations_client.php" class="sidebar-link <?= ($active_page == 'recommendations_client.php') ? 'active' : ''; ?>" title="Recomendações"><i class="fas fa-lightbulb fa-fw"></i></a>
                    </li>
                    <li class="sidebar-item">
                        <a href="recipes_client.php" class="sidebar-link <?= ($active_page == 'recipes_client.php') ? 'active' : ''; ?>" title="Receitas"><i class="fas fa-utensil-spoon fa-fw"></i></a>
                    </li>
                    <li class="sidebar-item">
                        <a href="diet_plan_history.php" class="sidebar-link <?= ($active_page == 'diet_plan_history.php') ? 'active' : ''; ?>" title="Histórico de Planos"><i class="fas fa-history fa-fw"></i></a>
                    </li>
                </ul>
            </li>
            <!-- Fim Item de Nutrição com Sub-menu -->
            <!-- Item de Conquistas com Sub-menu -->
            <?php
            $achievements_submenu_pages = ['all_achievements.php', 'claim_achievements.php'];
            $is_achievements_submenu_active = in_array($active_page, $achievements_submenu_pages);
            ?>
            <li class="sidebar-item">
                <a href="#" class="sidebar-link has-arrow <?= $is_achievements_submenu_active ? 'active' : ''; ?>" data-bs-toggle="collapse" data-bs-target="#achievementsSubmenu" aria-expanded="<?= $is_achievements_submenu_active ? 'true' : 'false'; ?>" aria-controls="achievementsSubmenu">
                    <i class="fas fa-trophy fa-fw" title="Conquistas"></i>
                    <!-- Texto removido -->
                </a>
                <ul id="achievementsSubmenu" class="sidebar-dropdown list-unstyled collapse <?= $is_achievements_submenu_active ? 'show' : ''; ?>" data-bs-parent=".sidebar-nav">
                    <li class="sidebar-item">
                        <a href="all_achievements.php" class="sidebar-link <?= ($active_page == 'all_achievements.php') ? 'active' : ''; ?>" title="Minhas Conquistas"><i class="fas fa-medal fa-fw"></i></a>
                    </li>
                    <li class="sidebar-item">
                        <a href="claim_achievements.php" class="sidebar-link <?= ($active_page == 'claim_achievements.php') ? 'active' : ''; ?>" title="Reivindicar"><i class="fas fa-hand-sparkles fa-fw"></i></a>
                    </li>
                </ul>
            </li>
            <!-- Item de Aulas -->
            <li>
                <a href="classes.php" class="<?= ($active_page == 'classes.php') ? 'active' : ''; ?>" title="Aulas">
                    <i class="fas fa-calendar-alt fa-fw"></i>
                    <!-- Texto removido -->
                </a>
            </li>

            <!-- Item de Perfil com Sub-menu ATUALIZADO -->
            <?php
            // Lista de páginas que pertencem ao submenu Perfil
            $profile_submenu_pages = ['profile.php', 'client_statistics.php', 'ficha_pessoal.php', 'evolution.php']; // <<< ADICIONADO evolution.php
            $is_profile_submenu_active = in_array($active_page, $profile_submenu_pages);
            ?>
            <li class="sidebar-item">
                <a href="#" class="sidebar-link has-arrow <?= $is_profile_submenu_active ? 'active' : ''; ?>" data-bs-toggle="collapse" data-bs-target="#profileSubmenu" aria-expanded="<?= $is_profile_submenu_active ? 'true' : 'false'; ?>" aria-controls="profileSubmenu">
                    <i class="fas fa-user fa-fw" title="Perfil"></i>
                    <!-- Texto removido -->
                </a>
                <ul id="profileSubmenu" class="sidebar-dropdown list-unstyled collapse <?= $is_profile_submenu_active ? 'show' : ''; ?>" data-bs-parent=".sidebar-nav">
                    <li class="sidebar-item">
                        <a href="profile.php" class="sidebar-link <?= ($active_page == 'profile.php') ? 'active' : ''; ?>" title="Ver/Editar Perfil">
                            <i class="fas fa-id-card fa-fw"></i>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a href="client_statistics.php" class="sidebar-link <?= ($active_page == 'client_statistics.php') ? 'active' : ''; ?>" title="Estatísticas">
                            <i class="fas fa-chart-line fa-fw"></i>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a href="ficha_pessoal.php" class="sidebar-link <?= ($active_page == 'ficha_pessoal.php') ? 'active' : ''; ?>" title="Ficha Pessoal">
                            <i class="fas fa-address-card fa-fw"></i>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a href="evolution.php" class="sidebar-link <?= ($active_page == 'evolution.php') ? 'active' : ''; ?>" title="Evolução">
                            <i class="fas fa-arrow-trend-up fa-fw"></i>
                        </a>
                    </li>
                </ul>
            </li>
            <!-- Fim Item de Perfil com Sub-menu -->

            <li><a href="chat.php" class="<?= ($active_page == 'chat.php') ? 'active' : ''; ?>" title="Chat"><i class="fas fa-comments fa-fw"></i></a></li>
        </ul>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php" title="Sair">
            <i class="fas fa-sign-out-alt fa-fw"></i>
            <!-- Texto removido -->
        </a>
    </div>
</aside>
