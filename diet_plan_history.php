<?php
session_start();

// Verifica se o usuário está logado e se é um cliente
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

// Inclui a conexão com o banco de dados
include 'db.php';

// Inclui o cabeçalho da página
include 'header.php';

// Define qual link da sidebar deve estar ativo
$active_page = basename($_SERVER['PHP_SELF']);
// Inclui a sidebar do cliente
include 'sidebar_client.php';

$user_id = $_SESSION['user_id'];
$page_error = '';
$inactive_plans = [];

try {
    // 1. Obter o client_id para o utilizador logado
    $stmt_client = $pdo->prepare("SELECT id FROM clients WHERE user_id = ? LIMIT 1");
    $stmt_client->execute([$user_id]);
    $client_id = $stmt_client->fetchColumn();

    if (!$client_id) {
        throw new Exception("Registo de cliente não encontrado.");
    }

    // 2. Buscar todos os planos INATIVOS do cliente
    $stmt_plans = $pdo->prepare("
        SELECT dp.id, dp.title, dp.plan_details, dp.created_at, u.name AS nutritionist_name
        FROM diet_plans dp
        JOIN users u ON dp.nutritionist_id = u.id
        WHERE dp.client_id = ? AND dp.is_active = 0
        ORDER BY dp.created_at DESC
    ");
    $stmt_plans->execute([$client_id]);
    $inactive_plans = $stmt_plans->fetchAll();

} catch (Exception $e) {
    $page_error = "Erro ao carregar o histórico: " . $e->getMessage();
    error_log("Erro em diet_plan_history.php: " . $e->getMessage());
}
?>

<main class="main-content">
    <div class="container mt-5">
        <h2><i class="fas fa-history me-2"></i>Histórico de Planos Alimentares</h2>

        <?php if ($page_error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($page_error) ?></div>
        <?php elseif (empty($inactive_plans)): ?>
            <div class="alert alert-info text-center">Nenhum plano alimentar antigo encontrado.</div>
        <?php else: ?>
            <p class="text-white">Aqui pode consultar os seus planos alimentares anteriores.</p>
            <div class="accordion" id="historyAccordion">
                <?php foreach ($inactive_plans as $plan): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading-<?= $plan['id'] ?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= $plan['id'] ?>" aria-expanded="false" aria-controls="collapse-<?= $plan['id'] ?>">
                                <i class="fas fa-calendar-alt me-2"></i>
                                Plano de <?= htmlspecialchars(date('d/m/Y', strtotime($plan['created_at']))) ?> (Nutricionista: <?= htmlspecialchars($plan['nutritionist_name']) ?>)
                            </button>
                        </h2>
                        <div id="collapse-<?= $plan['id'] ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?= $plan['id'] ?>" data-bs-parent="#historyAccordion">
                            <div class="accordion-body">
                                <?php if (!empty($plan['title'])): ?>
                                    <h5 class="card-title text-primary"><?= htmlspecialchars($plan['title']) ?></h5>
                                <?php endif; ?>
                                <h6>Observações Gerais do Plano:</h6>
                                <?php if (!empty($plan['plan_details'])): ?>
                                    <p><?= nl2br(htmlspecialchars($plan['plan_details'])) ?></p>
                                <?php else: ?>
                                    <p class="text-muted fst-italic">Nenhuma observação geral para este plano.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="nutrition_hub.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar ao Plano Ativo</a>
        </div>
    </div>
</main>

<?php
include 'footer.php';
?>

