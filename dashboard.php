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
// error_log("Dashboard - Iniciada para User ID (users.id): " . $user_id);


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
    'daily_tip' => "Lembre-se de se hidratar bem e manter uma postura correta durante os exercícios!",
    'motivational_quote' => "O esforço de hoje é o resultado de amanhã.",
    'achievements' => [],
    'mood_today_value' => null
];

try {
    // --- PONTO CRÍTICO 1: Obter client_id (clients.id) a partir do user_id (users.id) da sessão ---
    $stmt_client_info = $pdo->prepare("SELECT id, weight, height FROM clients WHERE user_id = ?");
    $stmt_client_info->execute([$user_id]);
    $client_info = $stmt_client_info->fetch();
    // error_log("Dashboard - Resultado da busca por client_info: " . print_r($client_info, true));

    if ($client_info) {
        $client_dashboard_data['client_id'] = $client_info['id']; // Este é o ID da tabela 'clients'
        // error_log("Dashboard - client_id (clients.id) definido como: " . $client_dashboard_data['client_id']);
        $client_dashboard_data['weight'] = $client_info['weight'];
        $client_dashboard_data['height'] = $client_info['height'];

        if (!empty($client_info['height']) && $client_info['height'] > 0 && !empty($client_info['weight'])) {
            $bmi_height = (float)$client_info['height'];
            $bmi_weight = (float)$client_info['weight'];
            $bmi_value = round($bmi_weight / ($bmi_height * $bmi_height), 1);
            $client_dashboard_data['bmi'] = $bmi_value;

            if ($bmi_value < 18.5) $client_dashboard_data['bmi_category'] = "Abaixo do peso";
            elseif ($bmi_value < 25) $client_dashboard_data['bmi_category'] = "Peso normal";
            elseif ($bmi_value < 30) $client_dashboard_data['bmi_category'] = "Sobrepeso";
            else $client_dashboard_data['bmi_category'] = "Obesidade";
        }

        // Plano de treino ativo (depende de client_id)
        if ($client_dashboard_data['client_id']) {
            $stmt_workout = $pdo->prepare("SELECT title FROM workout_plans WHERE client_id = ? AND is_active = 1 ORDER BY created_at DESC LIMIT 1");
            $stmt_workout->execute([$client_dashboard_data['client_id']]);
            $workout_plan = $stmt_workout->fetch();
            if ($workout_plan) {
                $client_dashboard_data['active_workout_plan_title'] = $workout_plan['title'] ?? 'Plano de Treino Ativo';
            }
        }

        // Plano alimentar ativo (depende de client_id)
        if ($client_dashboard_data['client_id']) {
            $stmt_diet = $pdo->prepare("SELECT title FROM diet_plans WHERE client_id = ? AND is_active = 1 ORDER BY created_at DESC LIMIT 1");
            $stmt_diet->execute([$client_dashboard_data['client_id']]);
            $diet_plan = $stmt_diet->fetch();
            if ($diet_plan) {
                $client_dashboard_data['active_diet_plan_title'] = $diet_plan['title'] ?? 'Plano Alimentar Ativo';
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
            // error_log("Dashboard - Tentando buscar objetivo para client_id (clients.id): " . $client_dashboard_data['client_id']);
            try {
                $stmt_goal = $pdo->prepare("SELECT id, goal_description, target_date FROM client_goals WHERE client_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1");
                $stmt_goal->execute([$client_dashboard_data['client_id']]);
                $goal = $stmt_goal->fetch();
                // error_log("Dashboard - Resultado da busca do objetivo (goal): " . print_r($goal, true));
                if ($goal) {
                    // Se um objetivo ativo for encontrado, atualiza os dados para exibição
                    $client_dashboard_data['current_goal_description'] = $goal['goal_description'];
                    $client_dashboard_data['current_goal_id'] = $goal['id']; // client_goals.id
                    $client_dashboard_data['current_goal_target_date'] = $goal['target_date'];
                    // error_log("Dashboard - Objetivo encontrado: ID " . $goal['id'] . " Desc: " . $goal['goal_description']);
                } else {
                    // error_log("Dashboard - NENHUM objetivo ativo encontrado para client_id: " . $client_dashboard_data['client_id']);
                }
            } catch (PDOException $e) {
                if (!(strpos($e->getMessage(), 'client_goals') !== false && strpos($e->getMessage(), 'exist') !== false)) {
                    error_log("Erro ao buscar objetivo: " . $e->getMessage());
                }
            }
        } else {
            // Se client_id não foi encontrado no PONTO CRÍTICO 1, esta mensagem de log será útil.
            error_log("Dashboard: client_id é nulo, não foi possível buscar objetivos para user_id: " . $user_id);
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

        // Função para buscar/definir conteúdo diário (depende de user_id)
        if (!function_exists('get_daily_content')) {
            function get_daily_content($pdo_conn, $current_user_id, $content_type_enum, $db_table_name, $text_column_name) {
                $today_date_str = date('Y-m-d');
                $default_text = ($content_type_enum === 'tip') ? "Mantenha-se hidratado e ativo!" : "Acredite em si e continue!";
                try {
                    $stmt_log_check = $pdo_conn->prepare("
                        SELECT sc_log.content_id, main_table.$text_column_name
                        FROM shown_content_log sc_log
                        JOIN `$db_table_name` main_table ON sc_log.content_id = main_table.id
                        WHERE sc_log.user_id = :user_id AND sc_log.content_type = :content_type AND sc_log.shown_date = :shown_date
                        LIMIT 1");
                    $stmt_log_check->execute([':user_id' => $current_user_id, ':content_type' => $content_type_enum, ':shown_date' => $today_date_str]);
                    $logged_content_data = $stmt_log_check->fetch();
                    if ($logged_content_data) return $logged_content_data[$text_column_name];
                    else {
                        $days_to_avoid_recent = 7; $date_to_avoid_from_str = date('Y-m-d', strtotime("-$days_to_avoid_recent days"));
                        $stmt_avoid_ids = $pdo_conn->prepare("SELECT GROUP_CONCAT(DISTINCT content_id) as ids_to_avoid FROM shown_content_log WHERE user_id = :user_id AND content_type = :content_type AND shown_date >= :date_from");
                        $stmt_avoid_ids->execute([':user_id' => $current_user_id, ':content_type' => $content_type_enum, ':date_from' => $date_to_avoid_from_str]);
                        $ids_to_avoid_list_str = ($stmt_avoid_ids->fetch()['ids_to_avoid']) ?? '';
                        $sql_random_content_query = "SELECT id, `$text_column_name` FROM `$db_table_name`" . (!empty($ids_to_avoid_list_str) ? " WHERE id NOT IN ($ids_to_avoid_list_str)" : "") . " ORDER BY RAND() LIMIT 1";
                        $stmt_random_content = $pdo_conn->prepare($sql_random_content_query);
                        $stmt_random_content->execute();
                        $new_content_data = $stmt_random_content->fetch();
                        if (!$new_content_data) {
                            $stmt_fallback_content = $pdo_conn->prepare("SELECT id, `$text_column_name` FROM `$db_table_name` ORDER BY RAND() LIMIT 1");
                            $stmt_fallback_content->execute(); $new_content_data = $stmt_fallback_content->fetch();
                        }
                        if ($new_content_data) {
                            $stmt_insert_log = $pdo_conn->prepare("INSERT INTO shown_content_log (user_id, content_type, content_id, shown_date) VALUES (:user_id, :content_type, :content_id, :shown_date) ON DUPLICATE KEY UPDATE content_id = :content_id_on_duplicate");
                            $stmt_insert_log->execute([':user_id' => $current_user_id, ':content_type' => $content_type_enum, ':content_id' => $new_content_data['id'], ':shown_date' => $today_date_str, ':content_id_on_duplicate' => $new_content_data['id']]);
                            return $new_content_data[$text_column_name];
                        }
                    }
                } catch (PDOException $e) { error_log("Erro get_daily_content ($content_type_enum): " . $e->getMessage()); }
                return $default_text;
            }
        }
        $client_dashboard_data['daily_tip'] = get_daily_content($pdo, $user_id, 'tip', 'daily_tips', 'tip_text');
        $client_dashboard_data['motivational_quote'] = get_daily_content($pdo, $user_id, 'quote', 'motivational_quotes', 'quote_text');

        // Buscar conquistas (depende de client_id)
        if ($client_dashboard_data['client_id']) {
            $stmt_achievements = $pdo->prepare("SELECT a.name, a.icon, a.description FROM client_achievements ca JOIN achievements a ON ca.achievement_id = a.id WHERE ca.client_id = ? ORDER BY ca.earned_at DESC LIMIT 3");
            $stmt_achievements->execute([$client_dashboard_data['client_id']]);
            $client_dashboard_data['achievements'] = $stmt_achievements->fetchAll();
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
        // Se $client_info for false, significa que não há entrada na tabela 'clients' para este 'user_id'.
        // O $client_dashboard_data['client_id'] permanecerá null.
        error_log("Dashboard: client_id não encontrado para user_id: " . $user_id . ". O cliente precisa de um registo na tabela 'clients'.");
        // O card de objetivos mostrará a mensagem padrão "Defina um novo objetivo!" e a mensagem de erro adicional.
    }
} catch (PDOException $e) {
    error_log("Erro fatal ao carregar dados do dashboard: " . $e->getMessage());
}

// Inclui a sidebar do cliente
include 'sidebar_client.php';
?>

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
                        <strong>Peso Atual:</strong> <?= htmlspecialchars($client_dashboard_data['weight'] ?? 'N/D') ?> kg
                    </div>
                    <div class="col-sm-6 mb-2">
                        <strong>Altura:</strong> <?= htmlspecialchars($client_dashboard_data['height'] ?? 'N/D') ?> m
                    </div>
                    <div class="col-sm-12 mb-2">
                        <strong>IMC:</strong> <?= htmlspecialchars($client_dashboard_data['bmi'] ?? 'N/D') ?>
                        <?php if ($client_dashboard_data['bmi']): ?>
                            <small class="text-muted">(<?= htmlspecialchars($client_dashboard_data['bmi_category']) ?>)</small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mt-2 text-center">
                     <?php if ($client_dashboard_data['client_id']): ?>
                        <a href="assessment_history.php?client_id=<?= $client_dashboard_data['client_id'] ?>" class="btn btn-outline-light btn-sm">Ver Histórico de Avaliações</a>
                     <?php else: ?>
                         <span class="text-muted small">Histórico de Avaliações N/D (ID Cliente em falta)</span>
                     <?php endif; ?>
                </div>
                <a href="client_statistics.php" class="btn btn-outline-light btn-sm mt-2">Ver Estatísticas Completas</a>
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
                            <a href="classes.php#class-<?= $client_dashboard_data['next_class_id'] ?>" class="btn btn-outline-light btn-sm ms-1 py-0 px-1" title="Ver Detalhes"><i class="fas fa-info-circle"></i></a>
                            <?php
                                $gcal_text = urlencode($client_dashboard_data['next_class_name']);
                                $gcal_start_date = gmdate("Ymd\THis\Z", strtotime($client_dashboard_data['next_class_schedule']));
                                $gcal_end_date = $client_dashboard_data['next_class_duration']
                                                 ? gmdate("Ymd\THis\Z", strtotime($client_dashboard_data['next_class_schedule'] . " +".$client_dashboard_data['next_class_duration']." minutes"))
                                                 : $gcal_start_date;
                                $gcal_details = urlencode("Aula agendada através do MyWellness");
                                $gcal_location = urlencode("Ginásio MyWellness");
                            ?>
                            <a href="https://calendar.google.com/calendar/render?action=TEMPLATE&text=<?= $gcal_text ?>&dates=<?= $gcal_start_date ?>/<?= $gcal_end_date ?>&details=<?= $gcal_details ?>&location=<?= $gcal_location ?>" target="_blank" class="btn btn-outline-light btn-sm ms-1 py-0 px-1" title="Adicionar ao Google Calendar"><i class="fab fa-google"></i> Add</a>
                        </li>
                    <?php else: ?>
                        <li class="mb-2" style="color: #D3D3D3;"><i class="fas fa-calendar-times me-2"></i>Nenhuma aula agendada. <a href="classes.php" style="color: #ADD8E6;">Ver aulas</a></li>
                    <?php endif; ?>
                    <li class="mb-2"><i class="fas fa-dumbbell me-2" style="color: #ADD8E6;"></i><strong>Plano de Treino:</strong> <?= htmlspecialchars($client_dashboard_data['active_workout_plan_title']) ?>. <a href="workout_hub.php" class="btn btn-outline-light btn-sm ms-2 py-0 px-1">Aceder</a></li>
                    <li class="mb-1"><i class="fas fa-utensils me-2" style="color: #B0E0E6;"></i><strong>Plano Alimentar:</strong> <?= htmlspecialchars($client_dashboard_data['active_diet_plan_title']) ?>. <a href="nutrition_hub.php" class="btn btn-outline-light btn-sm ms-2 py-0 px-1">Aceder</a></li>
                </ul>
                <h5 class="mt-3"><i class="fas fa-bell card-icon"></i>Lembretes</h5>
                <ul class="list-unstyled small">
                    <li><i class="fas fa-tint me-2" style="color: #87CEFA;"></i>Não te esqueças de beber água hoje!</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="dashboard-card">
                <h4><i class="fas fa-bullseye card-icon text-success"></i>Meu Objetivo Atual</h4>
                <div id="current-goal-content">
                <?php
                // --- PONTO CRÍTICO 3: Exibição do Objetivo ---
                // Verifica se client_id foi obtido E se um objetivo ativo foi encontrado (current_goal_id não é null)
                if (isset($client_dashboard_data['client_id']) && $client_dashboard_data['client_id'] && isset($client_dashboard_data['current_goal_id']) && $client_dashboard_data['current_goal_id']):
                ?>
                    <p id="goal-description-text"><?= htmlspecialchars($client_dashboard_data['current_goal_description']) ?></p>
                    <?php if ($client_dashboard_data['current_goal_target_date']): ?>
                        <p id="goal-target-date-text" class="small text-muted">Data Alvo: <?= htmlspecialchars(date('d/m/Y', strtotime($client_dashboard_data['current_goal_target_date']))) ?></p>
                    <?php endif; ?>
                    <button id="complete-goal-btn" class="btn btn-success btn-sm mt-2" data-goal-id="<?= $client_dashboard_data['current_goal_id'] ?>">
                        <i class="fas fa-check-circle me-1"></i>Marcar como Concluído
                    </button>
                <?php else: ?>
                    <!-- Mostra a mensagem padrão se não houver client_id ou objetivo ativo -->
                    <p id="goal-default-text"><?= htmlspecialchars($client_dashboard_data['current_goal_description']) // Mostra "Defina um novo objetivo!" ?></p>
                     <?php if (!isset($client_dashboard_data['client_id']) || !$client_dashboard_data['client_id']): ?>
                        <p class="small text-warning fst-italic">Não foi possível carregar objetivos (ID de cliente em falta). Verifique se o seu registo de cliente está completo.</p>
                    <?php endif; ?>
                <?php endif; ?>
                </div>
                <div id="goal-card-feedback" class="mt-2 small"></div>
                <div class="text-center mt-2">
                     <?php if (isset($client_dashboard_data['client_id']) && $client_dashboard_data['client_id']): ?>
                        <a href="goal_history.php" class="btn btn-outline-light btn-sm"><i class="fas fa-history me-1"></i>Histórico</a>
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
                    <a href="all_achievements.php" class="btn btn-outline-light btn-sm"><i class="fas fa-trophy me-1"></i>Ver Todas as Conquistas</a>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-12 mb-4">
            <div class="dashboard-card">
                <h4><i class="fas fa-lightbulb card-icon text-warning"></i>Dica do Dia</h4>
                <p class="fst-italic">"<?= htmlspecialchars($client_dashboard_data['daily_tip']) ?>"</p>
                <h5 class="mt-3"><i class="fas fa-quote-left card-icon text-primary"></i>Motivação</h5>
                <p>"<?= htmlspecialchars($client_dashboard_data['motivational_quote']) ?>"</p>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="dashboard-card">
                <h4><i class="fas fa-tasks card-icon text-info"></i>Desafio da Semana</h4>
                <p><strong>Objetivo:</strong> Fazer 3 treinos e seguir o plano alimentar por 5 dias.</p>
                <div class="progress mb-2" style="height: 20px;">
                    <div class="progress-bar bg-info" role="progressbar" style="width: 60%;" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100">60%</div>
                </div>
                <p class="small text-muted text-center">Continue o bom trabalho!</p>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 mb-4">
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

         <div class="col-lg-4 col-md-12 mb-4">
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

<?php include 'footer.php'; ?>

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
