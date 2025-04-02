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
    <h2>Plano Alimentar</h2>
    <?php
    // Consulta o plano alimentar do cliente
    $stmt = $pdo->prepare("SELECT dp.plan_details, u.name AS nutritionist_name 
                           FROM diet_plans dp 
                           JOIN users u ON dp.nutritionist_id = u.id 
                           WHERE dp.client_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $diet_plan = $stmt->fetch();

    if ($diet_plan) {
        // Exibe o nome do nutricionista e os detalhes do plano alimentar
        echo "<p><strong>Nutricionista:</strong> " . htmlspecialchars($diet_plan['nutritionist_name']) . "</p>";
        echo "<p>" . nl2br(htmlspecialchars($diet_plan['plan_details'])) . "</p>";
    } else {
        // Mensagem se não houver plano alimentar cadastrado
        echo "<p>Nenhum plano alimentar disponível.</p>";
    }
    ?>
    <a href="dashboard.php" class="btn btn-secondary">Voltar</a>
</div>

<?php
// Inclui o rodapé da página
include 'footer.php';
?>