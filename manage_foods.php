<?php
session_start();
// Verifica se o usuário está logado e se é um nutricionista
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'nutritionist') {
    header('Location: login.php');
    exit;
}
include 'db.php'; // Conexão com o banco de dados
include 'header.php'; // Cabeçalho HTML, CSS, e ícone de perfil

// Define a página ativa para a sidebar
$active_page = 'manage_foods.php'; // Nova página ativa
$user_role = $_SESSION['role'];
$nutritionist_id = $_SESSION['user_id']; // ID do nutricionista logado (ainda usado para adicionar)

$message = '';
$message_type = '';

$food_to_edit = null; // Para guardar dados do alimento a editar

// Processar adição de novo alimento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_food'])) {
    $name = trim($_POST['name']);
    $calories = filter_input(INPUT_POST, 'calories', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $protein = filter_input(INPUT_POST, 'protein', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $carbs = filter_input(INPUT_POST, 'carbs', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $fat = filter_input(INPUT_POST, 'fat', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $serving_size = trim($_POST['serving_size']); // Ex: 100
    $unit = trim($_POST['unit']); // Ex: g, ml, unidade
    // *** NOVO: Pega category_id ***
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    if ($category_id === false || $category_id <= 0) { $category_id = null; } // Define como NULL se inválido ou não selecionado

    if (empty($name) || $calories === false || $protein === false || $carbs === false || $fat === false || empty($serving_size) || empty($unit)) {
        $message = "Por favor, preencha todos os campos obrigatórios corretamente.";
        $message_type = 'danger';
    } else {
        try {
            // *** ATUALIZADO: Inclui category_id no INSERT ***
            $stmt = $pdo->prepare("INSERT INTO foods (name, calories, protein, carbs, fat, serving_size, unit, category_id, added_by_nutritionist_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $calories, $protein, $carbs, $fat, $serving_size, $unit, $category_id, $nutritionist_id]);
            $message = "Alimento '".htmlspecialchars($name)."' adicionado com sucesso!";
            $message_type = 'success';
        } catch (PDOException $e) {
            error_log("Erro ao adicionar alimento: " . $e->getMessage());
            if ($e->errorInfo[1] == 1062) { // Duplicate entry
                $message = "Erro: Já existe um alimento com este nome.";
            } else {
                $message = "Erro ao adicionar alimento no banco de dados.";
            }
            $message_type = 'danger';
        }
    }
}

// Atualizar Alimento Existente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_food'])) {
    $food_id = filter_input(INPUT_POST, 'food_id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name']);
    $calories = filter_input(INPUT_POST, 'calories', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $protein = filter_input(INPUT_POST, 'protein', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $carbs = filter_input(INPUT_POST, 'carbs', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $fat = filter_input(INPUT_POST, 'fat', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $serving_size = trim($_POST['serving_size']);
    $unit = trim($_POST['unit']);
    // *** NOVO: Pega category_id ***
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    if ($category_id === false || $category_id <= 0) { $category_id = null; }

    if (empty($food_id) || empty($name) || $calories === false || $protein === false || $carbs === false || $fat === false || empty($serving_size) || empty($unit)) {
        $message = "Erro ao atualizar. Dados inválidos ou faltando.";
        $message_type = 'danger';
    } else {
        try {
            // *** REMOVIDA A VERIFICAÇÃO added_by_nutritionist_id ***
            // *** ATUALIZADO: Inclui category_id no UPDATE ***
            $stmt = $pdo->prepare("UPDATE foods SET name = ?, calories = ?, protein = ?, carbs = ?, fat = ?, serving_size = ?, unit = ?, category_id = ? WHERE id = ?");
            $stmt->execute([$name, $calories, $protein, $carbs, $fat, $serving_size, $unit, $category_id, $food_id]);

            if ($stmt->rowCount() > 0) {
                $message = "Alimento '".htmlspecialchars($name)."' atualizado com sucesso!";
                $message_type = 'success';
            } else {
                $message = "Nenhuma alteração detetada no alimento.";
                $message_type = 'info';
            }
        } catch (PDOException $e) {
            error_log("Erro ao atualizar alimento: " . $e->getMessage());
            if ($e->errorInfo[1] == 1062) {
                $message = "Erro: Já existe outro alimento com este nome.";
            } else {
                $message = "Erro ao atualizar alimento no banco de dados.";
            }
            $message_type = 'danger';
            // Recarrega dados para edição em caso de erro
            $_GET['action'] = 'edit'; $_GET['id'] = $food_id;
        }
    }
}

// Excluir Alimento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_food'])) {
    $food_id = filter_input(INPUT_POST, 'food_id', FILTER_VALIDATE_INT);

    if (empty($food_id)) {
        $message = "ID do alimento inválido para exclusão.";
        $message_type = 'danger';
    } else {
        try {
            // *** REMOVIDA A VERIFICAÇÃO added_by_nutritionist_id ***
            $stmt = $pdo->prepare("DELETE FROM foods WHERE id = ?");
            $stmt->execute([$food_id]);

            if ($stmt->rowCount() > 0) {
                $message = "Alimento excluído com sucesso!";
                $message_type = 'success';
            } else {
                $message = "Alimento não encontrado.";
                $message_type = 'warning';
            }
        } catch (PDOException $e) {
            error_log("Erro ao excluir alimento: " . $e->getMessage());
            // Verificar se há restrições de chave estrangeira (ex: alimento em uso em planos)
            if ($e->errorInfo[1] == 1451) { // Código de erro para foreign key constraint
                 $message = "Erro: Este alimento não pode ser excluído pois está sendo usado em um ou mais planos alimentares.";
            } else {
                $message = "Erro ao excluir alimento do banco de dados.";
            }
            $message_type = 'danger';
        }
    }
}

// --- Buscar TODAS as Categorias para o Select ---
$categories = [];
try {
    $stmt_cat = $pdo->query("SELECT id, name FROM food_categories ORDER BY name ASC");
    $categories = $stmt_cat->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao buscar categorias de alimentos: " . $e->getMessage());
    // Não é fatal, mas o select ficará vazio
}

// --- Buscar TODOS os Alimentos para a Lista ---
$foods = [];
try {
    // *** REMOVIDA A VERIFICAÇÃO added_by_nutritionist_id ***
    // *** ADICIONADO JOIN para buscar nome da categoria ***
    $stmt_foods = $pdo->prepare("
        SELECT f.*, fc.name as category_name
        FROM foods f
        LEFT JOIN food_categories fc ON f.category_id = fc.id
        ORDER BY f.name ASC
    ");
    $stmt_foods->execute();
    $foods = $stmt_foods->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao buscar alimentos: " . $e->getMessage());
    $message = "Erro ao carregar a lista de alimentos.";
    $message_type = 'danger';
}

// --- Processamento de Ações GET (Editar) ---
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $food_id_to_edit = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($food_id_to_edit) {
        try {
            // *** REMOVIDA A VERIFICAÇÃO added_by_nutritionist_id ***
            $stmt = $pdo->prepare("SELECT * FROM foods WHERE id = ?");
            $stmt->execute([$food_id_to_edit]);
            $food_to_edit = $stmt->fetch();
            if (!$food_to_edit) {
                $message = "Alimento para edição não encontrado.";
                $message_type = 'warning';
            }
        } catch (PDOException $e) {
            error_log("Erro ao buscar alimento para editar: " . $e->getMessage());
            $message = "Erro ao carregar dados do alimento para edição.";
            $message_type = 'danger';
        }
    }
}

include 'sidebar_nutritionist.php'; // Inclui a sidebar específica
?>

<!-- Wrapper para o Conteúdo Principal -->
<main class="main-content">
    <div class="container mt-5 mb-5">
        <h2><i class="fas fa-carrot me-2"></i>Gerenciar Alimentos</h2>
        <p>Adicione, edite ou remova alimentos disponíveis para usar nos planos alimentares.</p>

        <?php if ($message): ?>
            <div class="alert alert-<?= htmlspecialchars($message_type) ?> alert-dismissible fade show" role="alert">
                <?= $message // Pode conter HTML seguro como 'htmlspecialchars($name)' ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Formulário para Adicionar/Editar Alimento -->
        <div class="card shadow-sm mb-4">
            <div class="card-header"><h5 class="mb-0"><?= $food_to_edit ? 'Editar Alimento' : 'Adicionar Novo Alimento' ?></h5></div>
            <div class="card-body">
                <form method="POST" action="manage_foods.php">
                    <?php if ($food_to_edit): ?>
                        <input type="hidden" name="food_id" value="<?= $food_to_edit['id'] ?>">
                    <?php endif; ?>
                    <div class="row g-3">
                        <div class="col-md-6"><label for="name" class="form-label">Nome do Alimento</label><input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($food_to_edit['name'] ?? '') ?>" required></div>
                        <div class="col-md-3"><label for="serving_size" class="form-label">Porção</label><input type="text" class="form-control" id="serving_size" name="serving_size" placeholder="Ex: 100" value="<?= htmlspecialchars($food_to_edit['serving_size'] ?? '') ?>" required></div>
                        <div class="col-md-3"><label for="unit" class="form-label">Unidade</label><input type="text" class="form-control" id="unit" name="unit" placeholder="Ex: g, ml, unidade" value="<?= htmlspecialchars($food_to_edit['unit'] ?? '') ?>" required></div>
                        <div class="col-md-3"><label for="calories" class="form-label">Calorias (kcal)</label><input type="text" inputmode="decimal" class="form-control" id="calories" name="calories" value="<?= htmlspecialchars($food_to_edit['calories'] ?? '') ?>" required></div>
                        <div class="col-md-3"><label for="protein" class="form-label">Proteína (g)</label><input type="text" inputmode="decimal" class="form-control" id="protein" name="protein" value="<?= htmlspecialchars($food_to_edit['protein'] ?? '') ?>" required></div>
                        <div class="col-md-3"><label for="carbs" class="form-label">Carboidratos (g)</label><input type="text" inputmode="decimal" class="form-control" id="carbs" name="carbs" value="<?= htmlspecialchars($food_to_edit['carbs'] ?? '') ?>" required></div>
                        <div class="col-md-3"><label for="fat" class="form-label">Gordura (g)</label><input type="text" inputmode="decimal" class="form-control" id="fat" name="fat" value="<?= htmlspecialchars($food_to_edit['fat'] ?? '') ?>" required></div>
                        <!-- *** NOVO: Select de Categoria *** -->
                        <div class="col-md-12">
                             <label for="category_id" class="form-label">Categoria</label>
                             <select class="form-select" id="category_id" name="category_id">
                                 <option value="">-- Sem Categoria --</option>
                                 <?php foreach ($categories as $category): ?>
                                     <option value="<?= $category['id'] ?>" <?= (isset($food_to_edit['category_id']) && $food_to_edit['category_id'] == $category['id']) ? 'selected' : '' ?>>
                                         <?= htmlspecialchars($category['name']) ?>
                                     </option>
                                 <?php endforeach; ?>
                             </select>
                        </div>
                        <!-- ****************************** -->
                    </div>

                    <?php if ($food_to_edit): ?>
                        <button type="submit" name="update_food" class="btn btn-primary mt-3">Atualizar Alimento</button>
                        <a href="manage_foods.php" class="btn btn-secondary mt-3">Cancelar Edição</a>
                    <?php else: ?>
                        <button type="submit" name="add_food" class="btn btn-success mt-3">Adicionar Alimento</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Tabela de Alimentos (Todos) -->
        <div class="card shadow-sm">
             <div class="card-header"><h5 class="mb-0">Todos os Alimentos</h5></div>
             <div class="card-body">
                 <div class="table-responsive">
                     <table class="table table-striped table-hover">
                         <thead>
                             <tr><th>Nome</th><th>Categoria</th><th>Porção</th><th>Unidade</th><th>Kcal</th><th>Prot.</th><th>Carb.</th><th>Gord.</th><th class="text-end">Ações</th></tr>
                         </thead>
                         <tbody>
                             <?php if (empty($foods)): ?>
                                 <tr><td colspan="9" class="text-center text-muted">Nenhum alimento adicionado ainda.</td></tr>
                             <?php else: ?>
                                 <?php foreach ($foods as $food): ?>
                                     <tr>
                                         <td><?= htmlspecialchars($food['name']) ?></td>
                                         <td><?= htmlspecialchars($food['category_name'] ?? 'N/D') ?></td> <!-- Mostra nome da categoria -->
                                         <td><?= htmlspecialchars($food['serving_size']) ?></td>
                                         <td><?= htmlspecialchars($food['unit']) ?></td>
                                         <td><?= htmlspecialchars(number_format($food['calories'], 0, ',', '.')) ?></td>
                                         <td><?= htmlspecialchars(number_format($food['protein'], 1, ',', '.')) ?></td>
                                         <td><?= htmlspecialchars(number_format($food['carbs'], 1, ',', '.')) ?></td>
                                         <td><?= htmlspecialchars(number_format($food['fat'], 1, ',', '.')) ?></td>
                                         <td class="text-end">
                                             <?php // *** REMOVIDA A CONDIÇÃO: Botões sempre visíveis *** ?>
                                             <a href="manage_foods.php?action=edit&id=<?= $food['id'] ?>" class="btn btn-warning btn-sm" title="Editar">
                                                 <i class="fas fa-edit"></i>
                                             </a>
                                             <form method="POST" action="manage_foods.php" style="display: inline-block;" onsubmit="return confirm('Tem certeza que deseja excluir o alimento \'<?= htmlspecialchars(addslashes($food['name'])) ?>\'? Esta ação não pode ser desfeita e pode afetar planos existentes.');">
                                                 <input type="hidden" name="food_id" value="<?= $food['id'] ?>">
                                                 <button type="submit" name="delete_food" class="btn btn-danger btn-sm" title="Excluir">
                                                     <i class="fas fa-trash-alt"></i>
                                                 </button>
                                             </form>
                                         </td>
                                     </tr>
                                 <?php endforeach; ?>
                             <?php endif; ?>
                         </tbody>
                     </table>
                 </div>
             </div>
        </div>

    </div>
</main> <!-- FIM: Wrapper <main> -->

<?php include 'footer.php'; // Inclui o rodapé HTML e scripts ?>
