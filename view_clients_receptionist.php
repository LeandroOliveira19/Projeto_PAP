<?php
session_start();
// Verifica se o usuário está logado e se é um recepcionista
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'receptionist') {
    header('Location: login.php');
    exit;
}

include 'db.php'; // Conexão com o banco de dados
include 'header.php'; // Inclui o cabeçalho

// Define a página ativa para a sidebar
$active_page = basename($_SERVER['PHP_SELF']); // view_clients_receptionist.php

// Inclui a sidebar do recepcionista
include 'sidebar_receptionist.php';

$clients = [];
$error_message = '';

try {
    // Busca todos os utilizadores com a role 'client'
    $sql = "SELECT u.id, u.name, u.email, u.numero_telemovel FROM users u WHERE u.role = 'client' ORDER BY u.name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Erro ao buscar clientes: " . $e->getMessage();
    error_log("PDOException in view_clients_receptionist.php: " . $e->getMessage());
}
?>

<!-- Conteúdo Principal -->
<main class="main-content">
    <div class="container mt-4">
        <div class="row mb-3">
            <div class="col">
                <h2 class="h4"><i class="fas fa-users me-2"></i>Lista de Clientes</h2>
                <p class="text-muted">Informações básicas dos clientes registados.</p>
            </div>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <?php if (empty($clients) && !$error_message): ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle me-2"></i>Nenhum cliente registado no sistema.
                    </div>
                <?php elseif (!empty($clients)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Nome</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Telemóvel</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clients as $client): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($client['name']) ?></td>
                                        <td><?= htmlspecialchars($client['email']) ?></td>
                                        <td><?= htmlspecialchars($client['numero_telemovel'] ?? 'N/D') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div> <!-- /.card-body -->
        </div> <!-- /.card -->

    </div><!-- /.container -->
</main> <!-- /.main-content -->

<?php
// Inclui o rodapé da página
include 'footer.php';
?>