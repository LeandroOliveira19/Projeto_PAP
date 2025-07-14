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
include 'header.php'; // Inclui o cabeçalho (que tem o início do body e o ícone de perfil)

$client_name = $_SESSION['name'] ?? 'Cliente';
$active_page = basename($_SERVER['PHP_SELF']); // Define a página ativa para a sidebar
$user_id = $_SESSION['user_id']; // Este é o users.id
// DEBUG: Verifique se o user_id e role estão corretos na sessão
error_log("Dashboard - Iniciada para User ID (users.id): " . $user_id . ", Role: " . ($_SESSION['role'] ?? 'N/D'));



$client_dashboard_data = [
    'client_id' => null, // Este será o clients.id
    'weight' => null,
    'height' => null,
    'bmi' => null,
    'bmi_category' => 'N/D',
    'active_workout_plan_title' => 'Nenhum plano de treino ativo',
    'active_diet_plan_title' => 'Nenhum plano alimentar ativo',
    'next_class_name' => null,
    'next_class_schedule' => null,
    'next_class_duration' => null,
    'next_class_id' => null,
    'current_goal_description' => 'Defina um novo objetivo!', // Mensagem padrão
    'current_goal_target_date' => null,
    'current_goal_id' => null, // ID do objetivo ativo (client_goals.id)
    'previous_weight' => null,
    'last_assessment_date' => null,
    'daily_reminder_text' => "Mantenha o foco nos seus objetivos e hidrate-se!", // MODIFICADO: Lembrete único
    'daily_reminder_author' => null, // MODIFICADO: Autor do lembrete (se citação)
    'achievements' => [],
    'mood_today_value' => null
];

try {
    // --- PONTO CRÍTICO 1: Obter client_id (clients.id) a partir do user_id (users.id) da sessão ---
    $stmt_client_info = $pdo->prepare("SELECT id, weight, height FROM clients WHERE user_id = ?");
    $stmt_client_info->execute([$user_id]);
    $client_info = $stmt_client_info->fetch(); // DEBUG: Verifique o resultado desta busca
    error_log("Dashboard - Resultado da busca por client_info para user_id " . $user_id . ": " . print_r($client_info, true));

    if ($client_info) {
        $client_dashboard_data['client_id'] = $client_info['id']; // Este é o ID da tabela 'clients'
        error_log("Dashboard - client_id (clients.id) definido como: " . $client_dashboard_data['client_id']);
        $client_dashboard_data['weight'] = $client_info['weight'];
        $client_dashboard_data['height'] = $client_info['height'];

        // --- NOVO: Fallback para buscar a avaliação mais recente se os dados em `clients` forem nulos ---
        if ((empty($client_dashboard_data['weight']) || empty($client_dashboard_data['height'])) && $client_dashboard_data['client_id']) {
            error_log("Dashboard - Peso ou Altura nulos na tabela clients para client_id: " . $client_dashboard_data['client_id'] . ". Tentando buscar do histórico.");
            $stmt_latest_assessment = $pdo->prepare(
                "SELECT weight, height FROM history_assessments WHERE client_id = ? ORDER BY assessment_date DESC, id DESC LIMIT 1"
            );
            $stmt_latest_assessment->execute([$client_dashboard_data['client_id']]);
            $latest_assessment = $stmt_latest_assessment->fetch();

            if ($latest_assessment) {
                error_log("Dashboard - Encontrada avaliação no histórico: " . print_r($latest_assessment, true));
                // Apenas sobrepõe se o valor estiver vazio no registo principal
                if (empty($client_dashboard_data['weight']) && !empty($latest_assessment['weight'])) {
                    $client_dashboard_data['weight'] = $latest_assessment['weight'];
                }
                if (empty($client_dashboard_data['height']) && !empty($latest_assessment['height'])) {
                    $client_dashboard_data['height'] = $latest_assessment['height'];
                }
            }
        }

        // --- CORRIGIDO: Usar os dados do array do dashboard, que podem ter sido atualizados pelo fallback ---
        if (!empty($client_dashboard_data['height']) && $client_dashboard_data['height'] > 0 && !empty($client_dashboard_data['weight'])) {
            $bmi_height = (float)$client_dashboard_data['height'];
            $bmi_weight = (float)$client_dashboard_data['weight'];
            $bmi_value = round($bmi_weight / ($bmi_height * $bmi_height), 1);
            $client_dashboard_data['bmi'] = $bmi_value;

            if ($bmi_value < 18.5) $client_dashboard_data['bmi_category'] = "Abaixo do peso";
            elseif ($bmi_value < 25) $client_dashboard_data['bmi_category'] = "Peso normal";
            elseif ($bmi_value < 30) $client_dashboard_data['bmi_category'] = "Sobrepeso";
            else $client_dashboard_data['bmi_category'] = "Obesidade";
        }

        // Plano de treino ativo (depende de client_id)
        if ($client_dashboard_data['client_id']) {
            // CORRIGIDO: A tabela workout_plans não tem 'title'. Apenas verificamos a existência.
            $stmt_workout = $pdo->prepare("SELECT id FROM workout_plans WHERE client_id = ? AND is_active = 1 LIMIT 1");
            $stmt_workout->execute([$client_dashboard_data['client_id']]);
            if ($stmt_workout->fetch()) {
                // Se um plano ativo existe, usamos um texto genérico.
                $client_dashboard_data['active_workout_plan_title'] = 'Plano de Treino Ativo';
            }
        }

        // Plano alimentar ativo (depende de client_id)
        if ($client_dashboard_data['client_id']) {
            $stmt_diet = $pdo->prepare("SELECT title FROM diet_plans WHERE client_id = ? AND is_active = 1 ORDER BY created_at DESC LIMIT 1");
            $stmt_diet->execute([$client_dashboard_data['client_id']]);
            $diet_plan = $stmt_diet->fetch();
            if ($diet_plan) {
                // Se o título for nulo ou uma string vazia, usa o placeholder.
                $client_dashboard_data['active_diet_plan_title'] = !empty(trim($diet_plan['title'])) ? trim($diet_plan['title']) : 'Plano Alimentar Ativo';
            }
        }

        // Próxima aula (depende de user_id)
        $stmt_class = $pdo->prepare("
            SELECT c.id, c.name, c.schedule, c.duration
            FROM classes c
            JOIN class_participants cp ON c.id = cp.class_id
            WHERE cp.user_id = ? AND c.schedule >= NOW()
            ORDER BY c.schedule ASC
            LIMIT 1
        ");
        $stmt_class->execute([$user_id]);
        $next_class = $stmt_class->fetch();
        if ($next_class) {
            $client_dashboard_data['next_class_id'] = $next_class['id'];
            $client_dashboard_data['next_class_name'] = $next_class['name'];
            $client_dashboard_data['next_class_schedule'] = $next_class['schedule'];
            $client_dashboard_data['next_class_duration'] = $next_class['duration'];
        }

        // --- PONTO CRÍTICO 2: Buscar Objetivo Atual usando o client_id (clients.id) ---
        if ($client_dashboard_data['client_id']) { // Só tenta buscar se client_id foi encontrado
            $stmt_goal = $pdo->prepare("SELECT id, goal_description, target_date FROM client_goals WHERE client_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1");
            $stmt_goal->execute([$client_dashboard_data['client_id']]);
            $goal = $stmt_goal->fetch();
            if ($goal) {
                $client_dashboard_data['current_goal_description'] = $goal['goal_description'];
                $client_dashboard_data['current_goal_id'] = $goal['id'];
                $client_dashboard_data['current_goal_target_date'] = $goal['target_date'];
            }
        } else {
            error_log("Dashboard: client_id é nulo, não foi possível buscar objetivos para user_id: " . $user_id);
        }

        // --- Função para buscar/definir conteúdo diário (dicas/citações) ---
        // Esta função permanece a mesma, pois a lógica de seleção será feita abaixo
        if (!function_exists('get_daily_content')) {
            function get_daily_content($pdo_conn, $current_user_id, $content_type_enum, $db_table_name, $text_column_name, $author_column_name = null, $additional_where_clause = '') {
                $today_date_str = date('Y-m-d');
                // Defaults internos da função
                $default_text_tip = "Mantenha-se hidratado e ativo!";
                $default_text_quote = "Acredite em si e continue!";
                $default_text = ($content_type_enum === 'tip') ? $default_text_tip : $default_text_quote;
                $default_result = ['text' => $default_text, 'author' => null];

                try {
                    $log_check_sql = "
                        SELECT sc_log.content_id, main_table.`$text_column_name`" . ($author_column_name ? ", main_table.`$author_column_name`" : "") . "
                        FROM shown_content_log sc_log
                        JOIN `$db_table_name` main_table ON sc_log.content_id = main_table.id
                        WHERE sc_log.user_id = :user_id AND sc_log.content_type = :content_type AND sc_log.shown_date = :shown_date
                        LIMIT 1";
                    $stmt_log_check = $pdo_conn->prepare($log_check_sql);
                    $stmt_log_check->execute([':user_id' => $current_user_id, ':content_type' => $content_type_enum, ':shown_date' => $today_date_str]);
                    $logged_content_data = $stmt_log_check->fetch();

                    if ($logged_content_data) {
                        $result = ['text' => $logged_content_data[$text_column_name]];
                        if ($author_column_name && isset($logged_content_data[$author_column_name])) {
                            $result['author'] = $logged_content_data[$author_column_name];
                        }
                        return $result;
                    } else {
                        $days_to_avoid_recent = 7;
                        $date_to_avoid_from_str = date('Y-m-d', strtotime("-$days_to_avoid_recent days"));
                        $stmt_avoid_ids = $pdo_conn->prepare("SELECT GROUP_CONCAT(DISTINCT content_id) as ids_to_avoid FROM shown_content_log WHERE user_id = :user_id AND content_type = :content_type AND shown_date >= :date_from");
                        $stmt_avoid_ids->execute([':user_id' => $current_user_id, ':content_type' => $content_type_enum, ':date_from' => $date_to_avoid_from_str]);
                        $ids_to_avoid_list_str = ($stmt_avoid_ids->fetch()['ids_to_avoid']) ?? '';
                        $sql_random_content_query = "SELECT id, `$text_column_name`" . ($author_column_name ? ", `$author_column_name`" : "") . " FROM `$db_table_name` WHERE is_active = TRUE ";
                        if (!empty($additional_where_clause)) {
                            $sql_random_content_query .= " AND (" . $additional_where_clause . ")";
                        }
                        if (!empty($ids_to_avoid_list_str)) {
                            $sql_random_content_query .= " AND id NOT IN ($ids_to_avoid_list_str)";
                        }
                        $sql_random_content_query .= " ORDER BY RAND() LIMIT 1";
                        $stmt_random_content = $pdo_conn->prepare($sql_random_content_query);
                        $stmt_random_content->execute();
                        $new_content_data = $stmt_random_content->fetch();
                        if (!$new_content_data) {
                            $fallback_sql = "SELECT id, `$text_column_name`" . ($author_column_name ? ", `$author_column_name`" : "") . " FROM `$db_table_name` WHERE is_active = TRUE ";
                            if (!empty($additional_where_clause)) {
                                $fallback_sql .= " AND (" . $additional_where_clause . ")";
                            }
                            $fallback_sql .= " ORDER BY RAND() LIMIT 1";
                            $stmt_fallback_content = $pdo_conn->prepare($fallback_sql);
                            $stmt_fallback_content->execute();
                            $new_content_data = $stmt_fallback_content->fetch();
                        }
                        if ($new_content_data) {
                            $stmt_insert_log = $pdo_conn->prepare("INSERT INTO shown_content_log (user_id, content_type, content_id, shown_date) VALUES (:user_id, :content_type, :content_id, :shown_date) ON DUPLICATE KEY UPDATE content_id = :content_id_on_duplicate");
                            $stmt_insert_log->execute([':user_id' => $current_user_id, ':content_type' => $content_type_enum, ':content_id' => $new_content_data['id'], ':shown_date' => $today_date_str, ':content_id_on_duplicate' => $new_content_data['id']]);
                            $result = ['text' => $new_content_data[$text_column_name]];
                            if ($author_column_name && isset($new_content_data[$author_column_name])) {
                                $result['author'] = $new_content_data[$author_column_name];
                            }
                            return $result;
                        }
                    }
                } catch (PDOException $e) {
                    if (!(strpos($e->getMessage(), $db_table_name) !== false && strpos($e->getMessage(), 'exist') !== false) &&
                        !(strpos($e->getMessage(), 'shown_content_log') !== false && strpos($e->getMessage(), 'exist') !== false)) {
                        error_log("Erro get_daily_content ($content_type_enum para tabela $db_table_name): " . $e->getMessage());
                    }
                }
                return $default_result;
            }
        }

        // --- LÓGICA PARA LEMBRETE ÚNICO DIÁRIO ---
        $daily_reminder_text_final = $client_dashboard_data['daily_reminder_text']; // Usa o default geral inicial
        $daily_reminder_author_final = null;

        // 1. Tenta buscar uma dica
        $tip_data = get_daily_content($pdo, $user_id, 'tip', 'dashboard_reminders', 'reminder_text', null, "reminder_type = 'tip'");

        // Verifica se a dica retornada NÃO é o default interno da função get_daily_content para 'tip'
        if ($tip_data['text'] !== "Mantenha-se hidratado e ativo!") {
            $daily_reminder_text_final = $tip_data['text'];
        } else {
            // 2. Se a dica for default, tenta buscar uma citação
            $quote_data = get_daily_content($pdo, $user_id, 'quote', 'dashboard_reminders', 'reminder_text', 'author', "reminder_type = 'quote'");
            // Verifica se a citação retornada NÃO é o default interno da função get_daily_content para 'quote'
            if ($quote_data['text'] !== "Acredite em si e continue!") {
                $daily_reminder_text_final = $quote_data['text'];
                $daily_reminder_author_final = $quote_data['author'];
            }
            // Se ambas (dica e citação) retornarem seus defaults internos,
            // $daily_reminder_text_final manterá o valor de $client_dashboard_data['daily_reminder_text']
        }
        // Atribui ao array principal do dashboard
        $client_dashboard_data['daily_reminder_text'] = $daily_reminder_text_final;
        $client_dashboard_data['daily_reminder_author'] = $daily_reminder_author_final;
        // --- FIM DA LÓGICA PARA LEMBRETE ÚNICO DIÁRIO ---


        // Buscar conquistas (depende de client_id)
        if ($client_dashboard_data['client_id']) {
            $stmt_achievements = $pdo->prepare("SELECT a.name, a.icon, a.description FROM client_achievements ca JOIN achievements a ON ca.achievement_id = a.id WHERE ca.client_id = ? ORDER BY ca.earned_at DESC LIMIT 3");
            $stmt_achievements->execute([$client_dashboard_data['client_id']]);
            $client_dashboard_data['achievements'] = $stmt_achievements->fetchAll();
        }
        
        // Últimas avaliações (depende de client_id)
        if ($client_dashboard_data['client_id']) {
            try {
                $stmt_assessments_summary = $pdo->prepare("
                    SELECT weight, assessment_date
                    FROM history_assessments
                    WHERE client_id = ?
                    ORDER BY assessment_date DESC
                    LIMIT 2
                ");
                $stmt_assessments_summary->execute([$client_dashboard_data['client_id']]);
                $assessments_summary = $stmt_assessments_summary->fetchAll();

                if (count($assessments_summary) > 0) {
                    $client_dashboard_data['last_assessment_date'] = $assessments_summary[0]['assessment_date'];
                    if (count($assessments_summary) > 1) {
                        $client_dashboard_data['previous_weight'] = $assessments_summary[1]['weight'];
                    }
                }
            } catch (PDOException $e) {
                if (!(strpos($e->getMessage(), 'history_assessments') !== false && strpos($e->getMessage(), 'exist') !== false)) {
                    error_log("Erro ao buscar avaliações: " . $e->getMessage());
                }
            }
        }

        // Verificar humor (depende de user_id)
        $today_date_str_for_mood = date('Y-m-d');
        $stmt_mood_today = $pdo->prepare("SELECT mood_value FROM mood_logs WHERE user_id = ? AND assessment_date = ? LIMIT 1");
        $stmt_mood_today->execute([$user_id, $today_date_str_for_mood]);
        $mood_logged_today_result = $stmt_mood_today->fetch();
        if ($mood_logged_today_result) {
            $client_dashboard_data['mood_today_value'] = (int)$mood_logged_today_result['mood_value'];
        }

    } else {
        error_log("Dashboard: client_id não encontrado para user_id: " . $user_id . ". O cliente precisa de um registo na tabela 'clients'.");
    }
} catch (PDOException $e) {
    error_log("Erro fatal ao carregar dados do dashboard: " . $e->getMessage());
}

// Helper function to display stats or N/D consistently
function displayDashboardInfo($value, $unit = '', $decimals = 1, $na_text = 'N/D') {
    if (isset($value) && $value !== null && $value !== '') {
        if (is_numeric($value) && !empty($unit)) {
             return htmlspecialchars(number_format((float)$value, $decimals, ',', '.')) . $unit;

        }
        return htmlspecialchars($value);
    }
    return $na_text;
}

// Inclui a sidebar do cliente
include 'sidebar_client.php';
?>
<!-- Adicionando estilos para os cards brancos -->
<style>
    .dashboard-card, .highlight-card {
        background-color: #ffffff !important;
        color: #212529; /* Cor de texto padrão escura */
        border: 1px solid #e9ecef; /* Borda suave para definição */
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); /* Sombra suave opcional */
    }

    .dashboard-card h4, .dashboard-card h5,
    .highlight-card h4, .highlight-card h5 {
        color: #343a40; /* Cor mais escura para títulos */
    }

    .dashboard-card p, .dashboard-card li, .dashboard-card strong,
    .highlight-card p, .highlight-card li, .highlight-card strong {
        color: #212529; /* Garante que o texto principal seja escuro */
    }

    .dashboard-card .text-muted,
    .highlight-card .text-muted {
        color: #6c757d !important; /* Cor padrão do Bootstrap para texto muted */
    }

    .dashboard-card .text-white-50, /* Usado no autor do lembrete */
    .highlight-card .text-white-50 {
        color: #6c757d !important; /* Alterar para cor escura muted */
    }

    .dashboard-card .card-icon {
        color: var(--bs-primary); /* Cor primária para ícones genéricos do card */
    }
    .highlight-card .card-icon {
        color: var(--bs-primary); /* Também para highlight cards */
    }

    /* Ajuste para botões que eram btn-outline-light */
    .dashboard-card .btn-outline-secondary,
    .highlight-card .btn-outline-secondary {
        /* Se precisar de ajustes específicos para btn-outline-secondary no card branco */
    }
</style>
<!-- O conteúdo principal da página -->
<main class="main-content">
    <h2>Bem-vindo, <?= htmlspecialchars($client_name) ?>!</h2>

    <!-- Mensagem de sucesso do set_goal.php -->
    <?php if (isset($_SESSION['dashboard_message'])): ?>
        <div class="alert alert-<?= $_SESSION['dashboard_message_type'] ?? 'info' ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['dashboard_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php
            unset($_SESSION['dashboard_message']);
            unset($_SESSION['dashboard_message_type']);
        ?>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-xl-6 col-lg-12 mb-4">
            <div class="dashboard-card highlight-card">
                <h4><i class="fas fa-tachometer-alt card-icon"></i>Resumo Rápido</h4>
                <div class="row">
                    <div class="col-sm-6 mb-2">
                        <strong>Peso Atual:</strong> <?= displayDashboardInfo($client_dashboard_data['weight'], ' kg', 1) ?>
                    </div>
                    <div class="col-sm-6 mb-2">
                        <strong>Altura:</strong> <?= displayDashboardInfo($client_dashboard_data['height'], ' m', 2) ?>
                    </div>
                    <div class="col-sm-12 mb-2">
                        <strong>IMC:</strong> <?= displayDashboardInfo($client_dashboard_data['bmi'], '', 1) ?>
                        <?php if ($client_dashboard_data['bmi']): ?>
                            <small class="text-muted">(<?= htmlspecialchars($client_dashboard_data['bmi_category']) ?>)</small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mt-2 text-center">
                     <?php if ($client_dashboard_data['client_id']): ?>
                        <a href="assessment_history.php?client_id=<?= $client_dashboard_data['client_id'] ?>" class="btn btn-outline-secondary btn-sm">Ver Histórico de Avaliações</a>
                     <?php else: ?>
                         <span class="text-muted small">Histórico de Avaliações N/D (ID Cliente em falta)</span>
                     <?php endif; ?>
                </div>
                <a href="client_statistics.php" class="btn btn-outline-secondary btn-sm mt-2">Ver Estatísticas Completas</a>
            </div>
        </div>

        <div class="col-xl-6 col-lg-12 mb-4">
            <div class="dashboard-card highlight-card">
                <h4><i class="fas fa-calendar-day card-icon"></i>Próximas Atividades</h4>
                <ul class="list-unstyled">
                    <?php if ($client_dashboard_data['next_class_name']): ?>
                        <li class="mb-2">
                            <i class="fas fa-calendar-check me-2" style="color: #90EE90;"></i>
                            <strong>Próxima Aula:</strong> <?= htmlspecialchars($client_dashboard_data['next_class_name']) ?>
                            em <?= htmlspecialchars(date('d/m/Y \à\s H:i', strtotime($client_dashboard_data['next_class_schedule']))) ?>.
                            <a href="classes.php#class-<?= $client_dashboard_data['next_class_id'] ?>" class="btn btn-outline-secondary btn-sm ms-1 py-0 px-1" title="Ver Detalhes"><i class="fas fa-info-circle"></i></a>
                            <?php
                                $gcal_text = urlencode($client_dashboard_data['next_class_name']);
                                $gcal_start_date = gmdate("Ymd\THis\Z", strtotime($client_dashboard_data['next_class_schedule']));
                                $gcal_end_date = $client_dashboard_data['next_class_duration']
                                                 ? gmdate("Ymd\THis\Z", strtotime($client_dashboard_data['next_class_schedule'] . " +".$client_dashboard_data['next_class_duration']." minutes"))
                                                 : $gcal_start_date;
                                $gcal_details = urlencode("Aula agendada através do MyWellness");
                                $gcal_location = urlencode("Ginásio MyWellness");
                            ?>
                            <a href="https://calendar.google.com/calendar/render?action=TEMPLATE&text=<?= $gcal_text ?>&dates=<?= $gcal_start_date ?>/<?= $gcal_end_date ?>&details=<?= $gcal_details ?>&location=<?= $gcal_location ?>" target="_blank" class="btn btn-outline-secondary btn-sm ms-1 py-0 px-1" title="Adicionar ao Google Calendar"><i class="fab fa-google"></i> Add</a>
                        </li>
                    <?php else: ?>
                        <li class="mb-2" style="color: #D3D3D3;"><i class="fas fa-calendar-times me-2"></i>Nenhuma aula agendada. <a href="classes.php" style="color: #ADD8E6;">Ver aulas</a></li>
                    <?php endif; ?>
                    <li class="mb-2"><i class="fas fa-dumbbell me-2" style="color: #ADD8E6;"></i><strong>Plano de Treino:</strong>  <a href="workout_hub.php" class="btn btn-outline-secondary btn-sm ms-2 py-0 px-1">Aceder</a></li>
                    <li class="mb-1"><i class="fas fa-utensils me-2" style="color: #B0E0E6;"></i><strong>Plano Alimentar:</strong>  <a href="nutrition_hub.php" class="btn btn-outline-secondary btn-sm ms-2 py-0 px-1">Aceder</a></li>
                </ul>
                <!-- MODIFICADO: Título e conteúdo do lembrete -->
                <h5 class="mt-3"><i class="fas fa-bell card-icon"></i>Lembrete</h5>
                <p class="small fst-italic">
                    <i class="fas fa-lightbulb me-2" style="color: #FFD700;"></i> <!-- Ícone genérico para o lembrete -->
                    "<?= htmlspecialchars($client_dashboard_data['daily_reminder_text']) ?>"
                    <?php if (!empty($client_dashboard_data['daily_reminder_author'])): ?>
                        <span class="text-muted">- <?= htmlspecialchars($client_dashboard_data['daily_reminder_author']) ?></span>
                    <?php endif; ?>
                </p>
                <!-- FIM DA MODIFICAÇÃO -->
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="dashboard-card">
                <h4><i class="fas fa-bullseye card-icon text-success"></i>Meu Objetivo Atual</h4>
                <div id="current-goal-content">
                <?php
                if ($client_dashboard_data['current_goal_id']): // Simplificado: se current_goal_id é truthy, um objetivo ativo foi carregado
                ?>
                    <p id="goal-description-text"><?= htmlspecialchars($client_dashboard_data['current_goal_description']) ?></p>
                    <?php if ($client_dashboard_data['current_goal_target_date']): ?>
                        <p id="goal-target-date-text" class="small text-muted">Data Alvo: <?= htmlspecialchars(date('d/m/Y', strtotime($client_dashboard_data['current_goal_target_date']))) ?></p>
                    <?php endif; ?>
                    <button id="complete-goal-btn" class="btn btn-success btn-sm mt-2" data-goal-id="<?= $client_dashboard_data['current_goal_id'] ?>">
                        <i class="fas fa-check-circle me-1"></i>Marcar como Concluído
                    </button>
                <?php else: ?>
                    <p id="goal-default-text"><?= htmlspecialchars($client_dashboard_data['current_goal_description']) ?></p>
                     <?php if (!isset($client_dashboard_data['client_id']) || !$client_dashboard_data['client_id']): ?>
                        <p class="small text-warning fst-italic">Não foi possível carregar objetivos (ID de cliente não encontrado). Verifique se o seu registo de cliente está completo ou contacte o suporte.</p>
                    <?php elseif ($client_dashboard_data['current_goal_description'] === 'Defina um novo objetivo!'): ?>
                        <!-- Nenhuma mensagem adicional aqui, pois "Defina um novo objetivo!" já é claro -->
                    <?php endif; ?>
                <?php endif; ?>
                </div>
                <div id="goal-card-feedback" class="mt-2 small"></div>
                <div class="text-center mt-2">
                     <?php if (isset($client_dashboard_data['client_id']) && $client_dashboard_data['client_id']): ?>
                        <a href="goal_history.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-history me-1"></i>Histórico</a>
                        <a href="set_goal.php" class="btn btn-info btn-sm ms-2">
                            <i class="fas fa-plus-circle me-1"></i>Novo Objetivo
                        </a>
                     <?php else: ?>
                         <span class="text-muted small d-block mb-1">Gerir Objetivos N/D (ID Cliente em falta)</span>
                         <a href="set_goal.php" class="btn btn-info btn-sm ms-2">
                            <i class="fas fa-plus-circle me-1"></i>Definir Objetivo
                        </a>
                     <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 mb-4">
            <div class="dashboard-card">
                <h4><i class="fas fa-medal card-icon text-warning"></i>Minhas Conquistas</h4>
                <?php if (!empty($client_dashboard_data['achievements'])): ?>
                    <ul>
                        <?php foreach($client_dashboard_data['achievements'] as $achievement_item): ?>
                            <li title="<?= htmlspecialchars($achievement_item['description']) ?>"><i class="<?= htmlspecialchars($achievement_item['icon'] ?? 'fas fa-medal') ?> me-1 text-warning"></i> <?= htmlspecialchars($achievement_item['name']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>Ainda nenhuma conquista desbloqueada. Continue assim!</p>
                <?php endif; ?>
                <div class="text-center mt-2">
                    <a href="all_achievements.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-trophy me-1"></i>Ver Todas as Conquistas</a>
                </div>
            </div>
        </div>

        <!-- REMOVIDO: Card Dica do Dia / Motivação -->
        <!--
        <div class="col-lg-4 col-md-12 mb-4">
            <div class="dashboard-card">
                <h4><i class="fas fa-lightbulb card-icon text-warning"></i>Dica do Dia</h4>
                <p class="fst-italic">"<?= htmlspecialchars($client_dashboard_data['daily_tip']) ?>"</p>
                <h5 class="mt-3"><i class="fas fa-quote-left card-icon text-primary"></i>Motivação</h5>
                <p>"<?= htmlspecialchars($client_dashboard_data['motivational_quote']) ?>"</p>
                 <?php if (isset($client_dashboard_data['motivational_quote_author']) && !empty($client_dashboard_data['motivational_quote_author'])): ?>
                    <p class="text-end small text-white-50">- <?= htmlspecialchars($client_dashboard_data['motivational_quote_author']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        -->
        <!-- FIM DA REMOÇÃO -->

        <!-- Este card foi movido para a posição do card de Dica/Motivação para manter o layout de 3 colunas -->
        <div class="col-lg-4 col-md-12 mb-4"> <!-- Era col-lg-4 col-md-6, ajustado para ocupar o espaço -->
            <div class="dashboard-card">
                <h4><i class="fas fa-tasks card-icon text-info"></i>Desafio da Semana</h4>
                <p><strong>Objetivo:</strong> Fazer 3 treinos e seguir o plano alimentar por 5 dias.</p>
                <div class="progress mb-2" style="height: 20px;">
                    <div class="progress-bar bg-info" role="progressbar" style="width: 60%;" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100">60%</div>
                </div>
                <p class="small text-muted text-center">Continue o bom trabalho!</p>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- O card "Desafio da Semana" foi movido para cima, então esta linha agora tem 2 cards -->
        <div class="col-lg-6 col-md-6 mb-4"> <!-- Ajustado para col-lg-6 para melhor distribuição -->
            <div class="dashboard-card">
                <h4><i class="far fa-smile-beam card-icon text-primary"></i>Como te sentes hoje?</h4>
                <p class="small">Avalie o seu dia ou o seu último treino:</p>
                <div class="text-center mood-tracker-buttons">
                    <button class="btn btn-lg btn-link p-1" title="Péssimo" data-mood="1">😞</button>
                    <button class="btn btn-lg btn-link p-1" title="Mau" data-mood="2">🙁</button>
                    <button class="btn btn-lg btn-link p-1" title="Neutro" data-mood="3">😐</button>
                    <button class="btn btn-lg btn-link p-1" title="Bom" data-mood="4">😊</button>
                    <button class="btn btn-lg btn-link p-1" title="Ótimo" data-mood="5">😄</button>
                </div>
                <div id="mood-feedback" class="small text-center mt-2" style="min-height: 20px;"></div>
                <div class="text-center mt-2">
                    <a href="mood_history.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-chart-line me-1"></i>Ver Histórico de Humor</a>
                </div>
            </div>
        </div>

         <div class="col-lg-6 col-md-12 mb-4"> <!-- Ajustado para col-lg-6 -->
            <div class="dashboard-card">
                <h4><i class="fas fa-history card-icon text-secondary"></i>Histórico Recente</h4>
                 <?php if ($client_dashboard_data['last_assessment_date']): ?>
                    <p class="small text-muted">Última avaliação: <?= htmlspecialchars(date('d/m/Y', strtotime($client_dashboard_data['last_assessment_date']))) ?></p>
                <?php else: ?>
                     <p class="small fst-italic">Nenhuma avaliação registada ainda.</p>
                <?php endif; ?>
                <div class="mb-2"><strong>Peso Atual:</strong> <?= htmlspecialchars($client_dashboard_data['weight'] ?? 'N/D') ?> kg</div>
                <?php if ($client_dashboard_data['previous_weight'] !== null && $client_dashboard_data['weight'] !== null): ?>
                    <div class="mb-2"><strong>Peso Anterior:</strong> <?= htmlspecialchars($client_dashboard_data['previous_weight']) ?> kg</div>
                     <?php
                        $weight_diff = (float)$client_dashboard_data['weight'] - (float)$client_dashboard_data['previous_weight'];
                        $diff_text = number_format(abs($weight_diff), 1, ',', '.') . ' kg';
                        if ($weight_diff < 0) {
                            echo '<p class="text-success">Perdeu ' . $diff_text . ' <i class="fas fa-arrow-down"></i></p>';
                        } elseif ($weight_diff > 0) {
                            echo '<p class="text-danger">Ganhou ' . $diff_text . ' <i class="fas fa-arrow-up"></i></p>';
                        } else {
                            echo '<p class="text-muted">Manteve o peso <i class="fas fa-equals"></i></p>';
                        }
                     ?>
                <?php elseif ($client_dashboard_data['weight'] !== null): ?>
                     <p class="fst-italic">Necessita de mais uma avaliação para ver a evolução do peso.</p>
                <?php endif; ?>
                 <a href="evolution.php" class="btn btn-outline-secondary btn-sm mt-2"><i class="fas fa-chart-line me-1"></i> Ver Evolução Completa</a>
            </div>
        </div>
    </div>


</main> <!-- Fim .main-content -->

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

            // --- VALIDAÇÃO DA POSIÇÃO (NOVO E MELHORADO) ---
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;
            const iconWidth = profileIconContainer.offsetWidth;
            const iconHeight = profileIconContainer.offsetHeight;

            // Extrai os valores numéricos de left e top (ex: "100px" -> 100)
            const left = parseFloat(pos.left);
            const top = parseFloat(pos.top);

            // Verifica se a posição é válida e está dentro da tela
            if (!isNaN(left) && !isNaN(top) && left >= 0 && top >= 0 && left <= (viewportWidth - iconWidth) && top <= (viewportHeight - iconHeight)) {
                // Posição válida, aplica
                profileIconContainer.style.position = 'fixed';
                profileIconContainer.style.left = pos.left;
                profileIconContainer.style.top = pos.top;
                profileIconContainer.style.right = 'auto';
                profileIconContainer.style.bottom = 'auto';
            } else {
                // Posição inválida (fora da tela), remove do localStorage para corrigir automaticamente
                console.warn("Posição do ícone de perfil guardada está fora da tela. A redefinir para o padrão.");
                localStorage.removeItem('profileIconPosition');
            }
            // --- FIM DA VALIDAÇÃO ---
        } catch (e) {
            console.error("Falha ao processar a posição guardada do ícone de perfil:", e);
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

    // --- LÓGICA PARA O BOTÃO DE REDEFINIR POSIÇÃO (NOVO) ---
    const resetButton = document.getElementById('reset-profile-icon-position');
    if (resetButton) {
        resetButton.addEventListener('click', function(e) {
            e.preventDefault();
            // Remove a posição guardada do localStorage
            localStorage.removeItem('profileIconPosition');
            // Informa o utilizador e recarrega a página para aplicar a posição padrão
            alert('A posição do ícone foi redefinida. A página será recarregada.');
            window.location.reload();
        });
    }

});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const completeGoalButton = document.getElementById('complete-goal-btn');
    const currentGoalContentDiv = document.getElementById('current-goal-content');
    const goalCardFeedbackDiv = document.getElementById('goal-card-feedback');

    if (completeGoalButton) {
        completeGoalButton.addEventListener('click', function() {
            const goalId = this.dataset.goalId;
            if (!goalId) {
                if(goalCardFeedbackDiv) goalCardFeedbackDiv.innerHTML = '<span class="text-danger">ID do objetivo não encontrado.</span>';
                return;
            }

            if (!confirm('Tem a certeza que deseja marcar este objetivo como concluído?')) {
                return;
            }

            if(goalCardFeedbackDiv) goalCardFeedbackDiv.innerHTML = '<span class="text-info">A processar...</span>';
            this.disabled = true;

            const formData = new FormData();
            formData.append('goal_id', goalId);
            formData.append('status', 'completed');

            fetch('update_goal_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    if(goalCardFeedbackDiv) {
                        goalCardFeedbackDiv.innerHTML = '<span class="text-success">Objetivo marcado como concluído!</span>';
                    }
                    if(currentGoalContentDiv) {
                        currentGoalContentDiv.innerHTML = '<p id="goal-default-text">Defina um novo objetivo!</p>';
                    }
                    // Opcional: Recarregar a página ou atualizar dinamicamente outras partes se necessário
                    // location.reload(); // Para uma atualização simples
                } else {
                    if(goalCardFeedbackDiv) goalCardFeedbackDiv.innerHTML = `<span class="text-danger">Erro: ${data.message || 'Não foi possível atualizar o objetivo.'}</span>`;
                    completeGoalButton.disabled = false;
                }
            })
            .catch(error => {
                console.error('Erro ao marcar objetivo como concluído:', error);
                if(goalCardFeedbackDiv) goalCardFeedbackDiv.innerHTML = '<span class="text-danger">Erro de comunicação. Tente novamente.</span>';
                completeGoalButton.disabled = false;
            });
        });
    }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const moodButtons = document.querySelectorAll('.mood-tracker-buttons button');
    const moodFeedbackDiv = document.getElementById('mood-feedback');
    const initialMoodToday = <?= json_encode($client_dashboard_data['mood_today_value']) ?>;

    function setMoodUI(selectedMoodValue, message, isInitialLoad = false) {
        if (moodFeedbackDiv) {
            moodFeedbackDiv.textContent = message;
            moodFeedbackDiv.style.color = 'var(--bs-success)';
        }
        const numericSelectedMood = Number(selectedMoodValue);
        moodButtons.forEach(btn => {
            btn.disabled = true;
            btn.classList.remove('btn-link', 'btn-success', 'btn-outline-secondary', 'active', 'mood-selected');
            if (parseInt(btn.dataset.mood) === numericSelectedMood) {
                btn.classList.add('btn-success', 'mood-selected');
            } else {
                btn.classList.add('btn-outline-secondary');
            }
        });
    }

    if (initialMoodToday !== null) {
        let moodEmojiForMessage = '';
        moodButtons.forEach(btn => {
            if (parseInt(btn.dataset.mood) === Number(initialMoodToday)) {
                moodEmojiForMessage = btn.textContent;
            }
        });
        setMoodUI(initialMoodToday, `Humor de hoje (${moodEmojiForMessage}) já registado. Obrigado!`, true);
    } else {
        moodButtons.forEach(button => {
            button.disabled = false;
            button.classList.remove('btn-success', 'btn-outline-secondary', 'active', 'mood-selected');
            button.classList.add('btn-link');
            button.addEventListener('click', function() {
                const moodValue = parseInt(this.dataset.mood);
                const clickedButtonTextContent = this.textContent;
                const todayDate = new Date().toISOString().slice(0,10);
                moodButtons.forEach(btn => {
                    btn.disabled = true;
                    btn.classList.remove('btn-link', 'active');
                    if (btn === button) {
                        btn.classList.add('btn-success', 'mood-selected');
                    } else {
                        btn.classList.add('btn-outline-secondary');
                    }
                });
                if(moodFeedbackDiv) moodFeedbackDiv.textContent = 'A guardar...';
                fetch('save_mood.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded',},
                    body: `mood_value=${encodeURIComponent(moodValue)}&assessment_date=${encodeURIComponent(todayDate)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        if(moodFeedbackDiv) {
                            moodFeedbackDiv.textContent = `Obrigado pelo seu feedback (${clickedButtonTextContent})!`;
                            moodFeedbackDiv.style.color = 'var(--bs-success)';
                        }
                    } else {
                        if(moodFeedbackDiv) {
                            moodFeedbackDiv.textContent = `Erro: ${data.message || 'Não foi possível guardar o seu humor.'}`;
                            moodFeedbackDiv.style.color = 'var(--bs-danger)';
                        }
                        moodButtons.forEach(btn => {
                            btn.disabled = false;
                            btn.classList.remove('btn-success', 'btn-outline-secondary', 'mood-selected');
                            btn.classList.add('btn-link');
                        });
                    }
                })
                .catch(error => {
                    if(moodFeedbackDiv) { moodFeedbackDiv.textContent = 'Erro de comunicação ao guardar o humor.'; moodFeedbackDiv.style.color = 'var(--bs-danger)';}
                    moodButtons.forEach(btn => {
                        btn.disabled = false;
                        btn.classList.remove('btn-success', 'btn-outline-secondary', 'mood-selected');
                        btn.classList.add('btn-link');
                    });
                });
            });
        });
    }
});
</script>
<?php include 'footer.php'; ?>
