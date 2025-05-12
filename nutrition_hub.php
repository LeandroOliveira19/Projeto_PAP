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

// *** ADICIONADO: Define qual link da sidebar deve estar ativo ***
$active_page = basename($_SERVER['PHP_SELF']);
?>

<?php include 'sidebar_client.php'; ?>

<!-- *** ADICIONADO: Wrapper <main> para o conteúdo principal *** -->
<main class="main-content">
    <div class="container mt-5"> <!-- Container original mantido dentro do main -->
        <h2>Central de Nutrição</h2>
        <ul class="nav nav-tabs" id="nutritionTabs"> <!-- ID alterado para evitar conflito -->
            <li class="nav-item">
                <!-- Links diretos para as páginas -->
                <a class="nav-link active" id="diet-plan-tab" data-bs-toggle="tab" href="#diet-plan-content">Plano Alimentar</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="recommendations-tab" data-bs-toggle="tab" href="#recommendations-content">Recomendações</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="recipes-tab" data-bs-toggle="tab" href="#recipes-content">Receitas</a>
            </li>
        </ul>

        <div class="tab-content mt-3" id="nutritionTabsContent"> <!-- ID alterado -->
            <!-- Plano Alimentar -->
            <div class="tab-pane fade show active" id="diet-plan-content" role="tabpanel" aria-labelledby="diet-plan-tab"> <!-- ID alterado -->
                <?php
                // (Conteúdo PHP e HTML existente para esta aba - sem alterações aqui)
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
            <div class="tab-pane fade" id="recommendations-content" role="tabpanel" aria-labelledby="recommendations-tab"> <!-- ID alterado -->
                 <?php
                 // (Conteúdo PHP e HTML existente para esta aba - sem alterações aqui)
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
            <div class="tab-pane fade" id="recipes-content" role="tabpanel" aria-labelledby="recipes-tab"> <!-- ID alterado -->
                <?php
                // (Conteúdo PHP e HTML existente para esta aba - sem alterações aqui)
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
    </div> <!-- Fim do container original -->
</main> <!-- *** FIM: Wrapper <main> *** -->

<script>
    // Script JS existente para as abas (sem alterações aqui)
    // Note: Bootstrap 5 usa data-bs-toggle, não data-toggle
    /*
    $(function () {
        $('#nutritionTabs li:first-child a').tab('show') // ID atualizado se usar jQuery
    })
    */
    // Ou usando JS puro do Bootstrap 5:
    document.addEventListener('DOMContentLoaded', function() {
        var firstTabEl = document.querySelector('#nutritionTabs li:first-child a');
        if (firstTabEl) {
            var tab = new bootstrap.Tab(firstTabEl);
            // tab.show(); // Descomente se quiser forçar a primeira aba a ser ativa via JS
        }
    });
</script>

<?php
// Inclui o rodapé da página
include 'footer.php';
?>
