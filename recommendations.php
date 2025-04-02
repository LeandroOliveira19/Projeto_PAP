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

// Busca todas as recomendações no banco de dados
$stmt = $pdo->query("SELECT * FROM recommendations");
$recommendations = $stmt->fetchAll();
?>

<div class="container mt-5">
    <h2>Recomendações Nutricionais</h2>
    <p>Aqui estão algumas recomendações sobre o que comer em diferentes momentos do dia:</p>

    <div class="row">
        <?php foreach ($recommendations as $recommendation): ?>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($recommendation['title']) ?></h5>
                        <p class="card-text">
                            <strong>O que comer:</strong><br>
                            <?= nl2br(htmlspecialchars($recommendation['description'])) ?>
                        </p>
                        <p class="card-text">
                            <strong>Hidratos de Carbono:</strong><br>
                            <?= nl2br(htmlspecialchars($recommendation['carbohydrates'])) ?>
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