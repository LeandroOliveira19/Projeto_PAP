<?php
// filepath: c:\xampp\htdocs\Projeto_PAP\sidebar_trainer.php

// Garante que $active_page está definida (mesmo que vazia),
// para evitar erros se não for definida na página que inclui a sidebar.
if (!isset($active_page)) {
    $active_page = '';
}
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="trainer.php" class="logo">MyWellness - Trainer</a>
    </div>
    <nav>
        <ul class="sidebar-nav">
            <li>
                <a href="trainer.php" class="<?= ($active_page == 'trainer.php') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt fa-fw"></i><span>Painel Principal</span>
                </a>
            </li>
            <!-- *** NOVO LINK ADICIONADO *** -->
            <li>
                <a href="my_clients.php" class="<?= ($active_page == 'my_clients.php') ? 'active' : ''; ?>">
                    <i class="fas fa-users fa-fw"></i><span>Meus Clientes</span>
                </a>
            </li>
            <li>
                <a href="my_classes_trainer.php" class="<?= ($active_page == 'my_classes_trainer.php') ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check fa-fw"></i><span>Minhas Aulas</span>
                </a>
            </li>
            <li>
                <a href="add_workout_plan.php" class="<?= ($active_page == 'add_workout_plan.php') ? 'active' : ''; ?>">
                    <i class="fas fa-plus-square fa-fw"></i><span>Adicionar Plano</span>
                </a>
            </li>
            <li>
                <a href="client_history.php" class="<?= ($active_page == 'client_history.php') ? 'active' : ''; ?>">
                    <i class="fas fa-history fa-fw"></i><span>Histórico Clientes</span>
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
        </ul>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php">
            <i class="fas fa-sign-out-alt fa-fw"></i><span>Sair</span>
        </a>
    </div>
</aside>
