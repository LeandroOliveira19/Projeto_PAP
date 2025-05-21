<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
include 'db.php';
include 'header.php';
include 'send_email.php';

$active_page = 'manage_clients_admin.php';
include 'sidebar_admin.php';

// Processa exclusão de cliente
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $user_id_to_delete = $_GET['id']; // Este é o user_id
    try {
        $pdo->beginTransaction();

        // Obter o client_id da tabela clients
        $getClientIdStmt = $pdo->prepare("SELECT id FROM clients WHERE user_id = ?");
        $getClientIdStmt->execute([$user_id_to_delete]);
        $client_table_id = $getClientIdStmt->fetchColumn();

        if ($client_table_id) {
            // Excluir dependências (planos, participações, etc.)
            $pdo->prepare("DELETE FROM workout_plans WHERE client_id = ?")->execute([$client_table_id]);
            $pdo->prepare("DELETE FROM diet_plans WHERE client_id = ?")->execute([$client_table_id]);
            // Adicionar outras tabelas de dependência se houver (ex: history_assessments)
            $pdo->prepare("DELETE FROM history_assessments WHERE client_id = ?")->execute([$client_table_id]);
            // Excluir da tabela clients
            $pdo->prepare("DELETE FROM clients WHERE id = ?")->execute([$client_table_id]);
        }
        // Excluir participações em aulas (usa user_id)
        $pdo->prepare("DELETE FROM class_participants WHERE user_id = ?")->execute([$user_id_to_delete]);
        $pdo->prepare("DELETE FROM user_favorite_classes WHERE user_id = ?")->execute([$user_id_to_delete]);
        $pdo->prepare("DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?")->execute([$user_id_to_delete, $user_id_to_delete]);
        
        // Finalmente, excluir da tabela users
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'client'");
        $stmt->execute([$user_id_to_delete]);

        $pdo->commit();
        echo "<script>alert('Cliente excluído com sucesso!'); window.location.href = 'manage_clients_admin.php';</script>";
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erro ao excluir cliente: " . $e->getMessage());
        echo "<script>alert('Erro ao excluir cliente.'); window.location.href = 'manage_clients_admin.php';</script>";
        exit;
    }
}

// Processa criação de cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_client'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $temp_password = bin2hex(random_bytes(8));
    $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(50));
    $first_login = 1;

    try {
        $checkEmail = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $checkEmail->execute([$email]);
        if ($checkEmail->fetch()) {
             echo "<script>alert('Erro: O e-mail fornecido já está em uso.'); window.location.href = 'manage_clients_admin.php';</script>";
             exit;
        }
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, token, first_login) VALUES (?, ?, ?, 'client', ?, ?)");
        $stmt->execute([$name, $email, $hashed_password, $token, $first_login]);
        $new_user_id = $pdo->lastInsertId();
        // Criar entrada na tabela 'clients'
        $stmt_client = $pdo->prepare("INSERT INTO clients (user_id) VALUES (?)");
        $stmt_client->execute([$new_user_id]);

        if (sendConfirmationEmail($email, $name, $token, "register_admin", $temp_password)) {
            echo "<script>alert('Cliente criado! Email de confirmação enviado.'); window.location.href = 'manage_clients_admin.php';</script>";
        } else {
            error_log("Falha ao enviar email de confirmação para cliente: $email");
            echo "<script>alert('Cliente criado, mas erro ao enviar email. Contacte manualmente.'); window.location.href = 'manage_clients_admin.php';</script>";
        }
        exit;
    } catch (PDOException $e) {
        error_log("Erro DB ao criar cliente: " . $e->getMessage());
        echo "<script>alert('Erro BD ao criar cliente.'); window.location.href = 'manage_clients_admin.php';</script>";
        exit;
    }
}
?>
<main class="main-content">
    <div class="container mt-5">
        <h2>Gerir Clientes</h2>
        <div class="card mb-4 bg-light">
            <div class="card-body">
                <h4 class="text-dark">Criar Novo Cliente</h4>
                <form method="POST" action="manage_clients_admin.php">
                    <div class="form-group mb-3"><label for="client_name">Nome</label><input type="text" class="form-control" id="client_name" name="name" required></div>
                    <div class="form-group mb-3"><label for="client_email">Email</label><input type="email" class="form-control" id="client_email" name="email" required></div>
                    <input type="hidden" name="create_client" value="1">
                    <button type="submit" class="btn btn-primary">Criar Cliente</button>
                </form>
            </div>
        </div>

        <div class="card bg-light">
            <div class="card-body">
                <h4 class="text-dark">Lista de Clientes</h4>
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>ID</th><th>Nome</th><th>Email</th><th>Status</th><th>Ações</th></tr></thead>
                        <tbody>
                            <?php
                            $stmt_list = $pdo->query("SELECT id, name, email, token, first_login FROM users WHERE role = 'client' ORDER BY name");
                            $list = $stmt_list->fetchAll();
                            foreach ($list as $item) {
                                $status = ($item['token'] !== null) ? '<span class="badge bg-warning text-dark">Pendente Conf.</span>' : (($item['first_login'] == 1) ? '<span class="badge bg-info text-dark">Pendente Senha</span>' : '<span class="badge bg-success">Ativo</span>');
                                echo "<tr><td>" . htmlspecialchars($item['id']) . "</td><td>" . htmlspecialchars($item['name']) . "</td><td>" . htmlspecialchars($item['email']) . "</td><td>" . $status . "</td><td>
                                      <a href='manage_clients_admin.php?action=delete&id=" . $item['id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Excluir este cliente e todos os seus dados associados?\")'>Excluir</a>
                                     </td></tr>";
                            }
                            if (count($list) === 0) echo "<tr><td colspan='5' class='text-center'>Nenhum cliente cadastrado</td></tr>";
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include 'footer.php'; ?>