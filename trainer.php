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

<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-header">
        <!-- Pode ser um texto ou uma imagem -->
        <a href="trainer.php" class="logo">MyWellness - Trainer</a>
        <!-- <img src="assets/img/logo-light.png" alt="Logo" style="max-width: 150px;"> -->
    </div>
    <nav> <!-- Usar <nav> semanticamente -->
        <ul class="sidebar-nav">
            <li>
                <!-- Link para a página principal do treinador -->
                <a href="trainer.php" class="<?= ($active_page == 'trainer.php') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt fa-fw"></i>
                    <span>Painel Principal</span>
                </a>
            </li>
            <li>
                <!-- Link para adicionar plano de treino -->
                <a href="add_workout_plan.php" class="<?= ($active_page == 'add_workout_plan.php') ? 'active' : ''; ?>">
                    <i class="fas fa-plus-square fa-fw"></i>
                    <span>Adicionar Plano</span>
                </a>
            </li>
            <li>
                <!-- Link para histórico de clientes (planos inativos) -->
                <a href="client_history.php" class="<?= ($active_page == 'client_history.php') ? 'active' : ''; ?>">
                    <i class="fas fa-history fa-fw"></i>
                    <span>Histórico Clientes</span>
                </a>
            </li>
            <!-- Adicionar link para Perfil se existir para trainers -->
            <li>
                <a href="profile.php" class="<?= ($active_page == 'profile.php') ? 'active' : ''; ?>">
                    <i class="fas fa-user fa-fw"></i>
                    <span>Perfil</span>
                </a>
            </li>
            <!-- Adicionar link para Chat se existir para trainers -->
             <li>
                <a href="chat.php" class="<?= ($active_page == 'chat.php') ? 'active' : ''; ?>">
                    <i class="fas fa-comments fa-fw"></i>
                    <span>Chat</span>
                </a>
            </li>
            <!-- Adicione mais links específicos do treinador conforme necessário -->
            <!-- Exemplo: Gerir Exercícios (se aplicável)
            <li>
                <a href="manage_exercises.php" class="<?= ($active_page == 'manage_exercises.php') ? 'active' : ''; ?>">
                    <i class="fas fa-cogs fa-fw"></i>
                    <span>Gerir Exercícios</span>
                </a>
            </li>
             -->
        </ul>
    </nav>
     <div class="sidebar-footer">
         <a href="logout.php">
            <i class="fas fa-sign-out-alt fa-fw"></i>
            <span>Sair</span>
        </a>
    </div>
</aside>

<!-- Conteúdo Principal -->
<main class="main-content">
    <div class="container mt-5"> <!-- Mantém o container para espaçamento interno -->
        <h2>Bem-vindo, <?= htmlspecialchars($trainer_name) ?>!</h2>
        <div class="row">
            <div class="col-md-12">
                <h4>Clientes Ativos e Planos de Treino</h4>
                <a href="add_workout_plan.php" class="btn btn-success mb-3">Adicionar Plano de Treino</a>
                <a href="client_history.php" class="btn btn-info mb-3">Ver Histórico de Clientes</a>
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

                                    // --- Coluna Plano de Treino ---
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

                                    // --- Coluna Exercícios ---
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

                                    // --- Coluna Ações ---
                                    echo "<td class='align-top'>";
                                    if ($client['workout_plan_id']) {
                                        // Link para editar o plano ativo existente
                                        echo "<a href='edit_workout_plan.php?client_id=" . $client['client_id'] . "&workout_plan_id=" . $client['workout_plan_id'] . "' class='btn btn-warning btn-sm mb-1 d-block'>Editar Plano</a>";
                                    } else {
                                        // ***** MODIFICAÇÃO 3: Adicionar client_id ao link Criar Plano *****
                                        // Link para adicionar um *novo* plano (passando client_id pode ser útil em add_workout_plan.php)
                                        echo "<a href='add_workout_plan.php?client_id=" . $client['client_id'] . "' class='btn btn-primary btn-sm mb-1 d-block'>Criar Plano</a>";
                                        // *****************************************************************
                                    }

                                    // Verifica se client_user_id existe (deve existir por causa da query modificada)
                                    if (isset($client['client_user_id'])) {
                                        echo "<a href='chat.php?user_id=" . $client['client_user_id'] . "' class='btn btn-info btn-sm mb-1 d-block'><i class='fas fa-comments'></i> Chat</a>";
                                    }
                                    echo "</td>";
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
