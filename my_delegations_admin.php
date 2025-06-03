<?php
// my_delegations_admin.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
include 'db.php';
include 'header.php';
include_once 'includes/auth_utils.php'; // Para $GLOBALS['available_permissions']
$active_page = 'my_delegations_admin.php'; // Para a sidebar
include 'sidebar_admin.php';

$mensagem = '';
$tipo_mensagem = '';
$admin_id = $_SESSION['user_id'];

// Processar revogação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['revogar_delegacao_id'])) {
    $delegacao_id_revogar = filter_input(INPUT_POST, 'revogar_delegacao_id', FILTER_VALIDATE_INT);
    if ($delegacao_id_revogar) {
        try {
            $stmt_revogar = $pdo->prepare("UPDATE access_delegations SET is_active = FALSE, revoked_at = NOW() WHERE id = ? AND granting_admin_id = ?");
            $stmt_revogar->execute([$delegacao_id_revogar, $admin_id]);
            if ($stmt_revogar->rowCount() > 0) {
                $mensagem = "Delegação revogada com sucesso.";
                $tipo_mensagem = "success";
            } else {
                $mensagem = "Não foi possível revogar a delegação (pode já estar inativa ou não pertence a si).";
                $tipo_mensagem = "warning";
            }
        } catch (PDOException $e) {
            $mensagem = "Erro ao revogar delegação: " . $e->getMessage();
            $tipo_mensagem = "danger";
            error_log("Erro PDO ao revogar delegação: " . $e->getMessage());
        }
    }
}

// Buscar delegações ativas concedidas por este admin
$delegacoes_ativas = [];
try {
    $stmt_deleg = $pdo->prepare("
        SELECT ad.id, u_delegado.name as nome_delegado, ad.expires_at, ad.delegated_permissions
        FROM access_delegations ad
        JOIN users u_delegado ON ad.delegated_user_id = u_delegado.id
        WHERE ad.granting_admin_id = ? AND ad.is_active = TRUE AND ad.expires_at > NOW()
        ORDER BY ad.expires_at ASC
    ");
    $stmt_deleg->execute([$admin_id]);
    $delegacoes_ativas = $stmt_deleg->fetchAll();
} catch (PDOException $e) {
    $mensagem = "Erro ao buscar delegações: " . $e->getMessage();
    $tipo_mensagem = "danger";
}

?>
<main class="main-content">
    <div class="container mt-5">
        <h2>Minhas Delegações de Acesso Ativas</h2>
        <p>Veja e gerencie as delegações de acesso que você concedeu.</p>

        <?php if ($mensagem): ?>
            <div class="alert alert-<?= htmlspecialchars($tipo_mensagem) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagem) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <?php if (empty($delegacoes_ativas) && empty($mensagem)): ?>
                    <div class="alert alert-info">Você não possui delegações de acesso ativas no momento.</div>
                <?php elseif (!empty($delegacoes_ativas)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Funcionário Delegado</th>
                                    <th>Expira em</th>
                                    <th>Permissões Delegadas</th>
                                    <th class="text-end">Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($delegacoes_ativas as $delegacao): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($delegacao['nome_delegado']) ?></td>
                                        <td><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($delegacao['expires_at']))) ?></td>
                                        <td>
                                            <?php
                                            if (!empty($delegacao['delegated_permissions'])) {
                                                $perm_keys = json_decode($delegacao['delegated_permissions'], true);
                                                if (is_array($perm_keys) && !empty($perm_keys)) {
                                                    $perm_descriptions = [];
                                                    foreach ($perm_keys as $key) {
                                                        $perm_descriptions[] = htmlspecialchars($GLOBALS['available_permissions'][$key] ?? $key);
                                                    }
                                                    echo implode(',<br>', $perm_descriptions);
                                                } else {
                                                    echo '<span class="text-muted">Nenhuma específica</span>';
                                                }
                                            } else {
                                                echo '<span class="text-muted">Nenhuma específica</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="text-end">
                                            <form method="POST" action="my_delegations_admin.php" onsubmit="return confirm('Tem certeza que deseja revogar esta delegação?');" style="display: inline;">
                                                <input type="hidden" name="revogar_delegacao_id" value="<?= $delegacao['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-user-slash me-1"></i>Revogar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                 <div class="mt-3">
                    <a href="delegate_access_admin.php" class="btn btn-success"><i class="fas fa-plus-circle me-2"></i>Nova Delegação</a>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include 'footer.php'; ?>
