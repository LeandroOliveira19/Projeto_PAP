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
            <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar ao Dashboard</a>
        </div>

    </div>
</main>

<?php
// Inclui o rodapé da página
include 'footer.php';
?>
