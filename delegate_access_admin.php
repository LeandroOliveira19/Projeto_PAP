<?php
// delegate_access_admin.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
include 'db.php';
include 'header.php';
include_once 'includes/auth_utils.php'; // Para $GLOBALS['available_permissions']
$active_page = 'delegate_access_admin.php'; // Para a sidebar
include 'sidebar_admin.php';

$mensagem = '';
$tipo_mensagem = '';

// $GLOBALS['available_permissions'] já deve estar definido por auth_utils.php
// Se não estiver, pode definir um fallback aqui, mas o ideal é centralizar.
if (!isset($GLOBALS['available_permissions'])) {
    // Fallback, mas idealmente auth_utils.php já definiu isto.
    $GLOBALS['available_permissions'] = [
        'manage_clients' => 'Gerir Clientes (Admin)',
        'manage_classes' => 'Gerir Aulas (Admin)',
        'manage_trainers' => 'Gerir Treinadores (Admin)',
        'manage_nutritionists' => 'Gerir Nutricionistas (Admin)',
        'manage_receptionists' => 'Gerir Recepcionistas (Admin)',
        'view_admin_dashboard' => 'Aceder Painel Admin (Estatísticas)',
    ];
}

$funcionarios_balcao = [];
try {
    // Modifique 'receptionist' se o role do seu funcionário de balcão for diferente
    $stmt_func = $pdo->query("SELECT id, name FROM users WHERE role = 'receptionist' ORDER BY name ASC");
    $funcionarios_balcao = $stmt_func->fetchAll();
} catch (PDOException $e) {
    $mensagem = "Erro ao buscar funcionários: " . $e->getMessage();
    $tipo_mensagem = "danger";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delegar'])) {
    $usuario_delegado_id = filter_input(INPUT_POST, 'usuario_delegado_id', FILTER_VALIDATE_INT);
    $duracao = $_POST['duracao'] ?? '1_hora';
    $permissoes_delegadas_post = $_POST['permissoes'] ?? []; // Array de permissões selecionadas
    $admin_concedente_id = $_SESSION['user_id'];

    if (!$usuario_delegado_id) {
        $mensagem = "Por favor, selecione um funcionário.";
        $tipo_mensagem = "warning";
    } elseif (empty($permissoes_delegadas_post)) {
        $mensagem = "Por favor, selecione pelo menos uma permissão para delegar.";
        $tipo_mensagem = "warning";
    } else {
        $permissoes_delegadas_json = json_encode(array_values($permissoes_delegadas_post)); // Garante que é um array JSON
        try {
            $expira_em_dt = new DateTime();

            switch ($duracao) {
                case '05_minutos':
                    $expira_em_dt->add(new DateInterval('PT5M'));
                    break;
                case '30_minutos':
                    $expira_em_dt->add(new DateInterval('PT30M'));
                    break;
                case '1_hora':
                    $expira_em_dt->add(new DateInterval('PT1H'));
                    break;
                case '2_horas':
                    $expira_em_dt->add(new DateInterval('PT2H'));
                    break;
                case '4_horas':
                    $expira_em_dt->add(new DateInterval('PT4H'));
                    break;
                case 'fim_dia':
                    $expira_em_dt->setTime(23, 59, 59);
                    break;
                default:
                    $expira_em_dt->add(new DateInterval('PT1H'));
            }
            $expira_em_sql = $expira_em_dt->format('Y-m-d H:i:s');

            // Desativar quaisquer delegações ativas anteriores para este mesmo usuário delegado
            $stmt_desativar_antigas = $pdo->prepare("UPDATE access_delegations SET is_active = FALSE, revoked_at = NOW() WHERE delegated_user_id = ? AND is_active = TRUE");
            $stmt_desativar_antigas->execute([$usuario_delegado_id]);

            // Inserir nova delegação
            $stmt_insert = $pdo->prepare("INSERT INTO access_delegations (granting_admin_id, delegated_user_id, expires_at, delegated_permissions, is_active) VALUES (?, ?, ?, ?, TRUE)");
            $stmt_insert->execute([$admin_concedente_id, $usuario_delegado_id, $expira_em_sql, $permissoes_delegadas_json]);

            $nome_delegado_stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
            $nome_delegado_stmt->execute([$usuario_delegado_id]);
            $nome_delegado = $nome_delegado_stmt->fetchColumn();

            $mensagem = "Acesso delegado com sucesso para " . htmlspecialchars($nome_delegado) . " até " . $expira_em_dt->format('d/m/Y H:i:s') . ".";
            $tipo_mensagem = "success";

        } catch (PDOException $e) {
            $mensagem = "Erro ao delegar acesso: " . $e->getMessage();
            $tipo_mensagem = "danger";
            error_log("Erro PDO ao delegar acesso: " . $e->getMessage());
        } catch (Exception $e) { // Catch para erros do DateTime
            $mensagem = "Erro na manipulação da data: " . $e->getMessage();
            $tipo_mensagem = "danger";
            error_log("Erro DateTime ao delegar acesso: " . $e->getMessage());
        }
    }
}
?>

<main class="main-content">
    <div class="container mt-5">
        <h2>Delegar Acesso Temporário</h2>
        <p>Conceda privilégios de administrador a um funcionário do balcão por um período limitado.</p>

        <?php if ($mensagem): ?>
            <div class="alert alert-<?= htmlspecialchars($tipo_mensagem) ?> alert-dismissible fade show" role="alert">
                <?= $mensagem ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" action="delegate_access_admin.php">
                    <div class="mb-3">
                        <label for="usuario_delegado_id" class="form-label">Funcionário do Balcão:</label>
                        <select class="form-select" id="usuario_delegado_id" name="usuario_delegado_id" required>
                            <option value="">-- Selecione um Funcionário --</option>
                            <?php foreach ($funcionarios_balcao as $funcionario): ?>
                                <option value="<?= $funcionario['id'] ?>"><?= htmlspecialchars($funcionario['name']) ?></option>
                            <?php endforeach; ?>
                            <?php if (empty($funcionarios_balcao) && empty($mensagem)): ?>
                                 <option value="" disabled>Nenhum funcionário do balcão encontrado.</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="duracao" class="form-label">Duração da Delegação:</label>
                        <select class="form-select" id="duracao" name="duracao" required>
                            <option value="05_minutos">5 Minutos (Teste)</option>
                            <option value="30_minutos">30 Minutos</option>
                            <option value="1_hora" selected>1 Hora</option>
                            <option value="2_horas">2 Horas</option>
                            <option value="4_horas">4 Horas</option>
                            <option value="fim_dia">Até ao final do dia (23:59)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Permissões a Delegar:</label>
                        <?php if (empty($GLOBALS['available_permissions'])): ?>
                            <p class="text-muted">Nenhuma permissão configurada para delegação.</p>
                        <?php else: ?>
                            <?php foreach ($GLOBALS['available_permissions'] as $key => $description): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissoes[]" value="<?= htmlspecialchars($key) ?>" id="perm_<?= htmlspecialchars($key) ?>">
                                    <label class="form-check-label" for="perm_<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($description) ?></label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="submit" name="delegar" class="btn btn-primary"><i class="fas fa-user-shield me-2"></i>Delegar Acesso</button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>
