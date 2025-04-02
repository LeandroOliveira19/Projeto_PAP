<?php
// Inicia a sessão
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
    <h2>Central de Treino</h2>
    <?php
    // Fetch the client's ID
    $stmt = $pdo->prepare("SELECT id FROM clients WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $client = $stmt->fetch();

    if ($client) {
        $client_id = $client['id'];

        // Fetch the active workout plan ID and trainer's name
        $stmt = $pdo->prepare("SELECT wp.id, u.name AS trainer_name
                                FROM workout_plans wp
                                JOIN users u ON wp.trainer_id = u.id
                                WHERE wp.client_id = ? AND wp.is_active = 1");
        $stmt->execute([$client_id]);
        $workout_plan = $stmt->fetch();

        if ($workout_plan) {
            // Display Trainer's Name prominently
            echo "<div class='alert alert-info' role='alert'>";
            echo "<strong>Seu Treinador:</strong> " . htmlspecialchars($workout_plan['trainer_name']);
            echo "</div>";
        }
    }
    ?>
    <ul class="nav nav-tabs" id="myTab">
        <li class="nav-item">
            <a class="nav-link active" id="personal-plan-tab" data-bs-toggle="tab" href="#personal-plan">Plano de Treino</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="generic-plans-tab" data-bs-toggle="tab" href="#generic-plans">Detalhes do Plano</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="workout-plan-history-tab" data-bs-toggle="tab" href="workout_plan_history.php">Histórico de Planos de Treino</a>
        </li>
    </ul>

    <div class="tab-content mt-3" id="myTabContent">
        <!-- Plano de Treino -->
        <div class="tab-pane fade show active" id="personal-plan">
            <?php
            if ($client && $workout_plan) {
                // Fetch the day-by-day details
                $stmt = $pdo->prepare("SELECT wpd.day_name, wpd.day_details, do.name AS day_option_name
                                        FROM workout_plan_days wpd
                                        LEFT JOIN day_options do ON wpd.day_option_id = do.id
                                        WHERE wpd.workout_plan_id = ?");
                $stmt->execute([$workout_plan['id']]);
                $workout_plan_days = $stmt->fetchAll();

                if ($workout_plan_days) {
                    foreach ($workout_plan_days as $day) {
                        echo "<div class='card mb-3'>";
                        echo "<div class='card-body'>";
                        echo "<div class='d-flex align-items-center'>";
                        echo "<div>";
                        echo "<h5 class='card-title'>" . htmlspecialchars($day['day_name']) . "</h5>";
                        echo "<p class='card-text'><strong>" . htmlspecialchars($day['day_option_name']) . "</strong></p>";
                        echo "<p class='card-text'>" . nl2br(htmlspecialchars($day['day_details'])) . "</p>";
                        echo "</div>";
                        echo "</div>";
                        echo "</div>";
                        echo "</div>";
                    }
                } else {
                    echo "<p>Nenhum detalhe diário encontrado.</p>";
                }
            } else {
                echo "<p>Nenhum plano de treino encontrado.</p>";
            }
            ?>
        </div>

        <!-- Detalhes do Plano -->
        <div class="tab-pane fade" id="generic-plans">
            <?php
            if ($client) {
                // Consulta o plano de treino ativo do cliente
                $stmt = $pdo->prepare("SELECT wp.plan_details, u.name AS trainer_name, wp.id AS workout_plan_id
                                        FROM workout_plans wp
                                        JOIN users u ON wp.trainer_id = u.id
                                        WHERE wp.client_id = ? AND wp.is_active = 1");
                $stmt->execute([$client_id]);
                $workout_plan = $stmt->fetch();

                if ($workout_plan) {
                    // Add the "Observação" title here
                    echo "<h4>Observação:</h4>";
                    echo "<p>" . nl2br(htmlspecialchars($workout_plan['plan_details'])) . "</p>";
                    echo "<h4>Exercícios:</h4>";
                    // Check if workout_plan_id exists before using it
                    if (isset($workout_plan['workout_plan_id'])) {
                        // Fetch and display exercises for this workout plan
                        $stmt = $pdo->prepare("SELECT e.name, e.video_url, e.image FROM workout_plan_exercises wpe JOIN exercises e ON wpe.exercise_id = e.id WHERE wpe.workout_plan_id = ?");
                        $stmt->execute([$workout_plan['workout_plan_id']]);
                        $exercises = $stmt->fetchAll();
                        if (count($exercises) > 0) {
                            echo "<ul class='list-group'>";
                            foreach ($exercises as $exercise) {
                                echo "<li class='list-group-item d-flex align-items-center'>";
                                // Display exercise image
                                // Changed width and height to 90px
                                echo "<div class='me-3' style='width: 90px; height: 90px; overflow: hidden; border-radius: 5px;'>";
                                if ($exercise['image'] && file_exists("assets/img/exercises/" . $exercise['image'])) {
                                    // Added object-position: center;
                                    echo "<img src='assets/img/exercises/" . htmlspecialchars($exercise['image']) . "' alt='" . htmlspecialchars($exercise['name']) . "' class='img-fluid h-110 w-110 object-fit-cover' style='object-position: center;'>";
                                } else {
                                    // Added object-position: center;
                                    echo "<img src='assets/img/exercises/default.jpg' alt='Default Exercise Image' class='img-fluid h-100 w-100 object-fit-cover' style='object-position: center;'>";
                                }
                                echo "</div>";
                                echo htmlspecialchars($exercise['name']);

                                // Display video link if available
                                if ($exercise['video_url']) {
                                    echo "<a href='" . htmlspecialchars($exercise['video_url']) . "' target='_blank' class='btn btn-sm btn-outline-primary ms-auto'>Ver Vídeo</a>";
                                }
                                echo "</li>";
                            }
                            echo "</ul>";
                        } else {
                            echo "<p>Nenhum exercício cadastrado.</p>";
                        }
                    } else {
                        echo "<p>Nenhum exercício cadastrado.</p>";
                    }
                } else {
                    echo "<p>Nenhum plano de treino disponível.</p>";
                }
            } else {
                echo "<p>Cliente não encontrado.</p>";
            }
            ?>
        </div>
    </div>
    <a href="dashboard.php" class="btn btn-secondary mt-3">Voltar</a>
</div>
<script>
    $(document).ready(function() {
        // Show the first tab on load
         $('#myTab li:first-child a').tab('show');
    });
</script>

<?php
// Inclui o rodapé da página
include 'footer.php';
?>
