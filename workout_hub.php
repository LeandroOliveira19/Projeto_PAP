<?php
// filepath: c:\xampp\htdocs\Projeto_PAP\workout_hub.php
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

// Define qual link da sidebar deve estar ativo
$active_page = basename($_SERVER['PHP_SELF']); // Pega o nome do arquivo atual (workout_hub.php)

// Inclui a sidebar do cliente
include 'sidebar_client.php';

$user_id = $_SESSION['user_id'];
$workout_plan = null;
$workout_plan_days = []; // Details for each day (including JSON actions)
$personal_records = []; // Para guardar os recordes pessoais
$page_error = '';

try {
    // 1. Get the client_id for the logged-in user
    $stmt_client = $pdo->prepare("SELECT id FROM clients WHERE user_id = ? LIMIT 1");
    $stmt_client->execute([$user_id]);
    $client_data = $stmt_client->fetch();

    if (!$client_data) {
        throw new Exception("Registo de cliente não encontrado.");
    }
    $client_id = $client_data['id'];

    // 2. Fetch the client's ACTIVE workout plan and trainer's name
    $stmt_plan = $pdo->prepare("
        SELECT wp.id, wp.plan_details, wp.created_at, u.name as trainer_name
        FROM workout_plans wp
        JOIN users u ON wp.trainer_id = u.id
        WHERE wp.client_id = ? AND wp.is_active = 1
        LIMIT 1
    ");
    $stmt_plan->execute([$client_id]);
    $workout_plan = $stmt_plan->fetch();

    if ($workout_plan) {
        $workout_plan_id = $workout_plan['id'];

        // 3. Fetch day-by-day details for this plan (for accordion)
        $stmt_days = $pdo->prepare("
            SELECT wpd.day_name, wpd.day_details, do.name AS day_option_name
            FROM workout_plan_days wpd
            LEFT JOIN day_options do ON wpd.day_option_id = do.id
            WHERE wpd.workout_plan_id = ?
            ORDER BY FIELD(wpd.day_name, 'Dia 1', 'Dia 2', 'Dia 3', 'Dia 4', 'Dia 5', 'Dia 6', 'Dia 7')
        ");
        $stmt_days->execute([$workout_plan_id]);
        $workout_plan_days = $stmt_days->fetchAll();

        // 4. Buscar recordes pessoais para os exercícios deste plano
        $stmt_records = $pdo->prepare("
            SELECT exercise_id, MAX(weight_lifted) as max_weight
            FROM workout_log
            WHERE client_id = ?
            GROUP BY exercise_id
        ");
        $stmt_records->execute([$client_id]);
        $personal_records = $stmt_records->fetchAll(PDO::FETCH_KEY_PAIR);

    } else { // No active plan found
        $page_error = "Nenhum plano de treino ativo encontrado. Contacte o seu treinador.";
    }

} catch (PDOException $e) {
    $page_error = "Erro ao carregar o plano de treino: " . $e->getMessage();
    error_log("Erro BD ao carregar plano de treino (workout_hub.php): " . $e->getMessage());
} catch (Exception $e) {
    $page_error = "Erro: " . $e->getMessage();
    error_log("Erro geral ao carregar plano de treino (workout_hub.php): " . $e->getMessage());
}

// Helper function to render actions from JSON
function renderActions($json_details) {
    $actions = json_decode($json_details, true);
    if (!is_array($actions) || empty($actions)) {
        return '<p class="text-muted small fst-italic">Nenhuma ação detalhada para este dia.</p>';
    }

    $html = '<ul class="list-unstyled small mb-0">';
    foreach ($actions as $action) {
        $html .= '<li>';
        switch ($action['type']) {
            case 'sets_reps':
                $html .= "<strong>Sets x Reps:</strong> " . htmlspecialchars($action['data']['sets'] ?? '') . " x " . htmlspecialchars($action['data']['reps'] ?? '');
                if (!empty($action['data']['notes'])) {
                    $html .= " <span class='text-muted'>(" . htmlspecialchars($action['data']['notes']) . ")</span>";
                }
                break;
            case 'rest_time':
                $html .= "<strong>Descanso:</strong> " . htmlspecialchars($action['data']['time'] ?? '') . " " . htmlspecialchars($action['data']['unit'] ?? '');
                break;
            case 'tempo':
                $html .= "<strong>Tempo:</strong> " . htmlspecialchars($action['data']['tempo'] ?? '');
                break;
            case 'rpe':
                $html .= "<strong>RPE:</strong> " . htmlspecialchars($action['data']['value'] ?? '');
                 if (!empty($action['data']['notes'])) {
                    $html .= " <span class='text-muted'>(" . htmlspecialchars($action['data']['notes']) . ")</span>";
                }
                break;
            case 'note':
                $html .= "<strong>Nota:</strong> " . nl2br(htmlspecialchars($action['data']['text'] ?? ''));
                break;
            case 'cardio':
                 $html .= "<strong>Cardio:</strong> " . htmlspecialchars($action['data']['type'] ?? '') . " (" . htmlspecialchars($action['data']['duration'] ?? '') . " min)";
                 break;
            default:
                $html .= "Ação desconhecida";
                break;
        }
        $html .= '</li>';
    }
    $html .= '</ul>';
    return $html;
}

// The getExercisesForDayOption function was moved to workout_plan_details.php

?>

<style>
    /* Optional: Add some styling for the layout */
    .workout-accordion .accordion-item {
        border: 1px solid var(--bs-border-color-translucent);
        border-radius: 0.5rem;
        margin-bottom: 0.75rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
    }
    .workout-accordion .accordion-button {
        font-weight: 500;
        color: var(--bs-body-color);
        background-color: var(--bs-tertiary-bg);
        border-radius: calc(0.5rem - 1px) calc(0.5rem - 1px) 0 0;
    }
    .workout-accordion .accordion-button:not(.collapsed) {
        color: var(--bs-primary-text-emphasis);
        background-color: var(--bs-primary-bg-subtle);
        box-shadow: inset 0 -1px 0 var(--bs-border-color-translucent);
    }
    .workout-accordion .accordion-body ul { list-style-type: none; padding-left: 0; }
    .workout-accordion .accordion-body ul li { padding: 0.4rem 0; border-bottom: 1px dashed var(--bs-border-color-translucent); }
    .workout-accordion .accordion-body ul li:last-child { border-bottom: none; }

     .section-divider {
        margin-top: 2.5rem;
        margin-bottom: 2.5rem;
        border-top: 1px solid var(--bs-border-color);
    }
</style>

<!-- Conteúdo Principal -->
<main class="main-content">
    <div class="container mt-5">
        <h2>Meu Plano de Treino</h2>

        <?php if ($page_error): ?>
            <div class="alert alert-warning"><?= htmlspecialchars($page_error) ?></div>
        <?php elseif ($workout_plan): ?>

            <!-- Secção: Informações Gerais do Plano -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light"><h4 class="mb-0 text-dark">Informações do Plano</h4></div>
                    <div class="card-body">
                        <p><strong>Criado em:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($workout_plan['created_at']))) ?></p>
                        <p><strong>Treinador:</strong> <?= htmlspecialchars($workout_plan['trainer_name']) ?></p>
                        <h6>Observações Gerais:</h6>
                        <?php if (!empty($workout_plan['plan_details'])): ?>
                            <p><?= nl2br(htmlspecialchars($workout_plan['plan_details'])) ?></p>
                        <?php else: ?>
                            <p class="text-muted fst-italic">Nenhuma observação geral.</p>
                        <?php endif; ?>
                        <!-- Botão de Download PDF -->
                        <div class="mt-3">
                            <a href="generate_workout_pdf.php?plan_id=<?= $workout_plan['id'] ?>" class="btn btn-danger" target="_blank"><i class="fas fa-file-pdf me-2"></i>Baixar Plano (PDF)</a>
                        </div>
                    </div>
            </div>

            <!-- Secção: Plano Diário (Ações) - Accordion -->
            <h4 class="mb-3 mt-5">Plano Diário (Ações e Instruções)</h4>
            <?php if (!empty($workout_plan_days)): ?>
                <div class="accordion workout-accordion" id="workoutPlanAccordion">
                    <?php foreach ($workout_plan_days as $index => $day):
                        $day_id_suffix = preg_replace('/[^a-zA-Z0-9]/', '', $day['day_name']) . $index;
                        $is_first_item = ($index === 0);
                    ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?= $day_id_suffix ?>">
                                <button class="accordion-button <?= !$is_first_item ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $day_id_suffix ?>" aria-expanded="<?= $is_first_item ? 'true' : 'false' ?>" aria-controls="collapse<?= $day_id_suffix ?>">
                                    <i class="fas fa-calendar-day me-2"></i>
                                    <strong><?= htmlspecialchars($day['day_name']) ?>:</strong>&nbsp;
                                    <span class="text-primary"><?= htmlspecialchars($day['day_option_name'] ?: 'Descanso / Outro') ?></span>
                                </button>
                            </h2>
                            <div id="collapse<?= $day_id_suffix ?>" class="accordion-collapse collapse <?= $is_first_item ? 'show' : '' ?>" aria-labelledby="heading<?= $day_id_suffix ?>" data-bs-parent="#workoutPlanAccordion">
                                <div class="accordion-body">
                                    <?= renderActions($day['day_details']) ?>
                                    <!-- Botão para registar treino -->
                                    <div class="mt-3 text-end">
                                        <button class="btn btn-success btn-sm log-workout-btn"
                                                data-bs-toggle="modal" data-bs-target="#logWorkoutModal"
                                                data-day-name="<?= htmlspecialchars($day['day_name']) ?>"
                                                data-plan-id="<?= $workout_plan['id'] ?>"><i class="fas fa-pencil-alt me-1"></i> Registar Treino</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                    <p class="text-muted">Nenhum detalhe diário configurado para este plano.</p>
                <?php endif; ?>

        <?php endif; ?>

        <div class="text-center mt-5 mb-4">
            <!-- Adicionado link para a nova página de detalhes -->
            <a href="workout_plan_details.php" class="btn btn-info"><i class="fas fa-list-ul me-1"></i>Ver Detalhes dos Exercícios</a>
            <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar ao Dashboard</a>
        </div>

    </div>
</main>

<!-- Modal para Registar Treino -->
<div class="modal fade" id="logWorkoutModal" tabindex="-1" aria-labelledby="logWorkoutModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="logWorkoutModalLabel" style="color: black;">Registar Treino - <span id="modalDayName"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="logWorkoutForm">
            <input type="hidden" name="workout_plan_id" id="modalPlanId">
            <input type="hidden" name="log_date" value="<?= date('Y-m-d') ?>">
            <div id="modalExercisesContainer">
                <!-- Exercícios serão carregados aqui via JS -->
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="saveWorkoutLogBtn">Guardar Registo</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Script para arrastar, duplo clique e dropdown do ícone de perfil
    const profileIconContainer = document.querySelector('.profile-icon-container');
    if (!profileIconContainer) return;

    const profileIconLink = document.getElementById('profileIconLink');
    const profileDropdown = document.getElementById('profileDropdown');

    let isDragging = false;
    let dragStarted = false; // Flag para saber se o arrasto realmente começou
    let offsetX, offsetY;

    let lastTapTime = 0;
    const doubleTapDelay = 300; // ms
    let tapCount = 0;
    let tapTimer = null;

    // --- Lógica de Clique/Duplo Clique/Dropdown ---
    if (profileIconLink && profileDropdown) {
        profileIconLink.addEventListener('click', function(e) {
            e.preventDefault(); // Prevenir navegação padrão do link

            if (dragStarted) { // Se um arrasto acabou de acontecer, não processar como clique/duplo clique
                dragStarted = false;
                return;
            }

            tapCount++;
            const currentTime = new Date().getTime();

            if (tapCount === 1) {
                lastTapTime = currentTime;
                tapTimer = setTimeout(function() {
                    // Ação de clique único: Abrir/Fechar dropdown
                    profileDropdown.classList.toggle('show');
                    tapCount = 0; // Reset
                }, doubleTapDelay);
            } else if (tapCount === 2) {
                if (currentTime - lastTapTime < doubleTapDelay) {
                    // Duplo clique
                    clearTimeout(tapTimer);
                    window.location.href = profileIconLink.href; // Navegar para o perfil
                    tapCount = 0; // Reset
                    lastTapTime = 0; // Reset para evitar falso positivo
                    profileDropdown.classList.remove('show'); // Esconder dropdown se estiver aberto
                } else {
                    // Segundo clique muito espaçado, tratar como primeiro de uma nova sequência
                    tapCount = 1;
                    lastTapTime = currentTime;
                    clearTimeout(tapTimer); // Limpa o timer anterior
                    tapTimer = setTimeout(function() {
                        profileDropdown.classList.toggle('show');
                        tapCount = 0;
                    }, doubleTapDelay);
                }
            }
        });

        // Fechar dropdown se clicar fora
        document.addEventListener('click', function(event) {
            if (!profileIconContainer.contains(event.target) && profileDropdown.classList.contains('show')) {
                profileDropdown.classList.remove('show');
                tapCount = 0; // Reset tap count para evitar que um clique fora e depois um clique no ícone seja duplo clique
            }
        });
    }

    // --- Lógica de Arrastar (com a flag dragStarted) ---
    const savedPosition = localStorage.getItem('profileIconPosition');
    if (savedPosition) {
        try {
            const pos = JSON.parse(savedPosition);
            profileIconContainer.style.position = 'fixed';
            profileIconContainer.style.left = pos.left;
            profileIconContainer.style.top = pos.top;
            profileIconContainer.style.right = 'auto';
            profileIconContainer.style.bottom = 'auto';
        } catch (e) {
            console.error("Failed to parse saved profile icon position:", e);
            localStorage.removeItem('profileIconPosition');
        }
    }

    function startDrag(e) {
        // Apenas botão primário do rato ou toque
        if (e.type === 'mousedown' && e.button !== 0) return;

        // Se o dropdown estiver aberto, não iniciar arrasto para permitir interação com o dropdown
        if (profileDropdown && profileDropdown.classList.contains('show')) {
            return;
        }

        isDragging = true;
        dragStarted = false; // Reset no início de um potencial arrasto

        const clientX = e.clientX || e.touches[0].clientX;
        const clientY = e.clientY || e.touches[0].clientY;
        const rect = profileIconContainer.getBoundingClientRect();
        offsetX = clientX - rect.left;
        offsetY = clientY - rect.top;

        profileIconContainer.style.position = 'fixed';
        profileIconContainer.style.right = 'auto';
        profileIconContainer.style.bottom = 'auto';
        // Aumentar z-index durante o arrasto para ficar sobre outros elementos
        profileIconContainer.style.zIndex = '1055';

        document.addEventListener('mousemove', drag);
        document.addEventListener('mouseup', endDrag);
        document.addEventListener('touchmove', drag, { passive: false });
        document.addEventListener('touchend', endDrag);
    }

    function drag(e) {
        if (!isDragging) return;
        if (!dragStarted) {
            if (profileDropdown && profileDropdown.classList.contains('show')) {
                profileDropdown.classList.remove('show');
                tapCount = 0; // Reset tap count
            }
        }
        dragStarted = true;
        if (e.cancelable) e.preventDefault();

        const clientX = e.clientX || e.touches[0].clientX;
        const clientY = e.clientY || e.touches[0].clientY;
        let newLeft = clientX - offsetX;
        let newTop = clientY - offsetY;
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        const elementWidth = profileIconContainer.offsetWidth;
        const elementHeight = profileIconContainer.offsetHeight;
        newLeft = Math.max(0, Math.min(newLeft, viewportWidth - elementWidth));
        newTop = Math.max(0, Math.min(newTop, viewportHeight - elementHeight));
        profileIconContainer.style.left = `${newLeft}px`;
        profileIconContainer.style.top = `${newTop}px`;
    }

    function endDrag() {
        if (!isDragging) return;

        isDragging = false;

        // Remove event listeners
        document.removeEventListener('mousemove', drag);
        document.removeEventListener('mouseup', endDrag);
        document.removeEventListener('touchmove', drag);
        document.removeEventListener('touchend', endDrag);

        // Save the final position
        const finalPosition = {
            left: profileIconContainer.style.left,
            top: profileIconContainer.style.top
        };
        localStorage.setItem('profileIconPosition', JSON.stringify(finalPosition));
    }

    // Add event listeners for starting the drag
    // Use capture phase for mousedown/touchstart to ensure we get the event before the link
    profileIconContainer.addEventListener('mousedown', startDrag, true);
    profileIconContainer.addEventListener('touchstart', startDrag, { passive: false, capture: true });

    // Prevent the link from being dragged as an image
    profileIconContainer.querySelector('a').addEventListener('dragstart', function(e) {
        e.preventDefault();
    });

    // --- LÓGICA PARA O MODAL DE REGISTO DE TREINO ---
    const logWorkoutModalEl = document.getElementById('logWorkoutModal');
    const modalDayNameEl = document.getElementById('modalDayName');
    const modalPlanIdInput = document.getElementById('modalPlanId');
    const modalExercisesContainer = document.getElementById('modalExercisesContainer');
    const saveWorkoutLogBtn = document.getElementById('saveWorkoutLogBtn');
    const personalRecords = <?= json_encode($personal_records) ?>;

    logWorkoutModalEl.addEventListener('show.bs.modal', async function (event) {
        const button = event.relatedTarget;
        const dayName = button.dataset.dayName;
        const planId = button.dataset.planId;

        modalDayNameEl.textContent = dayName;
        modalPlanIdInput.value = planId;
        modalExercisesContainer.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">A carregar...</span></div></div>';

        try {
            const response = await fetch(`ajax_get_day_exercises.php?plan_id=${planId}&day_name=${encodeURIComponent(dayName)}`);
            const data = await response.json();

            if (data.status === 'success' && data.exercises.length > 0) {
                modalExercisesContainer.innerHTML = '';
                data.exercises.forEach((exercise, index) => {
                    let recordHtml;
                    if (personalRecords[exercise.id]) {
                        // Formata o número para o padrão português (ex: 20,00) e adiciona um ícone
                        const recordValue = parseFloat(personalRecords[exercise.id]).toLocaleString('pt-PT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        recordHtml = `<div class="mt-2"><span class="badge bg-success fs-6"><i class="fas fa-trophy me-1"></i> Recorde: ${recordValue} kg</span></div>`;
                    } else {
                        recordHtml = `<small class="text-muted d-block mt-1">Sem registo anterior</small>`;
                    }
                    const exerciseHtml = `
                        <div class="mb-3 border-bottom pb-3">
                            <label for="weight_exercise_${exercise.id}" class="form-label" style="color: black;"><strong>${exercise.name}</strong></label>
                            <div class="input-group">
                                <input type="hidden" name="exercises[${index}][exercise_id]" value="${exercise.id}">
                                <input type="text" inputmode="decimal" class="form-control" id="weight_exercise_${exercise.id}" name="exercises[${index}][weight]" placeholder="Peso levantado">
                                <span class="input-group-text">kg</span>
                            </div>
                            ${recordHtml}
                        </div>
                    `;
                    modalExercisesContainer.insertAdjacentHTML('beforeend', exerciseHtml);
                });
            } else if (data.exercises.length === 0) {
                modalExercisesContainer.innerHTML = '<p class="text-muted">Nenhum exercício específico encontrado para este dia. Pode registar outros detalhes nas observações do plano.</p>';
            } else {
                modalExercisesContainer.innerHTML = `<p class="text-danger">Erro: ${data.message}</p>`;
            }
        } catch (error) {
            modalExercisesContainer.innerHTML = '<p class="text-danger">Erro ao carregar os exercícios.</p>';
            console.error('Fetch error:', error);
        }
    });

    saveWorkoutLogBtn.addEventListener('click', async function() {
        const form = document.getElementById('logWorkoutForm');
        const formData = new FormData(form);
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> A Guardar...';

        try {
            const response = await fetch('log_workout_session.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            alert(result.message);
            if (result.status === 'success') {
                window.location.reload(); // Recarrega a página para mostrar os novos recordes
            }
        } catch (error) {
            alert('Erro de comunicação ao guardar o registo.');
        } finally {
            this.disabled = false;
            this.innerHTML = 'Guardar Registo';
        }
    });
});
</script>
<?php
// Inclui o rodapé da página
include 'footer.php';
?>
