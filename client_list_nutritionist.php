<?php
session_start();

// Verifica se o usuário está logado e se é um nutricionista
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'nutritionist') {
    header('Location: login.php');
    exit;
}

$nutritionist_id = $_SESSION['user_id']; // ID do nutricionista logado
$clients = [];
$error_message = '';

// Inclui a conexão com o banco de dados e o cabeçalho
include 'db.php';
include 'header.php';

// Define a página ativa para a sidebar
$active_page = 'client_list_nutritionist.php';

// Inclui a sidebar do nutricionista
include 'sidebar_nutritionist.php';

try {
    // Busca clientes que têm um plano ATIVO associado a ESTE nutricionista
    $sql = "SELECT
                c.id as client_id,          -- ID da tabela clients
                u.id as client_user_id,     -- ID da tabela users (para chat)
                u.name as client_name,
                u.email as client_email,
                dp.id as active_diet_plan_id -- Seleciona o ID do plano ATIVO diretamente
            FROM clients c
            JOIN users u ON c.user_id = u.id -- JOIN normal para dados do user
            JOIN diet_plans dp ON c.id = dp.client_id -- JOIN para planos alimentares
            WHERE dp.nutritionist_id = :nutritionist_id -- Filtra pelos planos criados por este nutricionista
              AND dp.is_active = 1 -- Filtra APENAS planos ATIVOS
            ORDER BY u.name ASC"; // Ordena por nome do cliente

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':nutritionist_id', $nutritionist_id, PDO::PARAM_INT);
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Erro ao buscar clientes: " . $e->getMessage();
    error_log("PDOException in client_list_nutritionist.php: " . $e->getMessage());
}
?>

<!-- Conteúdo Principal -->
<main class="main-content">
    <div class="container mt-4">
        <div class="row mb-3">
            <div class="col">
                <h2 class="h4"><i class="fas fa-users me-2"></i>Meus Clientes (Com Plano Ativo)</h2>
                <p class="text-muted" style="color: white !important;">Lista de clientes com planos alimentares ativos atribuídos a si.</p>
            </div>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <?php if (empty($clients) && !$error_message): ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle me-2"></i>Você não tem clientes com planos alimentares ativos.
                    </div>
                <?php elseif (!empty($clients)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Nome</th>
                                    <th scope="col">Email</th>
                                    <th scope="col" class="text-center">Plano Ativo?</th>
                                    <th scope="col" class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clients as $client): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($client['client_name']) ?></td>
                                        <td><?= htmlspecialchars($client['client_email']) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-success">Sim</span> <!-- Sempre Sim, pois só lista ativos -->
                                        </td>
                                        <td class="text-center">
                                            <?php
                                            // Botão Editar Plano Alimentar
                                            if ($client['active_diet_plan_id']) {
                                                echo "<a href='edit_diet_plan.php?client_id=" . $client['client_id'] . "&diet_plan_id=" . $client['active_diet_plan_id'] . "' class='btn btn-warning btn-sm me-1 mb-1' title='Editar Plano Ativo'><i class='fas fa-edit'></i> <span class='d-none d-md-inline'>Plano</span></a>";
                                            } else {
                                                // Este else {} bloco não será mais alcançado com a nova query,
                                                // mas mantê-lo não causa erro.
                                                echo "<a href='add_diet_plan.php?client_id=" . $client['client_id'] . "' class='btn btn-primary btn-sm me-1 mb-1' title='Criar Novo Plano'><i class='fas fa-plus'></i> <span class='d-none d-md-inline'>Plano</span></a>";
                                            }

                                            // Botão para Registar Nova Avaliação Física
                                            // Nutricionistas também podem registrar avaliações
                                            echo "<a href='edit_client_assessment.php?client_id=" . $client['client_id'] . "' class='btn btn-info btn-sm me-1 mb-1' title='Registar Avaliação Física'><i class='fas fa-clipboard-list'></i> <span class='d-none d-md-inline'>Avaliação</span></a>";

                                            // Botão Histórico de Avaliações
                                            echo "<a href='assessment_history.php?client_id=" . $client['client_id'] . "' class='btn btn-secondary btn-sm me-1 mb-1' title='Ver Histórico de Avaliações'><i class='fas fa-history'></i> <span class='d-none d-md-inline'>Histórico</span></a>";

                                            // Botão Ver Perfil (user_id)
                                            echo "<a href='profile_view.php?user_id=" . $client['client_user_id'] . "' class='btn btn-dark btn-sm me-1 mb-1' title='Ver Perfil'><i class='fas fa-user'></i> <span class='d-none d-md-inline'>Perfil</span></a>";

                                            // Botão Chat (client_user_id)
                                            if (isset($client['client_user_id'])) {
                                                echo "<a href='chat.php?user_id=" . $client['client_user_id'] . "' class='btn btn-success btn-sm mb-1' title='Conversar com Cliente'><i class='fas fa-comments'></i> <span class='d-none d-md-inline'>Chat</span></a>";
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div> <!-- /.card-body -->
        </div> <!-- /.card -->

    </div><!-- /.container -->
</main> <!-- /.main-content -->

<?php
// Inclui o rodapé da página
include 'footer.php';
?>
