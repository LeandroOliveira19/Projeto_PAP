<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
include 'db.php';
include 'header.php';
include 'send_email.php';

// Processa exclusão de registros
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = $_GET['id'];
    $type = $_GET['type'];

    try {
        if ($type === 'class') {
            $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
            $message = "Aula excluída com sucesso!";
        } elseif ($type === 'trainer') {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'trainer'");
            $message = "Treinador excluído com sucesso!";
        } elseif ($type === 'nutritionist') {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'nutritionist'");
            $message = "Nutricionista excluído com sucesso!";
        } else {
            throw new Exception("Tipo inválido");
        }

        $stmt->execute([$id]);
        echo "<script>alert('$message'); window.location.href = 'admin.php';</script>";
        exit;
    } catch (Exception $e) {
        echo "<script>alert('Erro ao excluir: " . $e->getMessage() . "'); window.location.href = 'admin.php';</script>";
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
        echo "<script>alert('Aula adicionada com sucesso!'); window.location.href = 'admin.php';</script>";
        exit;
    } catch (Exception $e) {
        echo "<script>alert('Erro ao adicionar aula: " . $e->getMessage() . "'); window.location.href = 'admin.php';</script>";
        exit;
    }
}

// Processa adição de treinadores
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_trainer'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $token = bin2hex(random_bytes(16)); // Gera um token único

    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, role, token) VALUES (?, ?, 'trainer', ?)");
        $stmt->execute([$name, $email, $token]);

        // Envia email de confirmação
        if (sendConfirmationEmail($email, $name, $token, "register")) {
            echo "<script>alert('Treinador adicionado com sucesso! Email de confirmação enviado.'); window.location.href = 'admin.php';</script>";
        } else {
            echo "<script>alert('Treinador adicionado, mas erro ao enviar email de confirmação.'); window.location.href = 'admin.php';</script>";
        }
        exit;
    } catch (Exception $e) {
        echo "<script>alert('Erro ao adicionar treinador: " . $e->getMessage() . "'); window.location.href = 'admin.php';</script>";
        exit;
    }
}

// Processa adição de nutricionistas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_nutritionist'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $token = bin2hex(random_bytes(16)); // Gera um token único

    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, role, token) VALUES (?, ?, 'nutritionist', ?)");
        $stmt->execute([$name, $email, $token]);

        // Envia email de confirmação
        if (sendConfirmationEmail($email, $name, $token, "register")) {
            echo "<script>alert('Nutricionista adicionado com sucesso! Email de confirmação enviado.'); window.location.href = 'admin.php';</script>";
        } else {
            echo "<script>alert('Nutricionista adicionado, mas erro ao enviar email de confirmação.'); window.location.href = 'admin.php';</script>";
        }
        exit;
    } catch (Exception $e) {
        echo "<script>alert('Erro ao adicionar nutricionista: " . $e->getMessage() . "'); window.location.href = 'admin.php';</script>";
        exit;
    }
}

// Processa criação de cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_client'])) {
    $email = $_POST['email'];
    $name = $_POST['name']; // Get the client's name

    // Gera uma senha temporária
    $temp_password = bin2hex(random_bytes(8)); // Gera uma senha de 16 caracteres hexadecimais
    $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);

    // Gera um token único
    $token = bin2hex(random_bytes(50));

    // Define o perfil como cliente
    $role = 'client';
    $first_login = 1;

    // Insere o novo usuário no banco de dados com a senha temporária e o token
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, token, first_login) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $email, $hashed_password, $role, $token, $first_login]);

    // Envia o email de confirmação
    if (sendConfirmationEmail($email, $name, $token, "register_admin", $temp_password)) {
        echo "<script>alert('Cliente criado com sucesso! Um e-mail de confirmação foi enviado para o cliente.'); window.location.href = 'admin.php';</script>";
    } else {
        echo "<script>alert('Erro ao enviar o e-mail de confirmação.'); window.location.href = 'admin.php';</script>";
    }
    exit;
}
?>

<div class="container mt-5">
    <h2>Página de Administração</h2>

    <!-- Navegação por abas -->
    <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="classes-tab" data-bs-toggle="tab" data-bs-target="#classes" type="button" role="tab" aria-controls="classes" aria-selected="true">Aulas</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="trainers-tab" data-bs-toggle="tab" data-bs-target="#trainers" type="button" role="tab" aria-controls="trainers" aria-selected="false">Treinadores</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="nutritionists-tab" data-bs-toggle="tab" data-bs-target="#nutritionists" type="button" role="tab" aria-controls="nutritionists" aria-selected="false">Nutricionistas</button>
        </li>
        <!-- Nova aba de Clientes -->
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="clients-tab" data-bs-toggle="tab" data-bs-target="#clients" type="button" role="tab" aria-controls="clients" aria-selected="false">Clientes</button>
        </li>
    </ul>

    <div class="tab-content" id="adminTabsContent">
        <!-- Aba de Aulas -->
        <div class="tab-pane fade show active" id="classes" role="tabpanel" aria-labelledby="classes-tab">
            <div class="card mb-4">
                <div class="card-body">
                    <h4>Adicionar Nova Aula</h4>
                    <form method="POST">
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
                                <?php
                                // Busca todos os trainers
                                $stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'trainer'");
                                $trainers = $stmt->fetchAll();
                                // Exibe cada trainer como uma opção no select
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
                            <input type="number" class="form-control" id="duration" name="duration" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="max_participants">Número Máximo de Participantes</label>
                            <input type="number" class="form-control" id="max_participants" name="max_participants" required>
                        </div>
                        <input type="hidden" name="add_class" value="1">
                        <button type="submit" class="btn btn-primary">Adicionar Aula</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h4>Lista de Aulas</h4>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Treinador</th>
                                <th>Data/Hora</th>
                                <th>Duração</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Busca todas as aulas com nome do treinador
                            $stmt = $pdo->query("SELECT c.id, c.name, c.schedule, c.duration, u.name as trainer_name 
                                               FROM classes c 
                                               JOIN users u ON c.trainer_id = u.id 
                                               ORDER BY c.schedule");
                            $classes = $stmt->fetchAll();

                            foreach ($classes as $class) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($class['id']) . "</td>";
                                echo "<td>" . htmlspecialchars($class['name']) . "</td>";
                                echo "<td>" . htmlspecialchars($class['trainer_name']) . "</td>";
                                echo "<td>" . date('d/m/Y H:i', strtotime($class['schedule'])) . "</td>";
                                echo "<td>" . htmlspecialchars($class['duration']) . " min</td>";
                                echo "<td>
                                      <a href='admin.php?action=delete&type=class&id=" . $class['id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Tem certeza que deseja excluir esta aula?\")'>Excluir</a>
                                     </td>";
                                echo "</tr>";
                            }

                            if (count($classes) === 0) {
                                echo "<tr><td colspan='6' class='text-center'>Nenhuma aula cadastrada</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Aba de Treinadores -->
        <div class="tab-pane fade" id="trainers" role="tabpanel" aria-labelledby="trainers-tab">
            <div class="card mb-4">
                <div class="card-body">
                    <h4>Adicionar Novo Treinador</h4>
                    <form method="POST">
                        <div class="form-group mb-3">
                            <label for="trainer_name">Nome</label>
                            <input type="text" class="form-control" id="trainer_name" name="name" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="trainer_email">Email</label>
                            <input type="email" class="form-control" id="trainer_email" name="email" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="trainer_password">Senha</label>
                            <input type="password" class="form-control" id="trainer_password" name="password" required>
                        </div>
                        <input type="hidden" name="add_trainer" value="1">
                        <button type="submit" class="btn btn-primary">Adicionar Treinador</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h4>Lista de Treinadores</h4>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Busca todos os treinadores
                            $stmt = $pdo->query("SELECT id, name, email FROM users WHERE role = 'trainer' ORDER BY name");
                            $trainers = $stmt->fetchAll();

                            foreach ($trainers as $trainer) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($trainer['id']) . "</td>";
                                echo "<td>" . htmlspecialchars($trainer['name']) . "</td>";
                                echo "<td>" . htmlspecialchars($trainer['email']) . "</td>";
                                echo "<td>
                                      <a href='admin.php?action=delete&type=trainer&id=" . $trainer['id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Tem certeza que deseja excluir este treinador? Todas as aulas associadas também serão afetadas.\")'>Excluir</a>
                                     </td>";
                                echo "</tr>";
                            }

                            if (count($trainers) === 0) {
                                echo "<tr><td colspan='4' class='text-center'>Nenhum treinador cadastrado</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Aba de Nutricionistas -->
        <div class="tab-pane fade" id="nutritionists" role="tabpanel" aria-labelledby="nutritionists-tab">
            <div class="card mb-4">
                <div class="card-body">
                    <h4>Adicionar Novo Nutricionista</h4>
                    <form method="POST">
                        <div class="form-group mb-3">
                            <label for="nutritionist_name">Nome</label>
                            <input type="text" class="form-control" id="nutritionist_name" name="name" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="nutritionist_email">Email</label>
                            <input type="email" class="form-control" id="nutritionist_email" name="email" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="nutritionist_password">Senha</label>
                            <input type="password" class="form-control" id="nutritionist_password" name="password" required>
                        </div>
                        <input type="hidden" name="add_nutritionist" value="1">
                        <button type="submit" class="btn btn-primary">Adicionar Nutricionista</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h4>Lista de Nutricionistas</h4>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Busca todos os nutricionistas
                            $stmt = $pdo->query("SELECT id, name, email FROM users WHERE role = 'nutritionist' ORDER BY name");
                            $nutritionists = $stmt->fetchAll();

                            foreach ($nutritionists as $nutritionist) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($nutritionist['id']) . "</td>";
                                echo "<td>" . htmlspecialchars($nutritionist['name']) . "</td>";
                                echo "<td>" . htmlspecialchars($nutritionist['email']) . "</td>";
                                echo "<td>
                                      <a href='admin.php?action=delete&type=nutritionist&id=" . $nutritionist['id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Tem certeza que deseja excluir este nutricionista?\")'>Excluir</a>
                                     </td>";
                                echo "</tr>";
                            }

                            if (count($nutritionists) === 0) {
                                echo "<tr><td colspan='4' class='text-center'>Nenhum nutricionista cadastrado</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Nova aba de Clientes -->
        <div class="tab-pane fade" id="clients" role="tabpanel" aria-labelledby="clients-tab">
            <div class="card mb-4">
                <div class="card-body">
                    <h3>Criar Novo Cliente</h3>
                    <form method="POST">
                        <div class="form-group mb-3">
                            <label for="name">Nome do Cliente</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="email">E-mail do Cliente</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <input type="hidden" name="create_client" value="1">
                        <button type="submit" class="btn btn-primary">Criar Cliente</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Inicializa as abas do Bootstrap
    document.addEventListener('DOMContentLoaded', function() {
        const triggerTabList = [].slice.call(document.querySelectorAll('#adminTabs button'));
        triggerTabList.forEach(function(triggerEl) {
            const tabTrigger = new bootstrap.Tab(triggerEl);
            triggerEl.addEventListener('click', function(event) {
                event.preventDefault();
                tabTrigger.show();
            });
        });
    });
</script>

<?php
// Inclui o rodapé da página
include 'footer.php';
?>