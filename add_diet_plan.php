<?php
session_start();
// Verifica se o usuário está logado e se é um nutricionista
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'nutritionist') {
    header('Location: login.php');
    exit;
}
include 'db.php'; // Conexão com o banco de dados
// include 'header.php'; // MOVENDO ESTA LINHA PARA DEPOIS DO PROCESSAMENTO PHP

// Define a página ativa para a sidebar
$active_page = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['role']; // Necessário para a sidebar condicional
$nutritionist_id = $_SESSION['user_id']; // ID do nutricionista logado

// Lista de refeições padrão
$standard_meals = ['Pequeno-almoço', 'Lanche Manhã', 'Almoço', 'Lanche Tarde', 'Jantar', 'Ceia'];

// Array para mapear nomes de refeição para uma ordem numérica (para meal_order)
$meal_order_map = array_flip($standard_meals); // Cria um mapa nome => índice (0, 1, 2...)

// Total de dias fixos
$total_days = 7;

// Buscar lista de alimentos do nutricionista para o select
$foods_list = [];
try {
    // *** ALTERAÇÃO: Remover o filtro por nutricionista para buscar TODOS os alimentos ***
    $stmt_foods = $pdo->prepare("SELECT id, name, unit, serving_size FROM foods ORDER BY name ASC");
    $stmt_foods->execute(); // Não precisa mais passar o ID do nutricionista
    $foods_list = $stmt_foods->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar alimentos para formulário: " . $e->getMessage());
    // Não é um erro fatal para o carregamento da página, mas o select ficará vazio
}

// Buscar lista de clientes associados a este nutricionista (ou todos, dependendo da regra)
$clients = [];
$preselected_client_id = isset($_GET['client_id']) ? filter_var($_GET['client_id'], FILTER_VALIDATE_INT) : null; // Para pré-selecionar vindo da lista
try {
    // Exemplo: Busca todos os clientes (users com role client)
    // Idealmente, buscar apenas clientes associados a este nutricionista, mas por agora busca todos
    $stmt_clients = $pdo->query("
        SELECT c.id as client_table_id, u.name
        FROM users u
        JOIN clients c ON u.id = c.user_id
        WHERE u.role = 'client'
        ORDER BY u.name ASC
    ");
    $clients = $stmt_clients->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar clientes: " . $e->getMessage());
    // Não é fatal, mas o select de clientes ficará vazio
}


// Processa o formulário de adicionar plano alimentar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = filter_input(INPUT_POST, 'client_id', FILTER_VALIDATE_INT); // Validar ID do cliente
    $plan_title = trim($_POST['plan_title'] ?? 'Plano Alimentar'); // Título opcional
    $plan_description = trim($_POST['plan_description'] ?? ''); // Descrição opcional
    $days_data = $_POST['days'] ?? []; // Array de dados por dia/refeição

    // Validação básica
    if (empty($client_id) || empty($days_data)) {
        // Redireciona com erro se cliente ou dados dos dias estiverem faltando
        header('Location: add_diet_plan.php?error=missing_data' . ($client_id ? '&client_id='.$client_id : '')); // Mantém cliente pré-selecionado se houver erro
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Desativar planos antigos do mesmo cliente por este nutricionista
        $stmt_deactivate = $pdo->prepare("UPDATE diet_plans SET is_active = 0 WHERE client_id = ? AND nutritionist_id = ?");
        $stmt_deactivate->execute([$client_id, $nutritionist_id]);

        // 2. Inserir o novo plano principal como ativo
        $stmt_insert_plan = $pdo->prepare("INSERT INTO diet_plans (client_id, nutritionist_id, title, description, is_active) VALUES (?, ?, ?, ?, 1)");
        $stmt_insert_plan->execute([$client_id, $nutritionist_id, $plan_title, $plan_description]);
        $diet_plan_id = $pdo->lastInsertId();

        // 3. Inserir as refeições (combinando dia e nome) e os alimentos
        $stmt_insert_meal = $pdo->prepare("INSERT INTO diet_plan_meals (diet_plan_id, meal_name, meal_order) VALUES (?, ?, ?)");
        $stmt_insert_food = $pdo->prepare("INSERT INTO diet_plan_meal_foods (diet_plan_meal_id, food_id, quantity, notes) VALUES (?, ?, ?, ?)");

        $global_meal_order = 0; // Contador para a ordem geral das refeições

        // Itera sobre os dados recebidos por dia (Dia 1, Dia 2, ...)
        foreach ($days_data as $day_name => $meals_in_day) {
             if (empty($meals_in_day)) continue; // Pula dia vazio

             // Itera sobre os dados recebidos por refeição dentro do dia (Pequeno-almoço, ...)
             foreach ($meals_in_day as $meal_name => $meal_data) {
                 // Pula refeição se não tiver alimentos definidos
                 if (empty($meal_data['foods'])) continue;

                 // Cria o nome completo da refeição (ex: "Dia 1 - Pequeno-almoço")
                 // *** CORREÇÃO: Remover htmlspecialchars daqui. Usar apenas trim. ***
                 $full_meal_name = trim($day_name) . ' - ' . trim($meal_name);

                 // Inserir a refeição na tabela diet_plan_meals
                 // meal_order pode ser a ordem global ou uma combinação
                 $stmt_insert_meal->execute([$diet_plan_id, $full_meal_name, $global_meal_order]);
                 $diet_plan_meal_id = $pdo->lastInsertId();
                 $global_meal_order++;

                 // Inserir os alimentos para esta refeição
                 $foods_in_meal = $meal_data['foods'] ?? [];
                 foreach ($foods_in_meal as $food_item) {
                     // Pula alimento inválido (sem ID ou quantidade)
                     if (empty($food_item['food_id']) || !isset($food_item['quantity']) || $food_item['quantity'] === '') continue;

                     // Valida e formata a quantidade
                     $quantity = filter_var(str_replace(',', '.', $food_item['quantity']), FILTER_VALIDATE_FLOAT);
                     if ($quantity === false || $quantity <= 0) continue; // Pula quantidade inválida

                     // Pega as notas (pode ser nulo)
                     $notes = isset($food_item['notes']) ? trim($food_item['notes']) : null;

                     // Insere o alimento na tabela diet_plan_meal_foods
                     $stmt_insert_food->execute([$diet_plan_meal_id, $food_item['food_id'], $quantity, $notes]);
                 }
             }
        }

        $pdo->commit();

        // Redireciona para a página principal do nutricionista com sucesso
        header('Location: nutritionist.php?success=plan_added');
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Erro ao adicionar plano alimentar: " . $e->getMessage());
        // Redireciona com mensagem de erro (ou mostra na página)
        header('Location: add_diet_plan.php?error=db_error' . ($client_id ? '&client_id='.$client_id : ''));
        exit;
    }
}

// *** MOVENDO OS INCLUDES PARA AQUI ***
include 'header.php'; // Inclui o cabeçalho HTML AGORA
include 'sidebar_nutritionist.php';
// *** FIM DOS INCLUDES MOVIDOS ***
?>

<!-- Wrapper para o Conteúdo Principal -->
<main class="main-content">
    <div class="container mt-5 mb-5"> <!-- Aumentar margem inferior -->
        <h2>Adicionar Plano Alimentar</h2>
        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                Erro ao adicionar o plano. Verifique os dados ou tente novamente. (<?= htmlspecialchars($_GET['error']) ?>)
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="add_diet_plan.php" id="diet-plan-form">
             <div class="form-group mb-3">
                <label for="client_id">Cliente</label>
                <select class="form-select" id="client_id" name="client_id" required>
                    <option value="">-- Selecione um Cliente --</option>
                     <?php foreach ($clients as $client): ?>
                         <option value="<?= $client['client_table_id'] ?>" <?= ($preselected_client_id == $client['client_table_id']) ? 'selected' : '' ?>>
                             <?= htmlspecialchars($client['name']) ?>
                         </option>
                     <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-3">
                <label for="plan_title" class="form-label">Título do Plano (Opcional)</label>
                <input type="text" class="form-control" id="plan_title" name="plan_title" placeholder="Ex: Plano de Ganho de Massa - Semana 1">
            </div>
             <div class="form-group mb-4">
                <label for="plan_description" class="form-label">Descrição/Notas Gerais (Opcional)</label>
                <textarea class="form-control" id="plan_description" name="plan_description" rows="3" placeholder="Observações gerais sobre o plano..."></textarea>
            </div>

            <hr>
            <h4 class="mb-3">Estrutura do Plano (7 Dias)</h4>

            <div id="days-container">
                <?php for ($day_num = 1; $day_num <= $total_days; $day_num++): ?>
                    <div class="day-section card mb-4 shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Dia <?= $day_num ?></h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($standard_meals as $meal_name): ?>
                                <div class="meal-block card mb-3 border-light">
                                    <div class="card-header p-2 bg-white">
                                        <h6 class="mb-0"><?= htmlspecialchars($meal_name) ?></h6>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="foods-list">
                                            <!-- Food items will be added here dynamically by JS -->
                                            <p class="text-muted small no-foods-message">Nenhum alimento adicionado a esta refeição ainda.</p>
                                        </div>
                                        <!-- Button to add food to THIS meal -->
                                        <button type="button" class="btn btn-outline-success btn-sm add-food-btn mt-2" data-day="<?= $day_num ?>" data-meal-name="<?= htmlspecialchars($meal_name) ?>">
                                            <i class="fas fa-plus"></i> Adicionar Alimento
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>

            <hr class="my-4">

            <button type="submit" class="btn btn-primary btn-lg">Salvar Plano Completo</button>
            <a href="nutritionist.php" class="btn btn-outline-secondary btn-lg">Cancelar</a>
        </form>
    </div>

    <!-- ==================== TEMPLATES HIDDEN ==================== -->
    <div id="templates" style="display: none;">

        <!-- Template de Linha de Alimento -->
        <div class="food-row d-flex align-items-center gap-2 mb-2 border-bottom pb-2" id="food-row-template">
            <div class="flex-grow-1">
                <!-- Name format: days[Dia X][Nome Refeição][foods][INDEX][food_id] -->
                <select name="days[DAY_NAME][MEAL_NAME][foods][FOOD_INDEX][food_id]" class="form-select form-select-sm food-select" required>
                    <option value="">-- Selecione Alimento --</option>
                    <?php foreach ($foods_list as $food): ?>
                        <option value="<?= $food['id'] ?>"
                                data-unit="<?= htmlspecialchars($food['unit']) ?>"
                                data-serving="<?= htmlspecialchars($food['serving_size']) ?>">
                            <?= htmlspecialchars($food['name']) ?> (<?= htmlspecialchars($food['serving_size'] ?? '') ?><?= htmlspecialchars($food['unit'] ?? '') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="width: 100px;">
                 <!-- Name format: days[DAY_NAME][MEAL_NAME][foods][INDEX][quantity] -->
                <input type="text" inputmode="decimal" name="days[DAY_NAME][MEAL_NAME][foods][FOOD_INDEX][quantity]" class="form-control form-control-sm food-quantity" placeholder="Qtd" required>
            </div>
            <div style="width: 50px;">
                <span class="food-unit text-muted small"></span>
            </div>
            <div class="flex-grow-1">
                 <!-- Name format: days[DAY_NAME][MEAL_NAME][foods][INDEX][notes] -->
                 <input type="text" name="days[DAY_NAME][MEAL_NAME][foods][FOOD_INDEX][notes]" class="form-control form-control-sm food-notes" placeholder="Notas (opcional)">
            </div>
            <div>
                <button type="button" class="btn btn-outline-danger btn-sm remove-food-btn" title="Remover Alimento"><i class="fas fa-trash-alt"></i></button>
            </div>
        </div>

    </div>
    <!-- ==================== FIM TEMPLATES ==================== -->

</main> <!-- FIM: Wrapper <main> -->

<!-- JavaScript para funcionalidade dinâmica -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const daysContainer = document.getElementById('days-container');
    const foodRowTemplate = document.getElementById('food-row-template');

    // Função para adicionar uma linha de alimento a uma refeição específica
    function addFoodRow(mealBlock) {
        const foodsList = mealBlock.querySelector('.foods-list');
        const dayName = mealBlock.closest('.day-section').querySelector('.card-header h5').textContent.trim(); // Get "Dia X"
        const mealName = mealBlock.querySelector('.card-header h6').textContent.trim(); // Get "Pequeno-almoço", etc.
        const foodCounter = foodsList.querySelectorAll('.food-row').length;

        const newFoodRow = foodRowTemplate.cloneNode(true);
        newFoodRow.id = `food-row-${dayName.replace(' ', '-')}-${mealName.replace(' ', '-')}-${foodCounter}`; // Unique ID
        newFoodRow.style.display = 'flex'; // Torna visível

        // Atualiza os placeholders DAY_NAME, MEAL_NAME e FOOD_INDEX
        newFoodRow.querySelectorAll('[name*="[DAY_NAME]"]').forEach(el => {
            el.name = el.name.replace('[DAY_NAME]', `[${dayName}]`).replace('[MEAL_NAME]', `[${mealName}]`).replace('[FOOD_INDEX]', `[${foodCounter}]`);
        });

        // Remove a mensagem "Nenhum alimento..." se existir
        const noFoodsMsg = foodsList.querySelector('.no-foods-message');
        if (noFoodsMsg) {
            noFoodsMsg.remove();
        }

        foodsList.appendChild(newFoodRow);

        // Inicializa a unidade se um alimento já estiver selecionado (não aplicável em "add", mas útil para "edit")
        const foodSelect = newFoodRow.querySelector('.food-select');
        if (foodSelect && foodSelect.value) {
             updateFoodUnit(foodSelect);
        }
    }

    // Função para atualizar a unidade quando um alimento é selecionado
    function updateFoodUnit(selectElement) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const unitSpan = selectElement.closest('.food-row').querySelector('.food-unit');
        if (unitSpan && selectedOption && selectedOption.dataset.unit) {
            unitSpan.textContent = selectedOption.dataset.unit;
        } else if (unitSpan) {
            unitSpan.textContent = ''; // Limpa se nada for selecionado ou não tiver unidade
        }
    }

    // Função para reindexar os alimentos dentro de cada refeição após remoção
    function reindexFoodsInMeals() {
        // Itera sobre todos os blocos de refeição em todos os dias
        daysContainer.querySelectorAll('.meal-block').forEach(mealBlock => {
            const foodsList = mealBlock.querySelector('.foods-list');
            const dayName = mealBlock.closest('.day-section').querySelector('.card-header h5').textContent.trim();
            const mealName = mealBlock.querySelector('.card-header h6').textContent.trim();

            foodsList.querySelectorAll('.food-row').forEach((foodRow, foodIndex) => {
                // Atualiza os índices DAY_NAME, MEAL_NAME e FOOD_INDEX na string do nome
                foodRow.querySelectorAll('[name*="days["]').forEach(el => {
                    // Regex para encontrar e substituir [DAY_NAME][MEAL_NAME][foods][INDEX]
                    const currentName = el.name;
                    // Regex mais robusto para garantir que substitui corretamente
                    const newName = currentName.replace(/(\[foods\])\[\d+\]/, `$1[${foodIndex}]`);
                    // Re-aplica o dia e refeição para garantir consistência, caso o regex anterior falhe em algum caso
                    el.name = newName.replace(/days\[.*?\]/, `days[${dayName}]`).replace(/\[.*?\](?=\[foods\])/, `[${mealName}]`);
                });
            });
        });
    }


    // --- Event Listeners ---

    // Event Delegation para botões "Adicionar Alimento" e "Remover Alimento"
    daysContainer.addEventListener('click', function(event) {
        // Adicionar Alimento
        if (event.target.classList.contains('add-food-btn') || event.target.closest('.add-food-btn')) {
            const mealBlock = event.target.closest('.meal-block');
            if (mealBlock) {
                addFoodRow(mealBlock);
            }
        }
        // Remover Alimento
        else if (event.target.classList.contains('remove-food-btn') || event.target.closest('.remove-food-btn')) {
            const foodRow = event.target.closest('.food-row');
            const foodsList = foodRow?.closest('.foods-list'); // Pega a lista de alimentos pai (com verificação)
            if (foodRow && foodsList) { // Garante que ambos existem
                foodRow.remove();
                // Adiciona a mensagem "Nenhum alimento..." se a lista ficar vazia
                if (foodsList.querySelectorAll('.food-row').length === 0) {
                    const noFoodsMsg = document.createElement('p');
                    noFoodsMsg.classList.add('text-muted', 'small', 'no-foods-message');
                    noFoodsMsg.textContent = 'Nenhum alimento adicionado a esta refeição ainda.';
                    foodsList.appendChild(noFoodsMsg);
                }
                reindexFoodsInMeals(); // Reindexa após remover alimento
            }
        }
    });

    // Event Delegation para atualizar unidade ao selecionar alimento
    daysContainer.addEventListener('change', function(event) {
        if (event.target.classList.contains('food-select')) {
            updateFoodUnit(event.target);
        }
    });

    // Não adicionamos refeições automaticamente ao carregar,
    // a estrutura de dias e refeições já está no HTML.
    // O nutricionista clica em "Adicionar Alimento" para começar.

});
</script>

<?php include 'footer.php'; ?>
