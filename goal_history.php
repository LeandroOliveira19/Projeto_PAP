<?php
// filepath: c:\xampp\htdocs\Projeto_PAP\goal_history.php
session_start();

// Verifica se o usuário está logado e se é um cliente
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

include 'db.php';
include 'header.php';

$user_id = $_SESSION['user_id'];
$client_name = $_SESSION['name'] ?? 'Cliente';
$page_error = '';
$completed_goals = [];

// Define a página ativa para a sidebar (pode ser 'dashboard.php' ou uma nova entrada)
$active_page = 'goal_history.php';

// Inclui a sidebar do cliente
include 'sidebar_client.php';

// Buscar histórico de objetivos concluídos
try {
    // Primeiro, obter o client_id
    $stmt_get_client_id = $pdo->prepare("SELECT id FROM clients WHERE user_id = ? LIMIT 1");
    $stmt_get_client_id->execute([$user_id]);
    $client_id = $stmt_get_client_id->fetchColumn();

    if (!$client_id) {
        throw new Exception("Registo de cliente não encontrado.");
    }

    // Buscar objetivos concluídos ou cancelados para este cliente, usando finished_at
    $stmt = $pdo->prepare("
        SELECT id, goal_description, target_date, created_at, finished_at, status 
        FROM client_goals
        WHERE client_id = :client_id AND status IN ('completed', 'cancelled')
        ORDER BY finished_at DESC, created_at DESC 
    ");
    $stmt->execute(['client_id' => $client_id]);
    $completed_goals = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $page_error = "Erro ao carregar histórico de objetivos: " . $e->getMessage();
    error_log($page_error);
} catch (Exception $e) {
    $page_error = "Erro: " . $e->getMessage();
    error_log($page_error);
}

// Helper function to format date
function formatDate($date_str) {
    if (empty($date_str) || $date_str === '0000-00-00' || $date_str === '0000-00-00 00:00:00') {
        return '<span class="text-muted fst-italic">Não definida</span>';
    }
    // Tenta formatar como data e hora, se falhar, tenta só data
    try {
         $dateTime = new DateTime($date_str);
         // Se for apenas data (sem hora), formata só a data
         if ($dateTime->format('H:i:s') === '00:00:00' && strpos($date_str, ' ') === false) {
            return htmlspecialchars($dateTime->format('d/m/Y'));
         }
         return htmlspecialchars($dateTime->format('d/m/Y H:i'));
    } catch (Exception $e) {
         // Fallback para apenas data se a formatação com hora falhar
         try {
             $date = new DateTime($date_str);
             return htmlspecialchars($date->format('d/m/Y'));
         } catch (Exception $e2) {
             return '<span class="text-muted fst-italic">Data inválida</span>';
         }
    }
}
?>

<!-- Conteúdo Principal -->
<main class="main-content">
    <div class="container mt-5">
        <h2><i class="fas fa-history me-2"></i>Histórico de Objetivos</h2>
        <p class="text-white">Veja os objetivos que você já concluiu ou cancelou.</p>

        <?php if (!empty($page_error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($page_error) ?></div>
        <?php elseif (empty($completed_goals)): ?>
            <div class="alert alert-info">Nenhum objetivo concluído ou cancelado encontrado no seu histórico.</div>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($completed_goals as $goal): ?>
                    <?php
                        $status_badge_class = ($goal['status'] === 'completed') ? 'bg-success' : 'bg-warning text-dark';
                        $status_text = ($goal['status'] === 'completed') ? 'Concluído' : 'Cancelado';
                        $finished_date_text = ($goal['status'] === 'completed') ? 'Concluído em' : 'Cancelado em';
                    ?>
                    <div class="list-group-item list-group-item-action flex-column align-items-start">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1"><?= nl2br(htmlspecialchars($goal['goal_description'])) ?></h5>
                            <small class="badge <?= $status_badge_class ?>"><?= $status_text ?></small>
                        </div>
                        <p class="mb-1 small text-muted">Definido em: <?= formatDate($goal['created_at']) ?></p>
                        <p class="mb-1 small text-muted">Data Alvo: <?= formatDate($goal['target_date']) ?></p>
                        <?php if (!empty($goal['finished_at'])): // Usa finished_at ?>
                             <p class="mb-1 small text-muted"><?= $finished_date_text ?>: <?= formatDate($goal['finished_at']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar ao Dashboard</a>
        </div>
    </div><!-- /.container -->
</main> <!-- /.main-content -->

<?php
include 'footer.php';
?>
