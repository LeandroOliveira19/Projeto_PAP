<?php
session_start();
// Verifica se o usuário está logado e se é um treinador
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainer') {
    header('Location: login.php');
    exit;
}

include 'db.php'; // Conexão com o banco de dados
include 'header.php'; // Inclui o cabeçalho

// Define a página ativa para a sidebar
$active_page = basename($_SERVER['PHP_SELF']); // my_classes_trainer.php

// Inclui a sidebar do treinador
include 'sidebar_trainer.php';

// Informações do treinador logado
$trainer_id = $_SESSION['user_id'];
$classes = [];
$error_message = '';
$current_view = $_GET['view'] ?? 'upcoming'; // 'upcoming' or 'past'

try {
    // Ajustar a query SQL com base na view selecionada
    $sql_classes = "
        SELECT id, name, schedule, duration, max_participants,
               (SELECT COUNT(*) FROM class_participants WHERE class_id = classes.id) as current_participants,
               (SELECT COUNT(*) FROM class_participants WHERE class_id = classes.id AND checked_in_at IS NOT NULL) as checked_in_count
        FROM classes WHERE trainer_id = ?
    ";

    if ($current_view === 'upcoming') {
        $sql_classes .= " AND schedule >= CURDATE() ORDER BY schedule ASC"; // Próximas aulas, da mais próxima para a mais distante
    } else { // 'past'
        $sql_classes .= " AND schedule < CURDATE() ORDER BY schedule DESC"; // Aulas passadas, da mais recente para a mais antiga
    }

    $stmt_classes = $pdo->prepare($sql_classes);
    $stmt_classes->execute([$trainer_id]);
    $classes = $stmt_classes->fetchAll(PDO::FETCH_ASSOC);
    if ($current_view !== 'upcoming' && $current_view !== 'past') {
        // Se 'view' for inválido, redireciona para 'upcoming' por segurança/padrão
        header('Location: my_classes_trainer.php?view=upcoming');
        exit;
    }

} catch (PDOException $e) {
    $error_message = "Erro ao buscar informações das aulas: " . $e->getMessage();
    error_log("Erro PDO em my_classes_trainer.php: " . $e->getMessage());
}
?>

<!-- Conteúdo Principal -->
<main class="main-content">
    <div class="container mt-4">
        <h2><i class="fas fa-calendar-alt me-2"></i>Minhas Aulas Agendadas</h2>
        <p class="text-white">Visualize as suas aulas e os participantes inscritos.</p>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <!-- Abas de Navegação -->
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <a class="nav-link <?= ($current_view === 'upcoming') ? 'active' : '' ?>" 
                   href="my_classes_trainer.php?view=upcoming">Próximas Aulas</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($current_view === 'past') ? 'active' : '' ?>" 
                   href="my_classes_trainer.php?view=past">Aulas Anteriores</a>
            </li>
        </ul>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <?php if (empty($classes) && !$error_message): ?>
                    <p class="text-muted text-center">Nenhuma aula <?= ($current_view === 'upcoming') ? 'futura' : 'anterior' ?> encontrada.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Aula</th>
                                    <th>Data e Hora</th>
                                    <th>Duração</th>
                                    <th>Inscritos</th><th>Check-ins</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($classes as $class): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($class['name']) ?></td>
                                        <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($class['schedule']))) ?></td>
                                        <td><?= htmlspecialchars($class['duration']) ?> min</td>
                                        <td><?= htmlspecialchars($class['current_participants']) ?></td>
                                        <td><?= htmlspecialchars($class['checked_in_count']) ?></td>
                                        <td>
                                            <button class="btn btn-outline-primary btn-sm view-participants-btn"
                                                    data-class-id="<?= $class['id'] ?>"
                                                    data-class-name="<?= htmlspecialchars($class['name']) ?>"
                                                    data-bs-toggle="modal" data-bs-target="#viewParticipantsModal">
                                                <i class="fas fa-users me-1"></i> Ver Participantes
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div><!-- /.container -->
</main> <!-- /.main-content -->

<!-- Modal Ver Participantes (copiado de trainer.php) -->
<div class="modal fade" id="viewParticipantsModal" tabindex="-1" aria-labelledby="viewParticipantsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewParticipantsModalLabel">Participantes: <span id="modalViewClassName"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="viewParticipantsListContainer" class="list-group">
            <p class="text-center text-muted">A carregar participantes...</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<!-- JavaScript para o Modal (copiado de trainer.php) -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const viewParticipantsModal = new bootstrap.Modal(document.getElementById('viewParticipantsModal'));
    const modalViewClassNameSpan = document.getElementById('modalViewClassName');
    const viewParticipantsListContainer = document.getElementById('viewParticipantsListContainer');

    document.querySelectorAll('.view-participants-btn').forEach(button => {
        button.addEventListener('click', function () {
            const classId = this.dataset.classId;
            const className = this.dataset.className;
            modalViewClassNameSpan.textContent = className;
            loadClassParticipants(classId);
        });
    });

    // --- FUNÇÃO loadClassParticipants (Copiada de trainer.php) ---
    async function loadClassParticipants(classId) {
        viewParticipantsListContainer.innerHTML = '<p class="text-center text-muted">A carregar participantes...</p>';
        try {
            // Usa o mesmo endpoint AJAX, mas o PHP agora permite que treinadores o acessem para esta ação
            const response = await fetch(`ajax_handle_checkin.php?action=get_participants&class_id=${classId}`);
            if (!response.ok) throw new Error('Falha ao buscar participantes.');
            const data = await response.json();

            if (data.status === 'success' && data.participants) {
                renderClassParticipantsList(data.participants);
            } else {
                viewParticipantsListContainer.innerHTML = `<p class="text-danger text-center">${data.message || 'Nenhum participante inscrito ou erro.'}</p>`;
            }
        } catch (error) {
            console.error('Erro ao carregar participantes da aula:', error);
            viewParticipantsListContainer.innerHTML = `<p class="text-danger text-center">Erro ao carregar participantes: ${error.message}</p>`;
        }
    }
    // --- FIM FUNÇÃO loadClassParticipants ---

    // --- FUNÇÃO renderClassParticipantsList (Copiada de trainer.php) ---
    function renderClassParticipantsList(participants) {
        viewParticipantsListContainer.innerHTML = '';
        if (participants.length === 0) {
            viewParticipantsListContainer.innerHTML = '<p class="text-center text-muted">Nenhum participante inscrito nesta aula.</p>';
            return;
        }
        participants.forEach(p => {
            const item = document.createElement('div'); // Usar 'div' para list-group-item
            item.classList.add('list-group-item', 'd-flex', 'justify-content-between', 'align-items-center');

            let checkinStatus = p.checked_in_at
                ? `<span class="badge bg-success rounded-pill">Check-in Feito <small>(${new Date(p.checked_in_at).toLocaleTimeString('pt-PT', {hour: '2-digit', minute:'2-digit'})} por ${p.checked_in_by_name || 'Sistema'})</small></span>`
                : '<span class="badge bg-warning text-dark rounded-pill">Pendente</span>';

            item.innerHTML = `
                <span>
                    <img src="${p.profile_picture_url || 'assets/img/avatars/default.png'}" alt="${p.name}" class="rounded-circle me-2" style="width: 30px; height: 30px; object-fit: cover;">
                    ${p.name}
                </span>
                ${checkinStatus}
            `;
            viewParticipantsListContainer.appendChild(item);
        });
    }
    // --- FIM FUNÇÃO renderClassParticipantsList ---

});
</script>

<?php
// Inclui o rodapé da página
include 'footer.php';
?>
