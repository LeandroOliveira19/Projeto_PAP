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

        <div class="row">
            <div class="col-md-12">
                <h4>Ações Rápidas</h4>
                <a href="add_diet_plan.php" class="btn btn-success mb-3"><i class="fas fa-plus me-1"></i>Adicionar Novo Plano</a>
                <a href="client_list_nutritionist.php" class="btn btn-secondary mb-3"><i class="fas fa-users me-1"></i>Ver Todos os Clientes</a>

                <!-- Tabela Responsiva -->
                <h5 class="mt-4">Clientes Sem Plano Alimentar Ativo (Comigo)</h5>
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
                            try {
                                // Busca clientes que NÃO têm um plano ATIVO associado a este nutricionista
                                $stmt = $pdo->prepare("
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
                                $stmt->execute(['nutritionist_id' => $nutritionist_id]);
                                $clients_without_active_plan = $stmt->fetchAll();

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
                            } catch (PDOException $e) {
                                // Loga o erro e mostra mensagem de erro na tabela
                                error_log("Erro ao buscar clientes sem plano ativo para nutricionista: " . $e->getMessage());
                                echo "<tr><td colspan='3' class='text-center text-danger'>Erro ao carregar lista de clientes.</td></tr>";
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
