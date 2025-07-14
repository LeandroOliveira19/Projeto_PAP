<?php
// settings_admin.php
session_start();
// Inclui db.php primeiro para que $pdo esteja disponível
include 'db.php';
include 'includes/auth_utils.php'; // Inclui a função de verificação

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Verifica se o usuário tem a permissão específica 'manage_app_settings'
// Para este exemplo, vamos assumir que apenas o 'admin' principal tem acesso,
// mas a estrutura de permissão está aqui para expansão futura.
if ($_SESSION['role'] !== 'admin' && !user_has_specific_permission($pdo, $_SESSION['user_id'], 'manage_app_settings')) {
    $_SESSION['error_message'] = "Acesso negado. Permissão para 'Gerir Configurações da Aplicação' necessária.";
    // Redirecionar para uma página de "acesso negado" ou para o dashboard do usuário
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'receptionist') {
        header('Location: receptionist.php');
    } else {
        header('Location: login.php'); // Fallback
    }
    exit;
}

$message = '';
$message_type = '';

// --- PROCESSAMENTO DE FORMULÁRIOS (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    try {
        // --- AÇÕES PARA CONQUISTAS (ACHIEVEMENTS) ---
        if ($action === 'add_achievement') {
            $stmt = $pdo->prepare("INSERT INTO achievements (name, description, icon, points, criteria_type, criteria_value, is_manual_claim) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['name'],
                $_POST['description'],
                $_POST['icon'],
                $_POST['points'],
                $_POST['criteria_type'] ?: null,
                $_POST['criteria_value'] ?: null,
                isset($_POST['is_manual_claim']) ? 1 : 0
            ]);
            $message = "Conquista adicionada com sucesso!";
            $message_type = 'success';
        } elseif ($action === 'update_achievement') {
            $stmt = $pdo->prepare("UPDATE achievements SET name = ?, description = ?, icon = ?, points = ?, criteria_type = ?, criteria_value = ?, is_manual_claim = ? WHERE id = ?");
            $stmt->execute([
                $_POST['name'],
                $_POST['description'],
                $_POST['icon'],
                $_POST['points'],
                $_POST['criteria_type'] ?: null,
                $_POST['criteria_value'] ?: null,
                isset($_POST['is_manual_claim']) ? 1 : 0,
                $_POST['id']
            ]);
            $message = "Conquista atualizada com sucesso!";
            $message_type = 'success';
        } elseif ($action === 'delete_achievement') {
            $stmt = $pdo->prepare("DELETE FROM achievements WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $message = "Conquista excluída com sucesso!";
            $message_type = 'success';
        }

        // --- AÇÕES PARA LEMBRETES (REMINDERS) ---
        elseif ($action === 'add_reminder') {
            $stmt = $pdo->prepare("INSERT INTO dashboard_reminders (reminder_text, reminder_type, author, is_active) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $_POST['reminder_text'],
                $_POST['reminder_type'],
                $_POST['author'] ?: null,
                isset($_POST['is_active']) ? 1 : 0
            ]);
            $message = "Lembrete adicionado com sucesso!";
            $message_type = 'success';
        } elseif ($action === 'update_reminder') {
            $stmt = $pdo->prepare("UPDATE dashboard_reminders SET reminder_text = ?, reminder_type = ?, author = ?, is_active = ? WHERE id = ?");
            $stmt->execute([
                $_POST['reminder_text'],
                $_POST['reminder_type'],
                $_POST['author'] ?: null,
                isset($_POST['is_active']) ? 1 : 0,
                $_POST['id']
            ]);
            $message = "Lembrete atualizado com sucesso!";
            $message_type = 'success';
        } elseif ($action === 'delete_reminder') {
            $stmt = $pdo->prepare("DELETE FROM dashboard_reminders WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $message = "Lembrete excluído com sucesso!";
            $message_type = 'success';
        }

        // --- AÇÕES PARA OPÇÕES DE DIA (DAY OPTIONS) ---
        elseif ($action === 'add_day_option') {
            $stmt = $pdo->prepare("INSERT INTO day_options (name) VALUES (?)");
            $stmt->execute([$_POST['name']]);
            $message = "Opção de dia adicionada com sucesso!";
            $message_type = 'success';
        } elseif ($action === 'update_day_option') {
            $stmt = $pdo->prepare("UPDATE day_options SET name = ? WHERE id = ?");
            $stmt->execute([$_POST['name'], $_POST['id']]);
            $message = "Opção de dia atualizada com sucesso!";
            $message_type = 'success';
        } elseif ($action === 'delete_day_option') {
            $stmt = $pdo->prepare("DELETE FROM day_options WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $message = "Opção de dia excluída com sucesso!";
            $message_type = 'success';
        }

    } catch (PDOException $e) {
        $message = "Erro na operação com o banco de dados: " . $e->getMessage();
        $message_type = 'danger';
        error_log("Erro em settings_admin.php: " . $e->getMessage());
    }
}

// --- BUSCAR DADOS PARA EXIBIÇÃO ---
$achievements = $pdo->query("SELECT * FROM achievements ORDER BY name ASC")->fetchAll();
$reminders = $pdo->query("SELECT * FROM dashboard_reminders ORDER BY reminder_type, reminder_text ASC")->fetchAll();
$day_options = $pdo->query("SELECT * FROM day_options ORDER BY name ASC")->fetchAll();

include 'header.php';
$active_page = 'settings_admin.php';
include 'sidebar_admin.php';
?>

<main class="main-content">
    <div class="container mt-5">
        <h2><i class="fas fa-cogs me-2"></i>Configurações da Aplicação</h2>
        <p>Gerencie as configurações globais, como conquistas, lembretes e opções de treino.</p>

        <?php if ($message): ?>
            <div class="alert alert-<?= htmlspecialchars($message_type) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Abas de Navegação -->
        <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="achievements-tab" data-bs-toggle="tab" data-bs-target="#achievements-pane" type="button" role="tab" aria-controls="achievements-pane" aria-selected="true">
                    <i class="fas fa-trophy me-1"></i> Conquistas
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="reminders-tab" data-bs-toggle="tab" data-bs-target="#reminders-pane" type="button" role="tab" aria-controls="reminders-pane" aria-selected="false">
                    <i class="fas fa-bell me-1"></i> Lembretes do Painel
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="day-options-tab" data-bs-toggle="tab" data-bs-target="#day-options-pane" type="button" role="tab" aria-controls="day-options-pane" aria-selected="false">
                    <i class="fas fa-calendar-day me-1"></i> Opções de Dia de Treino
                </button>
            </li>
        </ul>

        <!-- Conteúdo das Abas -->
        <div class="tab-content" id="settingsTabsContent">
            <!-- Aba de Conquistas -->
            <div class="tab-pane fade show active" id="achievements-pane" role="tabpanel" aria-labelledby="achievements-tab" tabindex="0">
                <div class="card mt-3 bg-light">
                    <div class="card-body">
                        <h4 class="card-title text-dark">Gerir Conquistas</h4>
                        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#achievementModal" data-action="add">
                            <i class="fas fa-plus me-1"></i> Adicionar Nova Conquista
                        </button>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Descrição</th>
                                        <th>Pontos</th>
                                        <th>Critério</th>
                                        <th>Reivindicação Manual</th>
                                        <th class="text-end">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($achievements as $item): ?>
                                    <tr>
                                        <td><i class="<?= htmlspecialchars($item['icon']) ?> me-2"></i><?= htmlspecialchars($item['name']) ?></td>
                                        <td><?= htmlspecialchars($item['description']) ?></td>
                                        <td><?= htmlspecialchars($item['points']) ?></td>
                                        <td><small><?= htmlspecialchars($item['criteria_type'] ?? 'N/A') ?>: <?= htmlspecialchars($item['criteria_value'] ?? 'N/A') ?></small></td>
                                        <td><?= $item['is_manual_claim'] ? '<span class="badge bg-info">Sim</span>' : '<span class="badge bg-secondary">Não</span>' ?></td>
                                        <td class="text-end">
                                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#achievementModal"
                                                data-action="edit"
                                                data-id="<?= $item['id'] ?>"
                                                data-name="<?= htmlspecialchars($item['name']) ?>"
                                                data-description="<?= htmlspecialchars($item['description']) ?>"
                                                data-icon="<?= htmlspecialchars($item['icon']) ?>"
                                                data-points="<?= htmlspecialchars($item['points']) ?>"
                                                data-criteria_type="<?= htmlspecialchars($item['criteria_type'] ?? '') ?>"
                                                data-criteria_value="<?= htmlspecialchars($item['criteria_value'] ?? '') ?>"
                                                data-is_manual_claim="<?= $item['is_manual_claim'] ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="settings_admin.php" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta conquista?');">
                                                <input type="hidden" name="action" value="delete_achievement">
                                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Aba de Lembretes -->
            <div class="tab-pane fade" id="reminders-pane" role="tabpanel" aria-labelledby="reminders-tab" tabindex="0">
                <div class="card mt-3 bg-light">
                    <div class="card-body">
                        <h4 class="card-title text-dark">Gerir Lembretes do Painel</h4>
                        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#reminderModal" data-action="add">
                            <i class="fas fa-plus me-1"></i> Adicionar Novo Lembrete
                        </button>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Texto</th>
                                        <th>Tipo</th>
                                        <th>Autor</th>
                                        <th>Status</th>
                                        <th class="text-end">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reminders as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['reminder_text']) ?></td>
                                        <td><span class="badge bg-<?= $item['reminder_type'] === 'tip' ? 'primary' : 'secondary' ?>"><?= ucfirst($item['reminder_type']) ?></span></td>
                                        <td><?= htmlspecialchars($item['author'] ?? 'N/A') ?></td>
                                        <td><?= $item['is_active'] ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-danger">Inativo</span>' ?></td>
                                        <td class="text-end">
                                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#reminderModal"
                                                data-action="edit"
                                                data-id="<?= $item['id'] ?>"
                                                data-reminder_text="<?= htmlspecialchars($item['reminder_text']) ?>"
                                                data-reminder_type="<?= htmlspecialchars($item['reminder_type']) ?>"
                                                data-author="<?= htmlspecialchars($item['author'] ?? '') ?>"
                                                data-is_active="<?= $item['is_active'] ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="settings_admin.php" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este lembrete?');">
                                                <input type="hidden" name="action" value="delete_reminder">
                                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Aba de Opções de Dia -->
            <div class="tab-pane fade" id="day-options-pane" role="tabpanel" aria-labelledby="day-options-tab" tabindex="0">
                <div class="card mt-3 bg-light">
                    <div class="card-body">
                        <h4 class="card-title text-dark">Gerir Opções de Dia de Treino</h4>
                        <p class="card-subtitle mb-3 text-muted">Estas são as opções de "foco" que os treinadores podem selecionar para cada dia de um plano.</p>
                        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#dayOptionModal" data-action="add">
                            <i class="fas fa-plus me-1"></i> Adicionar Nova Opção
                        </button>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Nome da Opção</th>
                                        <th class="text-end">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($day_options as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['name']) ?></td>
                                        <td class="text-end">
                                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#dayOptionModal"
                                                data-action="edit"
                                                data-id="<?= $item['id'] ?>"
                                                data-name="<?= htmlspecialchars($item['name']) ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="settings_admin.php" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta opção?');">
                                                <input type="hidden" name="action" value="delete_day_option">
                                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal para Conquistas -->
<div class="modal fade" id="achievementModal" tabindex="-1" aria-labelledby="achievementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="settings_admin.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="achievementModalLabel">Adicionar Conquista</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="achievementAction" value="add_achievement">
                    <input type="hidden" name="id" id="achievementId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="achievementName" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="achievementName" name="name" required>
                        </div>
                        <div class="col-md-3">
                            <label for="achievementIcon" class="form-label">Ícone (Font Awesome)</label>
                            <input type="text" class="form-control" id="achievementIcon" name="icon" placeholder="fas fa-trophy">
                        </div>
                        <div class="col-md-3">
                            <label for="achievementPoints" class="form-label">Pontos</label>
                            <input type="number" class="form-control" id="achievementPoints" name="points" required>
                        </div>
                        <div class="col-12">
                            <label for="achievementDescription" class="form-label">Descrição</label>
                            <textarea class="form-control" id="achievementDescription" name="description" rows="2" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="achievementCriteriaType" class="form-label">Tipo de Critério</label>
                            <input type="text" class="form-control" id="achievementCriteriaType" name="criteria_type" placeholder="Ex: workouts_completed_count">
                        </div>
                        <div class="col-md-6">
                            <label for="achievementCriteriaValue" class="form-label">Valor do Critério</label>
                            <input type="text" class="form-control" id="achievementCriteriaValue" name="criteria_value" placeholder="Ex: 10">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="achievementIsManual" name="is_manual_claim" value="1">
                                <label class="form-check-label" for="achievementIsManual">Reivindicação Manual pelo Cliente</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Lembretes -->
<div class="modal fade" id="reminderModal" tabindex="-1" aria-labelledby="reminderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="settings_admin.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="reminderModalLabel">Adicionar Lembrete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="reminderAction" value="add_reminder">
                    <input type="hidden" name="id" id="reminderId">
                    <div class="mb-3">
                        <label for="reminderText" class="form-label">Texto do Lembrete/Citação</label>
                        <textarea class="form-control" id="reminderText" name="reminder_text" rows="3" required></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="reminderType" class="form-label">Tipo</label>
                            <select class="form-select" id="reminderType" name="reminder_type" required>
                                <option value="tip">Dica (Tip)</option>
                                <option value="quote">Citação (Quote)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="reminderAuthor" class="form-label">Autor (para Citações)</label>
                            <input type="text" class="form-control" id="reminderAuthor" name="author" placeholder="Opcional">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="reminderIsActive" name="is_active" value="1" checked>
                                <label class="form-check-label" for="reminderIsActive">Ativo</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Opções de Dia -->
<div class="modal fade" id="dayOptionModal" tabindex="-1" aria-labelledby="dayOptionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="settings_admin.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="dayOptionModalLabel">Adicionar Opção de Dia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="dayOptionAction" value="add_day_option">
                    <input type="hidden" name="id" id="dayOptionId">
                    <div class="mb-3">
                        <label for="dayOptionName" class="form-label">Nome da Opção</label>
                        <input type="text" class="form-control" id="dayOptionName" name="name" required placeholder="Ex: Pernas e Ombros">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Script para Modal de Conquistas ---
    const achievementModal = document.getElementById('achievementModal');
    achievementModal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget;
        const action = button.getAttribute('data-action');
        const modalTitle = achievementModal.querySelector('.modal-title');
        const actionInput = achievementModal.querySelector('#achievementAction');
        const idInput = achievementModal.querySelector('#achievementId');
        const nameInput = achievementModal.querySelector('#achievementName');
        const descriptionInput = achievementModal.querySelector('#achievementDescription');
        const iconInput = achievementModal.querySelector('#achievementIcon');
        const pointsInput = achievementModal.querySelector('#achievementPoints');
        const criteriaTypeInput = achievementModal.querySelector('#achievementCriteriaType');
        const criteriaValueInput = achievementModal.querySelector('#achievementCriteriaValue');
        const isManualInput = achievementModal.querySelector('#achievementIsManual');

        if (action === 'edit') {
            modalTitle.textContent = 'Editar Conquista';
            actionInput.value = 'update_achievement';
            idInput.value = button.getAttribute('data-id');
            nameInput.value = button.getAttribute('data-name');
            descriptionInput.value = button.getAttribute('data-description');
            iconInput.value = button.getAttribute('data-icon');
            pointsInput.value = button.getAttribute('data-points');
            criteriaTypeInput.value = button.getAttribute('data-criteria_type');
            criteriaValueInput.value = button.getAttribute('data-criteria_value');
            isManualInput.checked = button.getAttribute('data-is_manual_claim') == '1';
        } else {
            modalTitle.textContent = 'Adicionar Nova Conquista';
            actionInput.value = 'add_achievement';
            idInput.value = '';
            achievementModal.querySelector('form').reset();
        }
    });

    // --- Script para Modal de Lembretes ---
    const reminderModal = document.getElementById('reminderModal');
    reminderModal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget;
        const action = button.getAttribute('data-action');
        const modalTitle = reminderModal.querySelector('.modal-title');
        const actionInput = reminderModal.querySelector('#reminderAction');
        const idInput = reminderModal.querySelector('#reminderId');
        const textInput = reminderModal.querySelector('#reminderText');
        const typeInput = reminderModal.querySelector('#reminderType');
        const authorInput = reminderModal.querySelector('#reminderAuthor');
        const isActiveInput = reminderModal.querySelector('#reminderIsActive');

        if (action === 'edit') {
            modalTitle.textContent = 'Editar Lembrete';
            actionInput.value = 'update_reminder';
            idInput.value = button.getAttribute('data-id');
            textInput.value = button.getAttribute('data-reminder_text');
            typeInput.value = button.getAttribute('data-reminder_type');
            authorInput.value = button.getAttribute('data-author');
            isActiveInput.checked = button.getAttribute('data-is_active') == '1';
        } else {
            modalTitle.textContent = 'Adicionar Novo Lembrete';
            actionInput.value = 'add_reminder';
            idInput.value = '';
            reminderModal.querySelector('form').reset();
            isActiveInput.checked = true; // Default to active
        }
    });

    // --- Script para Modal de Opções de Dia ---
    const dayOptionModal = document.getElementById('dayOptionModal');
    dayOptionModal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget;
        const action = button.getAttribute('data-action');
        const modalTitle = dayOptionModal.querySelector('.modal-title');
        const actionInput = dayOptionModal.querySelector('#dayOptionAction');
        const idInput = dayOptionModal.querySelector('#dayOptionId');
        const nameInput = dayOptionModal.querySelector('#dayOptionName');

        if (action === 'edit') {
            modalTitle.textContent = 'Editar Opção de Dia';
            actionInput.value = 'update_day_option';
            idInput.value = button.getAttribute('data-id');
            nameInput.value = button.getAttribute('data-name');
        } else {
            modalTitle.textContent = 'Adicionar Nova Opção de Dia';
            actionInput.value = 'add_day_option';
            idInput.value = '';
            dayOptionModal.querySelector('form').reset();
        }
    });
});
</script>

<?php include 'footer.php'; ?>
