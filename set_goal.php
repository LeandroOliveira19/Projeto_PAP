<?php
// filepath: c:\xampp\htdocs\Projeto_PAP\set_goal.php
session_start();

// Verifica se o usuário está logado e se é um cliente
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

include 'db.php'; // Inclui apenas a conexão com a BD por agora

$user_id = $_SESSION['user_id'];
$client_name = $_SESSION['name'] ?? 'Cliente'; // Pode ser usado mais tarde se necessário antes do header.php
$active_page = 'dashboard.php'; // Para manter o item do dashboard ativo na sidebar

$page_message = '';
$message_type = '';

// Buscar client_id
$client_id = null;
try {
    $stmt_get_client_id = $pdo->prepare("SELECT id FROM clients WHERE user_id = ? LIMIT 1");
    $stmt_get_client_id->execute([$user_id]);
    $client_id_data = $stmt_get_client_id->fetch();
    if ($client_id_data) {
        $client_id = $client_id_data['id'];
    } else {
        // Esta mensagem será exibida após o header.php ser incluído
        $page_message = "Erro: Registo de cliente não encontrado.";
        $message_type = 'danger';
    }
} catch (PDOException $e) {
    $page_message = "Erro de base de dados ao buscar informações do cliente.";
    $message_type = 'danger';
    error_log("Erro ao buscar client_id em set_goal.php: " . $e->getMessage());
}

// Processamento do formulário ANTES de qualquer output HTML
if ($client_id && $_SERVER['REQUEST_METHOD'] === 'POST') { // Verifica client_id primeiro
    $goal_description = trim($_POST['goal_description'] ?? '');
    $target_date_str = trim($_POST['target_date'] ?? '');
    $target_date = null;

    if (empty($goal_description)) {
        $page_message = "A descrição do objetivo é obrigatória.";
        $message_type = 'danger';
    } else {
        if (!empty($target_date_str)) {
            $date_obj = DateTime::createFromFormat('Y-m-d', $target_date_str);
            if ($date_obj && $date_obj->format('Y-m-d') === $target_date_str) {
                if ($date_obj < new DateTime('today')) { // Compara com o início do dia atual
                    $page_message = "A data alvo deve ser hoje ou uma data futura.";
                    $message_type = 'danger';
                } else {
                    $target_date = $target_date_str;
                }
            } else {
                $page_message = "Formato de data alvo inválido.";
                $message_type = 'danger';
            }
        }

        if (empty($page_message)) { // Procede apenas se não houver erros de validação
            try {
                $pdo->beginTransaction();

                $stmt_deactivate_old = $pdo->prepare("
                    UPDATE client_goals 
                    SET status = 'cancelled', finished_at = NOW() 
                    WHERE client_id = :client_id AND status = 'active'
                ");
                $stmt_deactivate_old->execute(['client_id' => $client_id]);

                $stmt_insert = $pdo->prepare("
                    INSERT INTO client_goals (client_id, goal_description, target_date, status, created_at) 
                    VALUES (:client_id, :goal_description, :target_date, 'active', NOW())
                ");
                $stmt_insert->execute([
                    'client_id' => $client_id,
                    'goal_description' => $goal_description,
                    'target_date' => $target_date
                ]);

                $pdo->commit();
                $_SESSION['dashboard_message'] = "Novo objetivo definido com sucesso!";
                $_SESSION['dashboard_message_type'] = "success";
                header('Location: dashboard.php'); // Redirecionamento
                exit; // Importante sair após o redirecionamento

            } catch (PDOException $e) {
                $pdo->rollBack();
                $page_message = "Erro ao definir o novo objetivo: " . $e->getMessage();
                $message_type = 'danger';
                error_log("Erro PDO em set_goal.php: " . $e->getMessage());
            }
        }
    }
}

// Agora, após todo o processamento e possíveis redirecionamentos, incluímos os ficheiros de layout
include 'header.php';
include 'sidebar_client.php';
?>

<!-- Conteúdo Principal -->
<main class="main-content">
    <div class="container mt-5">
        <h2><i class="fas fa-plus-circle me-2"></i>Definir Novo Objetivo</h2>
        <p class="text-white">Descreva o seu novo objetivo e, se desejar, defina uma data para o alcançar.</p>

        <?php if (!empty($page_message)): ?>
            <div class="alert alert-<?= htmlspecialchars($message_type) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($page_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($client_id): // Só mostra o formulário se o client_id foi encontrado e não houve redirecionamento ?>
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="set_goal.php">
                        <div class="mb-3">
                            <label for="goal_description" class="form-label">Descrição do Objetivo <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="goal_description" name="goal_description" rows="4" required placeholder="Ex: Perder 5kg, correr 5km sem parar, aumentar a força no supino..."><?= htmlspecialchars($_POST['goal_description'] ?? '') ?></textarea>
                            <div class="form-text">Seja específico sobre o que quer alcançar.</div>
                        </div>

                        <div class="mb-3">
                            <label for="target_date" class="form-label">Data Alvo (Opcional)</label>
                            <input type="date" class="form-control" id="target_date" name="target_date" value="<?= htmlspecialchars($_POST['target_date'] ?? '') ?>" min="<?= date('Y-m-d') ?>">
                            <div class="form-text">Defina um prazo para o seu objetivo, se aplicável.</div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Salvar Objetivo</button>
                            <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-times me-1"></i>Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        <?php elseif (empty($page_message) && !$client_id): // Se client_id não foi encontrado e não há outra mensagem de erro (caso raro, já que $page_message seria setado) ?>
            <div class="alert alert-danger">Não foi possível carregar as informações necessárias para definir um objetivo. Verifique se o seu registo de cliente está ativo.</div>
        <?php endif; ?>

    </div><!-- /.container -->
</main> <!-- /.main-content -->

<?php
include 'footer.php';
?>
