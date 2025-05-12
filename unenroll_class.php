<?php
// filepath: c:\xampp\htdocs\Projeto_PAP\unenroll_class.php

// --- Configuração de Erros para AJAX ---
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json'); // Definir o header aqui

// Função para enviar resposta JSON de erro e sair
function sendJsonError($message, $logMessage = null) {
    if ($logMessage) {
        error_log($logMessage);
    } else {
        error_log("Erro em unenroll_class.php: " . $message);
    }
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

try {
    session_start();

    // Verifica se o usuário está logado
    if (!isset($_SESSION['user_id'])) {
        sendJsonError('Utilizador não autenticado. Faça login novamente.');
    }

    // Verifica se o pedido é POST e se class_id foi enviado
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['class_id'])) {
        sendJsonError('Pedido inválido.');
    }

    $class_id = filter_var($_POST['class_id'], FILTER_VALIDATE_INT);
    $user_id = $_SESSION['user_id'];

    if (!$class_id) {
        sendJsonError('ID da aula inválido.');
    }

    // Inclui a conexão com o banco de dados
    // Se db.php tiver um erro fatal, o catch global abaixo pode não apanhar,
    // mas o display_errors=0 deve impedir que polua a saída.
    include 'db.php'; // Este include é um ponto crítico. Se falhar, pode não haver JSON.

    // Opcional, mas recomendado: Verificar se a aula ainda não ocorreu
    $stmt_check_class = $pdo->prepare("SELECT schedule FROM classes WHERE id = ?");
    $stmt_check_class->execute([$class_id]);
    $class_info = $stmt_check_class->fetch();

    if (!$class_info) {
        sendJsonError('Aula não encontrada.');
    }

    if (strtotime($class_info['schedule']) < time()) {
        sendJsonError('Não é possível cancelar a inscrição numa aula que já ocorreu.');
    }

    // Tenta remover a inscrição do usuário da aula
    $stmt = $pdo->prepare("DELETE FROM class_participants WHERE class_id = :class_id AND user_id = :user_id");
    $stmt->execute(['class_id' => $class_id, 'user_id' => $user_id]);

    // Verifica se alguma linha foi realmente deletada
    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Inscrição cancelada com sucesso!']);
    } else {
        sendJsonError('Você não estava inscrito nesta aula ou a inscrição já foi cancelada.');
    }

} catch (PDOException $e) {
    // Em caso de erro no banco de dados
    sendJsonError('Ocorreu um erro ao tentar cancelar a inscrição. Tente novamente.', "PDOException ao cancelar inscrição (unenroll_class.php): " . $e->getMessage());
} catch (Throwable $t) {
    // Captura outros erros e exceções (incluindo alguns erros fatais se o PHP permitir)
    sendJsonError('Ocorreu um erro inesperado no servidor.', "Throwable em unenroll_class.php: " . $t->getMessage() . " em " . $t->getFile() . " na linha " . $t->getLine());
}
// Não deve haver mais nada aqui para garantir que a saída é apenas JSON
?>
