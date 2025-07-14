<?php
// filepath: c:\xampp\htdocs\Projeto_PAP\delete_message.php
session_start();
include 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Não autenticado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
    exit;
}

if (!isset($_POST['message_id']) || !isset($_POST['scope'])) {
    echo json_encode(['status' => 'error', 'message' => 'Dados em falta (ID da mensagem ou âmbito).']);
    exit;
}


$user_id_who_is_deleting = $_SESSION['user_id'];
$message_id = filter_var($_POST['message_id'], FILTER_VALIDATE_INT);

if (!$message_id) {
    echo json_encode(['status' => 'error', 'message' => 'ID da mensagem inválido.']);
    exit;
}

$scope = $_POST['scope']; // 'me' or 'all'

try {
    // Verificar se a mensagem existe e se o utilizador faz parte da conversa
    $stmt_check = $pdo->prepare("SELECT sender_id, receiver_id FROM messages WHERE id = ?");
    $stmt_check->execute([$message_id]);
    $message_data = $stmt_check->fetch();

    if (!$message_data) {
        echo json_encode(['status' => 'error', 'message' => 'Mensagem não encontrada.']);
        exit;
    }

    // Garante que o utilizador que está a apagar faz parte da conversa
    if ($message_data['sender_id'] != $user_id_who_is_deleting && $message_data['receiver_id'] != $user_id_who_is_deleting) {
         echo json_encode(['status' => 'error', 'message' => 'Não tem permissão para apagar esta mensagem.']);
         exit;
    }

    if ($scope === 'me') {
        // Inserir registo na tabela deleted_messages
        // Usar INSERT IGNORE para o caso de o utilizador clicar várias vezes rapidamente
        $stmt_delete_me = $pdo->prepare("
            INSERT IGNORE INTO deleted_messages (message_id, user_id, deleted_at)
            VALUES (?, ?, NOW())
        ");
        $stmt_delete_me->execute([$message_id, $user_id_who_is_deleting]);

        echo json_encode(['status' => 'success', 'message' => 'Mensagem apagada para si.']);

    } elseif ($scope === 'all') {
        // Apenas o remetente pode apagar para todos (dentro do limite de tempo definido no JS)
        if ($message_data['sender_id'] != $user_id_who_is_deleting) {
             echo json_encode(['status' => 'error', 'message' => 'Apenas o remetente pode apagar a mensagem para todos.']);
             exit;
        }
        // Atualizar a flag is_globally_deleted e registar quem apagou e quando
        $stmt_delete_all = $pdo->prepare("
            UPDATE messages SET is_globally_deleted = TRUE, globally_deleted_by_user_id = ?, globally_deleted_at = NOW()
            WHERE id = ?");
        $stmt_delete_all->execute([$user_id_who_is_deleting, $message_id]); // user_id_who_is_deleting aqui será o sender_id
        echo json_encode(['status' => 'success', 'message' => 'Mensagem apagada para todos.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Âmbito de eliminação inválido.']);
    }

} catch (PDOException $e) {
    error_log("Erro DB ao apagar mensagem (delete_message.php): " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Erro interno ao apagar a mensagem.']);
}
?>
