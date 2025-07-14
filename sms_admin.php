<?php
session_start();

// Autenticação: Apenas administradores podem aceder
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

include 'db.php';

// --- Verificação e Inclusão de Dependências ---
$twilio_sdk_path = __DIR__ . '/vendor/autoload.php';
$config_path = __DIR__ . '/config.php';
$twilio_ready = false;
$error_messages = [];
$success_messages = [];

if (!file_exists($twilio_sdk_path)) {
    $error_messages[] = "A biblioteca Twilio SDK não foi encontrada. Por favor, execute 'composer require twilio/sdk' na raiz do seu projeto.";
} elseif (!file_exists($config_path)) {
    $error_messages[] = "O ficheiro de configuração 'config.php' não foi encontrado. Por favor, crie-o com as suas credenciais Twilio.";
} else {
    require_once $twilio_sdk_path;
    require_once $config_path;
    if (defined('TWILIO_ACCOUNT_SID') && TWILIO_ACCOUNT_SID !== 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' && defined('TWILIO_AUTH_TOKEN') && defined('TWILIO_PHONE_NUMBER')) {
        $twilio_ready = true;
    } else {
        $error_messages[] = "As credenciais Twilio não estão configuradas corretamente no ficheiro 'config.php'.";
    }
}

use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;

// --- Processamento do Formulário (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $twilio_ready) { // Início do bloco de processamento
    $message_type = $_POST['message_type'] ?? '';
    $twilio_client = new Client(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN);
    $sent_count = 0;
    $failed_count = 0;
    $failed_details = [];

    try {
        switch ($message_type) {
            // --- Caso: Aviso Importante para todos os clientes ---
            case 'aviso_importante':
                $custom_message = trim($_POST['custom_message'] ?? '');
                if (empty($custom_message)) {
                    $error_messages[] = "A mensagem de aviso não pode estar vazia.";
                } else {
                    $stmt = $pdo->query("SELECT name, numero_telemovel FROM users WHERE role = 'client' AND numero_telemovel IS NOT NULL AND numero_telemovel != ''");
                    $clients = $stmt->fetchAll();
                    foreach ($clients as $client_data) {
                        try {
                            $twilio_client->messages->create($client_data['numero_telemovel'], ['from' => TWILIO_PHONE_NUMBER, 'body' => $custom_message]);
                            $sent_count++;
                        } catch (TwilioException $e) {
                            $failed_count++;
                            $failed_details[] = "{$client_data['name']} ({$client_data['numero_telemovel']}): {$e->getMessage()}";
                        }
                    }
                }
                break;

            // --- Caso: Lembrete de Aula para uma data específica ---
            case 'lembrete_aula':
                $reminder_date = $_POST['reminder_date'] ?? '';
                $participants_without_phone = 0; // Nova variável para contar participantes sem telemóvel

                if (empty($reminder_date)) {
                    $error_messages[] = "Por favor, selecione uma data para os lembretes de aula.";
                } else {
                    // Abordagem mais robusta para consultar um dia inteiro, que também pode usar um índice na coluna `schedule`
                    $start_of_day = $reminder_date . ' 00:00:00';
                    $end_of_day = $reminder_date . ' 23:59:59';

                    // MODIFICADO: A query agora busca TODOS os participantes, mesmo sem telemóvel.
                    $stmt = $pdo->prepare("
                        SELECT u.name as client_name, u.numero_telemovel, c.name as class_name, c.schedule
                        FROM class_participants cp
                        JOIN users u ON cp.user_id = u.id
                        JOIN classes c ON cp.class_id = c.id
                        WHERE c.schedule BETWEEN ? AND ?
                    ");
                    $stmt->execute([$start_of_day, $end_of_day]);
                    $participants = $stmt->fetchAll();

                    if (empty($participants)) {
                        $error_messages[] = "Nenhum participante encontrado para aulas na data selecionada.";
                    } else {
                        foreach ($participants as $p) {
                            // NOVO: A verificação do telemóvel é feita aqui, dentro do loop
                            if (empty($p['numero_telemovel'])) {
                                $participants_without_phone++;
                                continue; // Pula para o próximo participante
                            }

                            $class_time = date('H:i', strtotime($p['schedule']));
                            if ($reminder_date == date('Y-m-d')) {
                                $body = "Olá {$p['client_name']}, a sua aula de {$p['class_name']} é hoje às {$class_time}. Não se esqueça! Bons treinos, equipa MyWellness.";
                            } else {
                                $class_date_formatted = date('d/m/Y', strtotime($p['schedule']));
                                $body = "Olá {$p['client_name']}, lembramos que a sua aula de {$p['class_name']} está agendada para o dia {$class_date_formatted} às {$class_time}. Bons treinos, equipa MyWellness.";
                            }
                            try {
                                $twilio_client->messages->create($p['numero_telemovel'], ['from' => TWILIO_PHONE_NUMBER, 'body' => $body]);
                                $sent_count++;
                            } catch (TwilioException $e) {
                                $failed_count++;
                                $failed_details[] = "{$p['client_name']} ({$p['numero_telemovel']}): {$e->getMessage()}";
                            }
                        }
                    }
                }
                break;

            // --- Caso: Lembrete de Avaliação Física ---
            case 'lembrete_avaliacao':
                $months = filter_input(INPUT_POST, 'assessment_months', FILTER_VALIDATE_INT);
                if ($months === false || $months <= 0) {
                    $error_messages[] = "Número de meses inválido.";
                } else {
                    $date_limit = date('Y-m-d', strtotime("-$months months"));
                    $stmt = $pdo->prepare("
                        SELECT u.name, u.numero_telemovel
                        FROM clients c
                        JOIN users u ON c.user_id = u.id
                        WHERE (c.last_assessment_date <= ? OR c.last_assessment_date IS NULL)
                          AND u.numero_telemovel IS NOT NULL AND u.numero_telemovel != ''
                    ");
                    $stmt->execute([$date_limit]);
                    $clients_to_remind = $stmt->fetchAll();
                    foreach ($clients_to_remind as $client_data) {
                        $body = "Olá {$client_data['name']}, está na hora da sua avaliação física! Agende na receção para acompanharmos o seu progresso de perto. MyWellness.";
                        try {
                            $twilio_client->messages->create($client_data['numero_telemovel'], ['from' => TWILIO_PHONE_NUMBER, 'body' => $body]);
                            $sent_count++;
                        } catch (TwilioException $e) {
                            $failed_count++;
                            $failed_details[] = "{$client_data['name']} ({$client_data['numero_telemovel']}): {$e->getMessage()}";
                        }
                    }
                }
                break;

            // --- Caso: Novo Plano de Treino ---
            case 'novo_plano_treino':
                $client_id = filter_input(INPUT_POST, 'client_id_treino', FILTER_VALIDATE_INT);
                $trainer_id = filter_input(INPUT_POST, 'trainer_id', FILTER_VALIDATE_INT);
                if (!$client_id || !$trainer_id) {
                    $error_messages[] = "Por favor, selecione um cliente e um treinador.";
                } else {
                    $stmt = $pdo->prepare("SELECT name, numero_telemovel FROM users WHERE id = ?");
                    $stmt->execute([$client_id]);
                    $client_data = $stmt->fetch();

                    $stmt->execute([$trainer_id]);
                    $trainer_data = $stmt->fetch();

                    if ($client_data && $trainer_data && !empty($client_data['numero_telemovel'])) {
                        $body = "Boas notícias, {$client_data['name']}! O seu novo plano de treino com o(a) PT {$trainer_data['name']} já está disponível na sua área pessoal. MyWellness.";
                        try {
                            $twilio_client->messages->create($client_data['numero_telemovel'], ['from' => TWILIO_PHONE_NUMBER, 'body' => $body]);
                            $sent_count++;
                        } catch (TwilioException $e) {
                            $failed_count++;
                            $failed_details[] = "{$client_data['name']} ({$client_data['numero_telemovel']}): {$e->getMessage()}";
                        }
                    } else {
                        $error_messages[] = "Cliente ou treinador não encontrado, ou o cliente não tem número de telemóvel.";
                    }
                }
                break;

            // --- Caso: Novo Plano Alimentar ---
            case 'novo_plano_alimentar':
                $client_id = filter_input(INPUT_POST, 'client_id_nutri', FILTER_VALIDATE_INT);
                $nutritionist_id = filter_input(INPUT_POST, 'nutritionist_id', FILTER_VALIDATE_INT);
                if (!$client_id || !$nutritionist_id) {
                    $error_messages[] = "Por favor, selecione um cliente e um nutricionista.";
                } else {
                    $stmt = $pdo->prepare("SELECT name, numero_telemovel FROM users WHERE id = ?");
                    $stmt->execute([$client_id]);
                    $client_data = $stmt->fetch();

                    $stmt->execute([$nutritionist_id]);
                    $nutritionist_data = $stmt->fetch();

                    if ($client_data && $nutritionist_data && !empty($client_data['numero_telemovel'])) {
                        $body = "O seu novo plano alimentar, preparado por {$nutritionist_data['name']}, está pronto! Consulte-o na app para otimizar os seus resultados. MyWellness.";
                        try {
                            $twilio_client->messages->create($client_data['numero_telemovel'], ['from' => TWILIO_PHONE_NUMBER, 'body' => $body]);
                            $sent_count++;
                        } catch (TwilioException $e) {
                            $failed_count++;
                            $failed_details[] = "{$client_data['name']} ({$client_data['numero_telemovel']}): {$e->getMessage()}";
                        }
                    } else {
                        $error_messages[] = "Cliente ou nutricionista não encontrado, ou o cliente não tem número de telemóvel.";
                    }
                }
                break;

            default:
                $error_messages[] = "Tipo de mensagem desconhecido.";
                break;
        }

        // --- Mensagens de Sucesso/Falha ---
        if ($sent_count > 0) {
            $success_messages[] = "{$sent_count} SMS enviados com sucesso.";
        }
        // NOVO: Adiciona mensagem para participantes sem telemóvel
        if (isset($participants_without_phone) && $participants_without_phone > 0) {
            $error_messages[] = "{$participants_without_phone} participante(s) foram ignorados por não terem número de telemóvel registado.";
        }
        if ($failed_count > 0) {
            $error_messages[] = "{$failed_count} SMS falharam ao enviar.";
            foreach ($failed_details as $detail) {
                $error_messages[] = "- " . htmlspecialchars($detail);
            }
        }

    } catch (Exception $e) {
        $error_messages[] = "Ocorreu um erro geral: " . $e->getMessage();
    }
} // Fim do bloco de processamento

// --- Buscar dados para os formulários ---
$all_clients = $pdo->query("SELECT id, name FROM users WHERE role = 'client' ORDER BY name")->fetchAll();
$all_trainers = $pdo->query("SELECT id, name FROM users WHERE role = 'trainer' ORDER BY name")->fetchAll();
$all_nutritionists = $pdo->query("SELECT id, name FROM users WHERE role = 'nutritionist' ORDER BY name")->fetchAll();

include 'header.php';
$active_page = 'sms_admin.php';
include 'sidebar_admin.php';
?>

<main class="main-content">
    <div class="container mt-5">
        <h2><i class="fas fa-sms me-2"></i>Envio de Mensagens SMS</h2>
        <p class="text-white">Comunique com os seus clientes de forma rápida e direta.</p>

        <?php if (!$twilio_ready): ?>
            <div class="alert alert-danger">
                <strong>Configuração Incompleta:</strong>
                <ul>
                    <?php foreach ($error_messages as $msg): ?>
                        <li><?= htmlspecialchars($msg) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <!-- Mensagens de feedback do POST -->
            <?php if (!empty($success_messages)): ?>
                <div class="alert alert-success">
                    <?php foreach ($success_messages as $msg): ?>
                        <p class="mb-1"><?= htmlspecialchars($msg) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($error_messages)): ?>
                 <div class="alert alert-danger">
                    <?php foreach ($error_messages as $msg): ?>
                        <p class="mb-1"><?= htmlspecialchars($msg) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Abas de Navegação -->
            <ul class="nav nav-tabs" id="smsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="aviso-tab" data-bs-toggle="tab" data-bs-target="#aviso-pane" type="button" role="tab">Avisos Importantes</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="lembretes-tab" data-bs-toggle="tab" data-bs-target="#lembretes-pane" type="button" role="tab">Lembretes</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="planos-tab" data-bs-toggle="tab" data-bs-target="#planos-pane" type="button" role="tab">Notificações de Planos</button>
                </li>
            </ul>

            <!-- Conteúdo das Abas -->
            <div class="tab-content card" id="smsTabsContent">
                <!-- Aba: Avisos Importantes -->
                <div class="tab-pane fade show active p-4" id="aviso-pane" role="tabpanel">
                    <h5 class="card-title">Enviar Aviso para Todos os Clientes</h5>
                    <p class="card-text">Envie uma mensagem em massa para todos os clientes com número de telemóvel registado.</p>
                    <form method="POST" action="sms_admin.php">
                        <input type="hidden" name="message_type" value="aviso_importante">
                        <div class="mb-3">
                            <label for="custom_message" class="form-label">Mensagem:</label>
                            <textarea class="form-control" id="custom_message" name="custom_message" rows="4" required placeholder="Ex: Aviso MyWellness: O ginásio estará fechado no próximo feriado..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-2"></i>Enviar para Todos</button>
                    </form>
                </div>

                <!-- Aba: Lembretes -->
                <div class="tab-pane fade p-4" id="lembretes-pane" role="tabpanel">
                    <!-- Lembrete de Aula -->
                    <div class="mb-5">
                        <h5 class="card-title">Lembrete de Aulas</h5>
                        <p class="card-text">Envie um SMS de lembrete para todos os participantes de aulas numa data específica.</p>
                        <form method="POST" action="sms_admin.php">
                            <input type="hidden" name="message_type" value="lembrete_aula">
                            <div class="mb-3">
                                <label for="reminder_date" class="form-label">Selecione a Data das Aulas:</label>
                                <input type="date" class="form-control" id="reminder_date" name="reminder_date" required>
                            </div>
                            <button type="submit" class="btn btn-info"><i class="fas fa-bell me-2"></i>Enviar Lembretes de Aula</button>
                        </form>
                    </div>
                    <hr>
                    <!-- Lembrete de Avaliação Física -->
                    <div class="mt-4">
                        <h5 class="card-title">Lembrete de Avaliação Física</h5>
                        <p class="card-text">Envie um lembrete para clientes cuja última avaliação foi há mais de X meses.</p>
                        <form method="POST" action="sms_admin.php">
                            <input type="hidden" name="message_type" value="lembrete_avaliacao">
                            <div class="mb-3">
                                <label for="assessment_months" class="form-label">Lembrar clientes sem avaliação há mais de (meses):</label>
                                <input type="number" class="form-control" id="assessment_months" name="assessment_months" min="1" value="3" required>
                            </div>
                            <button type="submit" class="btn btn-info"><i class="fas fa-heartbeat me-2"></i>Enviar Lembretes de Avaliação</button>
                        </form>
                    </div>
                </div>

                <!-- Aba: Notificações de Planos -->
                <div class="tab-pane fade p-4" id="planos-pane" role="tabpanel">
                    <!-- Novo Plano de Treino -->
                    <div class="mb-5">
                        <h5 class="card-title">Notificação de Novo Plano de Treino</h5>
                        <p class="card-text">Envie um SMS para um cliente a avisar que o seu novo plano de treino está pronto.</p>
                        <form method="POST" action="sms_admin.php">
                            <input type="hidden" name="message_type" value="novo_plano_treino">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="client_id_treino" class="form-label">Selecione o Cliente:</label>
                                    <select class="form-select" id="client_id_treino" name="client_id_treino" required>
                                        <option value="">-- Cliente --</option>
                                        <?php foreach ($all_clients as $client): ?>
                                            <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="trainer_id" class="form-label">Selecione o Treinador:</label>
                                    <select class="form-select" id="trainer_id" name="trainer_id" required>
                                        <option value="">-- Treinador --</option>
                                        <?php foreach ($all_trainers as $trainer): ?>
                                            <option value="<?= $trainer['id'] ?>"><?= htmlspecialchars($trainer['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success"><i class="fas fa-dumbbell me-2"></i>Notificar Plano de Treino</button>
                        </form>
                    </div>
                    <hr>
                    <!-- Novo Plano Alimentar -->
                    <div class="mt-4">
                        <h5 class="card-title">Notificação de Novo Plano Alimentar</h5>
                        <p class="card-text">Envie um SMS para um cliente a avisar que o seu novo plano alimentar está pronto.</p>
                        <form method="POST" action="sms_admin.php">
                            <input type="hidden" name="message_type" value="novo_plano_alimentar">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="client_id_nutri" class="form-label">Selecione o Cliente:</label>
                                    <select class="form-select" id="client_id_nutri" name="client_id_nutri" required>
                                        <option value="">-- Cliente --</option>
                                        <?php foreach ($all_clients as $client): ?>
                                            <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="nutritionist_id" class="form-label">Selecione o Nutricionista:</label>
                                    <select class="form-select" id="nutritionist_id" name="nutritionist_id" required>
                                        <option value="">-- Nutricionista --</option>
                                        <?php foreach ($all_nutritionists as $nutritionist): ?>
                                            <option value="<?= $nutritionist['id'] ?>"><?= htmlspecialchars($nutritionist['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success"><i class="fas fa-apple-alt me-2"></i>Notificar Plano Alimentar</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
include 'footer.php';
?>
