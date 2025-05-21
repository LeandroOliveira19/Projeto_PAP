<?php
// c:\xampp\htdocs\Projeto_PAP\sidebar_receptionist.php
// Sidebar para utilizadores com a função 'receptionist'.
if (!isset($active_page)) {
    $active_page = '';
}
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="receptionist.php" class="logo">MyWellness - Receptionist</a>
    </div>
    <nav>
        <ul class="sidebar-nav">
            <li>
                <a href="receptionist.php" class="<?= ($active_page == 'receptionist.php') ? 'active' : ''; ?>">
                    <i class="fas fa-desktop fa-fw"></i>
                    <span>Main Dashboard</span>
                </a>
            </li>
            <li>
                <a href="view_clients_receptionist.php" class="<?= ($active_page == 'view_clients_receptionist.php') ? 'active' : ''; ?>">
                    <i class="fas fa-users fa-fw"></i>
                    <span>Ver Clientes</span>
                </a>
            </li>
            <li>
                <a href="view_schedule_receptionist.php" class="<?= ($active_page == 'view_schedule_receptionist.php') ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt fa-fw"></i>
                    <span>Ver Horário Aulas</span>
                </a>
            </li>
            <li>
                <a href="checkin_receptionist.php" class="<?= ($active_page == 'checkin_receptionist.php') ? 'active' : ''; ?>">
                    <i class="fas fa-user-check fa-fw"></i>
                    <span>Check-in Aulas</span>
                </a>
            </li>
            <li><a href="chat.php" class="<?= ($active_page == 'chat.php') ? 'active' : ''; ?>"><i class="fas fa-comments fa-fw"></i><span>Chat</span></a></li>
            <li><a href="profile.php" class="<?= ($active_page == 'profile.php') ? 'active' : ''; ?>"><i class="fas fa-user-cog fa-fw"></i><span>Meu Perfil</span></a></li>
        </ul>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php"><i class="fas fa-sign-out-alt fa-fw"></i><span>Sair</span></a>
    </div>
</aside>