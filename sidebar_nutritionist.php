<?php
// filepath: c:\xampp\htdocs\Projeto_PAP\sidebar_nutritionist.php

// Garante que $active_page está definida
if (!isset($active_page)) {
    $active_page = '';
}
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="nutritionist.php" class="logo">MyWellness - Nutri</a>
    </div>
    <nav>
        <ul class="sidebar-nav">
            <li>
                <a href="nutritionist.php" class="<?= ($active_page == 'nutritionist.php') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt fa-fw"></i><span>Painel Principal</span>
                </a>
            </li>
            <li>
                <a href="add_diet_plan.php" class="<?= ($active_page == 'add_diet_plan.php') ? 'active' : ''; ?>">
                    <i class="fas fa-plus-square fa-fw"></i><span>Adicionar Plano</span>
                </a>
            </li>
            <li>
                <a href="manage_foods.php" class="<?= ($active_page == 'manage_foods.php') ? 'active' : ''; ?>">
                    <i class="fas fa-carrot fa-fw"></i><span>Gerenciar Alimentos</span>
                </a>
            </li>
            <li>
                <a href="manage_recipes.php" class="<?= ($active_page == 'manage_recipes.php') ? 'active' : ''; ?>">
                    <i class="fas fa-book-open fa-fw"></i><span>Gerenciar Receitas</span>
                </a>
            </li>
            <li>
                <a href="client_list_nutritionist.php" class="<?= ($active_page == 'client_list_nutritionist.php') ? 'active' : ''; ?>">
                    <i class="fas fa-users fa-fw"></i><span>Clientes</span>
                </a>
            </li>
            <li>
                <a href="profile.php" class="<?= ($active_page == 'profile.php') ? 'active' : ''; ?>">
                    <i class="fas fa-user-edit fa-fw"></i><span>Meu Perfil</span>
                </a>
            </li>
            <li>
                <a href="chat.php" class="<?= ($active_page == 'chat.php') ? 'active' : ''; ?>">
                    <i class="fas fa-comments fa-fw"></i><span>Chat</span>
                </a>
            </li>
            <!-- Adicione outros links específicos do nutricionista aqui -->
        </ul>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php">
            <i class="fas fa-sign-out-alt fa-fw"></i><span>Sair</span>
        </a>
    </div>
</aside>