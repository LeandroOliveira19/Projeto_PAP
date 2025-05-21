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

// Define qual link da sidebar deve estar ativo
$active_page = basename($_SERVER['PHP_SELF']); // Pega o nome do arquivo atual (workout_plan_history.php)
// Inclui a sidebar do cliente
include 'sidebar_client.php';
?>

<!-- Conteúdo Principal -->
<main class="main-content">
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
                echo "<p class='text-white'>Aqui pode consultar os seus planos de treino anteriores.</p>";
                echo "<div class='accordion' id='historyAccordion'>";
                foreach ($workout_plans as $plan) {
                    // Gerar um ID único para cada item do acordeão
                    $plan_accordion_id = 'collapse-' . $plan['workout_plan_id'];
                    $heading_id = 'heading-' . $plan['workout_plan_id'];
                    ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="<?= $heading_id ?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $plan_accordion_id ?>" aria-expanded="false" aria-controls="<?= $plan_accordion_id ?>">
                                <i class="fas fa-history me-2"></i>
                                Plano de <?= htmlspecialchars(date('d/m/Y', strtotime($plan['created_at']))) ?> (Treinador: <?= htmlspecialchars($plan['trainer_name']) ?>)
                            </button>
                        </h2>
                        <div id="<?= $plan_accordion_id ?>" class="accordion-collapse collapse" aria-labelledby="<?= $heading_id ?>" data-bs-parent="#historyAccordion">
                            <div class="accordion-body">
                                <h6>Observações Gerais do Plano:</h6>
                                <?php if (!empty($plan['plan_details'])): ?>
                                    <p><?= nl2br(htmlspecialchars($plan['plan_details'])) ?></p>
                                <?php else: ?>
                                    <p class="text-muted fst-italic">Nenhuma observação geral para este plano.</p>
                                <?php endif; ?>

                                <h6 class="mt-3">Exercícios Incluídos no Plano:</h6>
                                <ul class="list-unstyled">
                                    <?php
                                    // Fetch and display exercises for this workout plan
                                    $stmt_exercises = $pdo->prepare("SELECT e.name FROM workout_plan_exercises wpe JOIN exercises e ON wpe.exercise_id = e.id WHERE wpe.workout_plan_id = ? ORDER BY e.name ASC");
                                    $stmt_exercises->execute([$plan['workout_plan_id']]);
                                    $exercises = $stmt_exercises->fetchAll();
                                    if (count($exercises) > 0) {
                                        foreach ($exercises as $exercise) {
                                            echo "<li><i class='fas fa-check me-2 text-success'></i>" . htmlspecialchars($exercise['name']) . "</li>";
                                        }
                                    } else {
                                        echo "<p class='text-muted fst-italic'>Nenhum exercício associado a este plano.</p>";
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                echo "</div>"; // Fim do Accordion
            } else {
                echo "<p class='alert alert-info text-center'>Nenhum plano de treino antigo encontrado.</p>";
            }
        } else {
            echo "<p class='alert alert-danger text-center'>Erro: Cliente não encontrado.</p>";
        }
        ?>

        <div class="text-center mt-4">
            <a href="workout_hub.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar ao Plano Ativo</a>
        </div>
    </div>
</main>

<?php
// Inclui o rodapé da página
include 'footer.php';
?>
