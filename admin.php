<?php
session_start();
// Inclui db.php primeiro para que $pdo esteja disponível
include 'db.php';
include 'includes/auth_utils.php'; // Inclui a função de verificação

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Verifica se o usuário tem a permissão específica 'view_admin_dashboard'
if (!user_has_specific_permission($pdo, $_SESSION['user_id'], 'view_admin_dashboard')) {
    $_SESSION['error_message'] = "Acesso negado. Permissão para 'Aceder Painel Admin' necessária.";
    // Redirecionar para uma página de "acesso negado" ou para o dashboard do usuário
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'receptionist') {
        header('Location: receptionist.php');
    } else {
        header('Location: login.php'); // Fallback
    }
    exit;
}

include 'header.php'; // Inclui o header (que tem o início do body)
include 'send_email.php';

// Decide qual sidebar incluir
$sidebar_to_include = ($_SESSION['role'] === 'admin') ? 'sidebar_admin.php' : 'sidebar_receptionist.php';
include $sidebar_to_include;

$active_page = basename($_SERVER['PHP_SELF']); // admin.php

// --- Fetch Statistics ---
$stats = [
    'total_clients' => 0,
    'total_trainers' => 0,
    'total_nutritionists' => 0,
    'total_admins' => 0,
    'total_receptionists' => 0, // Alterado de 'total_balcao'
    'total_classes_upcoming' => 0,
    'active_workout_plans' => 0,
    'active_diet_plans' => 0,
    'new_users_last_7_days' => 0
];

try {
    $stats['total_clients'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();
    $stats['total_trainers'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'trainer'")->fetchColumn();
    $stats['total_nutritionists'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'nutritionist'")->fetchColumn();
    $stats['total_admins'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    $stats['total_receptionists'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'receptionist'")->fetchColumn(); // Alterado de 'balcao'
    $stats['total_classes_upcoming'] = $pdo->query("SELECT COUNT(*) FROM classes WHERE schedule >= NOW()")->fetchColumn();
    $stats['active_workout_plans'] = $pdo->query("SELECT COUNT(*) FROM workout_plans WHERE is_active = 1")->fetchColumn();
    $stats['active_diet_plans'] = $pdo->query("SELECT COUNT(*) FROM diet_plans WHERE is_active = 1")->fetchColumn();

    $seven_days_ago = date('Y-m-d H:i:s', strtotime('-7 days'));
    $stmt_new_users = $pdo->prepare("SELECT COUNT(*) FROM users WHERE created_at >= ?");
    $stmt_new_users->execute([$seven_days_ago]);
    $stats['new_users_last_7_days'] = $stmt_new_users->fetchColumn();

} catch (PDOException $e) {
    error_log("Erro ao buscar estatísticas: " . $e->getMessage());
    // Pode definir uma mensagem de erro para exibir na página, se desejar
}

?>

<!-- INÍCIO DO CONTEÚDO PRINCIPAL -->
<main class="main-content">
    <div class="container mt-5"> <!-- Mantém o container para espaçamento -->
        <h2>Painel de Administração - Estatísticas</h2>
        <p>Visão geral da atividade na plataforma.</p>

        <div class="row g-4">
            <!-- Card Total Clientes -->
            <div class="col-md-6 col-lg-3">
                <div class="card text-white bg-primary shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-3x mb-2"></i>
                        <h5 class="card-title"><?= htmlspecialchars($stats['total_clients']) ?></h5>
                        <p class="card-text">Clientes Registados</p>
                    </div>
                </div>
            </div>
            <!-- Card Total Treinadores -->
            <div class="col-md-6 col-lg-3">
                <div class="card text-white bg-success shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-dumbbell fa-3x mb-2"></i>
                        <h5 class="card-title"><?= htmlspecialchars($stats['total_trainers']) ?></h5>
                        <p class="card-text">Treinadores</p>
                    </div>
                </div>
            </div>
            <!-- Card Total Nutricionistas -->
            <div class="col-md-6 col-lg-3">
                <div class="card text-white bg-info shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-apple-alt fa-3x mb-2"></i>
                        <h5 class="card-title"><?= htmlspecialchars($stats['total_nutritionists']) ?></h5>
                        <p class="card-text">Nutricionistas</p>
                    </div>
                </div>
            </div>
            <!-- Card Total Admins -->
            <div class="col-md-6 col-lg-3">
                <div class="card text-white bg-secondary shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-user-shield fa-3x mb-2"></i>
                        <h5 class="card-title"><?= htmlspecialchars($stats['total_admins']) ?></h5>
                        <p class="card-text">Administradores</p>
                    </div>
                </div>
            </div>

            <!-- Card Aulas Agendadas -->
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-check fa-3x mb-2 text-warning"></i>
                        <h5 class="card-title"><?= htmlspecialchars($stats['total_classes_upcoming']) ?></h5>
                        <p class="card-text">Aulas Futuras</p>
                    </div>
                </div>
            </div>
            <!-- Card Planos de Treino Ativos -->
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-running fa-3x mb-2 text-danger"></i>
                        <h5 class="card-title"><?= htmlspecialchars($stats['active_workout_plans']) ?></h5>
                        <p class="card-text">Planos de Treino Ativos</p>
                    </div>
                </div>
            </div>
            <!-- Card Planos Alimentares Ativos -->
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-utensils fa-3x mb-2 text-success"></i>
                        <h5 class="card-title"><?= htmlspecialchars($stats['active_diet_plans']) ?></h5>
                        <p class="card-text">Planos Alimentares Ativos</p>
                    </div>
                </div>
            </div>
             <!-- Card Novos Utilizadores -->
            <div class="col-md-6 col-lg-3">
                <div class="card bg-light shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-user-plus fa-3x mb-2 text-info"></i>
                        <h5 class="card-title"><?= htmlspecialchars($stats['new_users_last_7_days']) ?></h5>
                        <p class="card-text">Novos Utilizadores (7 dias)</p>
                    </div>
                </div>
            </div>
        </div>

    </div> 
</main>
<!-- FIM DO CONTEÚDO PRINCIPAL -->
<?php
// Inclui o rodapé da página
include 'footer.php';
?>