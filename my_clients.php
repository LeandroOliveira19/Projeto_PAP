<?php
// filepath: c:\xampp\htdocs\Projeto_PAP\my_clients.php
session_start();

// Verifica se o usuário está logado e se é um trainer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainer') {
    header('Location: login.php');
    exit;
}

$trainer_id = $_SESSION['user_id']; // Definido aqui para ser usado no processamento POST
$clients = [];
$error_message = '';
$transfer_message = '';
$transfer_message_type = '';

// Verificar se há mensagens de transferência na sessão
if (isset($_SESSION['transfer_message'])) {
    $transfer_message = $_SESSION['transfer_message'];
    $transfer_message_type = $_SESSION['transfer_message_type'] ?? 'info';
    unset($_SESSION['transfer_message']);
    unset($_SESSION['transfer_message_type']);
}

// Processar transferência de cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transfer_client_action'])) {
    if (!isset($pdo)) { 
        include 'db.php';
    }

    $client_to_transfer_id = filter_input(INPUT_POST, 'client_to_transfer_id', FILTER_VALIDATE_INT);
    $new_trainer_id = filter_input(INPUT_POST, 'new_trainer_id', FILTER_VALIDATE_INT);
    $current_trainer_id = $_SESSION['user_id']; 

    if ($client_to_transfer_id && $new_trainer_id && $new_trainer_id != $current_trainer_id) {
        try {
            $pdo->beginTransaction();

            // 1. Desativar qualquer plano ativo que o cliente já tenha com o NOVO trainer
            $stmt_deactivate_new_trainer_plans = $pdo->prepare(
                "UPDATE workout_plans SET is_active = 0 
                 WHERE client_id = :client_id AND trainer_id = :new_trainer_id AND is_active = 1"
            );
            $stmt_deactivate_new_trainer_plans->execute([
                'client_id' => $client_to_transfer_id,
                'new_trainer_id' => $new_trainer_id
            ]);

            // 2. Transferir o plano ativo do cliente do trainer ATUAL para o NOVO trainer
            $stmt_transfer = $pdo->prepare(
                "UPDATE workout_plans SET trainer_id = :new_trainer_id 
                 WHERE client_id = :client_id AND trainer_id = :current_trainer_id AND is_active = 1"
            );
            $stmt_transfer->execute([
                'new_trainer_id' => $new_trainer_id,
                'client_id' => $client_to_transfer_id,
                'current_trainer_id' => $current_trainer_id
            ]);

            $pdo->commit();
            $_SESSION['transfer_message'] = "Cliente transferido com sucesso!";
            $_SESSION['transfer_message_type'] = "success";

        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['transfer_message'] = "Erro ao transferir cliente: " . $e->getMessage();
            $_SESSION['transfer_message_type'] = "danger";
            error_log("Erro ao transferir cliente: " . $e->getMessage());
        }
    } else {
        $_SESSION['transfer_message'] = "Dados inválidos para transferência ou tentativa de transferir para si mesmo.";
        $_SESSION['transfer_message_type'] = "warning";
    }
    header("Location: my_clients.php"); 
    exit;
}

// --- O restante do código PHP que busca dados e inclui HTML vem DEPOIS do processamento do POST ---

// Inclui a conexão com o banco de dados e o cabeçalho
include 'db.php'; 
include 'header.php';

// Define a página ativa para a sidebar
$active_page = 'my_clients.php';

// Inclui a sidebar do trainer
include 'sidebar_trainer.php';


try {
    // ***** MODIFICAÇÃO: Buscar clientes que têm um plano ATIVO associado a ESTE trainer *****
    $sql = "SELECT
                c.id as client_id,          -- ID da tabela clients
                u.id as client_user_id,     -- ID da tabela users (para chat)
                u.name as client_name,
                u.email as client_email,
                wp.id as active_workout_plan_id -- Seleciona o ID do plano ATIVO diretamente
            FROM clients c
            JOIN users u ON c.user_id = u.id -- JOIN normal para dados do user
            JOIN workout_plans wp ON c.id = wp.client_id -- JOIN para planos
            WHERE wp.trainer_id = :trainer_id -- Filtra pelos planos criados por este trainer
              AND wp.is_active = 1 -- Filtra APENAS planos ATIVOS
            ORDER BY u.name ASC"; // Ordena por nome do cliente

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':trainer_id', $trainer_id, PDO::PARAM_INT);
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Erro ao buscar clientes: " . $e->getMessage();
    error_log("PDOException in my_clients.php: " . $e->getMessage());
}

// Buscar outros trainers para o modal de transferência
$other_trainers = [];
try {
    $stmt_other_trainers = $pdo->prepare("SELECT id, name FROM users WHERE role = 'trainer' AND id != :current_trainer_id ORDER BY name ASC");
    $stmt_other_trainers->execute(['current_trainer_id' => $trainer_id]);
    $other_trainers = $stmt_other_trainers->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar outros trainers: " . $e->getMessage());
}
?>
<style>
    /* Forçar texto preto no modal de transferência */
    #transferClientModal .modal-title,
    #transferClientModal .modal-body p,
    #transferClientModal .modal-body strong#clientNameToTransfer,
    #transferClientModal .modal-body label[for="new_trainer_id"] {
        color: #000000 !important; /* Cor preta */
    }
</style>

<!-- Conteúdo Principal -->
<main class="main-content">
    <div class="container mt-4">
        <div class="row mb-3">
            <div class="col">
                <h2 class="h4"><i class="fas fa-users me-2"></i>Meus Clientes</h2>
                <p class="text-muted">Lista de clientes com planos de treino ativos atribuídos a si.</p>
            </div>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <?php if ($transfer_message): ?>
            <div class="alert alert-<?= htmlspecialchars($transfer_message_type) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($transfer_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <?php if (empty($clients) && !$error_message): ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle me-2"></i>Você não tem clientes com planos de treino ativos.
                    </div>
                <?php elseif (!empty($clients)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Nome</th>
                                    <th scope="col">Email</th>
                                    <th scope="col" class="text-center">Plano Ativo?</th>
                                    <th scope="col" class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clients as $client): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($client['client_name']) ?></td>
                                        <td><?= htmlspecialchars($client['client_email']) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-success">Sim</span> <!-- Sempre Sim, pois só lista ativos -->
                                        </td>
                                        <td class="text-center">
                                            <?php
                                            // Botão Editar/Criar Plano de Treino
                                            if ($client['active_workout_plan_id']) {
                                                echo "<a href='edit_workout_plan.php?client_id=" . $client['client_id'] . "&workout_plan_id=" . $client['active_workout_plan_id'] . "' class='btn btn-warning btn-sm me-1 mb-1' title='Editar Plano Ativo'><i class='fas fa-edit'></i> <span class='d-none d-md-inline'>Plano</span></a>";
                                            } else {
                                                // Este else {} bloco não será mais alcançado com a nova query,
                                                // mas mantê-lo não causa erro.
                                                echo "<a href='add_workout_plan.php?client_id=" . $client['client_id'] . "' class='btn btn-primary btn-sm me-1 mb-1' title='Criar Novo Plano'><i class='fas fa-plus'></i> <span class='d-none d-md-inline'>Plano</span></a>";
                                            }

                                            // Botão para Registar Nova Avaliação Física
                                            echo "<a href='edit_client_assessment.php?client_id=" . $client['client_id'] . "' class='btn btn-info btn-sm me-1 mb-1' title='Registar Avaliação Física'><i class='fas fa-clipboard-list'></i> <span class='d-none d-md-inline'>Avaliação</span></a>";

                                            // *** NOVO BOTÃO HISTÓRICO ***
                                            echo "<a href='assessment_history.php?client_id=" . $client['client_id'] . "' class='btn btn-secondary btn-sm me-1 mb-1' title='Ver Histórico de Avaliações'><i class='fas fa-history'></i> <span class='d-none d-md-inline'>Histórico</span></a>";
                                            // ****************************
                                            
                                            // Botão Transferir Cliente
                                            echo "<button type='button' class='btn btn-outline-dark btn-sm me-1 mb-1' data-bs-toggle='modal' data-bs-target='#transferClientModal' data-client-id='" . $client['client_id'] . "' data-client-name='" . htmlspecialchars($client['client_name']) . "' title='Transferir Cliente'><i class='fas fa-exchange-alt'></i> <span class='d-none d-md-inline'>Transferir</span></button>";


                                            // Botão Chat
                                            if (isset($client['client_user_id'])) {
                                                echo "<a href='chat.php?user_id=" . $client['client_user_id'] . "' class='btn btn-success btn-sm mb-1' title='Conversar com Cliente'><i class='fas fa-comments'></i> <span class='d-none d-md-inline'>Chat</span></a>";
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div> <!-- /.card-body -->
        </div> <!-- /.card --> 

    </div><!-- /.container -->
</main> <!-- /.main-content -->

<!-- Modal de Transferência de Cliente -->
<div class="modal fade" id="transferClientModal" tabindex="-1" aria-labelledby="transferClientModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="my_clients.php">
        <div class="modal-header">
          <h5 class="modal-title" id="transferClientModalLabel">Transferir Cliente</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>Você está prestes a transferir o cliente <strong id="clientNameToTransfer"></strong>.</p>
          <p><small class="text-muted">O plano de treino ativo deste cliente será atribuído ao novo treinador. Se o novo treinador já tiver um plano ativo para este cliente, esse plano será desativado.</small></p>
          <input type="hidden" name="client_to_transfer_id" id="client_to_transfer_id_input">
          <div class="mb-3">
            <label for="new_trainer_id" class="form-label">Selecionar Novo Treinador:</label>
            <select class="form-select" id="new_trainer_id" name="new_trainer_id" required>
              <option value="">-- Escolha um treinador --</option>
              <?php foreach ($other_trainers as $ot): ?>
                <option value="<?= $ot['id'] ?>"><?= htmlspecialchars($ot['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" name="transfer_client_action" class="btn btn-primary">Confirmar Transferência</button>
        </div>
      </form>
    </div>
  </div>
</div>


<?php
// Inclui o rodapé da página
include 'footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var transferClientModal = document.getElementById('transferClientModal');
    if (transferClientModal) {
        transferClientModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var clientId = button.getAttribute('data-client-id');
            var clientName = button.getAttribute('data-client-name');

            var modalTitle = transferClientModal.querySelector('.modal-title'); 
            var clientNameSpan = transferClientModal.querySelector('#clientNameToTransfer');
            var clientIdInput = transferClientModal.querySelector('#client_to_transfer_id_input');

            clientNameSpan.textContent = clientName;
            clientIdInput.value = clientId;
        });
    }
});
</script>
