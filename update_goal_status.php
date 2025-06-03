<?php
session_start();
header('Content-Type: application/json');

// Verifica se o usuário está logado e se é um cliente
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    echo json_encode(['status' => 'error', 'message' => 'Acesso não autorizado.']);
    exit;
}

// Inclui a conexão com o banco de dados
include 'db.php';

// Verifica se os dados necessários foram enviados via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['goal_id']) || !isset($_POST['status'])) {
    echo json_encode(['status' => 'error', 'message' => 'Pedido inválido.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$goal_id = filter_input(INPUT_POST, 'goal_id', FILTER_VALIDATE_INT);
$status = $_POST['status']; // 'completed' ou 'cancelled'

// Valida o status recebido
$allowed_statuses = ['completed', 'cancelled'];
if (!$goal_id || !in_array($status, $allowed_statuses)) {
    echo json_encode(['status' => 'error', 'message' => 'Dados inválidos.']);
    exit;
}

try {
    // Obter o client_id para o user_id logado
    $stmt_get_client_id = $pdo->prepare("SELECT id FROM clients WHERE user_id = ? LIMIT 1");
    $stmt_get_client_id->execute([$user_id]);
    $client_id = $stmt_get_client_id->fetchColumn();

    if (!$client_id) {
        echo json_encode(['status' => 'error', 'message' => 'Registo de cliente não encontrado.']);
        exit;
    }

    // Atualiza o status do objetivo, garantindo que pertence a este cliente e está ativo
    // Define finished_at APENAS se o status for 'completed' ou 'cancelled'
    $sql_update = "
        UPDATE client_goals
        SET status = :status, finished_at = NOW() 
    "; // finished_at é definido para 'completed' ou 'cancelled'
    // A coluna updated_at (se existir) pode ser atualizada automaticamente pelo trigger da BD
    // ou pode adicionar ", updated_at = NOW()" se não tiver trigger.
    // Para este exemplo, assumimos que finished_at é o principal para a data de finalização.

    $sql_update .= " WHERE id = :goal_id AND client_id = :client_id AND status = 'active'";

    $stmt_update = $pdo->prepare($sql_update);
    $params = [
        ':status' => $status,
        ':goal_id' => $goal_id,
        ':client_id' => $client_id
    ];

    $stmt_update->execute($params);

    if ($stmt_update->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Status do objetivo atualizado com sucesso.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Objetivo não encontrado, já atualizado ou não pertence a você.']);
    }

} catch (PDOException $e) {
    error_log("Erro BD ao atualizar status do objetivo: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Erro interno ao atualizar o objetivo.']);
}
?>
