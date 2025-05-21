<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'receptionist') {
    header('Location: login.php');
    exit;
}
include 'db.php';
include 'header.php';

$active_page = basename($_SERVER['PHP_SELF']);
include 'sidebar_receptionist.php';

$receptionist_id = $_SESSION['user_id'];
$today_date = date('Y-m-d');
$selected_date = $_GET['date'] ?? $today_date;

$classes_today = [];
$page_error = '';

try {
    $stmt = $pdo->prepare("
        SELECT c.id, c.name, c.schedule, c.duration, u.name as trainer_name,
               (SELECT COUNT(*) FROM class_participants cp WHERE cp.class_id = c.id) as enrolled_count,
               (SELECT COUNT(*) FROM class_participants cp WHERE cp.class_id = c.id AND cp.checked_in_at IS NOT NULL) as checked_in_count
        FROM classes c
        JOIN users u ON c.trainer_id = u.id
        WHERE DATE(c.schedule) = :selected_date
        ORDER BY c.schedule ASC
    ");
    $stmt->execute(['selected_date' => $selected_date]);
    $classes_today = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $page_error = "Erro ao buscar aulas: " . $e->getMessage();
    error_log($page_error);
}

?>

<main class="main-content">
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4"><i class="fas fa-user-check me-2"></i>Check-in de Aulas</h2>
            <form method="GET" action="checkin_receptionist.php" class="d-flex align-items-center">
                <label for="date_filter" class="form-label me-2 mb-0">Data:</label>
                <input type="date" id="date_filter" name="date" value="<?= htmlspecialchars($selected_date) ?>" class="form-control form-control-sm" style="width: auto;" onchange="this.form.submit()">
            </form>
        </div>

        <?php if ($page_error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($page_error) ?></div>
        <?php endif; ?>

        <?php if (empty($classes_today) && !$page_error): ?>
            <div class="alert alert-info text-center">Nenhuma aula agendada para <?= htmlspecialchars(date('d/m/Y', strtotime($selected_date))) ?>.</div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($classes_today as $class): ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($class['name']) ?></h5>
                                <p class="card-text mb-1">
                                    <i class="fas fa-clock me-1"></i> <?= htmlspecialchars(date('H:i', strtotime($class['schedule']))) ?> (<?= htmlspecialchars($class['duration']) ?> min)
                                </p>
                                <p class="card-text mb-1">
                                    <i class="fas fa-chalkboard-teacher me-1"></i> <?= htmlspecialchars($class['trainer_name']) ?>
                                </p>
                                <p class="card-text mb-2">
                                    <i class="fas fa-users me-1"></i> Inscritos: <?= $class['enrolled_count'] ?> | Check-ins: <span id="checked_in_count_<?= $class['id'] ?>"><?= $class['checked_in_count'] ?></span>
                                </p>
                                <button class="btn btn-primary btn-sm w-100 manage-checkin-btn"
                                        data-class-id="<?= $class['id'] ?>"
                                        data-class-name="<?= htmlspecialchars($class['name']) ?>"
                                        data-bs-toggle="modal" data-bs-target="#checkinModal">
                                    <i class="fas fa-list-ul me-1"></i> Gerir Check-ins
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Modal de Check-in -->
<div class="modal fade" id="checkinModal" tabindex="-1" aria-labelledby="checkinModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="checkinModalLabel">Check-in para: <span id="modalClassName"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="participantsListContainer" class="list-group">
            <!-- Lista de participantes será carregada aqui -->
            <p class="text-center text-muted">A carregar participantes...</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkinModal = new bootstrap.Modal(document.getElementById('checkinModal'));
    const modalClassNameSpan = document.getElementById('modalClassName');
    const participantsListContainer = document.getElementById('participantsListContainer');
    let currentClassIdForModal = null;

    document.querySelectorAll('.manage-checkin-btn').forEach(button => {
        button.addEventListener('click', function () {
            currentClassIdForModal = this.dataset.classId;
            const className = this.dataset.className;
            modalClassNameSpan.textContent = className;
            loadParticipants(currentClassIdForModal);
        });
    });

    async function loadParticipants(classId) {
        participantsListContainer.innerHTML = '<p class="text-center text-muted">A carregar participantes...</p>';
        try {
            const response = await fetch(`ajax_handle_checkin.php?action=get_participants&class_id=${classId}`);
            if (!response.ok) throw new Error('Falha ao buscar participantes.');
            const data = await response.json();

            if (data.status === 'success' && data.participants) {
                renderParticipantsList(data.participants, classId);
            } else {
                participantsListContainer.innerHTML = `<p class="text-danger text-center">${data.message || 'Nenhum participante inscrito ou erro.'}</p>`;
            }
        } catch (error) {
            console.error('Erro ao carregar participantes:', error);
            participantsListContainer.innerHTML = `<p class="text-danger text-center">Erro ao carregar participantes: ${error.message}</p>`;
        }
    }

    function renderParticipantsList(participants, classId) {
        participantsListContainer.innerHTML = '';
        if (participants.length === 0) {
            participantsListContainer.innerHTML = '<p class="text-center text-muted">Nenhum participante inscrito nesta aula.</p>';
            return;
        }
        participants.forEach(p => {
            const item = document.createElement('div');
            item.classList.add('list-group-item', 'd-flex', 'justify-content-between', 'align-items-center');
            
            let checkedInByInfo = '';
            if (p.checked_in_at && p.checked_in_by_name) {
                const checkedInTime = new Date(p.checked_in_at).toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' });
                checkedInByInfo = `<small class="text-muted ms-2">(Check-in: ${checkedInTime} por ${p.checked_in_by_name})</small>`;
            } else if (p.checked_in_at) {
                const checkedInTime = new Date(p.checked_in_at).toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' });
                checkedInByInfo = `<small class="text-muted ms-2">(Check-in: ${checkedInTime})</small>`;
            }

            item.innerHTML = `
                <span>
                    <img src="${p.profile_picture_url || 'assets/img/avatars/default.png'}" alt="${p.name}" class="rounded-circle me-2" style="width: 30px; height: 30px; object-fit: cover;">
                    ${p.name} ${checkedInByInfo}
                </span>
                <div id="action_buttons_user_${p.user_id}">
                    ${p.checked_in_at ? 
                        `<button class="btn btn-outline-warning btn-sm cancel-checkin-btn" data-user-id="${p.user_id}" data-class-id="${classId}">Cancelar Check-in</button>` :
                        `<button class="btn btn-success btn-sm checkin-btn" data-user-id="${p.user_id}" data-class-id="${classId}">Check-in</button>`
                    }
                </div>
            `;
            participantsListContainer.appendChild(item);
        });

        // Add event listeners to new buttons
        participantsListContainer.querySelectorAll('.checkin-btn').forEach(btn => {
            btn.addEventListener('click', handleCheckinAction);
        });
        participantsListContainer.querySelectorAll('.cancel-checkin-btn').forEach(btn => {
            btn.addEventListener('click', handleCheckinAction);
        });
    }

    async function handleCheckinAction(event) {
        const button = event.target;
        const userId = button.dataset.userId;
        const classId = button.dataset.classId;
        const action = button.classList.contains('checkin-btn') ? 'check_in' : 'cancel_check_in';

        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

        try {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('class_id', classId);
            formData.append('client_user_id', userId);

            const response = await fetch('ajax_handle_checkin.php', {
                method: 'POST',
                body: formData
            });
            if (!response.ok) throw new Error('Falha na comunicação com o servidor.');
            const data = await response.json();

            if (data.status === 'success') {
                // Recarregar a lista de participantes no modal para refletir a mudança
                loadParticipants(classId);
                // Atualizar a contagem de check-ins na página principal
                const countSpan = document.getElementById(`checked_in_count_${classId}`);
                if (countSpan && data.new_checked_in_count !== undefined) {
                    countSpan.textContent = data.new_checked_in_count;
                }
            } else {
                alert('Erro: ' + data.message);
                // Restaurar botão em caso de erro
                button.disabled = false;
                button.innerHTML = action === 'check_in' ? 'Check-in' : 'Cancelar Check-in';
            }
        } catch (error) {
            console.error('Erro na ação de check-in:', error);
            alert('Erro ao processar o pedido: ' + error.message);
            button.disabled = false;
            button.innerHTML = action === 'check_in' ? 'Check-in' : 'Cancelar Check-in';
        }
    }
});
</script>

<?php include 'footer.php'; ?>
