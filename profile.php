<?php
// filepath: c:\xampp\htdocs\Projeto_PAP\profile.php
session_start();

// Verifica se o usuário está logado (qualquer role)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Inclui a conexão com o banco de dados
include 'db.php';

// Inclui o cabeçalho da página
include 'header.php'; // header.php includes the profile icon

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role']; // Pega o role da sessão
$message = '';
$message_type = ''; // 'success' or 'danger'
$page_error = ''; // Para erros de fetch

// --- Lógica de POST (Universal) ---
// Handle Profile Picture Upload (Comum a todos os roles)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_picture'])) {
    // ... (código de upload de foto - sem alterações) ...
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture']; $upload_dir = 'assets/img/profile_pics/';
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif']; $max_size = 5 * 1024 * 1024;
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
        if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            try {
                $stmt_old_pic = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?"); $stmt_old_pic->execute([$user_id]);
                $old_pic_filename = $stmt_old_pic->fetchColumn();
                if ($old_pic_filename && file_exists($upload_dir . $old_pic_filename)) { unlink($upload_dir . $old_pic_filename); }
            } catch (PDOException $e) { error_log("Error deleting old pic: " . $e->getMessage()); }
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                try {
                    $stmt_update = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?"); $stmt_update->execute([$new_filename, $user_id]);
                    $_SESSION['profile_picture'] = $new_filename; $message = 'Foto atualizada!'; $message_type = 'success';
                } catch (PDOException $e) { $message = 'Erro BD (foto).'; $message_type = 'danger'; error_log("DB Error updating profile picture: " . $e->getMessage()); if (file_exists($upload_path)) { unlink($upload_path); } }
            } else { $message = 'Erro ao mover ficheiro.'; $message_type = 'danger'; }
        } elseif ($file['size'] > $max_size) { $message = 'Ficheiro > 5MB.'; $message_type = 'danger'; } else { $message = 'Tipo inválido (JPG, PNG, GIF).'; $message_type = 'danger'; }
    } elseif (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) { $message = 'Erro upload: ' . $_FILES['profile_picture']['error']; $message_type = 'danger'; }
}

// Handle Details Update (Condicional baseado no role)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_details'])) {
    // Campos comuns
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $numero_telemovel = trim(filter_input(INPUT_POST, 'numero_telemovel', FILTER_SANITIZE_STRING));
    $errors = [];

    // Campos específicos do cliente
    $height = null;
    $weight = null;
    $activity_level = null;
    // Campos específicos do cliente (Número de Sócio é automático, não vem do POST)
    // $membership_number = null; // Removido, pois será o client_table_id
    $profession = null;
    $gym_frequency = null;
    $health_issues = null;
    
    if ($user_role === 'client') {
        $height_str = str_replace(',', '.', trim($_POST['height'] ?? ''));
        $weight_str = str_replace(',', '.', trim($_POST['weight'] ?? ''));
        $activity_level = trim(filter_input(INPUT_POST, 'activity_level', FILTER_SANITIZE_STRING));
        $height = ($height_str !== '' && is_numeric($height_str)) ? (float)$height_str : null;
        $weight = ($weight_str !== '' && is_numeric($weight_str)) ? (float)$weight_str : null;
        // Número de Sócio não é editável pelo cliente, não precisa pegar do POST.
        $profession = trim(filter_input(INPUT_POST, 'profession', FILTER_SANITIZE_STRING));
        $gym_frequency = trim(filter_input(INPUT_POST, 'gym_frequency', FILTER_SANITIZE_STRING)); // Este agora virá do select
        $health_issues = trim(filter_input(INPUT_POST, 'health_issues', FILTER_SANITIZE_STRING));
    }

    // Validações comuns
    if (empty($name)) $errors[] = "Nome obrigatório.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "E-mail inválido.";
    if (strlen($numero_telemovel) > 25) $errors[] = "Número de telemóvel demasiado longo.";


    // Check email uniqueness (comum)
    if (empty($errors)) {
        try {
            $stmt_check_email = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt_check_email->execute([$email, $user_id]);
            if ($stmt_check_email->fetch()) { $errors[] = "Este e-mail já está em uso por outra conta."; }
        } catch (PDOException $e) { $errors[] = "Erro BD (email check)."; error_log("DB Error checking email: " . $e->getMessage()); }
    }

    // Se não houver erros, atualiza o banco de dados
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $params_user = [$name, $email];
            $sql_user = "UPDATE users SET name = ?, email = ?";
            $params_user[] = ($numero_telemovel === '') ? null : $numero_telemovel;
            $sql_user .= ", numero_telemovel = ?";
            $sql_user .= " WHERE id = ?";
            $params_user[] = $user_id;
            $stmt_user = $pdo->prepare($sql_user);
            $stmt_user->execute($params_user);

            // Atualiza tabela 'clients' (APENAS se for cliente)
            if ($user_role === 'client') {
                $stmt_client_check = $pdo->prepare("SELECT id FROM clients WHERE user_id = ?");
                $stmt_client_check->execute([$user_id]);
                if ($stmt_client_check->fetch()) {
                    // *** ATUALIZADO: Inclui os novos campos no UPDATE ***
                    // Removido membership_number do update
                    $stmt_client = $pdo->prepare("
                        UPDATE clients SET
                            height = ?, weight = ?, activity_level = ?, 
                            profession = ?, gym_frequency = ?, health_issues = ?
                        WHERE user_id = ?
                    ");
                    $stmt_client->execute([
                        $height, $weight, $activity_level,
                        ($profession === '') ? null : $profession,
                        ($gym_frequency === '') ? null : $gym_frequency,
                        ($health_issues === '') ? null : $health_issues,
                        $user_id
                    ]);
                } else {
                    // *** ATUALIZADO: Inclui os novos campos no INSERT ***
                    // Removido membership_number do insert (será NULL por defeito na BD se a coluna existir e permitir NULL)
                    // Se a coluna foi removida, não há problema.
                    $stmt_client = $pdo->prepare("
                        INSERT INTO clients (user_id, height, weight, activity_level, profession, gym_frequency, health_issues)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt_client->execute([$user_id, $height, $weight, $activity_level, ($profession === '') ? null : $profession, ($gym_frequency === '') ? null : $gym_frequency, ($health_issues === '') ? null : $health_issues]);
                }
            }

            $pdo->commit();

            if ($_SESSION['name'] !== $name) { $_SESSION['name'] = $name; }

            $message = 'Detalhes atualizados com sucesso!'; $message_type = 'success';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = 'Erro BD ao atualizar detalhes.'; $message_type = 'danger';
            error_log("DB Error updating profile details for role {$user_role}: " . $e->getMessage());
        }
    } else {
        $message = implode('<br>', $errors); $message_type = 'danger';
    }
}
// --- FIM: Lógica de POST ---


// --- Fetch Data (Condicional baseado no role) ---
$profile_data = []; // Inicializa
try {
    $sql = "SELECT u.name, u.email, u.numero_telemovel, u.profile_picture";
    $params = [$user_id];

    // Adiciona JOIN e colunas específicas para clientes
    if ($user_role === 'client') {
        // *** ATUALIZADO: Seleciona c.id como client_table_id e os outros campos ***
        // A coluna `membership_number` foi removida da tabela, então não é mais selecionada.
        $sql .= ", c.id as client_table_id, c.height, c.weight, c.activity_level, c.profession, c.gym_frequency, c.health_issues ";
        $sql .= " FROM users u LEFT JOIN clients c ON u.id = c.user_id WHERE u.id = ?";
    } else {
        // Para outros roles, só busca da tabela users
        $sql .= " FROM users u WHERE u.id = ?";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $profile_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile_data) { throw new Exception("Dados do utilizador não encontrados."); }

} catch (Exception $e) {
    $page_error = 'Erro ao carregar dados do perfil: ' . $e->getMessage();
    error_log("Error fetching profile data for user $user_id (role: $user_role): " . $e->getMessage());
    $profile_data = []; // Limpa para evitar erros no HTML
}

// Define profile picture path for display (comum - sem alterações)
$profile_pic_path = 'assets/img/profile_pics/';
$profile_pic_file_display = $profile_data['profile_picture'] ?? null;
$profile_pic_src_display = 'assets/img/default_avatar.png';
if ($profile_pic_file_display && file_exists($profile_pic_path . $profile_pic_file_display)) {
    $profile_pic_src_display = $profile_pic_path . $profile_pic_file_display;
}

// Define active page for sidebar (comum - sem alterações)
$active_page = basename($_SERVER['PHP_SELF']); // Será 'profile.php'

?>

<?php
// Inclusão condicional da sidebar (sem alterações)
if ($user_role === 'client') {
    include 'sidebar_client.php';
} elseif ($user_role === 'trainer') {
    include 'sidebar_trainer.php';
} elseif ($user_role === 'nutritionist') {
    if (file_exists('sidebar_nutritionist.php')) {
        include 'sidebar_nutritionist.php';
    } else { echo "<!-- Erro: sidebar_nutritionist.php não encontrado. -->"; }
} elseif ($user_role === 'admin') {
    if (file_exists('sidebar_admin.php')) {
        include 'sidebar_admin.php';
    } else { echo "<!-- Erro: sidebar_admin.php não encontrado. -->"; }
} else { echo "<!-- Role desconhecido ou não definido - Nenhuma sidebar específica. -->"; }
?>


<!-- Conteúdo Principal -->
<main class="main-content">
    <!-- Título Dinâmico (sem alterações) -->
    <h2>
        <?php
            if ($user_role === 'client') echo "Meu Perfil e Definições";
            elseif ($user_role === 'trainer') echo "Perfil do Treinador";
            elseif ($user_role === 'nutritionist') echo "Perfil do Nutricionista";
            elseif ($user_role === 'admin') echo "Perfil do Administrador";
            else echo "Meu Perfil";
        ?>
    </h2>

    <?php if (!empty($page_error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($page_error) ?></div>
    <?php else: ?>

        <?php if ($message): ?>
            <div class="alert alert-<?= htmlspecialchars($message_type) ?> alert-dismissible fade show" role="alert">
                <?= $message // Mensagem já pode conter HTML (<br>), não usar htmlspecialchars aqui ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- === SECÇÃO DE VISUALIZAÇÃO (Comum + Condicional Cliente) === -->
        <div id="profile-view-section">
            <div class="row g-4 justify-content-center">
                <!-- Card Único: Foto, Nome, Email, Telemóvel e Botões -->
                <div class="col-lg-6 col-md-8">
                    <div class="card text-center shadow-sm mb-4">
                        <div class="card-body">
                            <img src="<?= htmlspecialchars($profile_pic_src_display) ?>?t=<?= time() ?>" alt="Foto de Perfil" class="img-thumbnail rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover; border: 3px solid var(--bs-primary-border-subtle);">
                            <h5 class="card-title"><?= htmlspecialchars($profile_data['name'] ?? 'N/D') ?></h5>
                            <p class="card-text text-muted mb-1"><?= htmlspecialchars($profile_data['email'] ?? 'N/D') ?></p>
                            <p class="card-text text-muted">
                                <i class="fas fa-phone-alt fa-fw me-1"></i><?= htmlspecialchars($profile_data['numero_telemovel'] ?? 'N/D') ?>
                            </p>
                            <button type="button" id="edit-profile-button" class="btn btn-outline-primary btn-sm mt-2">
                                <i class="fas fa-cog me-1"></i> Definições do Perfil
                            </button>
                            <?php if ($user_role === 'client'): // Botão Estatísticas SÓ para clientes ?>
                                <a href="client_statistics.php" class="btn btn-outline-info btn-sm mt-2">
                                    <i class="fas fa-chart-line me-1"></i> Ver Estatísticas
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- === FIM SECÇÃO DE VISUALIZAÇÃO === -->


        <!-- === SECÇÃO DE DEFINIÇÕES (Escondida por padrão - Condicional Cliente) === -->
        <div id="settings-section" style="display: none;">
             <h3 class="mb-4">Definições do Perfil</h3>
             <div class="row g-4">
                <!-- Coluna da Foto e Upload (Comum - sem alterações) -->
                <div class="col-lg-4 col-md-5">
                    <div class="card text-center shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Alterar Foto de Perfil</h5>
                            <img src="<?= htmlspecialchars($profile_pic_src_display) ?>?t=<?= time() ?>" alt="Foto Atual" class="img-thumbnail rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                            <form action="profile.php" method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="profile_picture_input" class="form-label small text-muted">Nova Foto (JPG, PNG, GIF - Máx 5MB)</label>
                                    <input class="form-control form-control-sm" type="file" id="profile_picture_input" name="profile_picture" accept="image/jpeg, image/png, image/gif">
                                </div>
                                <button type="submit" name="update_picture" class="btn btn-secondary btn-sm"> <i class="fas fa-upload me-1"></i> Carregar </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Coluna dos Detalhes (Formulário Condicional) -->
                <div class="col-lg-8 col-md-7">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Editar Detalhes Pessoais</h5>
                            <form action="profile.php" method="POST">
                                <div class="row g-3">
                                    <!-- Campos Comuns -->
                                    <div class="col-md-6"> <label for="name" class="form-label">Nome</label> <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($profile_data['name'] ?? '') ?>" required> </div>
                                    <div class="col-md-6"> <label for="email" class="form-label">E-mail</label> <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($profile_data['email'] ?? '') ?>" required> </div>
                                    <div class="col-md-6">
                                        <label for="numero_telemovel" class="form-label">Telemóvel</label>
                                        <input type="tel" inputmode="tel" class="form-control" id="numero_telemovel" name="numero_telemovel" value="<?= htmlspecialchars($profile_data['numero_telemovel'] ?? '') ?>" placeholder="Ex: +351 912345678">
                                    </div>

                                    <?php if ($user_role === 'client'): // Campos SÓ para clientes ?>
                                        <div class="col-md-6"> <label for="height" class="form-label">Altura (m)</label> <input type="text" inputmode="decimal" class="form-control" id="height" name="height" placeholder="Ex: 1.75" value="<?= htmlspecialchars(isset($profile_data['height']) ? number_format($profile_data['height'], 2, ',', '') : '') ?>"> </div>
                                        <div class="col-md-6"> <label for="weight" class="form-label">Peso (kg)</label> <input type="text" inputmode="decimal" class="form-control" id="weight" name="weight" placeholder="Ex: 70,5" value="<?= htmlspecialchars(isset($profile_data['weight']) ? number_format($profile_data['weight'], 1, ',', '') : '') ?>"> </div>
                                        
                                        <!-- *** NOVOS CAMPOS PARA CLIENTE *** -->
                                        <!-- Número de Sócio agora é readonly e usa client_table_id -->
                                        <div class="col-md-6">
                                            <label for="membership_number_display" class="form-label">Número de Sócio</label>
                                            <input type="text" class="form-control" id="membership_number_display" value="<?= htmlspecialchars($profile_data['client_table_id'] ?? 'N/A') ?>" readonly>
                                            <!-- O campo 'membership_number' não é mais enviado no POST -->
                                        </div>
                                        <div class="col-md-6">
                                            <label for="profession" class="form-label">Profissão</label>
                                            <input type="text" class="form-control" id="profession" name="profession" value="<?= htmlspecialchars($profile_data['profession'] ?? '') ?>" placeholder="Opcional">
                                        </div>
                                        <div class="col-md-12">
                                            <!-- *** ALTERADO PARA SELECT *** -->
                                            <label for="gym_frequency" class="form-label">Frequência no Ginásio</label>
                                            <select class="form-select" id="gym_frequency" name="gym_frequency">
                                                <option value="" <?= empty($profile_data['gym_frequency']) ? 'selected' : '' ?>>-- Selecione a frequência --</option>
                                                <option value="1 vez por semana" <?= ($profile_data['gym_frequency'] ?? '') == '1 vez por semana' ? 'selected' : '' ?>>1 vez por semana</option>
                                                <option value="2 vezes por semana" <?= ($profile_data['gym_frequency'] ?? '') == '2 vezes por semana' ? 'selected' : '' ?>>2 vezes por semana</option>
                                                <option value="3 vezes por semana" <?= ($profile_data['gym_frequency'] ?? '') == '3 vezes por semana' ? 'selected' : '' ?>>3 vezes por semana</option>
                                                <option value="4 vezes por semana" <?= ($profile_data['gym_frequency'] ?? '') == '4 vezes por semana' ? 'selected' : '' ?>>4 vezes por semana</option>
                                                <option value="5 vezes por semana" <?= ($profile_data['gym_frequency'] ?? '') == '5 vezes por semana' ? 'selected' : '' ?>>5 vezes por semana</option>
                                                <option value="6 vezes por semana" <?= ($profile_data['gym_frequency'] ?? '') == '6 vezes por semana' ? 'selected' : '' ?>>6 vezes por semana</option>
                                                <option value="Todos os dias" <?= ($profile_data['gym_frequency'] ?? '') == 'Todos os dias' ? 'selected' : '' ?>>Todos os dias</option>
                                            </select>
                                        </div>
                                        <div class="col-md-12">
                                            <label for="health_issues" class="form-label">Problemas de Saúde Relevantes</label>
                                            <textarea class="form-control" id="health_issues" name="health_issues" rows="3" placeholder="Indique alergias, lesões, condições médicas, etc."><?= htmlspecialchars($profile_data['health_issues'] ?? '') ?></textarea>
                                        </div>
                                        <div class="col-md-6"> <label for="activity_level" class="form-label">Nível Atividade</label> <select class="form-select" id="activity_level" name="activity_level"> <option value="" <?= empty($profile_data['activity_level']) ? 'selected' : '' ?>>-- Selecione --</option> <option value="Sedentário" <?= ($profile_data['activity_level'] ?? '') == 'Sedentário' ? 'selected' : '' ?>>Sedentário</option> <option value="Leve" <?= ($profile_data['activity_level'] ?? '') == 'Leve' ? 'selected' : '' ?>>Leve</option> <option value="Moderado" <?= ($profile_data['activity_level'] ?? '') == 'Moderado' ? 'selected' : '' ?>>Moderado</option> <option value="Ativo" <?= ($profile_data['activity_level'] ?? '') == 'Ativo' ? 'selected' : '' ?>>Ativo</option> <option value="Muito Ativo" <?= ($profile_data['activity_level'] ?? '') == 'Muito Ativo' ? 'selected' : '' ?>>Muito Ativo</option> </select> </div>
                                    <?php endif; ?>
                                </div>
                                <hr class="my-4">
                                <div class="d-flex justify-content-between">
                                    <button type="submit" name="update_details" class="btn btn-primary"> <i class="fas fa-save me-1"></i> Guardar </button>
                                    <button type="button" id="cancel-edit-button" class="btn btn-secondary"> <i class="fas fa-times me-1"></i> Cancelar </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div> <!-- Fim da .row (settings-section) -->
        </div>
        <!-- === FIM SECÇÃO DE DEFINIÇÕES === -->

    <?php endif; // End else for page_error check ?>

</main> <!-- Fim .main-content -->

<!-- JavaScript para alternar visibilidade (sem alterações) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const viewSection = document.getElementById('profile-view-section');
    const settingsSection = document.getElementById('settings-section');
    const editButton = document.getElementById('edit-profile-button');
    const cancelButton = document.getElementById('cancel-edit-button');
    if (viewSection && settingsSection && editButton && cancelButton) {
        editButton.addEventListener('click', function() { viewSection.style.display = 'none'; settingsSection.style.display = 'block'; });
        cancelButton.addEventListener('click', function() { settingsSection.style.display = 'none'; viewSection.style.display = 'block'; });
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($message || !empty($page_error) && empty($profile_data))): ?>
            // Mantém a secção de definições visível se houve um POST com erro ou sucesso
            viewSection.style.display = 'none'; settingsSection.style.display = 'block';
        <?php endif; ?>
    } else { console.error("Erro: Elementos essenciais (view/settings/buttons) não encontrados."); }
});
</script>
<!-- FIM JAVASCRIPT VISIBILIDADE -->

<?php
// Inclui o rodapé da página
include 'footer.php';
?>
