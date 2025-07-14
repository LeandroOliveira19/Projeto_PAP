<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    echo json_encode(['status' => 'error', 'message' => 'Acesso não autorizado.']);
    exit;
}

include 'db.php';

$user_id = $_SESSION['user_id'];
$workout_plan_id = filter_input(INPUT_POST, 'workout_plan_id', FILTER_VALIDATE_INT);
$log_date = $_POST['log_date'] ?? date('Y-m-d');
$exercises_log = $_POST['exercises'] ?? [];

if (!$workout_plan_id || empty($exercises_log) || !is_array($exercises_log)) {
    echo json_encode(['status' => 'error', 'message' => 'Dados inválidos ou em falta.']);
    exit;
}

try {
    // Obter o client_id para o user_id logado
    $stmt_client = $pdo->prepare("SELECT id FROM clients WHERE user_id = ? LIMIT 1");
    $stmt_client->execute([$user_id]);
    $client_id = $stmt_client->fetchColumn();

    if (!$client_id) {
        echo json_encode(['status' => 'error', 'message' => 'Registo de cliente não encontrado.']);
        exit;
    }

    $pdo->beginTransaction();

    $stmt_insert = $pdo->prepare(
        "INSERT INTO workout_log (client_id, workout_plan_id, exercise_id, log_date, weight_lifted) VALUES (:client_id, :plan_id, :exercise_id, :log_date, :weight)"
    );

    foreach ($exercises_log as $log) {
        $weight = str_replace(',', '.', $log['weight']);
        if (is_numeric($weight) && $weight > 0) {
            $stmt_insert->execute(['client_id' => $client_id, 'plan_id' => $workout_plan_id, 'exercise_id' => $log['exercise_id'], 'log_date' => $log_date, 'weight' => $weight]);
        }
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Treino registado com sucesso!']);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erro ao registar treino em log_workout_session.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Erro ao guardar o registo de treino.']);
}
?>

