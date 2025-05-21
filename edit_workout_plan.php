<?php
// filepath: c:\xampp\htdocs\Projeto_PAP\edit_workout_plan.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainer') {
    header('Location: login.php');
    exit;
}
include 'db.php';
include 'header.php';

$active_page = 'my_clients.php';
include 'sidebar_trainer.php';

if (!isset($_GET['client_id']) || !isset($_GET['workout_plan_id'])) {
    echo "<script>alert('ID do cliente ou do plano de treino não fornecido.'); window.location.href = 'trainer.php';</script>";
    exit;
}

$client_id = filter_var($_GET['client_id'], FILTER_VALIDATE_INT);
$workout_plan_id = filter_var($_GET['workout_plan_id'], FILTER_VALIDATE_INT);

if (!$client_id || !$workout_plan_id) {
    echo "<script>alert('IDs inválidos.'); window.location.href = 'trainer.php';</script>";
    exit;
}

$page_error = '';
$plan = ['plan_details' => ''];
$selected_exercises_ids = []; // Renomeado para clareza
$workout_plan_days = []; // Dados dos dias do plano (incluindo day_details como JSON)
$days_data_map = []; // Mapa para fácil acesso aos dados dos dias no JS
$day_options = [];
$client_name = 'Cliente';
$exercises = []; // Lista de todos os exercícios para seleção

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan_details_obs = trim($_POST['plan_details'] ?? ''); // Observações gerais do plano
    $posted_selected_exercises = $_POST['selected_exercises'] ?? [];
    $days_names_from_post = $_POST['day_name'] ?? []; // Nomes dos dias (Dia 1, Dia 2...)
    $day_option_ids_from_post = $_POST['day_option_id'] ?? [];
    // Detalhes específicos do dia virão como JSON
    $day_details_json_array = $_POST['day_details_json'] ?? [];

    try {
        $pdo->beginTransaction();

        // Update existing workout plan details (observações gerais)
        $stmt_update_plan = $pdo->prepare("UPDATE workout_plans SET plan_details = ? WHERE id = ? AND trainer_id = ? AND client_id = ?");
        $stmt_update_plan->execute([$plan_details_obs, $workout_plan_id, $_SESSION['user_id'], $client_id]);

        // Delete existing exercises for this workout plan
        $stmt_delete_exercises = $pdo->prepare("DELETE FROM workout_plan_exercises WHERE workout_plan_id = ?");
        $stmt_delete_exercises->execute([$workout_plan_id]);

        // Insert the selected exercises into the workout_plan_exercises table
        $stmt_insert_exercise = $pdo->prepare("INSERT INTO workout_plan_exercises (workout_plan_id, exercise_id) VALUES (?, ?)");
        foreach ($posted_selected_exercises as $exercise_id) {
            $stmt_insert_exercise->execute([$workout_plan_id, $exercise_id]);
        }

        // Delete existing day details
        $stmt_delete_days = $pdo->prepare("DELETE FROM workout_plan_days WHERE workout_plan_id = ?");
        $stmt_delete_days->execute([$workout_plan_id]);

        // Insert the day-by-day details
        $stmt_insert_day = $pdo->prepare("INSERT INTO workout_plan_days (workout_plan_id, day_name, day_details, day_option_id) VALUES (?, ?, ?, ?)");

        // Itera sobre os nomes dos dias que vieram do formulário (deveriam ser 7)
        foreach ($days_names_from_post as $key => $day_name_value) {
            if (empty($day_name_value)) continue; // Pula se o nome do dia estiver vazio

            // Pega o JSON dos detalhes para este dia
            // O JavaScript envia day_details_json[Dia 1], day_details_json[Dia 2], etc.
            $current_day_json_details = $day_details_json_array[$day_name_value] ?? '[]'; // Default para array JSON vazio
            $current_day_option_id = $day_option_ids_from_post[$key] ?? null;

            $stmt_insert_day->execute([$workout_plan_id, $day_name_value, $current_day_json_details, $current_day_option_id]);
        }

        $pdo->commit();
        echo "<script>alert('Plano de treino atualizado com sucesso!'); window.location.href = 'my_clients.php';</script>";
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Erro ao atualizar plano de treino: " . $e->getMessage());
        echo "<script>alert('Erro ao atualizar o plano de treino. Tente novamente. Detalhe: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
        exit;
    }
}

// --- Fetch existing data for the form ---
try {
    $stmt_client_name = $pdo->prepare("SELECT u.name FROM users u JOIN clients c ON u.id = c.user_id WHERE c.id = ?");
    $stmt_client_name->execute([$client_id]);
    $client_name_result = $stmt_client_name->fetchColumn();
    if ($client_name_result) {
        $client_name = $client_name_result;
    } else {
        throw new Exception("Cliente não encontrado.");
    }

    $stmt_plan = $pdo->prepare("SELECT plan_details FROM workout_plans WHERE id = ? AND trainer_id = ? AND client_id = ?");
    $stmt_plan->execute([$workout_plan_id, $_SESSION['user_id'], $client_id]);
    $plan_result = $stmt_plan->fetch();
    if ($plan_result) {
        $plan = $plan_result;
    } else {
        throw new Exception("Plano de treino não encontrado ou acesso negado.");
    }

    $stmt_selected_exercises = $pdo->prepare("SELECT exercise_id FROM workout_plan_exercises WHERE workout_plan_id = ?");
    $stmt_selected_exercises->execute([$workout_plan_id]);
    $selected_exercises_ids = $stmt_selected_exercises->fetchAll(PDO::FETCH_COLUMN);

    // Fetch day-by-day details (day_details será JSON)
    $stmt_days = $pdo->prepare("SELECT day_name, day_details, day_option_id FROM workout_plan_days WHERE workout_plan_id = ? ORDER BY FIELD(day_name, 'Dia 1', 'Dia 2', 'Dia 3', 'Dia 4', 'Dia 5', 'Dia 6', 'Dia 7')");
    $stmt_days->execute([$workout_plan_id]);
    $workout_plan_days_from_db = $stmt_days->fetchAll(); // Renomeado para evitar conflito

    // Prepara $days_data_map para o JavaScript
    foreach ($workout_plan_days_from_db as $day_db) {
        $days_data_map[$day_db['day_name']] = [
            'day_details' => $day_db['day_details'], // Este é o JSON
            'day_option_id' => $day_db['day_option_id']
        ];
    }


    $stmt_day_options = $pdo->query("SELECT id, name FROM day_options ORDER BY id");
    $day_options = $stmt_day_options->fetchAll();

    $stmt_all_exercises = $pdo->query("SELECT * FROM exercises ORDER BY muscle_group ASC, name ASC"); // Ordenado por grupo muscular
    $exercises = $stmt_all_exercises->fetchAll();

} catch (PDOException $e) {
    $page_error = "Erro ao carregar dados para edição (BD): " . $e->getMessage();
    error_log($page_error);
    $plan = ['plan_details' => '']; $selected_exercises_ids = []; $workout_plan_days = []; $day_options = []; $exercises = [];
} catch (Exception $e) {
     $page_error = $e->getMessage();
     echo "<script>alert('" . htmlspecialchars($page_error) . "'); window.location.href = 'my_clients.php';</script>";
     exit;
}
?>
<style>
    .table-group-header {
        font-weight: bold;
        background-color: var(--bs-light-bg-subtle, #f8f9fa); /* Cor de fundo subtil */
        color: var(--bs-emphasis-color, #212529); /* Cor de texto de destaque */
        padding: 0.5rem 0.75rem;
    }
</style>

<main class="main-content">
    <div class="container mt-5 mb-5">
        <h2>Editar Plano de Treino para <?= htmlspecialchars($client_name) ?></h2>

        <?php if ($page_error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($page_error) ?></div>
        <?php endif; ?>

        <form method="POST" action="edit_workout_plan.php?client_id=<?= $client_id ?>&workout_plan_id=<?= $workout_plan_id ?>" id="workoutPlanForm">
            <div class="form-group mb-3">
                <label for="plan_details" class="form-label">Observações Gerais do Plano</label>
                <textarea class="form-control" id="plan_details" name="plan_details" rows="3"><?= htmlspecialchars($plan['plan_details']) ?></textarea>
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
                        // Garante que $days_data_map está definido mesmo que $workout_plan_days_from_db esteja vazio
                        if (empty($days_data_map) && empty($workout_plan_days_from_db)) {
                             for ($i = 1; $i <= 7; $i++) {
                                $day_name_loop = "Dia " . $i;
                                $days_data_map[$day_name_loop] = ['day_details' => '[]', 'day_option_id' => null];
                            }
                        }

                        for ($i = 1; $i <= 7; $i++):
                            $current_day_name = "Dia " . $i;
                            // Garante que $day_data existe e tem as chaves esperadas
                            $day_data = $days_data_map[$current_day_name] ?? ['day_details' => '[]', 'day_option_id' => null];
                            // Garante que day_details é uma string JSON válida, mesmo que vazia
                            $day_data['day_details'] = (is_string($day_data['day_details']) && (json_decode($day_data['day_details']) !== null)) ? $day_data['day_details'] : '[]';
                        ?>
                            <tr>
                                <td>
                                    <input type="text" class="form-control form-control-sm" name="day_name[]" value="<?= htmlspecialchars($current_day_name) ?>" readonly>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm" name="day_option_id[]">
                                        <option value="">-- Selecione --</option>
                                        <?php foreach ($day_options as $option): ?>
                                            <option value="<?= $option['id'] ?>" <?= ($day_data['day_option_id'] == $option['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($option['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <div class="day-actions-container" data-day-name="<?= htmlspecialchars($current_day_name) ?>" data-existing-details="<?= htmlspecialchars($day_data['day_details']) ?>">
                                        <!-- As ações serão carregadas/adicionadas aqui pelo JS -->
                                    </div>
                                    <button type="button" class="btn btn-outline-secondary btn-sm mt-2 add-action-btn" data-day-name="<?= htmlspecialchars($current_day_name) ?>">
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
                        if (empty($exercises)) {
                            echo "<tr><td colspan='5' class='text-center text-muted py-3'>Nenhum exercício cadastrado no sistema.</td></tr>";
                        } else {
                            $current_muscle_group = null; // Para rastrear a mudança de grupo
                            foreach ($exercises as $exercise) {
                                $exercise_muscle_group_display = !empty($exercise['muscle_group']) ? htmlspecialchars($exercise['muscle_group']) : 'Outros Grupos';

                                // Se o grupo muscular mudou, imprime um cabeçalho de grupo
                                if ($exercise_muscle_group_display !== $current_muscle_group) {
                                    if ($current_muscle_group !== null) { // Adiciona um pequeno espaço entre grupos, exceto antes do primeiro
                                        echo "<tr><td colspan='5' style='padding:0.3rem; background-color: transparent; border:none;'></td></tr>";
                                    }
                                    echo "<tr><td colspan='5' class='table-group-header'>" . $exercise_muscle_group_display . "</td></tr>";
                                    $current_muscle_group = $exercise_muscle_group_display;
                                }

                                $is_checked = in_array($exercise['id'], $selected_exercises_ids);
                                echo "<tr>";
                                echo "<td class='text-center'><input type='checkbox' class='form-check-input' name='selected_exercises[]' value='" . $exercise['id'] . "'" . ($is_checked ? " checked" : "") . "></td>";
                                echo "<td>" . htmlspecialchars($exercise['name']) . "</td>";
                                echo "<td>" . $exercise_muscle_group_display . "</td>"; // Mostra o grupo (ou 'Outros Grupos')
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
                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-1"></i> Salvar Alterações</button>
                <a href="my_clients.php" class="btn btn-secondary btn-lg"><i class="fas fa-arrow-left me-1"></i> Cancelar</a>
            </div>
        </form>
    </div>
</main>

<!-- Templates e Modal (como na resposta anterior) -->
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

<div class="modal fade" id="selectActionModal" tabindex="-1" aria-labelledby="selectActionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="selectActionModalLabel">Escolha o tipo de ação</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="list-group">
          <button type="button" class="list-group-item list-group-item-action" data-action-type="sets_reps">Sets x Reps</button>
          <button type="button" class="list-group-item list-group-item-action" data-action-type="rest_time">Tempo de Descanso</button>
          <button type="button" class="list-group-item list-group-item-action" data-action-type="tempo">Tempo (Ex: 3-1-1-0)</button>
          <button type="button" class="list-group-item list-group-item-action" data-action-type="rpe">RPE (Esforço Percebido)</button>
          <button type="button" class="list-group-item list-group-item-action" data-action-type="note">Nota Livre / Instrução</button>
          <button type="button" class="list-group-item list-group-item-action" data-action-type="cardio">Cardio Específico</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dayDetailsTableBody = document.querySelector('#day-details-table tbody');
    const actionTemplates = document.getElementById('action-templates');
    const selectActionModalEl = document.getElementById('selectActionModal');
    const selectActionModal = new bootstrap.Modal(selectActionModalEl);
    const selectActionModalBody = selectActionModalEl.querySelector('.modal-body .list-group');
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
            case 'tempo':
                actionElement.querySelector('.action-tempo').value = data.tempo ?? '';
                break;
            case 'rpe':
                actionElement.querySelector('.action-rpe-value').value = data.value ?? '';
                actionElement.querySelector('.action-notes').value = data.notes ?? '';
                break;
            case 'note':
                actionElement.querySelector('.action-note-text').value = data.text ?? '';
                break;
            case 'cardio':
                actionElement.querySelector('.action-cardio-type').value = data.type ?? '';
                actionElement.querySelector('.action-cardio-duration').value = data.duration ?? '';
                break;
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
            let data = { type: type, data: {} }; // Garante que 'data' existe
            switch (type) {
                case 'sets_reps':
                    data.data = {
                        sets: actionElement.querySelector('.action-sets').value,
                        reps: actionElement.querySelector('.action-reps').value,
                        notes: actionElement.querySelector('.action-notes').value
                    };
                    break;
                case 'rest_time':
                    data.data = {
                        time: actionElement.querySelector('.action-time').value,
                        unit: actionElement.querySelector('.action-unit').value
                    };
                    break;
                case 'tempo':
                    data.data = { tempo: actionElement.querySelector('.action-tempo').value };
                    break;
                case 'rpe':
                    data.data = {
                        value: actionElement.querySelector('.action-rpe-value').value,
                        notes: actionElement.querySelector('.action-notes').value
                    };
                    break;
                case 'note':
                    data.data = { text: actionElement.querySelector('.action-note-text').value };
                    break;
                case 'cardio':
                     data.data = {
                        type: actionElement.querySelector('.action-cardio-type').value,
                        duration: actionElement.querySelector('.action-cardio-duration').value
                    };
                    break;
            }
            actionsData.push(data);
        });
        return actionsData;
    }

    dayDetailsTableBody.querySelectorAll('.day-actions-container').forEach(container => {
        const existingDetailsJson = container.dataset.existingDetails;
        if (existingDetailsJson) {
            try {
                const actions = JSON.parse(existingDetailsJson);
                if (Array.isArray(actions)) {
                    actions.forEach(action => {
                        if (action.type && action.data) {
                            addActionToDay(container, action.type, action.data);
                        }
                    });
                }
            } catch (e) {
                console.error(`Erro ao parsear JSON para ${container.dataset.dayName}:`, e, existingDetailsJson);
            }
        }
    });

    dayDetailsTableBody.addEventListener('click', function(event) {
        const addButton = event.target.closest('.add-action-btn');
        if (addButton) {
            currentDayActionsContainer = addButton.closest('td').querySelector('.day-actions-container');
            if (currentDayActionsContainer) { selectActionModal.show(); }
        }
        const removeButton = event.target.closest('.remove-action-btn');
        if (removeButton) { removeAction(removeButton); }
    });

    selectActionModalBody.addEventListener('click', function(event) {
        const actionButton = event.target.closest('.list-group-item-action');
        if (actionButton) {
            const actionType = actionButton.dataset.actionType;
            if (currentDayActionsContainer && actionType) {
                addActionToDay(currentDayActionsContainer, actionType);
                selectActionModal.hide();
                currentDayActionsContainer = null;
            }
        }
    });

    const form = document.getElementById('workoutPlanForm');
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
                    // O nome do input deve corresponder ao que o PHP espera no POST
                    // Ex: day_details_json[Dia 1], day_details_json[Dia 2]
                    hiddenInput.name = `day_details_json[${dayName}]`;
                    form.appendChild(hiddenInput);
                }
                hiddenInput.value = jsonString;
            });
        });
    } else {
        console.error("Formulário #workoutPlanForm não encontrado.");
    }
});
</script>

<?php include 'footer.php'; ?>
