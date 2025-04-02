<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'nutritionist') {
    header('Location: login.php');
    exit;
}
include 'db.php';
include 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'];
    $plan_details = $_POST['plan_details'];

    $stmt = $pdo->prepare("INSERT INTO diet_plans (client_id, nutritionist_id, plan_details) VALUES (?, ?, ?)");
    $stmt->execute([$client_id, $_SESSION['user_id'], $plan_details]);
    header('Location: nutritionist.php');
    exit;
}
?>

<div class="container mt-5">
    <h2>Adicionar Plano Alimentar</h2>
    <form method="POST">
        <div class="form-group">
            <label for="client_id">Cliente</label>
            <select class="form-control" id="client_id" name="client_id" required>
                <?php
                $stmt = $pdo->query("SELECT c.id, u.name FROM clients c JOIN users u ON c.user_id = u.id");
                $clients = $stmt->fetchAll();
                foreach ($clients as $client) {
                    echo "<option value='" . $client['id'] . "'>" . $client['name'] . "</option>";
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="plan_details">Detalhes do Plano</label>
            <textarea class="form-control" id="plan_details" name="plan_details" rows="5" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Salvar</button>
    </form>
</div>

<?php include 'footer.php'; ?>