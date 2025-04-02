<?php
session_start();

// Verifica se o usuário está logado e se é um cliente
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

// Inclui a conexão com o banco de dados
include 'db.php';

// Inclui o cabeçalho da página
include 'header.php';
?>

<div class="container mt-5">
    <h2>Histórico de Planos de Treino</h2>
    <?php
    // Fetch the client's ID
    $stmt = $pdo->prepare("SELECT id FROM clients WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $client = $stmt->fetch();

    if ($client) {
        $client_id = $client['id'];

        // Fetch all inactive workout plans for the client
        $stmt = $pdo->prepare("SELECT wp.plan_details, u.name AS trainer_name, wp.created_at, wp.id AS workout_plan_id
                                FROM workout_plans wp
                                JOIN users u ON wp.trainer_id = u.id
                                WHERE wp.client_id = ? AND wp.is_active = 0
                                ORDER BY wp.created_at DESC");
        $stmt->execute([$client_id]);
        $workout_plans = $stmt->fetchAll();

        if ($workout_plans) {
            foreach ($workout_plans as $plan) {
                echo "<div class='card mb-3'>";
                echo "<div class='card-body'>";
                echo "<h5 class='card-title'>Plano de Treino</h5>";
                echo "<p class='card-text'><strong>Treinador:</strong> " . htmlspecialchars($plan['trainer_name']) . "</p>";
                echo "<p class='card-text'><strong>Data de Criação:</strong> " . date('d/m/Y', strtotime($plan['created_at'])) . "</p>";
                echo "<p class='card-text'>" . nl2br(htmlspecialchars($plan['plan_details'])) . "</p>";
                echo "<h4>Exercícios:</h4>";
                // Fetch and display exercises for this workout plan
                $stmt = $pdo->prepare("SELECT e.name FROM workout_plan_exercises wpe JOIN exercises e ON wpe.exercise_id = e.id WHERE wpe.workout_plan_id = ?");
                $stmt->execute([$plan['workout_plan_id']]);
                $exercises = $stmt->fetchAll();
                if (count($exercises) > 0) {
                    foreach ($exercises as $exercise) {
                        echo htmlspecialchars($exercise['name']) . "<br>";
                    }
                } else {
                    echo "<p>Nenhum exercício cadastrado.</p>";
                }
                echo "</div>";
                echo "</div>";
            }
        } else {
            echo "<p>Nenhum plano de treino antigo encontrado.</p>";
        }
    } else {
        echo "<p>Cliente não encontrado.</p>";
    }
    ?>
    <a href="workout_hub.php" class="btn btn-secondary mt-3">Voltar</a>
</div>

<?php
// Inclui o rodapé da página
include 'footer.php';
?>
