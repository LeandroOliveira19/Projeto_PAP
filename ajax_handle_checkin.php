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
            // --- Lógica de Atribuição de Conquistas (APÓS CHECK-IN BEM-SUCEDIDO) ---
            if ($action === 'check_in') {
                try {
                    // 1. Obter o client_id para o client_user_id que fez check-in
                    $stmt_get_client_id = $pdo->prepare("SELECT id FROM clients WHERE user_id = ? LIMIT 1");
                    $stmt_get_client_id->execute([$client_user_id]);
                    $client_id = $stmt_get_client_id->fetchColumn();

                    if ($client_id) {
                        // Contar o total de check-ins concluídos por este cliente
                        $stmt_count_checkins = $pdo->prepare("
                            SELECT COUNT(cp.id)
                            FROM class_participants cp
                            WHERE cp.user_id = ? AND cp.checked_in_at IS NOT NULL
                        ");
                        $stmt_count_checkins->execute([$client_user_id]);
                        $total_checkins_count = (int)$stmt_count_checkins->fetchColumn();

                        // --- Conquista: "Primeiro Check-in" ---
                        if ($total_checkins_count === 1) {
                            $stmt_get_ach_first = $pdo->prepare("SELECT id FROM achievements WHERE criteria_type = 'first_class_checkin' AND is_manual_claim = 0 LIMIT 1");
                            $stmt_get_ach_first->execute();
                            $achievement_first_id = $stmt_get_ach_first->fetchColumn();

                            if ($achievement_first_id) {
                                $stmt_check_earned = $pdo->prepare("SELECT id FROM client_achievements WHERE client_id = ? AND achievement_id = ?");
                                $stmt_check_earned->execute([$client_id, $achievement_first_id]);
                                if (!$stmt_check_earned->fetch()) {
                                    $stmt_award = $pdo->prepare("INSERT INTO client_achievements (client_id, achievement_id, earned_at) VALUES (?, ?, NOW())");
                                    $stmt_award->execute([$client_id, $achievement_first_id]);
                                    error_log("Conquista 'first_class_checkin' atribuída ao cliente_id: " . $client_id);
                                }
                            }
                        }

                        // --- Conquista: "Assíduo nas Aulas" (Ex: 5, 10, 20 Check-ins) ---
                        $stmt_get_ach_counts = $pdo->prepare("
                            SELECT id, criteria_value 
                            FROM achievements 
                            WHERE criteria_type = 'classes_checked_in_count' 
                              AND is_manual_claim = 0 
                            ORDER BY criteria_value ASC
                        ");
                        $stmt_get_ach_counts->execute();
                        $ach_count_all_data = $stmt_get_ach_counts->fetchAll(PDO::FETCH_ASSOC);

                        if ($ach_count_all_data) {
                            foreach ($ach_count_all_data as $ach_count_data_item) {
                                $achievement_count_id = $ach_count_data_item['id'];
                                $required_count = (int)$ach_count_data_item['criteria_value'];

                                // Verificar se o cliente já ganhou ESTA conquista específica
                                $stmt_check_earned_specific = $pdo->prepare("SELECT id FROM client_achievements WHERE client_id = ? AND achievement_id = ?");
                                $stmt_check_earned_specific->execute([$client_id, $achievement_count_id]);
                                
                                if (!$stmt_check_earned_specific->fetch()) { // Se ainda não ganhou ESTA conquista
                                    if ($total_checkins_count >= $required_count) {
                                        $stmt_award = $pdo->prepare("INSERT INTO client_achievements (client_id, achievement_id, earned_at) VALUES (?, ?, NOW())");
                                        $stmt_award->execute([$client_id, $achievement_count_id]);
                                        error_log("Conquista 'classes_checked_in_count' (ID: $achievement_count_id, Req: $required_count) atribuída ao cliente_id: " . $client_id);
                                    }
                                }
                            }
                        }
                    }
                } catch (PDOException $e) { error_log("Erro BD ao verificar/atribuir conquistas de check-in: " . $e->getMessage()); }
            }
            // --- FIM Lógica de Atribuição de Conquistas ---

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
