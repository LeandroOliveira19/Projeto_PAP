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
                <h4>Clientes com Planos Ativos</h4>
                <a href="add_diet_plan.php" class="btn btn-success mb-3"><i class="fas fa-plus me-1"></i>Adicionar Novo Plano</a>
                <a href="client_list_nutritionist.php" class="btn btn-secondary mb-3"><i class="fas fa-users me-1"></i>Ver Todos os Clientes</a>

                <!-- Tabela Responsiva -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Cliente</th>
                                <th>Plano Alimentar Ativo</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                // Busca clientes que têm um plano ATIVO associado a este nutricionista
                                // A coluna dp.is_active AGORA DEVE EXISTIR
                                $stmt = $pdo->prepare("
                                    SELECT
                                        c.id AS client_id,
                                        u.id AS client_user_id, -- ID do usuário cliente (para chat)
                                        u.name AS client_name,
                                        dp.id AS diet_plan_id,
                                        dp.plan_details,
                                        dp.is_active -- Seleciona a coluna
                                    FROM clients c
                                    JOIN users u ON c.user_id = u.id
                                    JOIN diet_plans dp ON c.id = dp.client_id -- JOIN normal, pois queremos apenas os que têm plano
                                    WHERE dp.nutritionist_id = :nutritionist_id
                                      AND dp.is_active = 1 -- Filtra apenas os planos ativos
                                    ORDER BY u.name
                                ");
                                $stmt->execute(['nutritionist_id' => $nutritionist_id]);
                                $clients_with_active_plans = $stmt->fetchAll();

                                if (count($clients_with_active_plans) > 0) {
                                    foreach ($clients_with_active_plans as $client) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($client['client_name']) . "</td>";
                                        echo "<td>";
                                        // Mostra trecho do plano
                                        $details_preview = mb_substr(strip_tags($client['plan_details']), 0, 100);
                                        echo htmlspecialchars($details_preview) . (mb_strlen($client['plan_details']) > 100 ? '...' : '');
                                        echo "</td>";
                                        echo "<td>";
                                        // Botão para editar o plano ativo
                                        echo "<a href='edit_diet_plan.php?client_id=" . $client['client_id'] . "&diet_plan_id=" . $client['diet_plan_id'] . "' class='btn btn-warning btn-sm me-1' title='Editar Plano Ativo'><i class='fas fa-edit'></i></a>";
                                        // Botão para Chat
                                        echo "<a href='chat.php?user_id=" . $client['client_user_id'] . "' class='btn btn-info btn-sm' title='Conversar com Cliente'><i class='fas fa-comments'></i></a>";
                                        // Poderia adicionar botão para desativar plano aqui
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                     echo "<tr><td colspan='3' class='text-center text-muted'>Nenhum cliente com plano alimentar ativo atribuído a você.</td></tr>";
                                }
                            } catch (PDOException $e) {
                                // Loga o erro e mostra mensagem de erro na tabela
                                error_log("Erro ao buscar clientes com planos ativos para nutricionista: " . $e->getMessage());
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
