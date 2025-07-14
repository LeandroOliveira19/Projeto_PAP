<?php
session_start();
// Verifica se o usuário está logado e se é um nutricionista
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'nutritionist') {
    header('Location: login.php');
    exit;
}
include 'db.php'; // Conexão com o banco de dados
include 'header.php'; // Cabeçalho HTML, CSS, e ícone de perfil

// Define a página ativa para a sidebar
$active_page = basename($_SERVER['PHP_SELF']);
// Pega o role e ID do usuário logado
$user_role = $_SESSION['role'];
$nutritionist_id = $_SESSION['user_id']; // ID do nutricionista logado

// --- Fetch Statistics ---
$stats = [
    'active_clients' => 0,
    'total_recipes' => 0,
    'clients_without_plan' => 0,
];

try {
    // 1. Contar clientes com plano alimentar ATIVO atribuído a ESTE nutricionista
    $stmt_active_clients = $pdo->prepare("
        SELECT COUNT(DISTINCT client_id) 
        FROM diet_plans 
        WHERE nutritionist_id = ? AND is_active = 1
    ");
    $stmt_active_clients->execute([$nutritionist_id]);
    $stats['active_clients'] = $stmt_active_clients->fetchColumn();

    // 2. Contar o total de receitas no sistema
    $stats['total_recipes'] = $pdo->query("SELECT COUNT(*) FROM recipes")->fetchColumn();

} catch (PDOException $e) {
    error_log("Erro ao buscar estatísticas para o nutricionista: " . $e->getMessage());
}
?>

<?php include 'sidebar_nutritionist.php';  ?>

<!-- Wrapper para o Conteúdo Principal -->
<main class="main-content">
    <div class="container mt-5">
        <h2>Bem-vindo, Nutricionista!</h2>
        <p>Painel principal para gestão de planos alimentares dos seus clientes.</p>

        <!-- Mensagem de sucesso (opcional) -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php
                    if ($_GET['success'] == 'plan_added') echo "Plano alimentar adicionado com sucesso!";
                    if ($_GET['success'] == 'plan_updated') echo "Plano alimentar atualizado com sucesso!";
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php
            // ***** MODIFICAÇÃO: Buscar clientes SEM NENHUM plano ativo DESTE nutricionista *****
            $stmt_clients_no_plan = $pdo->prepare("
                SELECT
                    c.id AS client_id,
                    u.id AS client_user_id,
                    u.name AS client_name,
                    u.email AS client_email
                FROM clients c
                JOIN users u ON c.user_id = u.id
                WHERE u.role = 'client' AND NOT EXISTS (
                    SELECT 1
                    FROM diet_plans dp
                    WHERE dp.client_id = c.id
                      AND dp.nutritionist_id = :nutritionist_id
                      AND dp.is_active = 1
                )
                ORDER BY u.name
            ");
            $stmt_clients_no_plan->execute(['nutritionist_id' => $nutritionist_id]);
            $clients_without_active_plan = $stmt_clients_no_plan->fetchAll();
            $stats['clients_without_plan'] = count($clients_without_active_plan);
        ?>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-4">
                <div class="card text-white bg-primary shadow-sm h-100">
                    <div class="card-body text-center d-flex flex-column justify-content-center">
                        <i class="fas fa-users fa-3x mb-2"></i>
                        <h5 class="card-title"><?= htmlspecialchars($stats['active_clients']) ?></h5>
                        <p class="card-text">Clientes Ativos</p>
                        <a href="client_list_nutritionist.php" class="btn btn-outline-light btn-sm mt-2 stretched-link">Gerir Clientes</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card text-white bg-info shadow-sm h-100">
                    <div class="card-body text-center d-flex flex-column justify-content-center">
                        <i class="fas fa-book-open fa-3x mb-2"></i>
                        <h5 class="card-title"><?= htmlspecialchars($stats['total_recipes']) ?></h5>
                        <p class="card-text">Receitas Disponíveis</p>
                        <a href="manage_recipes.php" class="btn btn-outline-light btn-sm mt-2 stretched-link">Gerir Receitas</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card text-white bg-warning shadow-sm h-100">
                    <div class="card-body text-center d-flex flex-column justify-content-center">
                        <i class="fas fa-user-plus fa-3x mb-2"></i>
                        <h5 class="card-title"><?= htmlspecialchars($stats['clients_without_plan']) ?></h5>
                        <p class="card-text">Clientes Sem Plano</p>
                        <a href="#clients-without-plan-table" class="btn btn-outline-light btn-sm mt-2 stretched-link">Atribuir Plano</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <h4>Ações Rápidas</h4>
                <a href="add_diet_plan.php" class="btn btn-success mb-3"><i class="fas fa-plus me-1"></i>Adicionar Novo Plano</a>
                <a href="client_list_nutritionist.php" class="btn btn-secondary mb-3"><i class="fas fa-users me-1"></i>Ver Todos os Clientes</a>

                <!-- Tabela Responsiva -->
                <h5 class="mt-4" id="clients-without-plan-table">Clientes Sem Plano Alimentar Ativo (Comigo)</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Cliente</th>
                                <th>Email</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                if (count($clients_without_active_plan) > 0) {
                                    foreach ($clients_without_active_plan as $client) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($client['client_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($client['client_email']) . "</td>";
                                        echo "<td>";
                                        // Botão para adicionar plano alimentar
                                        echo "<a href='add_diet_plan.php?client_id=" . $client['client_id'] . "' class='btn btn-primary btn-sm me-1' title='Adicionar Plano Alimentar'><i class='fas fa-plus'></i> <span class='d-none d-md-inline'>Plano</span></a>";
                                        // Botão para Ver Perfil
                                        echo "<a href='profile_view.php?user_id=" . $client['client_user_id'] . "' class='btn btn-secondary btn-sm me-1' title='Ver Perfil'><i class='fas fa-user'></i> <span class='d-none d-md-inline'>Perfil</span></a>";
                                        // Botão para Chat
                                        echo "<a href='chat.php?user_id=" . $client['client_user_id'] . "' class='btn btn-success btn-sm' title='Conversar com Cliente'><i class='fas fa-comments'></i> <span class='d-none d-md-inline'>Chat</span></a>";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                     echo "<tr><td colspan='3' class='text-center text-muted'>Todos os clientes já têm um plano alimentar ativo consigo ou não existem clientes registados.</td></tr>";
                                }
                            ?>
                        </tbody>
                    </table>
                </div> <!-- Fim table-responsive -->
            </div>
        </div>
    </div>
</main> <!-- FIM: Wrapper <main> -->

<?php include 'footer.php'; // Inclui o rodapé HTML e scripts ?>
