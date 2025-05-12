<?php
// filepath: c:\xampp\htdocs\Projeto_PAP\my_clients.php
session_start();

// Verifica se o usuário está logado e se é um trainer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainer') {
    header('Location: login.php');
    exit;
}

// Inclui a conexão com o banco de dados e o cabeçalho
include 'db.php';
include 'header.php';

// Define a página ativa para a sidebar
$active_page = 'my_clients.php';

// Inclui a sidebar do trainer
include 'sidebar_trainer.php';

$trainer_id = $_SESSION['user_id'];
$clients = [];
$error_message = '';

try {
    // Query para buscar clientes (users + clients) para os quais ESTE trainer criou PELO MENOS UM plano (ativo ou inativo).
    // Usa LEFT JOIN para pegar o ID do plano ATIVO (se existir) para os botões de ação.
    $sql = "SELECT DISTINCT -- Garante que cada cliente aparece apenas uma vez
                c.id as client_id,          -- ID da tabela clients
                u.id as client_user_id,     -- ID da tabela users (para chat)
                u.name as client_name,
                u.email as client_email,
                -- Subquery ou LEFT JOIN para pegar o ID do plano ATIVO deste trainer para este cliente
                (SELECT wp_active.id
                 FROM workout_plans wp_active
                 WHERE wp_active.client_id = c.id
                   AND wp_active.trainer_id = :trainer_id_for_active_plan -- Mesmo trainer_id
                   AND wp_active.is_active = 1
                 LIMIT 1) as active_workout_plan_id
            FROM clients c
            JOIN users u ON c.user_id = u.id
            JOIN workout_plans wp ON c.id = wp.client_id -- JOIN para filtrar clientes que têm *algum* plano deste trainer
            WHERE wp.trainer_id = :trainer_id -- Filtra pelos planos criados por este trainer
            ORDER BY u.name ASC"; // Ordena por nome do cliente

    $stmt = $pdo->prepare($sql);
    // Binds para segurança (usamos o mesmo ID em dois lugares na query)
    $stmt->bindParam(':trainer_id', $trainer_id, PDO::PARAM_INT);
    $stmt->bindParam(':trainer_id_for_active_plan', $trainer_id, PDO::PARAM_INT);
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Erro ao buscar clientes: " . $e->getMessage();
    error_log("PDOException in my_clients.php: " . $e->getMessage());
}

?>

<!-- Conteúdo Principal -->
<main class="main-content">
    <div class="container mt-4">
        <div class="row mb-3">
            <div class="col">
                <h2 class="h4"><i class="fas fa-users me-2"></i>Meus Clientes</h2>
                <p class="text-muted">Lista de clientes para os quais você criou planos de treino.</p>
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
                        <i class="fas fa-info-circle me-2"></i>Você ainda não criou planos para nenhum cliente.
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
                                            <?= $client['active_workout_plan_id'] ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-secondary">Não</span>' ?>
                                        </td>
                                        <td class="text-center">
                                            <?php
                                            // Botão Editar/Criar Plano de Treino
                                            if ($client['active_workout_plan_id']) {
                                                echo "<a href='edit_workout_plan.php?client_id=" . $client['client_id'] . "&workout_plan_id=" . $client['active_workout_plan_id'] . "' class='btn btn-warning btn-sm me-1 mb-1' title='Editar Plano Ativo'><i class='fas fa-edit'></i> <span class='d-none d-md-inline'>Plano</span></a>";
                                            } else {
                                                echo "<a href='add_workout_plan.php?client_id=" . $client['client_id'] . "' class='btn btn-primary btn-sm me-1 mb-1' title='Criar Novo Plano'><i class='fas fa-plus'></i> <span class='d-none d-md-inline'>Plano</span></a>";
                                            }

                                            // Botão para Registar Nova Avaliação Física
                                            echo "<a href='edit_client_assessment.php?client_id=" . $client['client_id'] . "' class='btn btn-info btn-sm me-1 mb-1' title='Registar Avaliação Física'><i class='fas fa-clipboard-list'></i> <span class='d-none d-md-inline'>Avaliação</span></a>";

                                            // *** NOVO BOTÃO HISTÓRICO ***
                                            echo "<a href='assessment_history.php?client_id=" . $client['client_id'] . "' class='btn btn-secondary btn-sm me-1 mb-1' title='Ver Histórico de Avaliações'><i class='fas fa-history'></i> <span class='d-none d-md-inline'>Histórico</span></a>";
                                            // ****************************

                                            // Botão Chat
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
