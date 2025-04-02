<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'nutritionist') {
    header('Location: login.php');
    exit;
}
include '../includes/db.php';
include '../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan_details = $_POST['plan_details'];

    $stmt = $pdo->prepare("UPDATE diet_plans SET plan_details = ? WHERE client_id = ? AND nutritionist_id = ?");
    $stmt->execute([$plan_details, $_GET['client_id'], $_SESSION['user_id']]);
    header('Location: nutritionist.php');
    exit;
}

$stmt = $pdo->prepare("SELECT plan_details FROM diet_plans WHERE client_id = ? AND nutritionist_id = ?");
$stmt->execute([$_GET['client_id'], $_SESSION['user_id']]);
$plan = $stmt->fetch();
?>

<div class="container mt-5">
    <h2>Editar Plano Alimentar</h2>
    <form method="POST">
        <div class="form-group">
            <label for="plan_details">Detalhes do Plano</label>
            <textarea class="form-control" id="plan_details" name="plan_details" rows="5" required><?= $plan['plan_details'] ?? '' ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Salvar</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>