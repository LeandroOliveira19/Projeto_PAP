<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'nutritionist') {
    header('Location: login.php');
    exit;
}
include 'db.php';
include 'header.php';
?>

<div class="container mt-5">
    <h2>Bem-vindo, Nutricionista!</h2>
    <div class="row">
        <div class="col-md-12">
            <h4>Clientes e Planos Alimentares</h4>
            <a href="add_diet_plan.php" class="btn btn-success mb-3">Adicionar Plano Alimentar</a>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Plano Alimentar</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->prepare("SELECT c.id AS client_id, u.name AS client_name, dp.plan_details 
                                           FROM clients c 
                                           JOIN users u ON c.user_id = u.id 
                                           LEFT JOIN diet_plans dp ON c.id = dp.client_id 
                                           WHERE dp.nutritionist_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $clients = $stmt->fetchAll();

                    foreach ($clients as $client) {
                        echo "<tr>";
                        echo "<td>" . $client['client_name'] . "</td>";
                        echo "<td>" . ($client['plan_details'] ?? "Nenhum plano cadastrado") . "</td>";
                        echo "<td>
                                <a href='edit_diet_plan.php?client_id=" . $client['client_id'] . "' class='btn btn-warning btn-sm'>Editar</a>
                              </td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>