<?php
session_start();
header('Content-Type: application/json');

// Inclui a conexão com o banco de dados DENTRO do bloco try
// para que erros de conexão possam ser apanhados e retornados como JSON.
try {
    include 'db.php';

    // Verifica se o usuário está logado e se é recepcionista
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Acesso não autorizado.']);
        exit;
    }

    $user_role = $_SESSION['role'];
    $action_requested = $_REQUEST['action'] ?? null;

    // Permissões específicas por ação
    if ($action_requested === 'get_participants' && !in_array($user_role, ['receptionist', 'trainer'])) {
        echo json_encode(['status' => 'error', 'message' => 'Não autorizado a ver participantes.']);
        exit;
    } elseif (!in_array($action_requested, ['get_participants']) && $user_role !== 'receptionist') {
        echo json_encode(['status' => 'error', 'message' => 'Ação não autorizada para o seu perfil.']);
        exit;
    }

    $receptionist_user_id = $_SESSION['user_id'];
    $action = $_REQUEST['action'] ?? null; // Pode ser GET para get_participants

    if ($action === 'get_participants') {
        // Lógica para buscar participantes de uma aula específica
        if (!isset($_GET['class_id']) || !filter_var($_GET['class_id'], FILTER_VALIDATE_INT)) {
            echo json_encode(['status' => 'error', 'message' => 'ID da aula inválido.']);
            exit;
        }
        $class_id = (int)$_GET['class_id'];

        try {
            $stmt = $pdo->prepare("
                SELECT
                    u.id as user_id,
                    u.name,
                    u.profile_picture,
                    cp.checked_in_at,
                    cp.checked_in_by_user_id,
                    u_checker.name as checked_in_by_name
                FROM class_participants cp
                JOIN users u ON cp.user_id = u.id
                LEFT JOIN users u_checker ON cp.checked_in_by_user_id = u_checker.id
                WHERE cp.class_id = :class_id
                ORDER BY u.name ASC
            ");
            $stmt->execute(['class_id' => $class_id]);
            $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Adiciona URL da foto de perfil
            foreach ($participants as &$p) {
                $p['profile_picture_url'] = ($p['profile_picture'] && file_exists('assets/img/profile_pics/' . $p['profile_picture']))
                                          ? 'assets/img/profile_pics/' . $p['profile_picture']
                                          : 'assets/img/avatars/default.png';
            }
            unset($p); // Quebra a referência ao último elemento

            echo json_encode(['status' => 'success', 'participants' => $participants]);

        } catch (PDOException $e) {
            error_log("Erro ao buscar participantes para check-in: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Erro ao buscar participantes.']);
        }
        exit; // Termina o script após responder ao pedido GET
    }

    // Lógica para processar ações de check-in/cancelamento (POST requests)
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Método inválido para esta ação.']);
        exit;
    }

    if (!isset($_POST['class_id'], $_POST['client_user_id']) ||
        !filter_var($_POST['class_id'], FILTER_VALIDATE_INT) ||
        !filter_var($_POST['client_user_id'], FILTER_VALIDATE_INT)) {
        echo json_encode(['status' => 'error', 'message' => 'Dados inválidos para a ação.']);
        exit;
    }

    $class_id = (int)$_POST['class_id'];
    $client_user_id = (int)$_POST['client_user_id'];

    try {
        if ($action === 'check_in') {
            $stmt = $pdo->prepare("
                UPDATE class_participants
                SET checked_in_at = NOW(), checked_in_by_user_id = :receptionist_id
                WHERE class_id = :class_id AND user_id = :client_user_id AND checked_in_at IS NULL
            ");
            $stmt->execute([
                'receptionist_id' => $receptionist_user_id,
                'class_id' => $class_id,
                'client_user_id' => $client_user_id
            ]);
            $message = 'Check-in realizado com sucesso.';

        } elseif ($action === 'cancel_check_in') {
            $stmt = $pdo->prepare("
                UPDATE class_participants
                SET checked_in_at = NULL, checked_in_by_user_id = NULL
                WHERE class_id = :class_id AND user_id = :client_user_id
            ");
            $stmt->execute([
                'class_id' => $class_id,
                'client_user_id' => $client_user_id
            ]);
            $message = 'Check-in cancelado com sucesso.';
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Ação desconhecida.']);
            exit;
        }

        if ($stmt->rowCount() > 0) {
            // Obter nova contagem de check-ins para a aula
            $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM class_participants WHERE class_id = :class_id AND checked_in_at IS NOT NULL");
            $stmt_count->execute(['class_id' => $class_id]);
            $new_checked_in_count = $stmt_count->fetchColumn();

            echo json_encode(['status' => 'success', 'message' => $message, 'new_checked_in_count' => $new_checked_in_count]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Nenhuma alteração realizada. O estado pode já ser o desejado ou o participante não foi encontrado.']);
        }

    } catch (PDOException $e) {
        error_log("Erro ao processar check-in/cancelamento: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Erro no servidor ao processar o pedido.']);
    }

} catch (PDOException $e) {
    // Este catch apanha erros de conexão do db.php ou outros erros PDO não apanhados internamente
    error_log("Erro CRÍTICO em ajax_handle_checkin.php (PDOException): " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Erro crítico de banco de dados. Contacte o suporte.']);
} catch (Throwable $t) {
    // Apanha outros erros inesperados (Syntax errors, etc.)
    error_log("Erro INESPERADO em ajax_handle_checkin.php (Throwable): " . $t->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Ocorreu um erro inesperado no servidor.']);
}
?>