<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Inclui a conexão com o banco de dados
include 'db.php';

// Inclui o cabeçalho da página (agora com libs do FullCalendar e calendar.css)
include 'header.php';


// Pega o nome do usuário da sessão
$client_name = $_SESSION['name'] ?? 'Utilizador';

// Define qual link da sidebar deve estar ativo
$active_page = basename($_SERVER['PHP_SELF']);

// --- Preparar dados das aulas para o FullCalendar ---
$events_json = '[]'; // Default como JSON vazio
$classes_data = [];

try {
    // Busca todas as aulas disponíveis com contagem de participantes e status de inscrição
    $stmt = $pdo->prepare("
        SELECT
            c.id,
            c.name,
            c.description,
            c.schedule,
            c.duration,
            c.max_participants,
            u.name AS trainer_name,
            (SELECT COUNT(*) FROM class_participants cp WHERE cp.class_id = c.id) AS current_participants,
            EXISTS(SELECT 1 FROM class_participants cp WHERE cp.class_id = c.id AND cp.user_id = :user_id) AS is_enrolled
        FROM classes c
        JOIN users u ON c.trainer_id = u.id
        ORDER BY c.schedule
    ");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $classes = $stmt->fetchAll();

    if ($classes) {
        foreach ($classes as $class) {
            $vagas_disponiveis = $class['max_participants'] - $class['current_participants'];
            $data_aula_ts = strtotime($class['schedule']);
            $aula_passou = $data_aula_ts < time();

            // Formata a data para ISO8601, necessário para FullCalendar
            $start_date = date('c', $data_aula_ts);
            // Calcula a data/hora de fim (opcional, mas bom para visualização)
            $end_date = date('c', $data_aula_ts + ($class['duration'] * 60));

            // Adiciona a aula ao array de eventos
            $classes_data[] = [
                'id' => $class['id'],
                'title' => $class['name'], // Nome da aula será o título no calendário
                'start' => $start_date,
                'end' => $end_date,
                // Guarda dados extras para usar no modal
                'extendedProps' => [
                    'description' => $class['description'],
                    'trainer_name' => $class['trainer_name'],
                    'schedule_formatted' => date('d/m/Y \à\s H:i', $data_aula_ts),
                    'duration' => $class['duration'],
                    'max_participants' => $class['max_participants'],
                    'current_participants' => $class['current_participants'],
                    'vagas_disponiveis' => $vagas_disponiveis,
                    'aula_passou' => $aula_passou,
                    'is_enrolled' => (bool)$class['is_enrolled'] // Converte para booleano
                ]
            ];
        }
        // Converte o array PHP para JSON
        $events_json = json_encode($classes_data);
    }
} catch (PDOException $e) {
    // Lidar com erro de banco de dados, se necessário
    error_log("Erro ao buscar aulas: " . $e->getMessage());
    // Pode exibir uma mensagem de erro amigável aqui
    echo "<div class='alert alert-danger'>Erro ao carregar dados das aulas.</div>";
}

?>

<!-- Sidebar (Mantida) -->
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="logo">MyWellness</a>
    </div>
    <nav>
        <ul class="sidebar-nav">
            <li>
                <a href="dashboard.php" class="<?= ($active_page == 'dashboard.php') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt fa-fw"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="workout_hub.php" class="<?= ($active_page == 'workout_hub.php') ? 'active' : ''; ?>">
                    <i class="fas fa-dumbbell fa-fw"></i>
                    <span>Plano de Treino</span>
                </a>
            </li>
            <li>
                <a href="nutrition_hub.php" class="<?= ($active_page == 'nutrition_hub.php') ? 'active' : ''; ?>">
                    <i class="fas fa-utensils fa-fw"></i>
                    <span>Nutrição</span>
                </a>
            </li>
            <li>
                <a href="classes.php" class="<?= ($active_page == 'classes.php') ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check fa-fw"></i>
                    <span>Aulas</span>
                </a>
            </li>
             <li>
                <a href="profile.php" class="<?= ($active_page == 'profile.php') ? 'active' : ''; ?>">
                    <i class="fas fa-user fa-fw"></i>
                    <span>Perfil</span>
                </a>
            </li>
             <li>
                <a href="chat.php" class="<?= ($active_page == 'chat.php') ? 'active' : ''; ?>">
                    <i class="fas fa-comments fa-fw"></i>
                    <span>Chat</span>
                </a>
            </li>
        </ul>
    </nav>
     <div class="sidebar-footer">
         <a href="logout.php">
            <i class="fas fa-sign-out-alt fa-fw"></i>
            <span>Sair</span>
        </a>
    </div>
</aside>

<!-- Conteúdo Principal -->
<main class="main-content">
    <h2>Agenda de Aulas</h2>

    <!-- Container onde o calendário será renderizado -->
    <div id='calendar'></div>

</main>

<!-- Modal para Detalhes da Aula (Bootstrap) -->
<div class="modal fade" id="classDetailsModal" tabindex="-1" aria-labelledby="classDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="classDetailsModalLabel">Detalhes da Aula</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <h4 id="modalClassName" class="mb-3"></h4> 
        <p class="mb-2"><strong >Treinador:</strong> <span id="modalTrainerName"></span></p>
        <p class="mb-2"><strong >Horário:</strong> <span id="modalSchedule"></span></p>
        <p class="mb-2"><strong >Duração:</strong> <span id="modalDuration"></span> min</p>
        <p class="mb-3"><strong >Descrição:</strong><br><span id="modalDescription" class="text-muted"></span></p>
        <p class="mb-0"><strong >Vagas:</strong> <span id="modalVacancyInfo"></span></p> 
      </div>
      <div class="modal-footer justify-content-between" id="modalFooterActions"> 
        <!-- Botão de inscrição/status será inserido aqui via JS -->
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<!-- Adiciona os scripts e estilos necessários para o FullCalendar -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'pt',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: <?php echo $events_json; ?>,
            eventClick: function(info) {
                // Preenche o modal com os detalhes da aula
                var event = info.event.extendedProps;
                document.getElementById('modalClassName').textContent = info.event.title;
                document.getElementById('modalTrainerName').textContent = event.trainer_name;
                document.getElementById('modalSchedule').textContent = event.schedule_formatted;
                document.getElementById('modalDuration').textContent = event.duration;
                document.getElementById('modalDescription').textContent = event.description;
                document.getElementById('modalVacancyInfo').textContent = `${event.vagas_disponiveis} de ${event.max_participants}`;

                // Adiciona botão de inscrição ou status
                var modalFooter = document.getElementById('modalFooterActions');
                modalFooter.innerHTML = ''; // Limpa ações anteriores

                if (event.aula_passou) {
                    modalFooter.innerHTML = '<span class="text-danger">Esta aula já ocorreu.</span>';
                } else if (event.is_enrolled) {
                    modalFooter.innerHTML = '<button class="btn btn-danger">Cancelar Inscrição</button>';
                } else if (event.vagas_disponiveis > 0) {
                    modalFooter.innerHTML = '<button class="btn btn-primary">Inscrever-se</button>';
                } else {
                    modalFooter.innerHTML = '<span class="text-warning">Sem vagas disponíveis.</span>';
                }

                // Exibe o modal
                var modal = new bootstrap.Modal(document.getElementById('classDetailsModal'));
                modal.show();
            }
        });

        calendar.render();
    });
</script>

<?php
include 'footer.php';
?>