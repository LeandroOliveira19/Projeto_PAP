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

$recipes = [];
try {
    $stmt = $pdo->prepare("SELECT * from recipes ORDER BY name ASC"); // Ordenar por nome
    $stmt->execute();
    $recipes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao buscar receitas: " . $e->getMessage());
}
?>

<main class="main-content">
    <div class="container mt-5">
        <h2><i class="fas fa-utensil-spoon me-2"></i>Receitas Saudáveis</h2>
        <p class="text-muted" style="color: white !important;">Aqui estão algumas receitas saudáveis que você pode preparar.</p>

        <div class="row">
            <?php if (!empty($recipes)): ?>
                <?php foreach ($recipes as $recipe): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($recipe['name']) ?></h5>
                                <p class="card-text"><strong>Ingredientes:</strong><br><?= nl2br(htmlspecialchars($recipe['ingredients'])) ?></p>
                                <p class="card-text mt-auto"><strong>Modo de Preparo:</strong><br><?= nl2br(htmlspecialchars($recipe['instructions'])) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12"><div class="alert alert-info text-center">Nenhuma receita disponível no momento.</div></div>
            <?php endif; ?>
        </div>
        <a href="nutrition_hub.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left me-1"></i>Voltar à Central de Nutrição</a>
    </div>
</main>

<?php include 'footer.php'; ?>