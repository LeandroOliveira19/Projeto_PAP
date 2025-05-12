<?php
session_start();

// Verifica se o usuário está logado (qualquer role)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'db.php';
include 'header.php'; // Inclui o cabeçalho e o ícone de perfil do utilizador logado

$viewed_user_id = null;
$profile_data = null;
$page_error = '';

// Pega o ID do utilizador da URL
if (isset($_GET['user_id']) && filter_var($_GET['user_id'], FILTER_VALIDATE_INT)) {
    $viewed_user_id = (int)$_GET['user_id'];
} else {
    $page_error = "ID de utilizador inválido ou não fornecido.";
}

// Se não houver erro no ID, busca os dados do perfil
if ($viewed_user_id && !$page_error) {
    try {
        // Query para buscar dados do utilizador e, se for cliente, alguns dados da tabela clients
        $stmt = $pdo->prepare("
            SELECT
                u.id,
                u.name,
                u.email,
                u.profile_picture,
                u.role,
                u.numero_telemovel,
                c.id as client_table_id,
                c.activity_level,
                c.profession
            FROM users u
            LEFT JOIN clients c ON u.id = c.user_id
            WHERE u.id = :viewed_user_id
        ");
        $stmt->execute(['viewed_user_id' => $viewed_user_id]);
        $profile_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$profile_data) {
            $page_error = "Utilizador não encontrado.";
        }

    } catch (PDOException $e) {
        $page_error = "Erro ao carregar o perfil: " . $e->getMessage();
        error_log("Erro em profile_view.php: " . $e->getMessage());
    }
}

// Define a página ativa para a sidebar do UTILIZADOR LOGADO
$active_page = ''; // Não é uma página principal da sidebar, então pode ficar vazio ou definir um padrão

// Inclui a sidebar correta para o UTILIZADOR LOGADO
$logged_in_user_role = $_SESSION['role'];
if ($logged_in_user_role === 'client') {
    include 'sidebar_client.php';
} elseif ($logged_in_user_role === 'trainer') {
    include 'sidebar_trainer.php';
} elseif ($logged_in_user_role === 'nutritionist') {
    include 'sidebar_nutritionist.php';
} elseif ($logged_in_user_role === 'admin') {
    include 'sidebar_admin.php';
}

function formatRoleName($role) {
    switch ($role) {
        case 'client': return 'Cliente';
        case 'trainer': return 'Treinador';
        case 'nutritionist': return 'Nutricionista';
        case 'admin': return 'Administrador';
        default: return ucfirst($role);
    }
}

?>

<main class="main-content">
    <div class="container mt-5">
        <?php if ($page_error): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($page_error) ?>
                <p class="mt-2"><a href="javascript:history.back()" class="btn btn-secondary btn-sm">Voltar</a></p>
            </div>
        <?php elseif ($profile_data): ?>
            <?php
                $profile_pic_path_view = 'assets/img/profile_pics/';
                $profile_pic_file_view = $profile_data['profile_picture'] ?? null;
                $profile_pic_src_view = 'assets/img/avatars/default.png'; // Default avatar
                if ($profile_pic_file_view && $profile_pic_file_view !== 'default.png' && file_exists($profile_pic_path_view . $profile_pic_file_view)) {
                    $profile_pic_src_view = $profile_pic_path_view . $profile_pic_file_view;
                }
            ?>
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h2 class="h4 mb-0">Perfil de <?= htmlspecialchars($profile_data['name']) ?></h2>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3 mb-md-0">
                            <img src="<?= htmlspecialchars($profile_pic_src_view) ?>?t=<?= time() ?>"
                                 alt="Foto de Perfil de <?= htmlspecialchars($profile_data['name']) ?>"
                                 class="img-thumbnail rounded-circle"
                                 style="width: 180px; height: 180px; object-fit: cover; border: 3px solid var(--bs-primary-border-subtle);">
                        </div>
                        <div class="col-md-8">
                            <h3 class="mb-3"><?= htmlspecialchars($profile_data['name']) ?></h3>
                            <p class="lead fs-6 text-muted"><?= htmlspecialchars(formatRoleName($profile_data['role'])) ?></p>
                            <hr>
                            <dl class="row">
                                <dt class="col-sm-3">Email:</dt>
                                <dd class="col-sm-9"><?= htmlspecialchars($profile_data['email'] ?? 'N/D') ?></dd>

                                <dt class="col-sm-3">Telemóvel:</dt>
                                <dd class="col-sm-9"><?= htmlspecialchars($profile_data['numero_telemovel'] ?? 'N/D') ?></dd>

                                <?php if ($profile_data['role'] === 'client' && $profile_data['client_table_id']): ?>
                                    <dt class="col-sm-3">Nível de Atividade:</dt>
                                    <dd class="col-sm-9"><?= htmlspecialchars($profile_data['activity_level'] ?? 'N/D') ?></dd>

                                    <dt class="col-sm-3">Profissão:</dt>
                                    <dd class="col-sm-9"><?= htmlspecialchars($profile_data['profession'] ?? 'N/D') ?></dd>
                                <?php endif; ?>

                                <?php if ($profile_data['role'] === 'trainer' || $profile_data['role'] === 'nutritionist'): ?>
                                    <!-- Poderia adicionar aqui "Especialidades" ou "Sobre" se esses campos existissem na BD -->
                                <?php endif; ?>
                            </dl>

                            <?php
                            // Lógica para o botão de chat
                            // Não mostrar o botão se o perfil visualizado for o do próprio utilizador logado
                            if ($_SESSION['user_id'] != $viewed_user_id):
                                // Para esta funcionalidade, permitimos iniciar chat com qualquer outro utilizador.
                                // A lógica de visualização e gestão de conversas fica no chat.php.
                                ?>
                                    <a href="chat.php?user_id=<?= $viewed_user_id ?>" class="btn btn-primary mt-3"><i class="fas fa-comments me-1"></i> Iniciar Conversa</a>
                                <?php
                            endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4">
                <a href="javascript:history.back()" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'footer.php'; ?>
