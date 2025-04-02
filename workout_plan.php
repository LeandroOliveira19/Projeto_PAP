<?php
// Inicia a sessão
session_start();

// Verifica se o usuário está logado e se é um cliente
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php'); // Redireciona para a página de login se não estiver logado
    exit;
}

// Inclui a conexão com o banco de dados
include 'db.php';

// Inclui o cabeçalho da página
include 'header.php';
?>

<div class="container mt-5">
    <h2>Plano de Treino</h2>
    <?php
    // Consulta o plano de treino do cliente
    $stmt = $pdo->prepare("SELECT wp.plan_details, u.name AS trainer_name 
                           FROM workout_plans wp 
                           JOIN users u ON wp.trainer_id = u.id 
                           WHERE wp.client_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $workout_plan = $stmt->fetch();

    if ($workout_plan) {
        // Exibe o nome do personal trainer e os detalhes do plano de treino
        echo "<p><strong>Personal Trainer:</strong> " . htmlspecialchars($workout_plan['trainer_name']) . "</p>";
        echo "<p>" . nl2br(htmlspecialchars($workout_plan['plan_details'])) . "</p>";
    } else {
        // Mensagem se não houver plano de treino cadastrado
        echo "<p>Nenhum plano de treino disponível.</p>";
    }
    ?>
    <a href="dashboard.php" class="btn btn-secondary">Voltar</a>
</div>

<?php
// Inclui o rodapé da página
include 'footer.php';
?>