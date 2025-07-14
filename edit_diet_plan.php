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
$active_page = 'client_list_nutritionist.php'; // Mantém 'Clientes' ativo na sidebar
$user_role = $_SESSION['role']; // Necessário para a sidebar condicional

// Verifica se IDs foram passados
if (!isset($_GET['client_id']) || !isset($_GET['diet_plan_id'])) {
     echo "<script>alert('IDs inválidos.'); window.location.href='nutritionist.php';</script>";
     exit;
}
$client_id = filter_var($_GET['client_id'], FILTER_VALIDATE_INT);
$diet_plan_id = filter_var($_GET['diet_plan_id'], FILTER_VALIDATE_INT);
$nutritionist_id = $_SESSION['user_id'];

if (!$client_id || !$diet_plan_id) {
     echo "<script>alert('IDs inválidos.'); window.location.href='nutritionist.php';</script>";
     exit;
}

// Lista de refeições padrão (deve ser a mesma usada em add_diet_plan.php)
$standard_meals = ['Pequeno-almoço', 'Lanche Manhã', 'Almoço', 'Lanche Tarde', 'Jantar', 'Ceia'];
$total_days = 7; // Total de dias fixos

$page_error = '';
$plan_data = ['title' => '', 'plan_details' => '']; // Dados do plano principal
$client_name = 'Cliente';
$existing_meals_data_json = '[]'; // JSON para passar os dados existentes para o JS

// Buscar lista de alimentos para o select
$foods_list = [];
try {
    $stmt_foods = $pdo->prepare("SELECT id, name, unit, serving_size FROM foods ORDER BY name ASC");
    $stmt_foods->execute();
    $foods_list = $stmt_foods->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar alimentos para formulário de edição: " . $e->getMessage());
    $page_error = "Erro ao carregar lista de alimentos.";
}


// --- Processa o formulário de edição (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan_title = trim($_POST['plan_title'] ?? ''); // Pega o título do plano
    $plan_details_obs = trim($_POST['plan_details'] ?? ''); // Observações gerais do plano
    $days_data = $_POST['days'] ?? []; // Array de dados por dia/refeição do formulário

    try {
        $pdo->beginTransaction();

        // 1. Desativar o plano antigo que está a ser "editado"
        $stmt_deactivate_old = $pdo->prepare("UPDATE diet_plans SET is_active = 0 WHERE id = ? AND client_id = ? AND nutritionist_id = ?");
        $stmt_deactivate_old->execute([$diet_plan_id, $client_id, $nutritionist_id]);

        // 2. Inserir um NOVO plano com os dados do formulário, marcando-o como ativo
        $stmt_insert_plan = $pdo->prepare("
            INSERT INTO diet_plans (client_id, nutritionist_id, title, plan_details, is_active, created_at) 
            VALUES (?, ?, ?, ?, 1, NOW())
        ");
        $stmt_insert_plan->execute([$client_id, $nutritionist_id, $plan_title, $plan_details_obs]);
        $new_diet_plan_id = $pdo->lastInsertId(); // Pega o ID do novo plano

        // 3. Inserir as novas refeições e alimentos no NOVO plano
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
                 $full_meal_name = trim($day_name) . ' - ' . trim($meal_name);

                 // Inserir a refeição na tabela diet_plan_meals
                 $stmt_insert_meal->execute([$new_diet_plan_id, $full_meal_name, $global_meal_order]);
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

        // Redireciona com sucesso
        header('Location: client_list_nutritionist.php?success=plan_updated');
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Erro ao atualizar plano alimentar: " . $e->getMessage());
        $page_error = "Erro ao atualizar o plano. Detalhe: " . $e->getMessage();
        // Redireciona com erro (ou mostra na página)
        // header('Location: edit_diet_plan.php?client_id='.$client_id.'&diet_plan_id='.$diet_plan_id.'&error=db_error');
        // exit;
    } catch (Exception $e) {
         $pdo->rollBack(); // Garante rollback mesmo para outras exceções
         error_log("Erro geral ao atualizar plano alimentar: " . $e->getMessage());
         $page_error = "Erro inesperado ao atualizar o plano. Detalhe: " . $e->getMessage();
    }
}


// --- Busca os dados existentes do plano para preencher o formulário (GET ou após POST com erro) ---
if (empty($page_error)) { // Só busca se não houve erro fatal no POST
    try {
        // Busca dados do plano principal (incluindo 'title') e nome do cliente
        $stmt_plan = $pdo->prepare("SELECT dp.title, dp.plan_details, u.name as client_name FROM diet_plans dp JOIN clients c ON dp.client_id = c.id JOIN users u ON c.user_id = u.id WHERE dp.id = ? AND dp.client_id = ? AND dp.nutritionist_id = ?");
        $stmt_plan->execute([$diet_plan_id, $client_id, $nutritionist_id]);
        $plan_result = $stmt_plan->fetch();

        if (!$plan_result) {
            // Plano não encontrado ou não pertence a este nutricionista/cliente
            echo "<script>alert('Plano não encontrado ou acesso negado.'); window.location.href='nutritionist.php';</script>";
            exit;
        }
        $plan_data = $plan_result;
        $client_name = $plan_data['client_name'];

        // Busca os detalhes das refeições e alimentos para este plano
        $stmt_meals = $pdo->prepare("
            SELECT
                dpm.id AS meal_id,
                dpm.meal_name,
                dpm.meal_order,
                dpmf.id AS food_item_id,
                dpmf.quantity,
                dpmf.notes,
                f.id AS food_id,
                f.name AS food_name,
                f.unit,
                f.serving_size
            FROM diet_plan_meals dpm
            JOIN diet_plan_meal_foods dpmf ON dpm.id = dpmf.diet_plan_meal_id
            JOIN foods f ON dpmf.food_id = f.id
            WHERE dpm.diet_plan_id = ?
            ORDER BY dpm.meal_order ASC, dpmf.id ASC -- Ordena por ordem da refeição e depois por ordem do alimento
        ");
        $stmt_meals->execute([$diet_plan_id]);
        $meals_data_raw = $stmt_meals->fetchAll(PDO::FETCH_ASSOC);

        // Organiza os dados brutos em uma estrutura aninhada (Dia -> Refeição -> Alimentos)
        // e converte para JSON para ser usado pelo JavaScript
        $organized_meals = [];
        foreach ($meals_data_raw as $item) {
            // Extrai o dia e o nome da refeição do meal_name (ex: "Dia 1 - Pequeno-almoço")
            $parts = explode(' - ', $item['meal_name'], 2);
            $day_name = $parts[0] ?? 'Dia Desconhecido';
            $meal_name = $parts[1] ?? 'Refeição Desconhecida';

            // Garante que o dia existe no array organizado
            if (!isset($organized_meals[$day_name])) {
                $organized_meals[$day_name] = [];
            }

            // Garante que a refeição existe dentro do dia
            if (!isset($organized_meals[$day_name][$meal_name])) { // Usar meal_name como chave aqui para JS
                $organized_meals[$day_name][$meal_name] = [
                    'name' => $meal_name,
                    'order' => $item['meal_order'], // Manter ordem se necessário
                    'foods' => []
                ];
            }

            // Adiciona o alimento à refeição
            $organized_meals[$day_name][$meal_name]['foods'][] = [
                'food_id' => $item['food_id'],
                'quantity' => (float)$item['quantity'], // Garante float
                'notes' => $item['notes']
            ];
        }

        // Converte para JSON para passar para o JavaScript
        $existing_meals_data_json = json_encode($organized_meals);

    } catch (PDOException $e) {
         $page_error = "Erro ao carregar dados para edição (BD): " . $e->getMessage();
         error_log("PDOException em edit_diet_plan.php (fetch data): " . $e->getMessage());
         $plan_data = ['title' => '', 'plan_details' => '']; // Limpa dados se houver erro
         $existing_meals_data_json = '[]'; // Garante JSON vazio
    } catch (Exception $e) {
         $page_error = $e->getMessage();
         error_log("Erro geral em edit_diet_plan.php (fetch data): " . $e->getMessage());
         $plan_data = ['title' => '', 'plan_details' => '']; // Limpa dados se houver erro
         $existing_meals_data_json = '[]'; // Garante JSON vazio
    }
}


// SÓ INCLUIR HTML DEPOIS DE TODA A LÓGICA DE REDIRECIONAMENTO E PROCESSAMENTO
include 'header.php'; // Inclui o cabeçalho HTML AGORA
include 'sidebar_nutritionist.php'; // Inclui a sidebar específica
?>

<!-- Wrapper para o Conteúdo Principal -->
<main class="main-content">
    <div class="container mt-5 mb-5">
        <h2>Editar Plano Alimentar para <?= htmlspecialchars($client_name) ?></h2>

        <?php if ($page_error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($page_error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="edit_diet_plan.php?client_id=<?= $client_id ?>&diet_plan_id=<?= $diet_plan_id ?>" id="diet-plan-form">
            <!-- Campos ocultos para manter IDs na submissão -->
            <input type="hidden" name="client_id" value="<?= $client_id ?>">
            <input type="hidden" name="diet_plan_id" value="<?= $diet_plan_id ?>">

            <div class="form-group mb-3">
                <label for="plan_title" class="form-label">Título do Plano (Opcional)</label>
                <input type="text" class="form-control" id="plan_title" name="plan_title" placeholder="Ex: Plano de Ganho de Massa - Semana 1" value="<?= htmlspecialchars($plan_data['title'] ?? '') ?>">
            </div>
             <div class="form-group mb-4">
                <label for="plan_details" class="form-label">Descrição/Notas Gerais (Opcional)</label>
                <textarea class="form-control" id="plan_details" name="plan_details" rows="3" placeholder="Observações gerais sobre o plano..."><?= htmlspecialchars($plan_data['plan_details'] ?? '') ?></textarea>
            </div>

            <hr>
            <h4 class="mb-3">Estrutura do Plano (7 Dias)</h4>

            <div id="days-container">
                <?php for ($day_num = 1; $day_num <= $total_days; $day_num++): ?>
                    <div class="day-section card mb-4 shadow-sm" data-day-name="Dia <?= $day_num ?>">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Dia <?= $day_num ?></h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($standard_meals as $meal_name): ?>
                                <div class="meal-block card mb-3 border-light" data-meal-name="<?= htmlspecialchars($meal_name) ?>">
                                    <div class="card-header p-2 bg-white">
                                        <h6 class="mb-0"><?= htmlspecialchars($meal_name) ?></h6>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="foods-list">
                                            <!-- Food items will be added here dynamically by JS -->
                                            <p class="text-muted small no-foods-message">Nenhum alimento adicionado a esta refeição ainda.</p>
                                        </div>
                                        <!-- Button to add food to THIS meal -->
                                        <button type="button" class="btn btn-outline-success btn-sm add-food-btn mt-2">
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

            <button type="submit" class="btn btn-primary btn-lg">Salvar Alterações</button>
            <a href="client_list_nutritionist.php" class="btn btn-secondary btn-lg">Cancelar</a>
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
    const existingMealsData = <?= $existing_meals_data_json ?>; // Dados existentes do PHP

    // Função para adicionar uma linha de alimento a uma refeição específica (MODIFICADA para aceitar dados)
    function addFoodRow(mealBlock, foodData = {}) { // foodData pode ser vazio ou conter dados existentes
        const foodsList = mealBlock.querySelector('.foods-list');
        const daySection = mealBlock.closest('.day-section');
        const dayName = daySection.dataset.dayName; // Get "Dia X" from data attribute
        const mealName = mealBlock.querySelector('.card-header h6').textContent.trim(); // Get "Pequeno-almoço", etc.
        const foodCounter = foodsList.querySelectorAll('.food-row').length; // Índice baseado nos elementos atuais

        const newFoodRow = foodRowTemplate.cloneNode(true);
        newFoodRow.id = `food-row-${dayName.replace(' ', '-')}-${mealName.replace(' ', '-')}-${foodCounter}`; // Unique ID
        newFoodRow.style.display = 'flex'; // Torna visível

        // Atualiza os placeholders DAY_NAME, MEAL_NAME e FOOD_INDEX
        newFoodRow.querySelectorAll('[name*="[DAY_NAME]"]').forEach(el => {
            el.name = el.name.replace('[DAY_NAME]', `[${dayName}]`).replace('[MEAL_NAME]', `[${mealName}]`).replace('[FOOD_INDEX]', `[${foodCounter}]`);
        });

        // Preenche com dados existentes se fornecidos (NOVO)
        if (foodData.food_id) {
            const foodSelect = newFoodRow.querySelector('.food-select');
            foodSelect.value = foodData.food_id;
            // Dispara evento 'change' para atualizar a unidade
            const changeEvent = new Event('change');
            foodSelect.dispatchEvent(changeEvent);
        }
        if (foodData.quantity !== undefined && foodData.quantity !== null) {
             // Formata a quantidade com vírgula para exibição no input
             newFoodRow.querySelector('.food-quantity').value = String(foodData.quantity).replace('.', ',');
        }
        if (foodData.notes !== undefined && foodData.notes !== null) {
             newFoodRow.querySelector('.food-notes').value = foodData.notes;
        }

        // Remove a mensagem "Nenhum alimento..." se existir
        const noFoodsMsg = foodsList.querySelector('.no-foods-message');
        if (noFoodsMsg) {
            noFoodsMsg.remove();
        }

        foodsList.appendChild(newFoodRow);

        // Inicializa a unidade se um alimento já estiver selecionado (útil ao carregar dados existentes)
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
            const daySection = mealBlock.closest('.day-section');
            const dayName = daySection.dataset.dayName;
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
             // Adiciona a mensagem "Nenhum alimento..." se a lista ficar vazia após reindexar
             if (foodsList.querySelectorAll('.food-row').length === 0 && !foodsList.querySelector('.no-foods-message')) {
                const noFoodsMsg = document.createElement('p');
                noFoodsMsg.classList.add('text-muted', 'small', 'no-foods-message');
                noFoodsMsg.textContent = 'Nenhum alimento adicionado a esta refeição ainda.';
                foodsList.appendChild(noFoodsMsg);
            }
        });
    }

    // --- NOVO: Carregar dados existentes ao carregar a página ---
    function loadExistingData() {
        if (existingMealsData) {
            // Itera sobre os dias nos dados existentes
            for (const dayName in existingMealsData) {
                if (existingMealsData.hasOwnProperty(dayName)) {
                    const mealsInDay = existingMealsData[dayName];
                    // Encontra a secção do dia correspondente no HTML
                    const daySection = daysContainer.querySelector(`.day-section[data-day-name="${dayName}"]`);

                    if (daySection) {
                        // Itera sobre as refeições dentro do dia nos dados existentes
                        for (const mealName in mealsInDay) {
                            if (mealsInDay.hasOwnProperty(mealName)) {
                                const mealData = mealsInDay[mealName];
                                // Encontra o bloco da refeição correspondente no HTML
                                const mealBlock = daySection.querySelector(`.meal-block[data-meal-name="${mealName}"]`);

                                if (mealBlock && mealData.foods) {
                                    // Itera sobre os alimentos dentro da refeição nos dados existentes
                                    mealData.foods.forEach(foodItem => {
                                        // Adiciona uma linha de alimento para cada item existente
                                        addFoodRow(mealBlock, foodItem);
                                    });
                                }
                            }
                        }
                    }
                }
            }
             // Reindexa após carregar todos os dados
             reindexFoodsInMeals();
        }
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

    // Carregar dados existentes quando a página estiver pronta
    loadExistingData(); // Chama a nova função

});
</script>

<?php
include 'footer.php';
?>
