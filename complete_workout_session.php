<?php
// complete_workout_session.php (Exemplo Simplificado)
session_start();
include 'db.php'; // Sua conexão PDO

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    // Redirecionar ou tratar erro
    exit;
}

$user_id = $_SESSION['user_id'];
$client_id = null; // Você precisará obter o client_id da tabela 'clients'
$workout_session_id = $_POST['workout_session_id'] ?? null; // ID da sessão de treino concluída

// Obter client_id (assumindo que você tem uma tabela 'clients' ligada a 'users')
$stmt_get_client = $pdo->prepare("SELECT id FROM clients WHERE user_id = ?");
$stmt_get_client->execute([$user_id]);
$client_id = $stmt_get_client->fetchColumn();

if (!$client_id || !$workout_session_id) {
    // Tratar erro: cliente não encontrado ou ID da sessão de treino em falta
    exit;
}

// Lógica para marcar a sessão de treino como completa na sua base de dados...
// ... (seu código existente aqui) ...
// Exemplo:
// $stmt_complete = $pdo->prepare("UPDATE workout_sessions SET status = 'completed', completed_at = NOW() WHERE id = ? AND client_id = ?");
// $stmt_complete->execute([$workout_session_id, $client_id]);


// Após marcar o treino como completo com sucesso, verificar conquistas:
if ($client_id) { // Certifique-se de que temos um client_id válido

    // --- Conquista: "Pioneiro Fitness" (Primeiro Treino Completo) ---
    $achievement_pioneiro_id = null; // ID da conquista "Pioneiro Fitness" na tabela 'achievements'

    // 1. Obter o ID da conquista "Pioneiro Fitness" (assumindo que criteria_type é 'first_workout')
    $stmt_get_ach_pioneiro = $pdo->prepare("SELECT id FROM achievements WHERE criteria_type = 'first_workout_completed' AND is_manual_claim = 0 LIMIT 1");
    $stmt_get_ach_pioneiro->execute();
    $achievement_pioneiro_id = $stmt_get_ach_pioneiro->fetchColumn();

    if ($achievement_pioneiro_id) {
        // 2. Verificar se o cliente já ganhou esta conquista
        $stmt_check_earned_pioneiro = $pdo->prepare("SELECT id FROM client_achievements WHERE client_id = ? AND achievement_id = ?");
        $stmt_check_earned_pioneiro->execute([$client_id, $achievement_pioneiro_id]);

        if (!$stmt_check_earned_pioneiro->fetch()) { // Se ainda não ganhou
            // 3. Verificar se este é realmente o primeiro treino completo do cliente
            // (Esta query depende de como você armazena os treinos concluídos)
            $stmt_count_completed_workouts = $pdo->prepare("
                SELECT COUNT(id) 
                FROM workout_sessions -- Ou sua tabela de treinos concluídos
                WHERE client_id = ? AND status = 'completed' 
            ");
            $stmt_count_completed_workouts->execute([$client_id]);
            $completed_workouts_count = (int)$stmt_count_completed_workouts->fetchColumn();

            if ($completed_workouts_count === 1) { // Se este é o primeiro
                // 4. Atribuir a conquista
                $stmt_award_pioneiro = $pdo->prepare("INSERT INTO client_achievements (client_id, achievement_id, earned_at) VALUES (?, ?, NOW())");
                $stmt_award_pioneiro->execute([$client_id, $achievement_pioneiro_id]);
                // Opcional: Adicionar uma mensagem de sucesso à sessão para mostrar ao utilizador
                $_SESSION['achievement_unlocked'] = "Nova conquista desbloqueada: Pioneiro Fitness!";
            }
        }
    }

    // --- Conquista: "Maratonista de Treinos" (Completou 10 treinos) ---
    // (Lógica similar, mas verificaria se $completed_workouts_count >= 10)
    $achievement_maratonista_id = null;
    $stmt_get_ach_maratonista = $pdo->prepare("SELECT id, criteria_value FROM achievements WHERE criteria_type = 'workouts_completed_count' AND is_manual_claim = 0 LIMIT 1");
    $stmt_get_ach_maratonista->execute();
    $ach_maratonista_data = $stmt_get_ach_maratonista->fetch(PDO::FETCH_ASSOC);

    if ($ach_maratonista_data) {
        $achievement_maratonista_id = $ach_maratonista_data['id'];
        $required_count = (int)$ach_maratonista_data['criteria_value']; // Ex: 10

        $stmt_check_earned_maratonista = $pdo->prepare("SELECT id FROM client_achievements WHERE client_id = ? AND achievement_id = ?");
        $stmt_check_earned_maratonista->execute([$client_id, $achievement_maratonista_id]);

        if (!$stmt_check_earned_maratonista->fetch()) { // Se ainda não ganhou
            // Reutilizar $completed_workouts_count da verificação anterior se aplicável,
            // ou recalcular se necessário.
            if ($completed_workouts_count >= $required_count) {
                $stmt_award_maratonista = $pdo->prepare("INSERT INTO client_achievements (client_id, achievement_id, earned_at) VALUES (?, ?, NOW())");
                $stmt_award_maratonista->execute([$client_id, $achievement_maratonista_id]);
                $_SESSION['achievement_unlocked'] = "Nova conquista desbloqueada: Maratonista de Treinos!";
            }
        }
    }
    // Adicionar mais verificações para outras conquistas automáticas aqui...
}

// Redirecionar de volta para o dashboard ou página de treino com mensagem de sucesso
// header('Location: dashboard.php');
// exit;
?>
