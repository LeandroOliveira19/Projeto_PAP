<?php
// filepath: c:\xampp\htdocs\Projeto_PAP\add_workout_plan.php
session_start();

// Verifica se o usuário está logado e é um trainer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainer') {
    header('Location: login.php');
    exit;
}

// Inclui a conexão com o banco de dados
include 'db.php';

// Processa o formulário de adicionar plano de treino
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'];
    $custom_details = $_POST['custom_details']; // Observações gerais do plano
    $trainer_id = $_SESSION['user_id']; // Obtém o ID do trainer da sessão
    $selected_exercises = $_POST['selected_exercises'] ?? [];
    $days = $_POST['day'] ?? []; // Nomes dos dias (Dia 1, Dia 2...)
    // $day_details = $_POST['day_details'] ?? []; // Removido, agora virá como JSON
    $day_details_json_array = $_POST['day_details_json'] ?? []; // Novo array para JSON
    $day_option_ids = $_POST['day_option_id'] ?? [];

    try {
        $pdo->beginTransaction();

        // Set all existing plans for this client (by any trainer) to inactive
        // This ensures only one plan is active per client at a time.
        $stmt_deactivate = $pdo->prepare("UPDATE workout_plans SET is_active = 0 WHERE client_id = ?");
        $stmt_deactivate->execute([$client_id]);

        // Insere o novo plano de treino no banco de dados e define como ativo
        $stmt_insert_plan = $pdo->prepare("INSERT INTO workout_plans (client_id, trainer_id, plan_details, is_active) VALUES (?, ?, ?, 1)");
        $stmt_insert_plan->execute([$client_id, $trainer_id, $custom_details]);

        // Get the ID of the newly inserted workout plan
        $workout_plan_id = $pdo->lastInsertId();

        // Insert the selected exercises into the workout_plan_exercises table
        $stmt_insert_exercise = $pdo->prepare("INSERT INTO workout_plan_exercises (workout_plan_id, exercise_id) VALUES (?, ?)");
        foreach ($selected_exercises as $exercise_id) {
            $stmt_insert_exercise->execute([$workout_plan_id, $exercise_id]);
        }

        // Insert the day-by-day details
        $stmt_insert_day = $pdo->prepare("INSERT INTO workout_plan_days (workout_plan_id, day_name, day_details, day_option_id) VALUES (?, ?, ?, ?)");
        foreach ($days as $key => $day_name) {
            if (empty($day_name)) continue;

            $current_day_json_details = $day_details_json_array[$day_name] ?? '[]'; // Pega o JSON para o dia atual
            $current_day_option_id = $day_option_ids[$key] ?? null;

            $stmt_insert_day->execute([$workout_plan_id, $day_name, $current_day_json_details, $current_day_option_id]);
        }

        $pdo->commit();
        // Redireciona de volta para a página do trainer
        header('Location: trainer.php?success=plan_added'); // Adiciona parâmetro de sucesso
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Erro ao adicionar plano de treino: " . $e->getMessage());
        // Idealmente, passar uma mensagem de erro para a página
        header('Location: add_workout_plan.php?error=db_error&client_id=' . $client_id); // Mantém client_id se houver erro
        exit;
    }
}
?>

<?php
// Inclui o cabeçalho da página
include 'header.php';

// *** ADICIONADO: Define a página ativa para a sidebar ***
$active_page = basename($_SERVER['PHP_SELF']); // Será 'add_workout_plan.php'

// *** ADICIONADO: Inclui a sidebar do trainer ***
include 'sidebar_trainer.php';

// Buscar cliente pré-selecionado (se houver)
$preselected_client_id = isset($_GET['client_id']) ? filter_var($_GET['client_id'], FILTER_VALIDATE_INT) : null;
$client_name_preselected = '';
if ($preselected_client_id) {
    try {
        $stmt_client_name = $pdo->prepare("SELECT u.name FROM users u JOIN clients c ON u.id = c.user_id WHERE c.id = ?");
        $stmt_client_name->execute([$preselected_client_id]);
        $client_name_preselected = $stmt_client_name->fetchColumn();
    } catch (PDOException $e) {
        // Log error, mas não impede o carregamento da página
        error_log("Erro ao buscar nome do cliente pré-selecionado: " . $e->getMessage());
    }
}
?>

<!-- *** ADICIONADO: Wrapper <main> *** -->
<main class="main-content">
    <div class="container mt-5 mb-5">
        <h2>Adicionar Plano de Treino
            <?php if ($client_name_preselected): ?>
                para <span class="text-primary"><?= htmlspecialchars($client_name_preselected) ?></span>
            <?php endif; ?>
        </h2>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                Erro ao adicionar o plano. Verifique os dados e tente novamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="add_workout_plan.php" id="addWorkoutPlanForm"> <!-- Adicionado ID ao formulário -->
            <div class="form-group mb-3">
                <label for="client_id" class="form-label">Cliente</label>
                <select class="form-select" id="client_id" name="client_id" required <?= $preselected_client_id ? 'disabled' : '' ?>>
                    <?php if ($preselected_client_id && $client_name_preselected): ?>
                        <option value="<?= $preselected_client_id ?>" selected><?= htmlspecialchars($client_name_preselected) ?></option>
                    <?php else: ?>
                        <option value="">-- Selecione um Cliente --</option>
                        <?php
                        // Busca todos os clientes cadastrados que AINDA NÃO TÊM um plano ativo
                        $stmt_clients = $pdo->prepare("
                            SELECT c.id as client_table_id, u.name
                            FROM users u
                            JOIN clients c ON u.id = c.user_id
                            WHERE u.role = 'client' AND NOT EXISTS (
                                SELECT 1 FROM workout_plans wp WHERE wp.client_id = c.id AND wp.is_active = 1
                            )
                            ORDER BY u.name ASC
                        ");
                        $stmt_clients->execute();
                        $available_clients = $stmt_clients->fetchAll();

                        foreach ($available_clients as $client_item) {
                            echo "<option value='" . $client_item['client_table_id'] . "'>" . htmlspecialchars($client_item['name']) . "</option>";
                        }
                        if (empty($available_clients) && !$preselected_client_id) {
                             echo "<option value='' disabled>Nenhum cliente sem plano ativo</option>";
                        }
                        ?>
                    <?php endif; ?>
                </select>
                <?php if ($preselected_client_id): // Se pré-selecionado, envia o ID num campo hidden ?>
                    <input type="hidden" name="client_id" value="<?= $preselected_client_id ?>">
                <?php endif; ?>
            </div>
            <div class="form-group mb-3">
                <label for="custom_details" class="form-label">Observações Gerais do Plano</label>
                <textarea class="form-control" id="custom_details" name="custom_details" rows="3" placeholder="Instruções gerais, aquecimento, alongamento, etc."></textarea>
            </div>

            <hr class="my-4">
            <h4 class="mb-3">Detalhes Diários do Treino</h4>
            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle" id="day-details-table">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 15%;">Dia</th>
                            <th style="width: 25%;">Opção (Foco)</th>
                            <th>Detalhes Específicos do Dia (Ações)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch all day options
                        $stmt_day_options = $pdo->query("SELECT id, name FROM day_options ORDER BY id");
                        $day_options = $stmt_day_options->fetchAll();
                        for ($i = 1; $i <= 7; $i++):
                            $current_day_name_loop = "Dia " . $i;
                        ?>
                            <tr>
                                <td>
                                    <input type="text" class="form-control form-control-sm" name="day[]" value="<?= htmlspecialchars($current_day_name_loop) ?>" readonly>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm" name="day_option_id[]">
                                        <option value="">-- Selecione --</option>
                                        <?php foreach ($day_options as $option): ?>
                                            <option value="<?= $option['id'] ?>"><?= htmlspecialchars($option['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <!-- Container para as ações dinâmicas -->
                                    <div class="day-actions-container" data-day-name="<?= htmlspecialchars($current_day_name_loop) ?>">
                                        <!-- As ações serão adicionadas aqui pelo JS -->
                                    </div>
                                    <button type="button" class="btn btn-outline-secondary btn-sm mt-2 add-action-btn" data-day-name="<?= htmlspecialchars($current_day_name_loop) ?>">
                                        <i class="fas fa-plus me-1"></i> Adicionar Ação
                                    </button>
                                </td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <hr class="my-4">
            <h4 class="mb-3">Selecionar Exercícios para Incluir no Plano</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 5%;"><i class="fas fa-check"></i></th>
                            <th style="width: 25%;">Nome</th>
                            <th style="width: 20%;">Grupo Muscular</th>
                            <th style="width: 20%;">Equipamento</th>
                            <th>Descrição</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch all exercises from the database, ordered by muscle group then name
                        $stmt_exercises = $pdo->query("SELECT * FROM exercises ORDER BY muscle_group ASC, name ASC");
                        $exercises = $stmt_exercises->fetchAll();
                        $current_muscle_group_display = null;

                        if (empty($exercises)) {
                            echo "<tr><td colspan='5' class='text-center text-muted py-3'>Nenhum exercício cadastrado no sistema.</td></tr>";
                        } else {
                            foreach ($exercises as $exercise) {
                                $exercise_muscle_group = !empty($exercise['muscle_group']) ? htmlspecialchars($exercise['muscle_group']) : 'Outros Grupos';

                                if ($exercise_muscle_group !== $current_muscle_group_display) {
                                    if ($current_muscle_group_display !== null) {
                                        echo "<tr><td colspan='5' style='padding:0.3rem; background-color: transparent; border:none;'></td></tr>";
                                    }
                                    echo "<tr><td colspan='5' class='table-group-header'>" . $exercise_muscle_group . "</td></tr>";
                                    $current_muscle_group_display = $exercise_muscle_group;
                                }

                                echo "<tr>";
                                echo "<td class='text-center'><input type='checkbox' class='form-check-input' name='selected_exercises[]' value='" . $exercise['id'] . "'></td>";
                                echo "<td>" . htmlspecialchars($exercise['name']) . "</td>";
                                echo "<td>" . $exercise_muscle_group . "</td>";
                                echo "<td>" . htmlspecialchars($exercise['equipment']) . "</td>";
                                echo "<td><small>" . htmlspecialchars($exercise['description']) . "</small></td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 text-center">
                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-1"></i> Salvar Plano</button>
                <a href="trainer.php" class="btn btn-secondary btn-lg"><i class="fas fa-times me-1"></i> Cancelar</a>
            </div>
        </form>
    </div>
</main> <!-- *** FIM: Wrapper <main> *** -->

<!-- ==================== TEMPLATES HIDDEN (Copiado de edit_workout_plan.php) ==================== -->
<div id="action-templates" style="display: none;">
    <div class="action-item card mb-2 bg-light border-secondary" data-action-type="sets_reps" id="template-sets_reps">
        <div class="card-body p-2">
            <div class="d-flex align-items-center">
                <strong class="me-2">Sets x Reps:</strong>
                <input type="number" class="form-control form-control-sm w-auto me-1 action-sets" placeholder="Sets" min="1" value="3">
                <span>x</span>
                <input type="text" class="form-control form-control-sm w-auto ms-1 action-reps" placeholder="Reps" value="8-12">
                <button type="button" class="btn btn-outline-danger btn-sm ms-auto remove-action-btn" title="Remover Ação"><i class="fas fa-trash-alt"></i></button>
            </div>
             <input type="text" class="form-control form-control-sm mt-1 action-notes" placeholder="Notas (Ex: até à falha)">
        </div>
    </div>
    <div class="action-item card mb-2 bg-light border-secondary" data-action-type="rest_time" id="template-rest_time">
        <div class="card-body p-2">
            <div class="d-flex align-items-center">
                <strong class="me-2">Descanso:</strong>
                <input type="number" class="form-control form-control-sm w-auto me-1 action-time" placeholder="Tempo" min="0" value="60">
                <select class="form-select form-select-sm w-auto action-unit">
                    <option value="segundos">segundos</option>
                    <option value="minutos">minutos</option>
                </select>
                 <button type="button" class="btn btn-outline-danger btn-sm ms-auto remove-action-btn" title="Remover Ação"><i class="fas fa-trash-alt"></i></button>
            </div>
        </div>
    </div>
    <div class="action-item card mb-2 bg-light border-secondary" data-action-type="tempo" id="template-tempo">
        <div class="card-body p-2">
            <div class="d-flex align-items-center">
                <strong class="me-2">Tempo:</strong>
                <input type="text" class="form-control form-control-sm w-auto action-tempo" placeholder="Ex: 3-1-1-0">
                 <button type="button" class="btn btn-outline-danger btn-sm ms-auto remove-action-btn" title="Remover Ação"><i class="fas fa-trash-alt"></i></button>
            </div>
        </div>
    </div>
    <div class="action-item card mb-2 bg-light border-secondary" data-action-type="rpe" id="template-rpe">
        <div class="card-body p-2">
            <div class="d-flex align-items-center">
                <strong class="me-2">RPE:</strong>
                <input type="number" class="form-control form-control-sm w-auto me-1 action-rpe-value" placeholder="1-10" min="1" max="10">
                 <button type="button" class="btn btn-outline-danger btn-sm ms-auto remove-action-btn" title="Remover Ação"><i class="fas fa-trash-alt"></i></button>
            </div>
             <input type="text" class="form-control form-control-sm mt-1 action-notes" placeholder="Notas (Ex: esforço alto)">
        </div>
    </div>
    <div class="action-item card mb-2 bg-light border-secondary" data-action-type="note" id="template-note">
        <div class="card-body p-2">
            <div class="d-flex align-items-center">
                 <strong class="me-2">Nota:</strong>
                 <input type="text" class="form-control form-control-sm action-note-text" placeholder="Instrução ou observação livre">
                 <button type="button" class="btn btn-outline-danger btn-sm ms-auto remove-action-btn" title="Remover Ação"><i class="fas fa-trash-alt"></i></button>
            </div>
        </div>
    </div>
    <div class="action-item card mb-2 bg-light border-secondary" data-action-type="cardio" id="template-cardio">
        <div class="card-body p-2">
            <div class="d-flex align-items-center">
                 <strong class="me-2">Cardio:</strong>
                 <input type="text" class="form-control form-control-sm w-auto me-1 action-cardio-type" placeholder="Tipo (Ex: HIIT)">
                 <input type="number" class="form-control form-control-sm w-auto me-1 action-cardio-duration" placeholder="Duração" min="1"> min
                 <button type="button" class="btn btn-outline-danger btn-sm ms-auto remove-action-btn" title="Remover Ação"><i class="fas fa-trash-alt"></i></button>
            </div>
        </div>
    </div>
</div>
<!-- ==================== FIM TEMPLATES ==================== -->

<!-- Modal para selecionar tipo de ação (Copiado de edit_workout_plan.php) -->
<div class="modal fade" id="selectActionModal" tabindex="-1" aria-labelledby="selectActionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="selectActionModalLabel">Escolha o tipo de ação</h5>
        <button type="button" class="btn btn-sm btn-outline-info ms-auto me-2" data-action-type="note" id="noteActionHeaderBtn">Nota Livre / Instrução</button>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="list-group">
          <button type="button" class="list-group-item list-group-item-action" data-action-type="sets_reps">Sets x Reps</button>
          <button type="button" class="list-group-item list-group-item-action" data-action-type="rest_time">Tempo de Descanso</button>
          <button type="button" class="list-group-item list-group-item-action" data-action-type="tempo">Tempo (Ex: 3-1-1-0)</button>
          <button type="button" class="list-group-item list-group-item-action" data-action-type="rpe">RPE (Esforço Percebido)</button>
          <button type="button" class="list-group-item list-group-item-action" data-action-type="cardio">Cardio Específico</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- JavaScript (Adaptado de edit_workout_plan.php) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dayDetailsTableBody = document.querySelector('#day-details-table tbody');
    const actionTemplates = document.getElementById('action-templates');
    const selectActionModalEl = document.getElementById('selectActionModal');
    const selectActionModal = new bootstrap.Modal(selectActionModalEl);
    // const selectActionModalBody = selectActionModalEl.querySelector('.modal-body .list-group'); // Removido, pois o listener será mais genérico
    let currentDayActionsContainer = null;

    function createActionElement(type, data = {}) {
        const templateId = `template-${type}`;
        const template = actionTemplates.querySelector(`#${templateId}`);
        if (!template) { console.error(`Template para "${type}" não encontrado.`); return null; }
        const actionElement = template.cloneNode(true);
        actionElement.removeAttribute('id');
        actionElement.style.display = 'block';

        switch (type) {
            case 'sets_reps':
                actionElement.querySelector('.action-sets').value = data.sets ?? '3';
                actionElement.querySelector('.action-reps').value = data.reps ?? '8-12';
                actionElement.querySelector('.action-notes').value = data.notes ?? '';
                break;
            case 'rest_time':
                actionElement.querySelector('.action-time').value = data.time ?? '60';
                actionElement.querySelector('.action-unit').value = data.unit ?? 'segundos';
                break;
            case 'tempo': actionElement.querySelector('.action-tempo').value = data.tempo ?? ''; break;
            case 'rpe': actionElement.querySelector('.action-rpe-value').value = data.value ?? ''; actionElement.querySelector('.action-notes').value = data.notes ?? ''; break;
            case 'note': actionElement.querySelector('.action-note-text').value = data.text ?? ''; break;
            case 'cardio': actionElement.querySelector('.action-cardio-type').value = data.type ?? ''; actionElement.querySelector('.action-cardio-duration').value = data.duration ?? ''; break;
        }
        return actionElement;
    }

    function addActionToDay(container, type, data = {}) {
        const actionElement = createActionElement(type, data);
        if (actionElement) { container.appendChild(actionElement); }
    }

    function removeAction(button) {
        const actionItem = button.closest('.action-item');
        if (actionItem) { actionItem.remove(); }
    }

    function collectDayActionsData(container) {
        const actionsData = [];
        container.querySelectorAll('.action-item').forEach(actionElement => {
            const type = actionElement.dataset.actionType;
            let data = { type: type, data: {} };
            switch (type) {
                case 'sets_reps': data.data = { sets: actionElement.querySelector('.action-sets').value, reps: actionElement.querySelector('.action-reps').value, notes: actionElement.querySelector('.action-notes').value }; break;
                case 'rest_time': data.data = { time: actionElement.querySelector('.action-time').value, unit: actionElement.querySelector('.action-unit').value }; break;
                case 'tempo': data.data = { tempo: actionElement.querySelector('.action-tempo').value }; break;
                case 'rpe': data.data = { value: actionElement.querySelector('.action-rpe-value').value, notes: actionElement.querySelector('.action-notes').value }; break;
                case 'note': data.data = { text: actionElement.querySelector('.action-note-text').value }; break;
                case 'cardio': data.data = { type: actionElement.querySelector('.action-cardio-type').value, duration: actionElement.querySelector('.action-cardio-duration').value }; break;
            }
            actionsData.push(data);
        });
        return actionsData;
    }

    dayDetailsTableBody.addEventListener('click', function(event) {
        const addButton = event.target.closest('.add-action-btn');
        if (addButton) {
            currentDayActionsContainer = addButton.closest('td').querySelector('.day-actions-container');
            if (currentDayActionsContainer) { selectActionModal.show(); }
        }
        const removeButton = event.target.closest('.remove-action-btn');
        if (removeButton) { removeAction(removeButton); }
    });

    selectActionModalEl.addEventListener('click', function(event) { // Ouve cliques em todo o modal
        const actionButton = event.target.closest('[data-action-type]'); // Procura qualquer elemento com data-action-type
        if (actionButton) {
            const actionType = actionButton.dataset.actionType;
            if (currentDayActionsContainer && actionType) {
                addActionToDay(currentDayActionsContainer, actionType);
                selectActionModal.hide();
                currentDayActionsContainer = null;
            }
        }
    });

    const form = document.getElementById('addWorkoutPlanForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            dayDetailsTableBody.querySelectorAll('.day-actions-container').forEach(container => {
                const dayName = container.dataset.dayName;
                const actionsData = collectDayActionsData(container);
                const jsonString = JSON.stringify(actionsData);

                let hiddenInput = form.querySelector(`input[name="day_details_json[${dayName}]"]`);
                if (!hiddenInput) {
                    hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = `day_details_json[${dayName}]`;
                    form.appendChild(hiddenInput);
                }
                hiddenInput.value = jsonString;
            });
        });
    } else {
        console.error("Formulário #addWorkoutPlanForm não encontrado.");
    }
});
</script>

<?php
// Inclui o rodapé da página
include 'footer.php';
?>
