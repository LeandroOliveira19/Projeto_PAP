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
$active_page = 'manage_recipes.php'; // Nova página ativa
$user_role = $_SESSION['role'];
$nutritionist_id = $_SESSION['user_id']; // ID do nutricionista logado (ainda usado para adicionar)

$message = '';
$message_type = '';
$recipe_to_edit = null; // Para guardar dados da receita a editar

// --- Processamento de Ações POST ---

// Adicionar Nova Receita
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_recipe'])) {
    $name = trim($_POST['name']);
    $ingredients = trim($_POST['ingredients']);
    $instructions = trim($_POST['instructions']);

    if (empty($name) || empty($ingredients) || empty($instructions)) {
        $message = "Por favor, preencha todos os campos da receita.";
        $message_type = 'danger';
    } else {
        try {
            // Insere associando ao nutricionista que adicionou
            $stmt = $pdo->prepare("INSERT INTO recipes (name, ingredients, instructions, added_by_nutritionist_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $ingredients, $instructions, $nutritionist_id]);
            $message = "Receita '".htmlspecialchars($name)."' adicionada com sucesso!";
            $message_type = 'success';
        } catch (PDOException $e) {
            error_log("Erro ao adicionar receita: " . $e->getMessage());
            // Verifica erro de duplicidade (ajuste o código 1062 se necessário, ou a constraint unique)
            if ($e->errorInfo[1] == 1062) {
                // Ajustar a mensagem dependendo se o nome é único globalmente ou por nutricionista
                $message = "Erro: Já existe uma receita com este nome.";
            } else {
                $message = "Erro ao adicionar receita no banco de dados.";
            }
            $message_type = 'danger';
        }
    }
}

// Atualizar Receita Existente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_recipe'])) {
    $recipe_id = filter_input(INPUT_POST, 'recipe_id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name']);
    $ingredients = trim($_POST['ingredients']);
    $instructions = trim($_POST['instructions']);

    if (empty($recipe_id) || empty($name) || empty($ingredients) || empty($instructions)) {
        $message = "Erro ao atualizar. Dados inválidos ou faltando.";
        $message_type = 'danger';
    } else {
        try {
            // *** REMOVIDA A VERIFICAÇÃO: Qualquer nutricionista pode editar ***
            $stmt = $pdo->prepare("UPDATE recipes SET name = ?, ingredients = ?, instructions = ? WHERE id = ?");
            // $stmt->execute([$name, $ingredients, $instructions, $recipe_id, $nutritionist_id]); // Não precisa mais do nutritionist_id
            $stmt->execute([$name, $ingredients, $instructions, $recipe_id]);

            if ($stmt->rowCount() > 0) {
                $message = "Receita '".htmlspecialchars($name)."' atualizada com sucesso!";
                $message_type = 'success';
            } else {
                // Mensagem pode indicar que não houve alteração
                $message = "Nenhuma alteração detetada na receita.";
                $message_type = 'info';
            }
        } catch (PDOException $e) {
            error_log("Erro ao atualizar receita: " . $e->getMessage());
             if ($e->errorInfo[1] == 1062) {
                $message = "Erro: Já existe outra receita com este nome.";
            } else {
                $message = "Erro ao atualizar receita no banco de dados.";
            }
            $message_type = 'danger';
            // Recarrega dados para edição em caso de erro
            $_GET['action'] = 'edit'; $_GET['id'] = $recipe_id;
        }
    }
}

// Excluir Receita
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_recipe'])) {
    $recipe_id = filter_input(INPUT_POST, 'recipe_id', FILTER_VALIDATE_INT);

    if (empty($recipe_id)) {
        $message = "ID da receita inválido para exclusão.";
        $message_type = 'danger';
    } else {
        try {
            // *** REMOVIDA A VERIFICAÇÃO: Qualquer nutricionista pode excluir ***
            $stmt = $pdo->prepare("DELETE FROM recipes WHERE id = ?");
            // $stmt->execute([$recipe_id, $nutritionist_id]); // Não precisa mais do nutritionist_id
            $stmt->execute([$recipe_id]);

            if ($stmt->rowCount() > 0) {
                $message = "Receita excluída com sucesso!";
                $message_type = 'success';
            } else {
                $message = "Receita não encontrada."; // Mensagem mais genérica
                $message_type = 'warning';
            }
        } catch (PDOException $e) {
            error_log("Erro ao excluir receita: " . $e->getMessage());
            $message = "Erro ao excluir receita do banco de dados.";
            $message_type = 'danger';
        }
    }
}

// --- Processamento de Ações GET (Editar) ---
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $recipe_id_to_edit = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($recipe_id_to_edit) {
        try {
            // *** REMOVIDA A VERIFICAÇÃO: Busca a receita pelo ID, qualquer nutricionista pode carregar para editar ***
            $stmt = $pdo->prepare("SELECT * FROM recipes WHERE id = ?");
            // $stmt->execute([$recipe_id_to_edit, $nutritionist_id]); // Não precisa mais do nutritionist_id
            $stmt->execute([$recipe_id_to_edit]);
            $recipe_to_edit = $stmt->fetch();
            if (!$recipe_to_edit) {
                $message = "Receita para edição não encontrada.";
                $message_type = 'warning'; // ID pode estar errado
            }
        } catch (PDOException $e) {
            error_log("Erro ao buscar receita para editar: " . $e->getMessage());
            $message = "Erro ao carregar dados da receita para edição.";
            $message_type = 'danger';
        }
    }
}

// --- Buscar TODAS as Receitas para a Lista ---
$recipes = [];
try {
    // *** ALTERAÇÃO APLICADA AQUI: Remove o filtro por nutricionista ***
    $stmt_recipes = $pdo->prepare("SELECT id, name, added_by_nutritionist_id FROM recipes ORDER BY name ASC");
    $stmt_recipes->execute(); // Não precisa mais passar o ID do nutricionista
    $recipes = $stmt_recipes->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao buscar receitas: " . $e->getMessage());
    $message = "Erro ao carregar a lista de receitas.";
    $message_type = 'danger';
}

include 'sidebar_nutritionist.php'; // Inclui a sidebar específica
?>

<!-- Wrapper para o Conteúdo Principal -->
<main class="main-content">
    <div class="container mt-5 mb-5">
        <h2><i class="fas fa-book-open me-2"></i>Gerenciar Receitas</h2>
        <p>Adicione, edite ou remova receitas. Todos os nutricionistas podem gerenciar todas as receitas.</p>

        <?php if ($message): ?>
            <div class="alert alert-<?= htmlspecialchars($message_type) ?> alert-dismissible fade show" role="alert">
                <?= $message // Pode conter HTML seguro como 'htmlspecialchars($name)' ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Formulário para Adicionar/Editar Receita -->
        <div class="card shadow-sm mb-4">
            <div class="card-header"><h5 class="mb-0"><?= $recipe_to_edit ? 'Editar Receita' : 'Adicionar Nova Receita' ?></h5></div>
            <div class="card-body">
                <form method="POST" action="manage_recipes.php">
                    <?php if ($recipe_to_edit): ?>
                        <input type="hidden" name="recipe_id" value="<?= $recipe_to_edit['id'] ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome da Receita</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($recipe_to_edit['name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="ingredients" class="form-label">Ingredientes</label>
                        <textarea class="form-control" id="ingredients" name="ingredients" rows="5" required><?= htmlspecialchars($recipe_to_edit['ingredients'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="instructions" class="form-label">Modo de Preparo</label>
                        <textarea class="form-control" id="instructions" name="instructions" rows="8" required><?= htmlspecialchars($recipe_to_edit['instructions'] ?? '') ?></textarea>
                    </div>

                    <?php if ($recipe_to_edit): ?>
                        <button type="submit" name="update_recipe" class="btn btn-primary">Atualizar Receita</button>
                        <a href="manage_recipes.php" class="btn btn-secondary">Cancelar Edição</a>
                    <?php else: ?>
                        <button type="submit" name="add_recipe" class="btn btn-success">Adicionar Receita</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Tabela de Receitas (Todas) -->
        <div class="card shadow-sm">
             <div class="card-header"><h5 class="mb-0">Todas as Receitas</h5></div>
             <div class="card-body">
                 <div class="table-responsive">
                     <table class="table table-striped table-hover">
                         <thead>
                             <tr><th>Nome da Receita</th><th class="text-end">Ações</th></tr>
                         </thead>
                         <tbody>
                             <?php if (empty($recipes)): ?>
                                 <tr><td colspan="2" class="text-center text-muted">Nenhuma receita adicionada ainda.</td></tr>
                             <?php else: ?>
                                 <?php foreach ($recipes as $recipe): ?>
                                     <tr>
                                         <td><?= htmlspecialchars($recipe['name']) ?></td>
                                         <td class="text-end">
                                             <?php // *** REMOVIDA A CONDIÇÃO: Botões sempre visíveis *** ?>
                                             <a href="manage_recipes.php?action=edit&id=<?= $recipe['id'] ?>" class="btn btn-warning btn-sm" title="Editar">
                                                 <i class="fas fa-edit"></i>
                                             </a>
                                             <form method="POST" action="manage_recipes.php" style="display: inline-block;" onsubmit="return confirm('Tem certeza que deseja excluir a receita \'<?= htmlspecialchars(addslashes($recipe['name'])) ?>\'?');">
                                                 <input type="hidden" name="recipe_id" value="<?= $recipe['id'] ?>">
                                                 <button type="submit" name="delete_recipe" class="btn btn-danger btn-sm" title="Excluir">
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
