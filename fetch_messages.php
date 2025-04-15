<?php
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
    // *** MODIFICAÇÃO: Adiciona as novas colunas ao SELECT ***
    $stmt = $pdo->prepare("
        SELECT
            m.id,
            m.sender_id,
            m.receiver_id,
            m.message_type,         -- <<< ADICIONADO
            m.message,              -- (agora é legenda ou texto)
            m.file_url,             -- <<< ADICIONADO
            m.file_name,            -- <<< ADICIONADO
            m.file_size,            -- <<< ADICIONADO
            m.duration,             -- <<< ADICIONADO
            m.timestamp,
            u_sender.name as sender_name -- Pega o nome do remetente
        FROM messages m
        JOIN users u_sender ON m.sender_id = u_sender.id -- Junta para pegar o nome do remetente
        WHERE
            (m.sender_id = :user_id AND m.receiver_id = :other_user_id) -- Mensagens enviadas pelo usuário logado para o outro
            OR
            (m.sender_id = :other_user_id AND m.receiver_id = :user_id) -- Mensagens enviadas pelo outro para o usuário logado
        ORDER BY m.timestamp ASC -- Ordena pela data/hora de envio
    ");

    // Executa a consulta, substituindo os placeholders pelos IDs
    $stmt->execute([
        'user_id' => $user_id,
        'other_user_id' => $other_user_id
    ]);

    // Busca todas as mensagens encontradas
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Opcional: Marcar mensagens como lidas aqui (se implementar a funcionalidade 'is_read')
    // $updateStmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = :user_id AND sender_id = :other_user_id AND is_read = 0");
    // $updateStmt->execute(['user_id' => $user_id, 'other_user_id' => $other_user_id]);

    // Retorna as mensagens em formato JSON (agora incluindo os novos campos)
    echo json_encode(['status' => 'success', 'messages' => $messages]);

} catch (PDOException $e) {
    // Em caso de erro no banco de dados, registra o erro e retorna uma mensagem genérica
    error_log("Erro ao buscar mensagens (fetch_messages.php): " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Erro interno ao buscar mensagens.', 'messages' => []]);
}
?>
