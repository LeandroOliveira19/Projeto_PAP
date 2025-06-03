<?php
session_start();
// Inclui db.php primeiro para que $pdo esteja disponível
include 'db.php';
include 'includes/auth_utils.php'; // Inclui a função de verificação

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Verifica se o usuário tem a permissão específica 'manage_classes'
if (!user_has_specific_permission($pdo, $_SESSION['user_id'], 'manage_classes')) {
    $_SESSION['error_message'] = "Acesso negado. Permissão para 'Gerir Aulas' necessária.";
    // Redirecionar para uma página de "acesso negado" ou para o dashboard do usuário
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'receptionist') {
        header('Location: receptionist.php');
    } else {
        header('Location: login.php'); // Fallback
    }
    exit;
}

include 'header.php';

$active_page = 'manage_classes_admin.php'; // Define a página ativa
include 'sidebar_admin.php';

// Processa exclusão de aula
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
        $stmt->execute([$id]);
        echo "<script>alert('Aula excluída com sucesso!'); window.location.href = 'manage_classes_admin.php';</script>";
        exit;
    } catch (Exception $e) {
        error_log("Erro ao excluir aula: " . $e->getMessage());
        echo "<script>alert('Erro ao excluir aula. Verifique as dependências ou contacte o suporte.'); window.location.href = 'manage_classes_admin.php';</script>";
        exit;
    }
}

// Processa adição de aulas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_class'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $trainer_id = $_POST['trainer_id'];
    $schedule = $_POST['schedule'];
    $duration = $_POST['duration'];
    $max_participants = $_POST['max_participants'];

    try {
        $stmt = $pdo->prepare("INSERT INTO classes (name, description, trainer_id, schedule, duration, max_participants) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $trainer_id, $schedule, $duration, $max_participants]);
        echo "<script>alert('Aula adicionada com sucesso!'); window.location.href = 'manage_classes_admin.php';</script>";
        exit;
    } catch (Exception $e) {
        error_log("Erro ao adicionar aula: " . $e->getMessage());
        echo "<script>alert('Erro ao adicionar aula.'); window.location.href = 'manage_classes_admin.php';</script>";
        exit;
    }
}
?>

<main class="main-content">
    <div class="container mt-5">
        <h2>Gerir Aulas</h2>

        <!-- Card Adicionar Aula -->
        <div class="card mb-4 bg-light">
            <div class="card-body">
                <h4 class="text-dark">Adicionar Nova Aula</h4>
                <form method="POST" action="manage_classes_admin.php">
                     <div class="form-group mb-3">
                        <label for="name">Nome da Aula</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="description">Descrição</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                    </div>
                    <div class="form-group mb-3">
                        <label for="trainer_id">Treinador</label>
                        <select class="form-control" id="trainer_id" name="trainer_id" required>
                            <option value="">-- Selecione um Treinador --</option>
                            <?php
                            $stmt_trainers = $pdo->query("SELECT id, name FROM users WHERE role = 'trainer' ORDER BY name");
                            $trainers = $stmt_trainers->fetchAll();
                            foreach ($trainers as $trainer) {
                                echo "<option value='" . $trainer['id'] . "'>" . htmlspecialchars($trainer['name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="schedule">Data e Horário</label>
                        <input type="datetime-local" class="form-control" id="schedule" name="schedule" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="duration">Duração (minutos)</label>
                        <input type="number" class="form-control" id="duration" name="duration" min="1" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="max_participants">Número Máximo de Participantes</label>
                        <input type="number" class="form-control" id="max_participants" name="max_participants" min="1" required>
                    </div>
                    <input type="hidden" name="add_class" value="1">
                    <button type="submit" class="btn btn-primary">Adicionar Aula</button>
                </form>
            </div>
        </div>

        <!-- Card Lista de Aulas -->
        <div class="card bg-light">
            <div class="card-body">
                <h4 class="text-dark">Lista de Aulas</h4>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr><th>ID</th><th>Nome</th><th>Treinador</th><th>Data/Hora</th><th>Duração</th><th>Vagas</th><th>Ações</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt_classes = $pdo->query("
                                SELECT c.id, c.name, c.schedule, c.duration, c.max_participants, u.name as trainer_name,
                                       (SELECT COUNT(*) FROM class_participants cp WHERE cp.class_id = c.id) as current_participants
                                FROM classes c
                                JOIN users u ON c.trainer_id = u.id
                                ORDER BY c.schedule DESC
                            ");
                            $classes = $stmt_classes->fetchAll();
                            foreach ($classes as $class) {
                                $vagas_ocupadas = $class['current_participants'];
                                $vagas_totais = $class['max_participants'];
                                echo "<tr><td>" . htmlspecialchars($class['id']) . "</td><td>" . htmlspecialchars($class['name']) . "</td><td>" . htmlspecialchars($class['trainer_name']) . "</td><td>" . date('d/m/Y H:i', strtotime($class['schedule'])) . "</td><td>" . htmlspecialchars($class['duration']) . " min</td><td>" . $vagas_ocupadas . "/" . $vagas_totais . "</td><td>
                                      <a href='manage_classes_admin.php?action=delete&id=" . $class['id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Tem certeza que deseja excluir esta aula? As inscrições serão perdidas.\")'>Excluir</a>
                                     </td></tr>";
                            }
                            if (count($classes) === 0) echo "<tr><td colspan='7' class='text-center'>Nenhuma aula cadastrada</td></tr>";
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include 'footer.php'; ?>