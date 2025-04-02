<?php
session_start();

// Verifica se o usuário está logado e se é um cliente
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php'); // Redireciona para a página de login se não estiver logado
    exit;
}

// Inclui a conexão com o banco de dados
include 'db.php';

// Inclui o cabeçalho da página
include 'header.php';

// Busca todas as receitas no banco de dados
$stmt = $pdo->query("SELECT * FROM recipes");
$recipes = $stmt->fetchAll();
?>

<div class="container mt-5">
    <h2>Receitas Saudáveis</h2>
    <p>Aqui estão algumas receitas saudáveis que você pode preparar:</p>

    <div class="row">
        <?php foreach ($recipes as $recipe): ?>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($recipe['name']) ?></h5>
                        <p class="card-text">
                            <strong>Ingredientes:</strong><br>
                            <?= nl2br(htmlspecialchars($recipe['ingredients'])) ?>
                        </p>
                        <p class="card-text">
                            <strong>Modo de Preparo:</strong><br>
                            <?= nl2br(htmlspecialchars($recipe['instructions'])) ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <a href="nutrition_hub.php" class="btn btn-secondary">Voltar</a>
</div>

<?php
// Inclui o rodapé da página
include 'footer.php';
?>