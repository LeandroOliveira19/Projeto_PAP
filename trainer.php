<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainer') {
    header('Location: login.php');
    exit;
}
include 'db.php';
include 'header.php';
?>

<div class="container mt-5">
    <h2>Bem-vindo, Treinador!</h2>
    <div class="row">
        <div class="col-md-12">
            <h4>Clientes e Planos de Treino Ativos</h4>
            <a href="add_workout_plan.php" class="btn btn-success mb-3">Adicionar Plano de Treino</a>
            <a href="client_history.php" class="btn btn-secondary mb-3">Histórico de Clientes</a>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th style="width: 30%;">Plano de Treino</th>
                        <th style="width: 40%;">Exercícios</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->prepare("SELECT c.id AS client_id, u.name AS client_name, wp.plan_details, wp.id AS workout_plan_id, wp.is_active
                                           FROM clients c 
                                           JOIN users u ON c.user_id = u.id 
                                           LEFT JOIN workout_plans wp ON c.id = wp.client_id 
                                           WHERE wp.trainer_id = ? AND wp.is_active = 1"); // Only active plans
                    $stmt->execute([$_SESSION['user_id']]);
                    $clients = $stmt->fetchAll();

                    if (count($clients) > 0) {
                        foreach ($clients as $client) {
                            echo "<tr>";
                            echo "<td>" . $client['client_name'] . "</td>";
                            echo "<td class='align-top'>";
                            // Display workout plan details with collapse
                            if ($client['plan_details']) {
                                $plan_details_id = "plan-details-" . $client['client_id'];
                                echo "<div class='d-flex justify-content-between align-items-center'>";
                                echo "<strong class='me-2'>Detalhes do Plano</strong>";
                                if ($client['is_active'] == 1) {
                                    echo "<span class='badge bg-success'>Ativo</span>";
                                }
                                echo "<button type='button' class='btn btn-sm btn-outline-secondary' data-bs-toggle='collapse' data-bs-target='#" . $plan_details_id . "' aria-expanded='false' aria-controls='" . $plan_details_id . "'>Mostrar</button>";
                                echo "</div>";
                                echo "<div class='collapse mt-2' id='" . $plan_details_id . "'>";
                                // Fetch and display day-by-day details
                                if ($client['workout_plan_id']) {
                                    $stmt = $pdo->prepare("SELECT wpd.day_name, wpd.day_details, do.name AS day_option_name
                                                            FROM workout_plan_days wpd
                                                            LEFT JOIN day_options do ON wpd.day_option_id = do.id
                                                            WHERE wpd.workout_plan_id = ?");
                                    $stmt->execute([$client['workout_plan_id']]);
                                    $workout_plan_days = $stmt->fetchAll();
                                    if ($workout_plan_days) {
                                        foreach ($workout_plan_days as $day) {
                                            echo "<div class='card mb-3'>";
                                            echo "<div class='card-body'>";
                                            echo "<div class='d-flex align-items-center'>";
                                            echo "<div>";
                                            echo "<h5 class='card-title'>" . htmlspecialchars($day['day_name']) . "</h5>";
                                            echo "<p class='card-text'><strong>" . htmlspecialchars($day['day_option_name']) . "</strong></p>";
                                            echo "<p class='card-text'><small>" . nl2br(htmlspecialchars($day['day_details'])) . "</small></p>";
                                            echo "</div>";
                                            echo "</div>";
                                            echo "</div>";
                                            echo "</div>";
                                        }
                                    } else {
                                        echo "<p><small>Nenhum detalhe diário encontrado.</small></p>";
                                    }
                                }
                                echo "</div>";
                            } else {
                                echo "Nenhum plano cadastrado";
                            }
                            echo "</td>";
                            echo "<td class='align-top'>";
                            // Fetch and display exercises for this workout plan
                            if ($client['workout_plan_id']) {
                                $stmt = $pdo->prepare("SELECT e.id, e.name, e.description, e.muscle_group, e.equipment FROM workout_plan_exercises wpe JOIN exercises e ON wpe.exercise_id = e.id WHERE wpe.workout_plan_id = ?");
                                $stmt->execute([$client['workout_plan_id']]);
                                $exercises = $stmt->fetchAll();
                                if (count($exercises) > 0) {
                                    echo "<ul class='list-group list-group-flush'>";
                                    foreach ($exercises as $exercise) {
                                        // Use the exercise ID to create a unique ID
                                        $exercise_details_id = "exercise-details-" . $exercise['id'];
                                        echo "<li class='list-group-item p-1'>";
                                        echo "<div class='d-flex justify-content-between align-items-center'>";
                                        echo "<strong class='me-2'>" . htmlspecialchars($exercise['name']) . "</strong>";
                                        // Use the unique ID in the button's data-bs-target
                                        echo "<button type='button' class='btn btn-sm btn-outline-secondary' data-bs-toggle='collapse' data-bs-target='#" . $exercise_details_id . "' aria-expanded='false' aria-controls='" . $exercise_details_id . "'>Detalhes</button>";
                                        echo "</div>";
                                        // Use the unique ID in the details div's id
                                        echo "<div class='collapse mt-2' id='" . $exercise_details_id . "'>";
                                        echo "<small>Descrição: " . htmlspecialchars($exercise['description']) . "</small><br>";
                                        echo "<small>Grupo Muscular: " . htmlspecialchars($exercise['muscle_group']) . "</small><br>";
                                        echo "<small>Equipamento: " . htmlspecialchars($exercise['equipment']) . "</small>";
                                        echo "</div>";
                                        echo "</li>";
                                    }
                                    echo "</ul>";
                                } else {
                                    echo "Nenhum exercício cadastrado.";
                                }
                            } else {
                                echo "Nenhum exercício cadastrado.";
                            }
                            echo "</td>";
                            echo "<td>
                                    <a href='edit_workout_plan.php?client_id=" . $client['client_id'] . "&workout_plan_id=" . ($client['workout_plan_id'] ?? '') . "' class='btn btn-warning btn-sm'>Editar</a>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' class='text-center'>Nenhum cliente ativo</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
