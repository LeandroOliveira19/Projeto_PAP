<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'receptionist') {
    header('Location: login.php');
    exit;
}
include 'db.php';
include 'header.php';

$active_page = basename($_SERVER['PHP_SELF']); // receptionist.php
include 'sidebar_receptionist.php';

$receptionist_name = $_SESSION['name'] ?? 'Recepcionista';

// Exemplo de estatísticas simples para o recepcionista
$upcoming_classes_count = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM classes WHERE schedule >= NOW() AND schedule <= DATE_ADD(NOW(), INTERVAL 24 HOUR)");
    $upcoming_classes_count = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Erro ao buscar aulas próximas para receptionist.php: " . $e->getMessage());
}

?>

<main class="main-content">
    <div class="container mt-5">
        <h2>Painel do Recepcionista</h2>
        <p>Bem-vindo(a), <?= htmlspecialchars($receptionist_name) ?>!</p>

        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="card text-white bg-info shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-check fa-3x mb-2"></i>
                        <h5 class="card-title"><?= htmlspecialchars($upcoming_classes_count) ?></h5>
                        <p class="card-text">Aulas Próximas (24h)</p>
                        <a href="view_schedule_receptionist.php" class="btn btn-light btn-sm mt-2">Ver Horário</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card text-white bg-success shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-3x mb-2"></i>
                        <h5 class="card-title">Clientes</h5>
                        <p class="card-text">Consultar lista de clientes</p>
                        <a href="view_clients_receptionist.php" class="btn btn-light btn-sm mt-2">Ver Clientes</a>
                    </div>
                </div>
            </div>
            <!-- Adicionar mais cards para outras funcionalidades (ex: Check-in) -->
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>