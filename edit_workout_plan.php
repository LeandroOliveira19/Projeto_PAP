<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainer') {
    header('Location: login.php');
    exit;
}
include 'db.php';
include 'header.php';

// Check if client_id and workout_plan_id are provided
if (!isset($_GET['client_id']) || !isset($_GET['workout_plan_id'])) {
    echo "<script>alert('ID do cliente ou do plano de treino não fornecido.'); window.location.href = 'trainer.php';</script>";
    exit;
}

$client_id = $_GET['client_id'];
$workout_plan_id = $_GET['workout_plan_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan_details = $_POST['plan_details'];
    $selected_exercises = $_POST['selected_exercises'] ?? [];
    $days = $_POST['day'] ?? [];
    $day_details = $_POST['day_details'] ?? [];
    $day_option_ids = $_POST['day_option_id'] ?? [];

    // Check if a workout plan already exists for this client
    if ($workout_plan_id != '') {
        // Update existing workout plan
        $stmt = $pdo->prepare("UPDATE workout_plans SET plan_details = ? WHERE id = ? AND trainer_id = ? AND client_id = ?");
        $stmt->execute([$plan_details, $workout_plan_id, $_SESSION['user_id'], $client_id]);

        // Delete existing exercises for this workout plan
        $stmt = $pdo->prepare("DELETE FROM workout_plan_exercises WHERE workout_plan_id = ?");
        $stmt->execute([$workout_plan_id]);

        // Insert the selected exercises into the workout_plan_exercises table
        foreach ($selected_exercises as $exercise_id) {
            $stmt = $pdo->prepare("INSERT INTO workout_plan_exercises (workout_plan_id, exercise_id) VALUES (?, ?)");
            $stmt->execute([$workout_plan_id, $exercise_id]);
        }

        // Delete existing day details
        $stmt = $pdo->prepare("DELETE FROM workout_plan_days WHERE workout_plan_id = ?");
        $stmt->execute([$workout_plan_id]);

        // Insert the day-by-day details
        foreach ($days as $key => $day_name) {
            $stmt = $pdo->prepare("INSERT INTO workout_plan_days (workout_plan_id, day_name, day_details, day_option_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$workout_plan_id, $day_name, $day_details[$key], $day_option_ids[$key]]);
        }
    } else {
        // Set all existing plans for this client to inactive
        $stmt = $pdo->prepare("UPDATE workout_plans SET is_active = 0 WHERE client_id = ?");
        $stmt->execute([$client_id]);

        // Create a new workout plan and set it to active
        $stmt = $pdo->prepare("INSERT INTO workout_plans (client_id, trainer_id, plan_details, is_active) VALUES (?, ?, ?, 1)");
        $stmt->execute([$client_id, $_SESSION['user_id'], $plan_details]);

        // Get the ID of the newly inserted workout plan
        $workout_plan_id = $pdo->lastInsertId();

        // Insert the selected exercises into the workout_plan_exercises table
        foreach ($selected_exercises as $exercise_id) {
            $stmt = $pdo->prepare("INSERT INTO workout_plan_exercises (workout_plan_id, exercise_id) VALUES (?, ?)");
            $stmt->execute([$workout_plan_id, $exercise_id]);
        }

        // Insert the day-by-day details
        foreach ($days as $key => $day_name) {
            $stmt = $pdo->prepare("INSERT INTO workout_plan_days (workout_plan_id, day_name, day_details, day_option_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$workout_plan_id, $day_name, $day_details[$key], $day_option_ids[$key]]);
        }
    }

    echo "<script>alert('Plano de treino atualizado com sucesso!'); window.location.href = 'trainer.php';</script>";
    exit;
}

// Fetch existing workout plan details
if ($workout_plan_id != '') {
    $stmt = $pdo->prepare("SELECT plan_details FROM workout_plans WHERE id = ? AND trainer_id = ? AND client_id = ?");
    $stmt->execute([$workout_plan_id, $_SESSION['user_id'], $client_id]);
    $plan = $stmt->fetch();
} else {
    $plan['plan_details'] = "";
}

// Fetch selected exercises for this workout plan
$stmt = $pdo->prepare("SELECT exercise_id FROM workout_plan_exercises WHERE workout_plan_id = ?");
$stmt->execute([$workout_plan_id]);
$selected_exercises = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch day-by-day details
$stmt = $pdo->prepare("SELECT day_name, day_details, day_option_id FROM workout_plan_days WHERE workout_plan_id = ?");
$stmt->execute([$workout_plan_id]);
$workout_plan_days = $stmt->fetchAll();

// Fetch all day options
$stmt = $pdo->query("SELECT id, name FROM day_options");
$day_options = $stmt->fetchAll();
?>

<div class="container mt-5">
    <h2>Editar Plano de Treino</h2>
    <form method="POST">
        <div class="form-group">
            <label for="plan_details">Detalhes do Plano</label>
            <textarea class="form-control" id="plan_details" name="plan_details" rows="5"><?= htmlspecialchars($plan['plan_details']) ?></textarea>
        </div>

        <!-- Day-by-Day Workout Details Table -->
        <div class="form-group mb-3">
            <label>Detalhes Diários do Treino</label>
            <table class="table table-bordered" id="day-details-table">
                <thead>
                    <tr>
                        <th>Dia</th>
                        <th>Opção</th>
                        <th>Detalhes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $day_count = 1;
                    foreach ($workout_plan_days as $day): ?>
                        <tr>
                            <td>
                                <input type="text" class="form-control" name="day[]" value="<?= htmlspecialchars($day['day_name']) ?>" required>
                            </td>
                            <td>
                                <select class="form-control" name="day_option_id[]">
                                    <?php foreach ($day_options as $option): ?>
                                        <option value="<?= $option['id'] ?>" <?= ($day['day_option_id'] == $option['id']) ? 'selected' : '' ?>><?= htmlspecialchars($option['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <textarea class="form-control" name="day_details[]" rows="3"><?= htmlspecialchars($day['day_details']) ?></textarea>
                            </td>
                        </tr>
                    <?php
                    $day_count++;
                    endforeach;
                    // Add empty rows if there are less than 7 days
                    for ($i = $day_count; $i <= 7; $i++): ?>
                        <tr>
                            <td>
                                <input type="text" class="form-control" name="day[]" value="Dia <?= $i ?>" required>
                            </td>
                            <td>
                                <select class="form-control" name="day_option_id[]">
                                    <?php foreach ($day_options as $option): ?>
                                        <option value="<?= $option['id'] ?>"><?= htmlspecialchars($option['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <textarea class="form-control" name="day_details[]" rows="3"></textarea>
                            </td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>

        <!-- Exercise Selection Table -->
        <div class="form-group mb-3">
            <label>Selecionar Exercícios</label>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Selecionar</th>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Grupo Muscular</th>
                        <th>Equipamento</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch all exercises from the database
                    $stmt = $pdo->query("SELECT * FROM exercises");
                    $exercises = $stmt->fetchAll();


                    // Display all exercises
                    foreach ($exercises as $exercise) {
                        echo "<tr>";
                        echo "<td><input type='checkbox' name='selected_exercises[]' value='" . $exercise['id'] . "'";
                        // Check if the exercise is selected
                        if (in_array($exercise['id'], $selected_exercises)) {
                            echo " checked";
                        }
                        echo "></td>";
                        echo "<td>" . htmlspecialchars($exercise['name']) . "</td>";
                        echo "<td>" . htmlspecialchars($exercise['description']) . "</td>";
                        echo "<td>" . htmlspecialchars($exercise['muscle_group']) . "</td>";
                        echo "<td>" . htmlspecialchars($exercise['equipment']) . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <button type="submit" class="btn btn-primary">Salvar</button>
    </form>
</div>

<?php include 'footer.php'; ?>
