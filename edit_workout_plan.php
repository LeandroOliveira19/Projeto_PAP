<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainer') {
    header('Location: login.php');
    exit;
}
include 'db.php';
include 'header.php';

// *** ADICIONADO: Define a página ativa para a sidebar ***
// Como esta página é acessada a partir da lista de clientes, destacar 'Meus Clientes'
$active_page = 'my_clients.php';

// *** ADICIONADO: Inclui a sidebar do trainer ***
include 'sidebar_trainer.php';

// Check if client_id and workout_plan_id are provided
if (!isset($_GET['client_id']) || !isset($_GET['workout_plan_id'])) {
    echo "<script>alert('ID do cliente ou do plano de treino não fornecido.'); window.location.href = 'trainer.php';</script>";
    exit;
}

$client_id = $_GET['client_id'];
$workout_plan_id = $_GET['workout_plan_id'];
$page_error = ''; // Para erros de busca
$plan = ['plan_details' => '']; // Inicializa $plan
$selected_exercises = [];
$workout_plan_days = [];
$day_options = [];
$client_name = 'Cliente'; // Nome padrão

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan_details = $_POST['plan_details'];
    $selected_exercises = $_POST['selected_exercises'] ?? [];
    $days = $_POST['day'] ?? [];
    $day_details = $_POST['day_details'] ?? [];
    $day_option_ids = $_POST['day_option_id'] ?? [];

    try {
        $pdo->beginTransaction();

        // Update existing workout plan details
        $stmt_update_plan = $pdo->prepare("UPDATE workout_plans SET plan_details = ? WHERE id = ? AND trainer_id = ? AND client_id = ?");
        $stmt_update_plan->execute([$plan_details, $workout_plan_id, $_SESSION['user_id'], $client_id]);

        // Delete existing exercises for this workout plan
        $stmt_delete_exercises = $pdo->prepare("DELETE FROM workout_plan_exercises WHERE workout_plan_id = ?");
        $stmt_delete_exercises->execute([$workout_plan_id]);

        // Insert the selected exercises into the workout_plan_exercises table
        $stmt_insert_exercise = $pdo->prepare("INSERT INTO workout_plan_exercises (workout_plan_id, exercise_id) VALUES (?, ?)");
        foreach ($selected_exercises as $exercise_id) {
            $stmt_insert_exercise->execute([$workout_plan_id, $exercise_id]);
        }

        // Delete existing day details
        $stmt_delete_days = $pdo->prepare("DELETE FROM workout_plan_days WHERE workout_plan_id = ?");
        $stmt_delete_days->execute([$workout_plan_id]);

        // Insert the day-by-day details
        $stmt_insert_day = $pdo->prepare("INSERT INTO workout_plan_days (workout_plan_id, day_name, day_details, day_option_id) VALUES (?, ?, ?, ?)");
        foreach ($days as $key => $day_name) {
            // Garante que temos dados para todas as colunas
            $current_day_details = $day_details[$key] ?? '';
            $current_day_option_id = $day_option_ids[$key] ?? null; // Assume que pode ser NULL se não selecionado
            if (!empty($day_name)) { // Só insere se o nome do dia não estiver vazio
                 $stmt_insert_day->execute([$workout_plan_id, $day_name, $current_day_details, $current_day_option_id]);
            }
        }

        $pdo->commit();
        echo "<script>alert('Plano de treino atualizado com sucesso!'); window.location.href = 'my_clients.php';</script>"; // Redireciona para a lista de clientes
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Erro ao atualizar plano de treino: " . $e->getMessage());
        echo "<script>alert('Erro ao atualizar o plano de treino. Tente novamente.'); window.history.back();</script>"; // Volta para o formulário
        exit;
    }
}

// --- Fetch existing data for the form ---
try {
    // Fetch client name
    $stmt_client_name = $pdo->prepare("SELECT u.name FROM users u JOIN clients c ON u.id = c.user_id WHERE c.id = ?");
    $stmt_client_name->execute([$client_id]);
    $client_name_result = $stmt_client_name->fetchColumn();
    if ($client_name_result) {
        $client_name = $client_name_result;
    } else {
        throw new Exception("Cliente não encontrado.");
    }

    // Fetch existing workout plan details
    $stmt_plan = $pdo->prepare("SELECT plan_details FROM workout_plans WHERE id = ? AND trainer_id = ? AND client_id = ?");
    $stmt_plan->execute([$workout_plan_id, $_SESSION['user_id'], $client_id]);
    $plan_result = $stmt_plan->fetch();
    if ($plan_result) {
        $plan = $plan_result; // $plan['plan_details'] estará disponível
    } else {
        // Plano não encontrado ou não pertence a este trainer/cliente
        throw new Exception("Plano de treino não encontrado ou acesso negado.");
    }

    // Fetch selected exercises for this workout plan
    $stmt_selected_exercises = $pdo->prepare("SELECT exercise_id FROM workout_plan_exercises WHERE workout_plan_id = ?");
    $stmt_selected_exercises->execute([$workout_plan_id]);
    $selected_exercises = $stmt_selected_exercises->fetchAll(PDO::FETCH_COLUMN);

    // Fetch day-by-day details
    $stmt_days = $pdo->prepare("SELECT day_name, day_details, day_option_id FROM workout_plan_days WHERE workout_plan_id = ? ORDER BY FIELD(day_name, 'Dia 1', 'Dia 2', 'Dia 3', 'Dia 4', 'Dia 5', 'Dia 6', 'Dia 7')");
    $stmt_days->execute([$workout_plan_id]);
    $workout_plan_days = $stmt_days->fetchAll();

    // Fetch all day options
    $stmt_day_options = $pdo->query("SELECT id, name FROM day_options ORDER BY id"); // Ordena para consistência
    $day_options = $stmt_day_options->fetchAll();

    // Fetch all exercises for the selection table
    $stmt_all_exercises = $pdo->query("SELECT * FROM exercises ORDER BY name");
    $exercises = $stmt_all_exercises->fetchAll();

} catch (PDOException $e) {
    $page_error = "Erro ao carregar dados para edição: " . $e->getMessage();
    error_log($page_error);
    // Limpa dados para evitar erros no formulário
    $plan = ['plan_details' => ''];
    $selected_exercises = [];
    $workout_plan_days = [];
    $day_options = [];
    $exercises = [];
} catch (Exception $e) {
     $page_error = $e->getMessage();
     // Redireciona se for erro crítico como plano/cliente não encontrado
     echo "<script>alert('" . htmlspecialchars($page_error) . "'); window.location.href = 'my_clients.php';</script>";
     exit;
}

?>

<!-- *** ADICIONADO: Wrapper <main> *** -->
<main class="main-content">
    <div class="container mt-5">
        <h2>Editar Plano de Treino para <?= htmlspecialchars($client_name) ?></h2>

        <?php if ($page_error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($page_error) ?></div>
        <?php endif; ?>

        <form method="POST" action="edit_workout_plan.php?client_id=<?= $client_id ?>&workout_plan_id=<?= $workout_plan_id ?>">
            <div class="form-group mb-3">
                <label for="plan_details">Observações Gerais do Plano</label>
                <textarea class="form-control" id="plan_details" name="plan_details" rows="5"><?= htmlspecialchars($plan['plan_details']) ?></textarea>
            </div>

            <!-- Day-by-Day Workout Details Table -->
            <div class="form-group mb-3">
                <label>Detalhes Diários do Treino</label>
                <table class="table table-bordered" id="day-details-table">
                    <thead class="table-light">
                        <tr>
                            <th>Dia</th>
                            <th>Opção (Grupo Muscular/Foco)</th>
                            <th>Detalhes Específicos do Dia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $days_data_map = [];
                        foreach ($workout_plan_days as $day) {
                            $days_data_map[$day['day_name']] = $day;
                        }

                        for ($i = 1; $i <= 7; $i++):
                            $current_day_name = "Dia " . $i;
                            $day_data = $days_data_map[$current_day_name] ?? ['day_details' => '', 'day_option_id' => null];
                        ?>
                            <tr>
                                <td>
                                    <input type="text" class="form-control" name="day[]" value="<?= htmlspecialchars($current_day_name) ?>" readonly required> <!-- Tornar readonly -->
                                </td>
                                <td>
                                    <select class="form-select" name="day_option_id[]"> <!-- Usar form-select -->
                                        <option value="">-- Selecione --</option> <!-- Opção vazia -->
                                        <?php foreach ($day_options as $option): ?>
                                            <option value="<?= $option['id'] ?>" <?= ($day_data['day_option_id'] == $option['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($option['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <textarea class="form-control" name="day_details[]" rows="2" placeholder="Ex: Aquecimento, séries, repetições, descanso..."><?= htmlspecialchars($day_data['day_details']) ?></textarea>
                                </td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <!-- Exercise Selection Table -->
            <div class="form-group mb-3">
                <label>Selecionar Exercícios para Incluir no Plano</label>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center"><i class="fas fa-check"></i></th>
                                <th>Nome</th>
                                <th>Grupo Muscular</th>
                                <th>Equipamento</th>
                                <th>Descrição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (empty($exercises)) {
                                echo "<tr><td colspan='5' class='text-center text-muted'>Nenhum exercício cadastrado no sistema. <a href='#'>Adicionar exercícios</a>.</td></tr>"; // Link para adicionar exercícios (implementar)
                            } else {
                                foreach ($exercises as $exercise) {
                                    $is_checked = in_array($exercise['id'], $selected_exercises);
                                    echo "<tr>";
                                    echo "<td class='text-center'><input type='checkbox' class='form-check-input' name='selected_exercises[]' value='" . $exercise['id'] . "'" . ($is_checked ? " checked" : "") . "></td>";
                                    echo "<td>" . htmlspecialchars($exercise['name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($exercise['muscle_group']) . "</td>";
                                    echo "<td>" . htmlspecialchars($exercise['equipment']) . "</td>";
                                    echo "<td><small>" . htmlspecialchars($exercise['description']) . "</small></td>";
                                    echo "</tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Salvar Alterações</button>
                <a href="my_clients.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Cancelar</a>
            </div>
        </form>
    </div>
</main> <!-- *** FIM: Wrapper <main> *** -->

<?php include 'footer.php'; ?>
