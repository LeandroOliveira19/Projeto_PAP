<?php
// filepath: c:\xampp\htdocs\Projeto_PAP\fetch_messages.php
session_start();
include 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Não autenticado.']);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$other_user_id = filter_var($_GET['other_user_id'] ?? null, FILTER_VALIDATE_INT);

if (!$other_user_id) {
    echo json_encode(['status' => 'error', 'message' => 'ID do outro utilizador não fornecido.', 'messages' => []]);
    exit;
}

try {
    // Busca mensagens entre current_user_id e other_user_id
    // Também busca a foto de perfil e nome do remetente para exibição
    // Filtra mensagens apagadas globalmente ou apagadas para o utilizador atual
    $stmt = $pdo->prepare("
        SELECT
            m.id,
            m.sender_id,
            m.receiver_id,
            m.message_type,
            m.message,
            m.file_url,
            m.file_name,
            m.file_size,
            m.duration,
            m.timestamp,
            m.globally_deleted_by_user_id,
            m.globally_deleted_at,
            s.name AS sender_name,
            s.profile_picture AS sender_avatar,
            CASE WHEN dm.message_id IS NOT NULL THEN 1 ELSE 0 END AS is_deleted_for_current_user
        FROM messages m
        JOIN users s ON m.sender_id = s.id
        LEFT JOIN deleted_messages dm ON m.id = dm.message_id AND dm.user_id = :current_user_id
        WHERE
            ((m.sender_id = :current_user_id AND m.receiver_id = :other_user_id) OR (m.sender_id = :other_user_id AND m.receiver_id = :current_user_id))
            AND m.globally_deleted_by_user_id IS NULL -- Exclui mensagens apagadas globalmente
            AND dm.message_id IS NULL -- Exclui mensagens apagadas apenas para o utilizador atual
        ORDER BY m.timestamp ASC
    ");
    $stmt->execute([
        'current_user_id' => $current_user_id,
        'other_user_id' => $other_user_id,
    ]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Marca mensagens como lidas se o utilizador atual for o recetor
    $stmt_mark_read = $pdo->prepare("
        UPDATE messages
        SET is_read = 1
        WHERE receiver_id = :current_user_id AND sender_id = :other_user_id AND is_read = 0
    ");
    $stmt_mark_read->execute([
        'current_user_id' => $current_user_id,
        'other_user_id' => $other_user_id
    ]);

    echo json_encode(['status' => 'success', 'messages' => $messages]);

} catch (PDOException $e) {
    error_log("Erro DB ao buscar mensagens (fetch_messages.php): " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Erro interno ao buscar mensagens.']);
}

?>