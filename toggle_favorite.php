<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Utilizador não autenticado.']);
    exit;
}

if (!isset($_POST['class_id']) || !filter_var($_POST['class_id'], FILTER_VALIDATE_INT)) {
    echo json_encode(['status' => 'error', 'message' => 'ID da aula inválido.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$class_id = (int)$_POST['class_id'];
$is_favorite_now = false;

try {
    // Verificar se já é favorito
    $stmt_check = $pdo->prepare("SELECT id FROM user_favorite_classes WHERE user_id = :user_id AND class_id = :class_id");
    $stmt_check->execute(['user_id' => $user_id, 'class_id' => $class_id]);
    $existing_favorite = $stmt_check->fetch();

    if ($existing_favorite) {
        // Remover dos favoritos
        $stmt_delete = $pdo->prepare("DELETE FROM user_favorite_classes WHERE id = :id");
        $stmt_delete->execute(['id' => $existing_favorite['id']]);
        $is_favorite_now = false;
        $message = "Aula removida dos favoritos.";
    } else {
        // Adicionar aos favoritos
        $stmt_insert = $pdo->prepare("INSERT INTO user_favorite_classes (user_id, class_id) VALUES (:user_id, :class_id)");
        $stmt_insert->execute(['user_id' => $user_id, 'class_id' => $class_id]);
        $is_favorite_now = true;
        $message = "Aula adicionada aos favoritos.";
    }

    echo json_encode([
        'status' => 'success',
        'message' => $message,
        'is_favorite' => $is_favorite_now
    ]);

} catch (PDOException $e) {
    error_log("Erro ao alternar favorito: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Erro na base de dados.']);
}

?>