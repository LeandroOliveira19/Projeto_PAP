<?php
// filepath: c:\xampp\htdocs\Projeto_PAP\fetch_messages.php
session_start();
include 'db.php'; // Inclui a conexão com o banco de dados

header('Content-Type: application/json'); // Define o tipo de resposta como JSON

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Não autenticado.', 'messages' => []]);
    exit;
}

// Verifica se o ID do outro usuário na conversa foi fornecido via GET
if (!isset($_GET['other_user_id'])) {
     echo json_encode(['status' => 'error', 'message' => 'ID do interlocutor não especificado.', 'messages' => []]);
    exit;
}

$user_id = $_SESSION['user_id']; // ID do usuário logado
$other_user_id = filter_var($_GET['other_user_id'], FILTER_VALIDATE_INT); // ID do outro usuário

// Valida o ID do outro usuário
if (!$other_user_id) {
    echo json_encode(['status' => 'error', 'message' => 'ID do interlocutor inválido.', 'messages' => []]);
    exit;
}

try {
    // Prepara a consulta para buscar mensagens entre os dois usuários
    // *** MODIFICAÇÃO AQUI: Adiciona u_sender.profile_picture ***
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
            u_sender.name as sender_name,
            u_sender.profile_picture as sender_avatar -- <<< ADICIONADO AQUI
        FROM messages m
        JOIN users u_sender ON m.sender_id = u_sender.id -- Junta para pegar o nome e avatar do remetente
        WHERE
            (m.sender_id = :user_id AND m.receiver_id = :other_user_id)
            OR
            (m.sender_id = :other_user_id AND m.receiver_id = :user_id)
        ORDER BY m.timestamp ASC
    ");
    // **********************************************************

    // Executa a consulta, substituindo os placeholders pelos IDs
    $stmt->execute([
        'user_id' => $user_id,
        'other_user_id' => $other_user_id
    ]);

    // Busca todas as mensagens encontradas
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Opcional: Marcar mensagens como lidas aqui (se implementar a funcionalidade 'is_read')
    // ...

    // Retorna as mensagens em formato JSON (agora incluindo sender_avatar)
    echo json_encode(['status' => 'success', 'messages' => $messages]);

} catch (PDOException $e) {
    // Em caso de erro no banco de dados, registra o erro e retorna uma mensagem genérica
    error_log("Erro ao buscar mensagens (fetch_messages.php): " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Erro interno ao buscar mensagens.', 'messages' => []]);
}
?>
