<?php
// filepath: c:\xampp\htdocs\Projeto_PAP\save_mood.php
session_start();
header('Content-Type: application/json');

// Verificar se o utilizador está logado
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    echo json_encode(['status' => 'error', 'message' => 'Acesso não autorizado.']);
    exit;
}

// Verificar se os dados foram enviados via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método de pedido inválido.']);
    exit;
}

include 'db.php'; // Conexão com o banco de dados

$user_id = $_SESSION['user_id'];
$mood_value = filter_input(INPUT_POST, 'mood_value', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
$assessment_date_str = $_POST['assessment_date'] ?? null;

// Validar data
$assessment_date = null;
if ($assessment_date_str) {
    $date_obj = DateTime::createFromFormat('Y-m-d', $assessment_date_str);
    if ($date_obj && $date_obj->format('Y-m-d') === $assessment_date_str) {
        $assessment_date = $assessment_date_str;
    }
}

if (!$mood_value) {
    echo json_encode(['status' => 'error', 'message' => 'Valor de humor inválido.']);
    exit;
}

if (!$assessment_date) {
    echo json_encode(['status' => 'error', 'message' => 'Data da avaliação inválida.']);
    exit;
}

try {
    // Tenta inserir ou atualizar o registo de humor para o utilizador e data
    // A constraint UNIQUE na tabela (user_id, assessment_date) tratará disso
    $stmt = $pdo->prepare("
        INSERT INTO mood_logs (user_id, mood_value, assessment_date)
        VALUES (:user_id, :mood_value, :assessment_date)
        ON DUPLICATE KEY UPDATE mood_value = :mood_value_update
    ");

    $stmt->execute([
        ':user_id' => $user_id,
        ':mood_value' => $mood_value,
        ':assessment_date' => $assessment_date,
        ':mood_value_update' => $mood_value // Valor para o caso de UPDATE
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Humor registado com sucesso!']);
    } else {
        // Se rowCount for 0 num INSERT ON DUPLICATE KEY UPDATE, pode significar que os valores eram os mesmos
        // e não houve alteração real, o que ainda é um "sucesso" do ponto de vista do utilizador.
        echo json_encode(['status' => 'success', 'message' => 'Humor já estava registado com este valor para hoje.']);
    }

} catch (PDOException $e) {
    error_log("Erro ao guardar humor: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Erro ao guardar o seu humor no banco de dados.']);
}
?>
