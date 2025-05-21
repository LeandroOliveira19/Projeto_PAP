<?php
// Inicia a sessão
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

include 'sidebar_client.php';

$recommendations = [];
try {
    $stmt = $pdo->prepare("SELECT * from recommendations ORDER BY title ASC"); // Ordenar por título
    $stmt->execute();
    $recommendations = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao buscar recomendações: " . $e->getMessage());
}

?>

<main class="main-content">
    <div class="container mt-5">
        <h2><i class="fas fa-lightbulb me-2"></i>Recomendações Nutricionais</h2>
        <p class="text-muted" style="color: white !important;">Aqui estão algumas recomendações sobre o que comer em diferentes momentos do dia.</p>

        <?php if (!empty($recommendations)): ?>
            <?php foreach ($recommendations as $recommendation): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light"><h5 class="mb-0 text-dark"><?= htmlspecialchars($recommendation['title']) ?></h5></div>
                    <div class="card-body">
                        <p><strong>O que comer:</strong><br><?= nl2br(htmlspecialchars($recommendation['description'])) ?></p>
                        <p><strong>Hidratos de Carbono:</strong><br><?= nl2br(htmlspecialchars($recommendation['carbohydrates'])) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info text-center">Nenhuma recomendação nutricional disponível no momento.</div>
        <?php endif; ?>
        <a href="nutrition_hub.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left me-1"></i>Voltar à Central de Nutrição</a>
    </div>
</main>

<?php include 'footer.php'; ?>