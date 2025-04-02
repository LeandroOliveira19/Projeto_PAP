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
?>

<div class="container mt-5">
    <h2>Central de Nutrição</h2>
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link active" id="diet-plan-tab" data-toggle="tab" href="diet_plan.php">Plano Alimentar</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="recommendations-tab" data-toggle="tab" href="recommendations.php">Recomendações</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="recipes-tab" data-toggle="tab" href="recipes.php">Receitas</a>
        </li>
    </ul>

    <div class="tab-content mt-3">
        <!-- Plano Alimentar -->
        <div class="tab-pane fade show active" id="diet-plan">
            <?php
            // Consulta o plano alimentar do cliente
            $stmt = $pdo->prepare("SELECT dp.plan_details, u.name AS nutritionist_name 
                                   FROM diet_plans dp 
                                   JOIN users u ON dp.nutritionist_id = u.id
                                   JOIN clients c ON dp.client_id = c.id 
                                   WHERE c.user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $diet_plan = $stmt->fetch();

            if ($diet_plan) {
                echo "<p><strong>Nutricionista:</strong> " . htmlspecialchars($diet_plan['nutritionist_name']) . "</p>";
                echo "<p>" . nl2br(htmlspecialchars($diet_plan['plan_details'])) . "</p>";
            } else {
                echo "<p>Nenhum plano alimentar disponível.</p>";
            }
            ?>
        </div>

        <!-- Recomendações -->
        <div class="tab-pane fade" id="recommendations">
             <?php
                $stmt = $pdo->prepare("SELECT * from recommendations");
                $stmt->execute();
                $recommendations = $stmt->fetchAll();

                if ($recommendations) {
                   foreach ($recommendations as $recommendation){
                    echo "<h3>" . htmlspecialchars($recommendation['title']) . "</h3>";
                    echo "<p><strong>O que comer:</strong></p>";
                    echo "<p>" . nl2br(htmlspecialchars($recommendation['description'])) . "</p>";
                    echo "<p><strong>Hidratos de Carbono:</strong></p>";
                    echo "<p>" . nl2br(htmlspecialchars($recommendation['carbohydrates'])) . "</p>";
                    echo "<br>";
                   }
                } else {
                    echo "<p>Nenhuma recomendação disponível.</p>";
                }
             ?>
        </div>

        <!-- Receitas -->
        <div class="tab-pane fade" id="recipes">
            <?php
                $stmt = $pdo->prepare("SELECT * from recipes");
                $stmt->execute();
                $recipes = $stmt->fetchAll();

                if ($recipes) {
                   foreach ($recipes as $recipe){
                    echo "<h3>" . htmlspecialchars($recipe['name']) . "</h3>";
                    echo "<p><strong>Ingredientes:</strong></p>";
                    echo "<p>" . nl2br(htmlspecialchars($recipe['ingredients'])) . "</p>";
                    echo "<p><strong>Modo de Preparo:</strong></p>";
                    echo "<p>" . nl2br(htmlspecialchars($recipe['instructions'])) . "</p>";
                    echo "<br>";
                   }
                } else {
                    echo "<p>Nenhuma receita disponível.</p>";
                }
             ?>
        </div>
    </div>
    <a href="dashboard.php" class="btn btn-secondary mt-3">Voltar</a>
</div>
<script>
    $(function () {
        $('#myTab li:first-child a').tab('show')
    })
</script>

<?php
// Inclui o rodapé da página
include 'footer.php';
?>
