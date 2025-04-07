<?php
session_start();

// Verifica se o usuário está logado e é um trainer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainer') {
    header('Location: login.php');
    exit;
}

// Inclui a conexão com o banco de dados
include 'db.php';

// Inclui o cabeçalho da página
include 'header.php';

// Processa o formulário de adicionar plano de treino
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'];
    $custom_details = $_POST['custom_details'];
    $trainer_id = $_SESSION['user_id']; // Obtém o ID do trainer da sessão
    $selected_exercises = $_POST['selected_exercises'] ?? [];
    $days = $_POST['day'] ?? [];
    $day_details = $_POST['day_details'] ?? [];
    $day_option_ids = $_POST['day_option_id'] ?? [];

    // Set all existing plans for this client to inactive
    $stmt = $pdo->prepare("UPDATE workout_plans SET is_active = 0 WHERE client_id = ?");
    $stmt->execute([$client_id]);

    // Insere o novo plano de treino no banco de dados e define como ativo
    $stmt = $pdo->prepare("INSERT INTO workout_plans (client_id, trainer_id, plan_details, is_active) VALUES (?, ?, ?, 1)");
    $stmt->execute([$client_id, $trainer_id, $custom_details]);

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

    // Redireciona de volta para a página do trainer
    header('Location: trainer.php');
    exit;
}
?>

<div class="container mt-5">
    <h2>Adicionar Plano de Treino</h2>
    <form method="POST">
        <div class="form-group mb-3">
            <label for="client_id">Cliente</label>
            <select class="form-control" id="client_id" name="client_id" required>
                <?php
                // Busca todos os clientes cadastrados
                $stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'client'");
                $clients = $stmt->fetchAll();

                // Exibe cada cliente como uma opção no select
                foreach ($clients as $client) {
                    // Check if the client exists in the clients table
                    $checkClientStmt = $pdo->prepare("SELECT id FROM clients WHERE user_id = ?");
                    $checkClientStmt->execute([$client['id']]);
                    $existingClient = $checkClientStmt->fetch();

                    // If the client doesn't exist in the clients table, create it
                    if (!$existingClient) {
                        $createClientStmt = $pdo->prepare("INSERT INTO clients (user_id) VALUES (?)");
                        $createClientStmt->execute([$client['id']]);
                    }

                    // Get the client id from the clients table
                    $getClientIdStmt = $pdo->prepare("SELECT id FROM clients WHERE user_id = ?");
                    $getClientIdStmt->execute([$client['id']]);
                    $clientId = $getClientIdStmt->fetchColumn();

                    echo "<option value='" . $clientId . "'>" . htmlspecialchars($client['name']) . "</option>";
                }
                ?>
            </select>
        </div>
        <div class="form-group mb-3">
            <label for="custom_details">Detalhes do Plano</label>
            <textarea class="form-control" id="custom_details" name="custom_details" rows="5"></textarea>
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
                    // Fetch all day options
                    $stmt = $pdo->query("SELECT id, name FROM day_options");
                    $day_options = $stmt->fetchAll();
                    for ($i = 1; $i <= 7; $i++): ?>
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
                        echo "<td><input type='checkbox' name='selected_exercises[]' value='" . $exercise['id'] . "'></td>";
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

<?php
// Inclui o rodapé da página
include 'footer.php';
?>
