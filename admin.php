<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
include 'db.php';
include 'header.php'; // Inclui o header (que tem o início do body)
include 'send_email.php';

// --- INÍCIO DA SIDEBAR (COPIADA DO DASHBOARD.PHP) ---
$active_page = basename($_SERVER['PHP_SELF']); // Para destacar o link ativo, se necessário
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="admin.php" class="logo">MyWellness - Admin</a>
    </div>
    <nav>
        <ul class="sidebar-nav">
            <li>
                <!-- Link para a própria página admin, pode ser útil -->
                <a href="admin.php" class="<?= ($active_page == 'admin.php') ? 'active' : ''; ?>">
                    <i class="fas fa-shield-alt fa-fw"></i>
                    <span>Administração</span>
                </a>
            </li>
            <!-- Podes adicionar outros links específicos do admin aqui, se quiseres -->
            <!-- Exemplo:
            <li>
                <a href="manage_users.php" class="<?= ($active_page == 'manage_users.php') ? 'active' : ''; ?>">
                    <i class="fas fa-users-cog fa-fw"></i>
                    <span>Gerir Utilizadores</span>
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
<?php
// --- FIM DA SIDEBAR ---


// Processa exclusão de registros
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    // ... (código de exclusão existente) ...
    $id = $_GET['id'];
    $type = $_GET['type'];

    try {
        if ($type === 'class') {
            $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
            $message = "Aula excluída com sucesso!";
        } elseif ($type === 'trainer') {
            // Considerar o que fazer com planos/aulas associados a este trainer
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'trainer'");
            $message = "Treinador excluído com sucesso!";
        } elseif ($type === 'nutritionist') {
             // Considerar o que fazer com planos associados a este nutricionista
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'nutritionist'");
            $message = "Nutricionista excluído com sucesso!";
        } elseif ($type === 'client') { // Adicionar exclusão de cliente
             // Primeiro, obter o client_id da tabela clients
             $getClientIdStmt = $pdo->prepare("SELECT id FROM clients WHERE user_id = ?");
             $getClientIdStmt->execute([$id]);
             $client_id = $getClientIdStmt->fetchColumn();

             if ($client_id) {
                 // Excluir dependências (planos, participações, etc.) - IMPORTANTE
                 $pdo->prepare("DELETE FROM workout_plans WHERE client_id = ?")->execute([$client_id]);
                 $pdo->prepare("DELETE FROM diet_plans WHERE client_id = ?")->execute([$client_id]);
                 $pdo->prepare("DELETE FROM class_participants WHERE user_id = ?")->execute([$id]);
                 // Excluir da tabela clients
                 $pdo->prepare("DELETE FROM clients WHERE id = ?")->execute([$client_id]);
             }
             // Finalmente, excluir da tabela users
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'client'");
            $message = "Cliente excluído com sucesso!";
        } else {
            throw new Exception("Tipo inválido para exclusão");
        }

        $stmt->execute([$id]);
        echo "<script>alert('$message'); window.location.href = 'admin.php';</script>";
        exit;
    } catch (Exception $e) {
        error_log("Erro ao excluir: " . $e->getMessage()); // Log do erro
        echo "<script>alert('Erro ao excluir. Verifique as dependências ou contacte o suporte.'); window.location.href = 'admin.php';</script>";
        exit;
    }
}

// Processa adição de aulas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_class'])) {
    // ... (código de adição de aula existente) ...
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
        error_log("Erro ao adicionar aula: " . $e->getMessage());
        echo "<script>alert('Erro ao adicionar aula.'); window.location.href = 'admin.php';</script>";
        exit;
    }
}

// Processa adição de treinadores
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_trainer'])) {
    // ... (código de adição de treinador existente - ATENÇÃO À SENHA) ...
    $name = $_POST['name'];
    $email = $_POST['email'];
    // A senha deve ser temporária e enviada por email, ou definida num passo posterior
    // Vamos gerar uma senha temporária e usar o fluxo de 'register_admin'
    $temp_password = bin2hex(random_bytes(8));
    $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(50)); // Token para confirmação inicial
    $first_login = 1; // Forçar mudança de senha no primeiro login

    try {
        // Verificar se email já existe
        $checkEmail = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $checkEmail->execute([$email]);
        if ($checkEmail->fetch()) {
             echo "<script>alert('Erro: O e-mail fornecido já está em uso.'); window.location.href = 'admin.php';</script>";
             exit;
        }

        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, token, first_login) VALUES (?, ?, ?, 'trainer', ?, ?)");
        $stmt->execute([$name, $email, $hashed_password, $token, $first_login]);
        $user_id = $pdo->lastInsertId(); // Obter ID do usuário recém-criado

        // Envia email com senha temporária e link de confirmação
        if (sendConfirmationEmail($email, $name, $token, "register_admin", $temp_password)) {
            echo "<script>alert('Treinador adicionado com sucesso! Email de confirmação enviado.'); window.location.href = 'admin.php';</script>";
        } else {
            // Idealmente, deveria haver um rollback ou uma forma de alertar o admin sobre a falha no email
            error_log("Falha ao enviar email de confirmação para treinador: $email");
            echo "<script>alert('Treinador adicionado, mas ocorreu um erro ao enviar o email de confirmação. Contacte o treinador manualmente.'); window.location.href = 'admin.php';</script>";
        }
        exit;
    } catch (PDOException $e) {
        error_log("Erro DB ao adicionar treinador: " . $e->getMessage());
         if ($e->errorInfo[1] == 1062) { // Código de erro para entrada duplicada (geralmente email)
             echo "<script>alert('Erro: O e-mail fornecido já está em uso.'); window.location.href = 'admin.php';</script>";
         } else {
             echo "<script>alert('Erro na base de dados ao adicionar treinador.'); window.location.href = 'admin.php';</script>";
         }
        exit;
    } catch (Exception $e) {
        error_log("Erro geral ao adicionar treinador: " . $e->getMessage());
        echo "<script>alert('Erro inesperado ao adicionar treinador.'); window.location.href = 'admin.php';</script>";
        exit;
    }
}

// Processa adição de nutricionistas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_nutritionist'])) {
    // ... (código de adição de nutricionista existente - ATENÇÃO À SENHA) ...
     $name = $_POST['name'];
    $email = $_POST['email'];
    // Gerar senha temporária e usar fluxo 'register_admin'
    $temp_password = bin2hex(random_bytes(8));
    $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(50));
    $first_login = 1;

    try {
         // Verificar se email já existe
        $checkEmail = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $checkEmail->execute([$email]);
        if ($checkEmail->fetch()) {
             echo "<script>alert('Erro: O e-mail fornecido já está em uso.'); window.location.href = 'admin.php';</script>";
             exit;
        }

        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, token, first_login) VALUES (?, ?, ?, 'nutritionist', ?, ?)");
        $stmt->execute([$name, $email, $hashed_password, $token, $first_login]);
        $user_id = $pdo->lastInsertId();

        // Envia email com senha temporária e link de confirmação
        if (sendConfirmationEmail($email, $name, $token, "register_admin", $temp_password)) {
            echo "<script>alert('Nutricionista adicionado com sucesso! Email de confirmação enviado.'); window.location.href = 'admin.php';</script>";
        } else {
            error_log("Falha ao enviar email de confirmação para nutricionista: $email");
            echo "<script>alert('Nutricionista adicionado, mas ocorreu um erro ao enviar o email de confirmação. Contacte o nutricionista manualmente.'); window.location.href = 'admin.php';</script>";
        }
        exit;
    } catch (PDOException $e) {
        error_log("Erro DB ao adicionar nutricionista: " . $e->getMessage());
         if ($e->errorInfo[1] == 1062) {
             echo "<script>alert('Erro: O e-mail fornecido já está em uso.'); window.location.href = 'admin.php';</script>";
         } else {
             echo "<script>alert('Erro na base de dados ao adicionar nutricionista.'); window.location.href = 'admin.php';</script>";
         }
        exit;
    } catch (Exception $e) {
        error_log("Erro geral ao adicionar nutricionista: " . $e->getMessage());
        echo "<script>alert('Erro inesperado ao adicionar nutricionista.'); window.location.href = 'admin.php';</script>";
        exit;
    }
}

// Processa criação de cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_client'])) {
    // ... (código de criação de cliente existente) ...
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

    try {
         // Verificar se email já existe
        $checkEmail = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $checkEmail->execute([$email]);
        if ($checkEmail->fetch()) {
             echo "<script>alert('Erro: O e-mail fornecido já está em uso.'); window.location.href = 'admin.php';</script>";
             exit;
        }

        // Insere o novo usuário no banco de dados com a senha temporária e o token
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, token, first_login) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hashed_password, $role, $token, $first_login]);
        $user_id = $pdo->lastInsertId(); // Obter ID do usuário

        // Criar entrada na tabela 'clients'
        $stmt_client = $pdo->prepare("INSERT INTO clients (user_id) VALUES (?)");
        $stmt_client->execute([$user_id]);


        // Envia o email de confirmação
        if (sendConfirmationEmail($email, $name, $token, "register_admin", $temp_password)) {
            echo "<script>alert('Cliente criado com sucesso! Um e-mail de confirmação foi enviado para o cliente.'); window.location.href = 'admin.php';</script>";
        } else {
             error_log("Falha ao enviar email de confirmação para cliente: $email");
            echo "<script>alert('Cliente criado, mas erro ao enviar o e-mail de confirmação. Contacte o cliente manualmente.'); window.location.href = 'admin.php';</script>";
        }
        exit;
     } catch (PDOException $e) {
        error_log("Erro DB ao criar cliente: " . $e->getMessage());
         if ($e->errorInfo[1] == 1062) {
             echo "<script>alert('Erro: O e-mail fornecido já está em uso.'); window.location.href = 'admin.php';</script>";
         } else {
             echo "<script>alert('Erro na base de dados ao criar cliente.'); window.location.href = 'admin.php';</script>";
         }
        exit;
    } catch (Exception $e) {
        error_log("Erro geral ao criar cliente: " . $e->getMessage());
        echo "<script>alert('Erro inesperado ao criar cliente.'); window.location.href = 'admin.php';</script>";
        exit;
    }
}
?>

<!-- INÍCIO DO CONTEÚDO PRINCIPAL -->
<main class="main-content">
    <div class="container mt-5"> <!-- Mantém o container para espaçamento -->
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
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="clients-tab" data-bs-toggle="tab" data-bs-target="#clients" type="button" role="tab" aria-controls="clients" aria-selected="false">Clientes</button>
            </li>
        </ul>

        <div class="tab-content" id="adminTabsContent">
            <!-- Aba de Aulas -->
            <div class="tab-pane fade show active" id="classes" role="tabpanel" aria-labelledby="classes-tab">
                <!-- Card Adicionar Aula com bg-light -->
                <div class="card mb-4 bg-light">
                    <div class="card-body">
                        <h4 class="text-dark">Adicionar Nova Aula</h4>
                        <form method="POST">
                            <!-- ... (campos do formulário de aula existentes) ... -->
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
                                    // Busca todos os trainers ativos
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

                <!-- Card Lista de Aulas com bg-light -->
                <div class="card bg-light">
                    <div class="card-body">
                        <h4 class="text-dark">Lista de Aulas</h4>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome</th>
                                        <th>Treinador</th>
                                        <th>Data/Hora</th>
                                        <th>Duração</th>
                                        <th>Vagas</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Busca todas as aulas com nome do treinador e contagem de participantes
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
                                        $vagas_disponiveis = $vagas_totais - $vagas_ocupadas;
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($class['id']) . "</td>";
                                        echo "<td>" . htmlspecialchars($class['name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($class['trainer_name']) . "</td>";
                                        echo "<td>" . date('d/m/Y H:i', strtotime($class['schedule'])) . "</td>";
                                        echo "<td>" . htmlspecialchars($class['duration']) . " min</td>";
                                        echo "<td>" . $vagas_ocupadas . "/" . $vagas_totais . "</td>";
                                        echo "<td>
                                              <a href='admin.php?action=delete&type=class&id=" . $class['id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Tem certeza que deseja excluir esta aula? As inscrições serão perdidas.\")'>Excluir</a>
                                             </td>";
                                        echo "</tr>";
                                    }

                                    if (count($classes) === 0) {
                                        echo "<tr><td colspan='7' class='text-center'>Nenhuma aula cadastrada</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Aba de Treinadores -->
            <div class="tab-pane fade" id="trainers" role="tabpanel" aria-labelledby="trainers-tab">
                <!-- Card Adicionar Treinador com bg-light -->
                <div class="card mb-4 bg-light">
                    <div class="card-body">
                        <h4 class="text-dark">Adicionar Novo Treinador</h4>
                        <form method="POST">
                            <!-- Campos ajustados para o fluxo com senha temporária -->
                            <div class="form-group mb-3">
                                <label for="trainer_name">Nome</label>
                                <input type="text" class="form-control" id="trainer_name" name="name" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="trainer_email">Email</label>
                                <input type="email" class="form-control" id="trainer_email" name="email" required>
                            </div>
                            <!-- O campo de senha foi removido daqui, pois é gerada automaticamente -->
                            <input type="hidden" name="add_trainer" value="1">
                            <button type="submit" class="btn btn-primary">Adicionar Treinador</button>
                        </form>
                    </div>
                </div>

                <!-- Card Lista de Treinadores com bg-light -->
                <div class="card bg-light">
                    <div class="card-body">
                        <h4 class="text-dark">Lista de Treinadores</h4>
                         <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Busca todos os treinadores
                                    $stmt_trainers_list = $pdo->query("SELECT id, name, email, token, first_login FROM users WHERE role = 'trainer' ORDER BY name");
                                    $trainers_list = $stmt_trainers_list->fetchAll();

                                    foreach ($trainers_list as $trainer) {
                                         $status = '';
                                        if ($trainer['token'] !== null) {
                                            $status = '<span class="badge bg-warning text-dark">Pendente Confirmação</span>';
                                        } elseif ($trainer['first_login'] == 1) {
                                            $status = '<span class="badge bg-info text-dark">Pendente Definição Senha</span>';
                                        } else {
                                            $status = '<span class="badge bg-success">Ativo</span>';
                                        }

                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($trainer['id']) . "</td>";
                                        echo "<td>" . htmlspecialchars($trainer['name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($trainer['email']) . "</td>";
                                        echo "<td>" . $status . "</td>";
                                        echo "<td>
                                              <a href='admin.php?action=delete&type=trainer&id=" . $trainer['id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Tem certeza que deseja excluir este treinador? Planos e aulas associados podem ser afetados.\")'>Excluir</a>
                                             </td>";
                                        echo "</tr>";
                                    }

                                    if (count($trainers_list) === 0) {
                                        echo "<tr><td colspan='5' class='text-center'>Nenhum treinador cadastrado</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Aba de Nutricionistas -->
            <div class="tab-pane fade" id="nutritionists" role="tabpanel" aria-labelledby="nutritionists-tab">
                <!-- Card Adicionar Nutricionista com bg-light -->
                <div class="card mb-4 bg-light">
                    <div class="card-body">
                        <h4 class="text-dark">Adicionar Novo Nutricionista</h4>
                        <form method="POST">
                             <!-- Campos ajustados para o fluxo com senha temporária -->
                            <div class="form-group mb-3">
                                <label for="nutritionist_name">Nome</label>
                                <input type="text" class="form-control" id="nutritionist_name" name="name" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="nutritionist_email">Email</label>
                                <input type="email" class="form-control" id="nutritionist_email" name="email" required>
                            </div>
                            <!-- O campo de senha foi removido daqui -->
                            <input type="hidden" name="add_nutritionist" value="1">
                            <button type="submit" class="btn btn-primary">Adicionar Nutricionista</button>
                        </form>
                    </div>
                </div>

                <!-- Card Lista de Nutricionistas com bg-light -->
                <div class="card bg-light">
                    <div class="card-body">
                        <h4 class="text-dark">Lista de Nutricionistas</h4>
                         <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Busca todos os nutricionistas
                                    $stmt_nutritionists_list = $pdo->query("SELECT id, name, email, token, first_login FROM users WHERE role = 'nutritionist' ORDER BY name");
                                    $nutritionists_list = $stmt_nutritionists_list->fetchAll();

                                    foreach ($nutritionists_list as $nutritionist) {
                                        $status = '';
                                        if ($nutritionist['token'] !== null) {
                                            $status = '<span class="badge bg-warning text-dark">Pendente Confirmação</span>';
                                        } elseif ($nutritionist['first_login'] == 1) {
                                            $status = '<span class="badge bg-info text-dark">Pendente Definição Senha</span>';
                                        } else {
                                            $status = '<span class="badge bg-success">Ativo</span>';
                                        }

                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($nutritionist['id']) . "</td>";
                                        echo "<td>" . htmlspecialchars($nutritionist['name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($nutritionist['email']) . "</td>";
                                         echo "<td>" . $status . "</td>";
                                        echo "<td>
                                              <a href='admin.php?action=delete&type=nutritionist&id=" . $nutritionist['id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Tem certeza que deseja excluir este nutricionista? Planos associados podem ser afetados.\")'>Excluir</a>
                                             </td>";
                                        echo "</tr>";
                                    }

                                    if (count($nutritionists_list) === 0) {
                                        echo "<tr><td colspan='5' class='text-center'>Nenhum nutricionista cadastrado</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Aba de Clientes -->
            <div class="tab-pane fade" id="clients" role="tabpanel" aria-labelledby="clients-tab">
                <!-- Card Criar Cliente com bg-light -->
                <div class="card mb-4 bg-light">
                    <div class="card-body">
                        <h3 class="text-dark">Criar Novo Cliente</h3>
                        <form method="POST">
                            <div class="form-group mb-3">
                                <label for="client_create_name">Nome do Cliente</label> <!-- ID alterado para evitar conflito -->
                                <input type="text" class="form-control" id="client_create_name" name="name" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="client_create_email">E-mail do Cliente</label> <!-- ID alterado para evitar conflito -->
                                <input type="email" class="form-control" id="client_create_email" name="email" required>
                            </div>
                            <input type="hidden" name="create_client" value="1">
                            <button type="submit" class="btn btn-primary">Criar Cliente</button>
                        </form>
                    </div>
                </div>
                 <!-- Card Lista de Clientes com bg-light -->
                 <div class="card bg-light">
                    <div class="card-body">
                        <h4 class="text-dark">Lista de Clientes</h4>
                         <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Busca todos os clientes
                                    $stmt_clients_list = $pdo->query("SELECT id, name, email, token, first_login FROM users WHERE role = 'client' ORDER BY name");
                                    $clients_list = $stmt_clients_list->fetchAll();

                                    foreach ($clients_list as $client) {
                                         $status = '';
                                        if ($client['token'] !== null) {
                                            $status = '<span class="badge bg-warning text-dark">Pendente Confirmação</span>';
                                        } elseif ($client['first_login'] == 1) {
                                            $status = '<span class="badge bg-info text-dark">Pendente Definição Senha</span>';
                                        } else {
                                            $status = '<span class="badge bg-success">Ativo</span>';
                                        }

                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($client['id']) . "</td>";
                                        echo "<td>" . htmlspecialchars($client['name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($client['email']) . "</td>";
                                        echo "<td>" . $status . "</td>";
                                        echo "<td>
                                              <a href='admin.php?action=delete&type=client&id=" . $client['id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Tem certeza que deseja excluir este cliente? Todos os seus dados (planos, inscrições) serão perdidos.\")'>Excluir</a>
                                             </td>";
                                        echo "</tr>";
                                    }

                                    if (count($clients_list) === 0) {
                                        echo "<tr><td colspan='5' class='text-center'>Nenhum cliente cadastrado</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div> 
    </div> 
</main>
<!-- FIM DO CONTEÚDO PRINCIPAL -->

<script>
    // Inicializa as abas do Bootstrap (já existente)
    document.addEventListener('DOMContentLoaded', function() {
        const triggerTabList = [].slice.call(document.querySelectorAll('#adminTabs button'));
        triggerTabList.forEach(function(triggerEl) {
            const tabTrigger = new bootstrap.Tab(triggerEl);
            triggerEl.addEventListener('click', function(event) {
                event.preventDefault();
                tabTrigger.show();
            });
        });

        // Manter a aba ativa após refresh (opcional, mas útil)
        var activeTab = localStorage.getItem('activeAdminTab');
        if (activeTab) {
            var someTabTriggerEl = document.querySelector(activeTab);
            if (someTabTriggerEl) {
                 var tab = new bootstrap.Tab(someTabTriggerEl);
                 tab.show();
            }
        }

        // Salvar a aba ativa no localStorage
        const tabButtons = document.querySelectorAll('#adminTabs button[data-bs-toggle="tab"]');
        tabButtons.forEach(el => {
            el.addEventListener('shown.bs.tab', function (event) {
                localStorage.setItem('activeAdminTab', event.target.getAttribute('data-bs-target'));
            });
        });
    });
</script>

<?php
// Inclui o rodapé da página
include 'footer.php';
?>