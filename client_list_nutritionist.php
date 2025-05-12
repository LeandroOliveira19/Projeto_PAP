<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'nutritionist') {
    header('Location: login.php');
    exit;
}
include 'db.php';
include 'header.php';

// *** ADICIONADO: Define página ativa ***
$active_page = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['role']; // Necessário para a sidebar condicional
$nutritionist_id = $_SESSION['user_id'];

?>

<?php include 'sidebar_nutritionist.php';  ?>

<!-- *** ADICIONADO: Wrapper <main> *** -->
<main class="main-content">
    <div class="container mt-5">
        <h2>Meus Clientes</h2>
        <p>Lista de clientes para os quais você criou ou pode criar planos alimentares.</p>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Nome do Cliente</th>
                        <th>Email</th>
                        <th>Plano Ativo?</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        // Busca todos os usuários que são clientes
                        // E verifica se existe um plano ATIVO associado a este nutricionista
                        $stmt = $pdo->prepare("
                            SELECT
                                u.id AS user_id,
                                u.name,
                                u.email,
                                c.id AS client_id,
                                EXISTS (
                                    SELECT 1 FROM diet_plans dp
                                    WHERE dp.client_id = c.id
                                    AND dp.nutritionist_id = :nutritionist_id
                                    AND dp.is_active = 1
                                ) AS has_active_plan,
                                (SELECT dp.id FROM diet_plans dp
                                 WHERE dp.client_id = c.id
                                 AND dp.nutritionist_id = :nutritionist_id
                                 AND dp.is_active = 1 LIMIT 1) AS active_plan_id
                            FROM users u
                            JOIN clients c ON u.id = c.user_id
                            WHERE u.role = 'client'
                            ORDER BY u.name
                        ");
                        $stmt->execute(['nutritionist_id' => $nutritionist_id]);
                        $clients = $stmt->fetchAll();

                        if (count($clients) > 0) {
                            foreach ($clients as $client) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($client['name']) . "</td>";
                                echo "<td>" . htmlspecialchars($client['email']) . "</td>";
                                echo "<td>" . ($client['has_active_plan'] ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-secondary">Não</span>') . "</td>";
                                echo "<td>";
                                if ($client['has_active_plan']) {
                                    echo "<a href='edit_diet_plan.php?client_id=" . $client['client_id'] . "&diet_plan_id=" . $client['active_plan_id'] . "' class='btn btn-warning btn-sm me-1' title='Editar Plano Ativo'><i class='fas fa-edit'></i></a>";
                                } else {
                                    echo "<a href='add_diet_plan.php?client_id=" . $client['client_id'] . "' class='btn btn-primary btn-sm me-1' title='Criar Novo Plano'><i class='fas fa-plus'></i></a>";
                                }
                                echo "<a href='chat.php?user_id=" . $client['user_id'] . "' class='btn btn-info btn-sm' title='Conversar com Cliente'><i class='fas fa-comments'></i></a>";
                                // Adicionar link para histórico de planos do cliente (se existir)
                                // echo "<a href='client_diet_history.php?client_id=" . $client['client_id'] . "' class='btn btn-secondary btn-sm ms-1' title='Histórico de Planos'><i class='fas fa-history'></i></a>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' class='text-center text-muted'>Nenhum cliente encontrado no sistema.</td></tr>";
                        }
                    } catch (PDOException $e) {
                        error_log("Erro ao buscar clientes para nutricionista: " . $e->getMessage());
                        echo "<tr><td colspan='4' class='text-center text-danger'>Erro ao carregar lista de clientes.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <a href="nutritionist.php" class="btn btn-secondary mt-3">Voltar ao Painel</a>
    </div>
</main> <!-- *** FIM: Wrapper <main> *** -->

<?php include 'footer.php'; ?>
