<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainer') {
    header('Location: login.php');
    exit;
}
include 'db.php';
include 'header.php';

// *** ADICIONADO: Define a página ativa para a sidebar ***
$active_page = basename($_SERVER['PHP_SELF']); // Será 'client_history.php'

// *** ADICIONADO: Inclui a sidebar do trainer ***
include 'sidebar_trainer.php';
?>

<!-- *** ADICIONADO: Wrapper <main> *** -->
<main class="main-content">
    <div class="container mt-5">
        <h2>Histórico de Clientes</h2>
        <div class="row">
            <div class="col-md-12">
                <a href="trainer.php" class="btn btn-secondary mb-3">Voltar para Clientes Ativos</a>
                <div class="table-responsive"> <!-- Adicionado para melhor visualização em telas pequenas -->
                    <table class="table table-bordered table-hover"> <!-- Adicionado table-hover -->
                        <thead class="table-light"> <!-- Cabeçalho com fundo claro -->
                            <tr>
                                <th>Cliente</th>
                                <th>Plano de Treino (Inativo)</th>
                                <th>Exercícios</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->prepare("
                                SELECT
                                    c.id AS client_id,
                                    u.name AS client_name,
                                    wp.plan_details,
                                    wp.id AS workout_plan_id,
                                    wp.is_active,
                                    wp.created_at -- Adicionado para mostrar data
                                FROM clients c
                                JOIN users u ON c.user_id = u.id
                                JOIN workout_plans wp ON c.id = wp.client_id -- JOIN normal, pois só queremos clientes com planos inativos
                                WHERE wp.trainer_id = :trainer_id AND wp.is_active = 0 -- Apenas planos inativos deste trainer
                                ORDER BY u.name, wp.created_at DESC -- Ordena por nome e depois por data do plano
                            ");
                            $stmt->execute(['trainer_id' => $_SESSION['user_id']]);
                            $clients_history = $stmt->fetchAll(); // Renomeado para clareza

                            if (count($clients_history) > 0) {
                                foreach ($clients_history as $client) {
                                    echo "<tr>";
                                    echo "<td class='align-top'>" . htmlspecialchars($client['client_name']) . "</td>";

                                    // --- Coluna Plano de Treino (Inativo) ---
                                    echo "<td class='align-top'>";
                                    $plan_details_id = "plan-details-" . $client['workout_plan_id']; // Usa ID do plano para ser único
                                    echo "<div class='d-flex justify-content-between align-items-center mb-2'>";
                                    echo "<div>";
                                    echo "<strong class='me-2'>Criado em:</strong> " . date('d/m/Y', strtotime($client['created_at']));
                                    echo "<span class='badge bg-secondary ms-2'>Inativo</span>";
                                    echo "</div>";
                                    echo "<button type='button' class='btn btn-sm btn-outline-secondary' data-bs-toggle='collapse' data-bs-target='#" . $plan_details_id . "' aria-expanded='false' aria-controls='" . $plan_details_id . "'>Mostrar Detalhes</button>";
                                    echo "</div>";
                                    echo "<div class='collapse mt-2' id='" . $plan_details_id . "'>";

                                    // Detalhes gerais do plano (observações)
                                    if (!empty($client['plan_details'])) {
                                        echo "<h6>Observações:</h6>";
                                        echo "<p class='text-muted small'>" . nl2br(htmlspecialchars($client['plan_details'])) . "</p>";
                                    }

                                    // Detalhes dia-a-dia
                                    $stmt_days = $pdo->prepare("
                                        SELECT wpd.day_name, wpd.day_details, do.name AS day_option_name
                                        FROM workout_plan_days wpd
                                        LEFT JOIN day_options do ON wpd.day_option_id = do.id
                                        WHERE wpd.workout_plan_id = ?
                                        ORDER BY FIELD(wpd.day_name, 'Dia 1', 'Dia 2', 'Dia 3', 'Dia 4', 'Dia 5', 'Dia 6', 'Dia 7')
                                    ");
                                    $stmt_days->execute([$client['workout_plan_id']]);
                                    $workout_plan_days = $stmt_days->fetchAll();
                                    if ($workout_plan_days) {
                                        echo "<h6 class='mt-3'>Detalhes Diários:</h6>";
                                        foreach ($workout_plan_days as $day) {
                                            echo "<div class='card mb-2 shadow-sm'>";
                                            echo "<div class='card-body p-2'>";
                                            echo "<h6 class='card-title mb-1'>" . htmlspecialchars($day['day_name']) . "</h6>";
                                            echo "<p class='card-text mb-1'><small><strong>" . htmlspecialchars($day['day_option_name'] ?? 'N/A') . "</strong></small></p>";
                                            if (!empty($day['day_details'])) {
                                                echo "<p class='card-text text-muted small'>" . nl2br(htmlspecialchars($day['day_details'])) . "</p>";
                                            }
                                            echo "</div>";
                                            echo "</div>";
                                        }
                                    } else {
                                        echo "<p class='text-muted small mt-2'>Nenhum detalhe diário encontrado para este plano.</p>";
                                    }
                                    echo "</div>"; // End collapse
                                    echo "</td>";
                                    // --- FIM Coluna Plano de Treino ---

                                    // --- Coluna Exercícios ---
                                    echo "<td class='align-top'>";
                                    $stmt_exercises = $pdo->prepare("
                                        SELECT e.id, e.name, e.description, e.muscle_group, e.equipment
                                        FROM workout_plan_exercises wpe
                                        JOIN exercises e ON wpe.exercise_id = e.id
                                        WHERE wpe.workout_plan_id = ?
                                        ORDER BY e.name
                                    ");
                                    $stmt_exercises->execute([$client['workout_plan_id']]);
                                    $exercises = $stmt_exercises->fetchAll();

                                    if (count($exercises) > 0) {
                                        echo "<ul class='list-group list-group-flush'>";
                                        foreach ($exercises as $exercise) {
                                            $exercise_details_id = "exercise-details-" . $client['workout_plan_id'] . "-" . $exercise['id']; // ID único
                                            echo "<li class='list-group-item p-1'>";
                                            echo "<div class='d-flex justify-content-between align-items-center'>";
                                            echo "<span class='me-2 small'>" . htmlspecialchars($exercise['name']) . "</span>";
                                            echo "<button type='button' class='btn btn-sm btn-outline-secondary py-0 px-1' data-bs-toggle='collapse' data-bs-target='#" . $exercise_details_id . "' aria-expanded='false' aria-controls='" . $exercise_details_id . "'><i class='fas fa-info-circle'></i></button>";
                                            echo "</div>";
                                            echo "<div class='collapse mt-1' id='" . $exercise_details_id . "'>";
                                            echo "<small class='d-block text-muted'>Grupo: " . htmlspecialchars($exercise['muscle_group']) . "</small>";
                                            echo "<small class='d-block text-muted'>Equip.: " . htmlspecialchars($exercise['equipment']) . "</small>";
                                            // echo "<small class='d-block text-muted'>Desc: " . htmlspecialchars($exercise['description']) . "</small>";
                                            echo "</div>";
                                            echo "</li>";
                                        }
                                        echo "</ul>";
                                    } else {
                                        echo "<span class='text-muted small'>Nenhum exercício associado.</span>";
                                    }
                                    echo "</td>";
                                    // --- FIM Coluna Exercícios ---

                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3' class='text-center text-muted'>Nenhum plano inativo encontrado para seus clientes.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div> <!-- /.table-responsive -->
            </div>
        </div>
    </div>
</main> <!-- *** FIM: Wrapper <main> *** -->

<?php include 'footer.php'; ?>
