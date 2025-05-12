<?php
session_start();

// Verifica se o usuário está logado e se é um trainer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainer') {
    header('Location: login.php');
    exit;
}

// Inclui a conexão com o banco de dados
include 'db.php';

// Inclui o cabeçalho da página
include 'header.php'; // header.php deve ter <link rel="stylesheet" href="assets/css/dashboard_cliente.css"> ou similar

// Pega o nome do usuário da sessão
$trainer_name = $_SESSION['name'] ?? 'Treinador'; // Usa 'Treinador' como fallback

// Define qual link da sidebar deve estar ativo
$active_page = basename($_SERVER['PHP_SELF']); // Pega o nome do arquivo atual (ex: trainer.php)

?>

<!-- A classe dashboard-body já está no <body> incluído pelo header.php -->

<?php include 'sidebar_trainer.php'; ?>

<!-- Conteúdo Principal -->
<main class="main-content">
    <div class="container mt-5"> <!-- Mantém o container para espaçamento interno -->
        <h2>Bem-vindo, <?= htmlspecialchars($trainer_name) ?>!</h2>
        <div class="row">
            <div class="col-md-12">
                <h4>Clientes Ativos e Planos de Treino</h4>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Plano de Treino Ativo</th>
                                <th>Exercícios</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch clients and their *active* workout plans assigned to this trainer
                            // ***** MODIFICAÇÃO 1: Adicionar u.id AS client_user_id *****
                            $stmt = $pdo->prepare("
                                SELECT
                                    c.id AS client_id,
                                    u.id AS client_user_id, -- <<< ADICIONADO AQUI
                                    u.name AS client_name,
                                    wp.id AS workout_plan_id,
                                    wp.plan_details,
                                    wp.is_active
                                FROM clients c
                                JOIN users u ON c.user_id = u.id
                                LEFT JOIN workout_plans wp ON c.id = wp.client_id AND wp.trainer_id = :trainer_id AND wp.is_active = 1
                                ORDER BY u.name
                            ");
                            // **********************************************************
                            $stmt->execute(['trainer_id' => $_SESSION['user_id']]);
                            $clients = $stmt->fetchAll();

                            if (count($clients) > 0) {
                                foreach ($clients as $client) {
                                    echo "<tr>";
                                    echo "<td class='align-top'>" . htmlspecialchars($client['client_name']) . "</td>";

                                    // --- Coluna Plano de Treino (SEU CÓDIGO EXISTENTE) ---
                                    echo "<td class='align-top'>";
                                    if ($client['workout_plan_id']) {
                                        $plan_details_id = "plan-details-" . $client['client_id']; // Unique ID for collapse
                                        echo "<div class='d-flex justify-content-between align-items-center'>";
                                        echo "<strong class='me-2'>Detalhes do Plano</strong>";
                                        echo "<span class='badge bg-success'>Ativo</span>"; // Always active here
                                        echo "<button type='button' class='btn btn-sm btn-outline-secondary' data-bs-toggle='collapse' data-bs-target='#" . $plan_details_id . "' aria-expanded='false' aria-controls='" . $plan_details_id . "'>Mostrar</button>";
                                        echo "</div>";
                                        echo "<div class='collapse mt-2' id='" . $plan_details_id . "'>";
                                        // Fetch and display day-by-day details
                                        $stmt_days = $pdo->prepare("
                                            SELECT wpd.day_name, wpd.day_details, do.name AS day_option_name
                                            FROM workout_plan_days wpd
                                            LEFT JOIN day_options do ON wpd.day_option_id = do.id
                                            WHERE wpd.workout_plan_id = ?
                                            ORDER BY FIELD(wpd.day_name, 'Dia 1', 'Dia 2', 'Dia 3', 'Dia 4', 'Dia 5', 'Dia 6', 'Dia 7') -- Optional: Order days correctly
                                        ");
                                        $stmt_days->execute([$client['workout_plan_id']]);
                                        $workout_plan_days = $stmt_days->fetchAll();
                                        if ($workout_plan_days) {
                                            foreach ($workout_plan_days as $day) {
                                                echo "<div class='card mb-2 shadow-sm'>"; // Smaller card, subtle shadow
                                                echo "<div class='card-body p-2'>"; // Less padding
                                                echo "<h6 class='card-title mb-1'>" . htmlspecialchars($day['day_name']) . "</h6>"; // Smaller title
                                                echo "<p class='card-text mb-1'><strong>" . htmlspecialchars($day['day_option_name'] ?? 'N/A') . "</strong></p>"; // Show option name
                                                if (!empty($day['day_details'])) {
                                                    echo "<p class='card-text text-muted small'>" . nl2br(htmlspecialchars($day['day_details'])) . "</p>"; // Smaller, muted text
                                                }
                                                echo "</div>";
                                                echo "</div>";
                                            }
                                        } else {
                                            echo "<p class='text-muted small'>Nenhum detalhe diário encontrado.</p>";
                                        }
                                        echo "</div>"; // End collapse
                                    } else {
                                        echo "<span class='text-muted'>Nenhum plano ativo cadastrado</span>";
                                    }
                                    echo "</td>";
                                    // --- FIM Coluna Plano de Treino ---


                                    // --- Coluna Exercícios (SEU CÓDIGO EXISTENTE) ---
                                    echo "<td class='align-top'>";
                                    if ($client['workout_plan_id']) {
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
                                            echo "<ul class='list-group list-group-flush'>"; // Flush list group for less borders
                                            foreach ($exercises as $exercise) {
                                                $exercise_details_id = "exercise-details-" . $client['workout_plan_id'] . "-" . $exercise['id']; // More specific unique ID
                                                echo "<li class='list-group-item p-1'>"; // Less padding
                                                echo "<div class='d-flex justify-content-between align-items-center'>";
                                                echo "<span class='me-2 small'>" . htmlspecialchars($exercise['name']) . "</span>"; // Smaller text
                                                echo "<button type='button' class='btn btn-sm btn-outline-secondary py-0 px-1' data-bs-toggle='collapse' data-bs-target='#" . $exercise_details_id . "' aria-expanded='false' aria-controls='" . $exercise_details_id . "'><i class='fas fa-info-circle'></i></button>"; // Icon button
                                                echo "</div>";
                                                echo "<div class='collapse mt-1' id='" . $exercise_details_id . "'>";
                                                echo "<small class='d-block text-muted'>Grupo: " . htmlspecialchars($exercise['muscle_group']) . "</small>";
                                                echo "<small class='d-block text-muted'>Equip.: " . htmlspecialchars($exercise['equipment']) . "</small>";
                                                // echo "<small class='d-block text-muted'>Desc: " . htmlspecialchars($exercise['description']) . "</small>"; // Description might be too long
                                                echo "</div>";
                                                echo "</li>";
                                            }
                                            echo "</ul>";
                                        } else {
                                            echo "<span class='text-muted small'>Nenhum exercício associado.</span>";
                                        }
                                    } else {
                                        echo "<span class='text-muted small'>N/A (sem plano ativo)</span>";
                                    }
                                    echo "</td>";
                                    // --- FIM Coluna Exercícios ---


                                    // --- Coluna Ações (COM O NOVO BOTÃO INTEGRADO) ---
                                    echo "<td class='align-top text-center'>"; // Adicionado text-center para alinhar os botões

                                    // Botão Editar/Criar Plano
                                    if ($client['workout_plan_id']) {
                                        echo "<a href='edit_workout_plan.php?client_id=" . $client['client_id'] . "&workout_plan_id=" . $client['workout_plan_id'] . "' class='btn btn-warning btn-sm mb-1 d-block text-nowrap' title='Editar Plano de Treino'><i class='fas fa-edit me-1'></i> <span class='d-none d-md-inline'>Editar Plano</span></a>";
                                    } else {
                                        echo "<a href='add_workout_plan.php?client_id=" . $client['client_id'] . "' class='btn btn-primary btn-sm mb-1 d-block text-nowrap' title='Criar Plano de Treino'><i class='fas fa-plus me-1'></i> <span class='d-none d-md-inline'>Criar Plano</span></a>";
                                    }

                                    // *** NOVO BOTÃO AVALIAÇÃO ADICIONADO AQUI ***
                                    echo "<a href='edit_client_assessment.php?client_id=" . $client['client_id'] . "' class='btn btn-info btn-sm mb-1 d-block text-nowrap' title='Editar Avaliação Física'><i class='fas fa-clipboard-list me-1'></i> <span class='d-none d-md-inline'>Avaliação</span></a>";
                                    // ********************************************

                                    // Botão Chat
                                    if (isset($client['client_user_id'])) {
                                        echo "<a href='chat.php?user_id=" . $client['client_user_id'] . "' class='btn btn-success btn-sm mb-1 d-block text-nowrap' title='Conversar com Cliente'><i class='fas fa-comments me-1'></i> <span class='d-none d-md-inline'>Chat</span></a>";
                                    }

                                    echo "</td>";
                                    // --- FIM Coluna Ações ---

                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' class='text-center'>Nenhum cliente encontrado ou associado a você.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div> <!-- /.table-responsive -->
            </div>
        </div>
    </div> <!-- /.container -->
</main> <!-- /.main-content -->

<?php
// Inclui o rodapé da página
include 'footer.php';
?>
