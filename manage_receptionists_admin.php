<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
include 'db.php';
include 'header.php';
include 'send_email.php'; // Para enviar email de confirmação

$active_page = 'manage_receptionists_admin.php'; // Define a página ativa
include 'sidebar_admin.php';

// Processa exclusão de recepcionista
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        // Adicionar lógica para lidar com dependências se necessário (geralmente não há muitas para recepcionista)
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'receptionist'");
        $stmt->execute([$id]);
        echo "<script>alert('Recepcionista excluído com sucesso!'); window.location.href = 'manage_receptionists_admin.php';</script>";
        exit;
    } catch (Exception $e) {
        error_log("Erro ao excluir recepcionista: " . $e->getMessage());
        echo "<script>alert('Erro ao excluir recepcionista.'); window.location.href = 'manage_receptionists_admin.php';</script>";
        exit;
    }
}

// Processa adição de recepcionista
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_receptionist'])) {
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
             echo "<script>alert('Erro: O e-mail fornecido já está em uso.'); window.location.href = 'manage_receptionists_admin.php';</script>";
             exit;
        }
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, token, first_login) VALUES (?, ?, ?, 'receptionist', ?, ?)");
        $stmt->execute([$name, $email, $hashed_password, $token, $first_login]);
        if (sendConfirmationEmail($email, $name, $token, "register_admin", $temp_password)) {
            echo "<script>alert('Recepcionista adicionado! Email de confirmação enviado.'); window.location.href = 'manage_receptionists_admin.php';</script>";
        } else {
            error_log("Falha ao enviar email de confirmação para recepcionista: $email");
            echo "<script>alert('Recepcionista adicionado, mas erro ao enviar email. Contacte manualmente.'); window.location.href = 'manage_receptionists_admin.php';</script>";
        }
        exit;
    } catch (PDOException $e) {
        error_log("Erro DB ao adicionar recepcionista: " . $e->getMessage());
        echo "<script>alert('Erro BD ao adicionar recepcionista.'); window.location.href = 'manage_receptionists_admin.php';</script>";
        exit;
    }
}
?>
<main class="main-content">
    <div class="container mt-5">
        <h2>Gerir Recepcionistas</h2>
        <div class="card mb-4 bg-light">
            <div class="card-body">
                <h4 class="text-dark">Adicionar Novo Recepcionista</h4>
                <form method="POST" action="manage_receptionists_admin.php">
                    <div class="form-group mb-3"><label for="receptionist_name">Nome</label><input type="text" class="form-control" id="receptionist_name" name="name" required></div>
                    <div class="form-group mb-3"><label for="receptionist_email">Email</label><input type="email" class="form-control" id="receptionist_email" name="email" required></div>
                    <input type="hidden" name="add_receptionist" value="1">
                    <button type="submit" class="btn btn-primary">Adicionar Recepcionista</button>
                </form>
            </div>
        </div>

        <div class="card bg-light">
            <div class="card-body">
                <h4 class="text-dark">Lista de Recepcionistas</h4>
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>ID</th><th>Nome</th><th>Email</th><th>Status</th><th>Ações</th></tr></thead>
                        <tbody>
                            <?php
                            $stmt_list = $pdo->query("SELECT id, name, email, token, first_login FROM users WHERE role = 'receptionist' ORDER BY name");
                            $list = $stmt_list->fetchAll();
                            foreach ($list as $item) {
                                $status = ($item['token'] !== null) ? '<span class="badge bg-warning text-dark">Pendente Conf.</span>' : (($item['first_login'] == 1) ? '<span class="badge bg-info text-dark">Pendente Senha</span>' : '<span class="badge bg-success">Ativo</span>');
                                echo "<tr><td>" . htmlspecialchars($item['id']) . "</td><td>" . htmlspecialchars($item['name']) . "</td><td>" . htmlspecialchars($item['email']) . "</td><td>" . $status . "</td><td>
                                      <a href='manage_receptionists_admin.php?action=delete&id=" . $item['id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Excluir este recepcionista?\")'>Excluir</a>
                                     </td></tr>";
                            }
                            if (count($list) === 0) echo "<tr><td colspan='5' class='text-center'>Nenhum recepcionista cadastrado</td></tr>";
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include 'footer.php'; ?>