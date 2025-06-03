<?php
// c:\xampp\htdocs\Projeto_PAP\sidebar_receptionist.php
// Sidebar para utilizadores com a função 'receptionist'.

// Garante que a sessão está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($active_page)) {
    $active_page = '';
}

// Incluir auth_utils se ainda não foi (pode ser incluído no header ou na página principal)
// É crucial que auth_utils.php seja incluído ANTES desta sidebar se não for aqui.
if (!function_exists('user_has_specific_permission')) {
    if (file_exists(__DIR__ . '/includes/auth_utils.php')) {
        include_once __DIR__ . '/includes/auth_utils.php';
    }
}

// Incluir db.php se ainda não foi (para $pdo)
// É crucial que db.php seja incluído ANTES desta sidebar se não for aqui.
if (!isset($pdo) && file_exists(__DIR__ . '/db.php')) {
    include_once __DIR__ . '/db.php';
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
                    <span>Painel Principal</span>
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

            <?php
            // Debugging output (comentado para não quebrar o layout)
            /*
            echo "<li style='color: yellow; font-size: 0.8em; padding: 5px;'>DEBUG (Sidebar): ";
            if (!isset($pdo)) { echo "PDO not set. "; }
            if (!isset($_SESSION['user_id'])) { echo "User ID not in session. "; }
            else { echo "User ID: " . $_SESSION['user_id'] . ". "; }
            if (!function_exists('user_has_specific_permission')) { echo "Function user_has_specific_permission not found. "; }
            echo "</li>";
            */

            // Links Condicionais Baseados em Permissões Delegadas
            if (isset($pdo) && isset($_SESSION['user_id']) && function_exists('user_has_specific_permission')) {
                // Debugging para cada permissão (descomente para depurar)
                
                // $debug_manage_clients = user_has_specific_permission($pdo, $_SESSION['user_id'], 'manage_clients');
                // echo "<li style='color: yellow; font-size: 0.8em; padding: 5px;'>DEBUG manage_clients: " . ($debug_manage_clients ? 'YES' : 'NO') . "</li>";
                
                if (user_has_specific_permission($pdo, $_SESSION['user_id'], 'manage_clients')) {
                    echo "<li><a href='manage_clients_admin.php' class='" . ($active_page == 'manage_clients_admin.php' ? 'active' : '') . "'><i class='fas fa-users-cog fa-fw text-warning'></i><span class='text-warning'>Gerir Clientes (Delegado)</span></a></li>";
                }

                // $debug_manage_classes = user_has_specific_permission($pdo, $_SESSION['user_id'], 'manage_classes');
                // echo "<li style='color: yellow; font-size: 0.8em; padding: 5px;'>DEBUG manage_classes: " . ($debug_manage_classes ? 'YES' : 'NO') . "</li>";
                
                if (user_has_specific_permission($pdo, $_SESSION['user_id'], 'manage_classes')) {
                    echo "<li><a href='manage_classes_admin.php' class='" . ($active_page == 'manage_classes_admin.php' ? 'active' : '') . "'><i class='fas fa-calendar-plus fa-fw text-warning'></i><span class='text-warning'>Gerir Aulas (Delegado)</span></a></li>";
                }
                
                // $debug_view_admin_dashboard = user_has_specific_permission($pdo, $_SESSION['user_id'], 'view_admin_dashboard');
                // echo "<li style='color: yellow; font-size: 0.8em; padding: 5px;'>DEBUG view_admin_dashboard: " . ($debug_view_admin_dashboard ? 'YES' : 'NO') . "</li>";
                
                if (user_has_specific_permission($pdo, $_SESSION['user_id'], 'view_admin_dashboard')) {
                    echo "<li><a href='admin.php' class='" . ($active_page == 'admin.php' ? 'active' : '') . "'><i class='fas fa-chart-line fa-fw text-warning'></i><span class='text-warning'>Painel Admin (Delegado)</span></a></li>";
                }

                // Adicionando verificações para outras permissões delegáveis
                if (user_has_specific_permission($pdo, $_SESSION['user_id'], 'manage_trainers')) {
                    echo "<li><a href='manage_trainers_admin.php' class='" . ($active_page == 'manage_trainers_admin.php' ? 'active' : '') . "'><i class='fas fa-dumbbell fa-fw text-warning'></i><span class='text-warning'>Gerir Treinadores (Delegado)</span></a></li>";
                }

                if (user_has_specific_permission($pdo, $_SESSION['user_id'], 'manage_nutritionists')) {
                   echo "<li><a href='manage_nutritionists_admin.php' class='" . ($active_page == 'manage_nutritionists_admin.php' ? 'active' : '') . "'><i class='fas fa-apple-alt fa-fw text-warning'></i><span class='text-warning'>Gerir Nutricionistas (Delegado)</span></a></li>";
                }

                if (user_has_specific_permission($pdo, $_SESSION['user_id'], 'manage_receptionists')) {
                   echo "<li><a href='manage_receptionists_admin.php' class='" . ($active_page == 'manage_receptionists_admin.php' ? 'active' : '') . "'><i class='fas fa-concierge-bell fa-fw text-warning'></i><span class='text-warning'>Gerir Recepcionistas (Delegado)</span></a></li>";
                }
                // Adicione mais verificações para outras permissões aqui, se houver

            }
            ?>
            <li><a href="chat.php" class="<?= ($active_page == 'chat.php') ? 'active' : ''; ?>"><i class="fas fa-comments fa-fw"></i><span>Chat</span></a></li>
            <li><a href="profile.php" class="<?= ($active_page == 'profile.php') ? 'active' : ''; ?>"><i class="fas fa-user-cog fa-fw"></i><span>Meu Perfil</span></a></li>
        </ul>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php"><i class="fas fa-sign-out-alt fa-fw"></i><span>Sair</span></a>
    </div>
</aside>
