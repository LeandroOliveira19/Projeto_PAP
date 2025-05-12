<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'nutritionist') {
    header('Location: login.php');
    exit;
}
// *** CORREÇÃO: Caminho para includes ***
include 'db.php';
include 'header.php';

// *** ADICIONADO: Define página ativa ***
$active_page = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['role']; // Necessário para a sidebar condicional

// Verifica se IDs foram passados
if (!isset($_GET['client_id']) || !isset($_GET['diet_plan_id'])) {
     echo "<script>alert('IDs inválidos.'); window.location.href='nutritionist.php';</script>";
     exit;
}
$client_id = filter_var($_GET['client_id'], FILTER_VALIDATE_INT);
$diet_plan_id = filter_var($_GET['diet_plan_id'], FILTER_VALIDATE_INT);
$nutritionist_id = $_SESSION['user_id'];

if (!$client_id || !$diet_plan_id) {
     echo "<script>alert('IDs inválidos.'); window.location.href='nutritionist.php';</script>";
     exit;
}


// Processa o formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan_details = $_POST['plan_details'];

    try {
        // 1. Desativar outros planos ativos (se houver) deste cliente por este nutri
        $stmt_deactivate = $pdo->prepare("UPDATE diet_plans SET is_active = 0 WHERE client_id = ? AND nutritionist_id = ? AND id != ?");
        $stmt_deactivate->execute([$client_id, $nutritionist_id, $diet_plan_id]);

        // 2. Atualizar o plano atual e garantir que está ativo
        $stmt_update = $pdo->prepare("UPDATE diet_plans SET plan_details = ?, is_active = 1 WHERE id = ? AND client_id = ? AND nutritionist_id = ?");
        $stmt_update->execute([$plan_details, $diet_plan_id, $client_id, $nutritionist_id]);

        header('Location: nutritionist.php?success=plan_updated');
        exit;
    } catch (PDOException $e) {
        error_log("Erro ao editar plano alimentar: " . $e->getMessage());
        header('Location: edit_diet_plan.php?client_id='.$client_id.'&diet_plan_id='.$diet_plan_id.'&error=db_error');
        exit;
    }
}

// Busca os detalhes do plano existente
try {
    $stmt_fetch = $pdo->prepare("SELECT dp.plan_details, u.name as client_name FROM diet_plans dp JOIN clients c ON dp.client_id = c.id JOIN users u ON c.user_id = u.id WHERE dp.id = ? AND dp.client_id = ? AND dp.nutritionist_id = ?");
    $stmt_fetch->execute([$diet_plan_id, $client_id, $nutritionist_id]);
    $plan = $stmt_fetch->fetch();

    if (!$plan) {
        // Plano não encontrado ou não pertence a este nutricionista/cliente
        echo "<script>alert('Plano não encontrado ou acesso negado.'); window.location.href='nutritionist.php';</script>";
        exit;
    }
} catch (PDOException $e) {
     error_log("Erro ao buscar plano para edição: " . $e->getMessage());
     echo "<script>alert('Erro ao carregar dados do plano.'); window.location.href='nutritionist.php';</script>";
     exit;
}

?>

<!-- *** INÍCIO: Sidebar Condicional (Adaptada de profile.php) *** -->
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="<?= ($user_role == 'client') ? 'dashboard.php' : (($user_role == 'trainer') ? 'trainer.php' : (($user_role == 'nutritionist') ? 'nutritionist.php' : (($user_role == 'admin') ? 'admin.php' : '#'))) ?>" class="logo">
            MyWellness<?= ($user_role == 'trainer') ? ' - Trainer' : (($user_role == 'nutritionist') ? ' - Nutri' : (($user_role == 'admin') ? ' - Admin' : '')) ?>
        </a>
    </div>
    <nav>
        <ul class="sidebar-nav">
            <?php if ($user_role == 'client'): ?>
                <!-- Links do Cliente -->
                <li><a href="dashboard.php" class="<?= ($active_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt fa-fw"></i><span>Dashboard</span></a></li>
                <li><a href="workout_hub.php" class="<?= ($active_page == 'workout_hub.php') ? 'active' : ''; ?>"><i class="fas fa-dumbbell fa-fw"></i><span>Plano de Treino</span></a></li>
                <li><a href="nutrition_hub.php" class="<?= ($active_page == 'nutrition_hub.php') ? 'active' : ''; ?>"><i class="fas fa-utensils fa-fw"></i><span>Nutrição</span></a></li>
                <li><a href="classes.php" class="<?= ($active_page == 'classes.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-check fa-fw"></i><span>Aulas</span></a></li>
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link has-arrow <?= (in_array($active_page, ['profile.php', 'client_statistics.php'])) ? 'active' : ''; ?>" data-bs-toggle="collapse" data-bs-target="#profileSubmenu" aria-expanded="<?= (in_array($active_page, ['profile.php', 'client_statistics.php'])) ? 'true' : 'false'; ?>" aria-controls="profileSubmenu">
                        <i class="fas fa-user fa-fw"></i> <span>Perfil</span>
                    </a>
                    <ul id="profileSubmenu" class="sidebar-dropdown list-unstyled collapse <?= (in_array($active_page, ['profile.php', 'client_statistics.php'])) ? 'show' : ''; ?>" data-bs-parent=".sidebar-nav">
                        <li class="sidebar-item"><a href="profile.php" class="sidebar-link <?= ($active_page == 'profile.php') ? 'active' : ''; ?>"><i class="fas fa-id-card fa-fw ms-3"></i> <span>Ver/Editar Perfil</span></a></li>
                        <li class="sidebar-item"><a href="client_statistics.php" class="sidebar-link <?= ($active_page == 'client_statistics.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line fa-fw ms-3"></i> <span>Estatísticas</span></a></li>
                    </ul>
                </li>
                <li><a href="chat.php" class="<?= ($active_page == 'chat.php') ? 'active' : ''; ?>"><i class="fas fa-comments fa-fw"></i><span>Chat</span></a></li>

            <?php elseif ($user_role == 'trainer'): ?>
                <!-- Links do Treinador -->
                <li><a href="trainer.php" class="<?= ($active_page == 'trainer.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt fa-fw"></i><span>Painel Principal</span></a></li>
                <li><a href="add_workout_plan.php" class="<?= ($active_page == 'add_workout_plan.php') ? 'active' : ''; ?>"><i class="fas fa-plus-square fa-fw"></i><span>Adicionar Plano</span></a></li>
                <li><a href="client_history.php" class="<?= ($active_page == 'client_history.php') ? 'active' : ''; ?>"><i class="fas fa-history fa-fw"></i><span>Histórico Clientes</span></a></li>
                <li><a href="profile.php" class="<?= ($active_page == 'profile.php') ? 'active' : ''; ?>"><i class="fas fa-user-edit fa-fw"></i><span>Meu Perfil</span></a></li>
                <li><a href="chat.php" class="<?= ($active_page == 'chat.php') ? 'active' : ''; ?>"><i class="fas fa-comments fa-fw"></i><span>Chat</span></a></li>

            <?php elseif ($user_role == 'nutritionist'): ?>
                <!-- Links do Nutricionista -->
                 <li><a href="nutritionist.php" class="<?= ($active_page == 'nutritionist.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt fa-fw"></i><span>Painel Principal</span></a></li>
                <li><a href="add_diet_plan.php" class="<?= ($active_page == 'add_diet_plan.php') ? 'active' : ''; ?>"><i class="fas fa-plus-square fa-fw"></i><span>Adicionar Plano</span></a></li>
                 <li><a href="client_list_nutritionist.php" class="<?= ($active_page == 'client_list_nutritionist.php') ? 'active' : ''; ?>"><i class="fas fa-users fa-fw"></i><span>Clientes</span></a></li>
                <li><a href="profile.php" class="<?= ($active_page == 'profile.php') ? 'active' : ''; ?>"><i class="fas fa-user-edit fa-fw"></i><span>Meu Perfil</span></a></li>
                <li><a href="chat.php" class="<?= ($active_page == 'chat.php') ? 'active' : ''; ?>"><i class="fas fa-comments fa-fw"></i><span>Chat</span></a></li>

             <?php elseif ($user_role == 'admin'): ?>
                 <!-- Links do Admin -->
                 <li><a href="admin.php" class="<?= ($active_page == 'admin.php') ? 'active' : ''; ?>"><i class="fas fa-shield-alt fa-fw"></i><span>Administração</span></a></li>
                 <li><a href="profile.php" class="<?= ($active_page == 'profile.php') ? 'active' : ''; ?>"><i class="fas fa-user-cog fa-fw"></i><span>Meu Perfil</span></a></li>

            <?php endif; ?>
        </ul>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php"><i class="fas fa-sign-out-alt fa-fw"></i><span>Sair</span></a>
    </div>
</aside>
<!-- *** FIM: Sidebar Condicional *** -->

<!-- *** ADICIONADO: Wrapper <main> *** -->
<main class="main-content">
    <div class="container mt-5">
        <h2>Editar Plano Alimentar para <?= htmlspecialchars($plan['client_name']) ?></h2>
         <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger">Erro ao atualizar o plano. Tente novamente.</div>
        <?php endif; ?>
        <form method="POST" action="edit_diet_plan.php?client_id=<?= $client_id ?>&diet_plan_id=<?= $diet_plan_id ?>"> <!-- Action aponta para si mesmo com IDs -->
            <div class="form-group mb-3">
                <label for="plan_details">Detalhes do Plano</label>
                <textarea class="form-control" id="plan_details" name="plan_details" rows="10" required><?= htmlspecialchars($plan['plan_details'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            <a href="nutritionist.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</main> <!-- *** FIM: Wrapper <main> *** -->

<?php
// *** CORREÇÃO: Caminho para footer ***
include 'footer.php';
?>
