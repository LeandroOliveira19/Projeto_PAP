<?php
session_start();
include 'db.php'; // Include database connection

// Definir o fuso horário de referência. Use 'UTC' ou o fuso horário padrão da sua aplicação.
// Exemplo: define('APP_TIMEZONE', 'Europe/Lisbon'); ou define('APP_TIMEZONE', 'UTC');
// Se já tiver date_default_timezone_set() num ficheiro de configuração global, pode não ser necessário aqui.
// Para este exemplo, vamos usar 'UTC' explicitamente nas comparações.
const REFERENCE_TIMEZONE = 'UTC'; // Ou o fuso horário que usou para gerar a data de expiração

$token = $_GET['token'] ?? null;
$message = '';
$message_type = ''; // 'success' or 'danger'
$show_form = false;
$user_id = null;

if (empty($token)) {
    $message = "Token de recuperação inválido ou ausente.";
    $message_type = 'danger';
} else {
    try {
        // Find the user by the reset token
        $stmt = $pdo->prepare("SELECT id, reset_token_expires_at FROM users WHERE reset_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if ($user) {
            // Check if the token has expired using DateTime objects for robust comparison
            $expiration_string_from_db = $user['reset_token_expires_at'];
            $now = new DateTime("now", new DateTimeZone(REFERENCE_TIMEZONE));
            $expires_at_dt = null;
            $token_expired = true; // Assume expired until proven valid

            if ($expiration_string_from_db) {
                try {
                    // Assume que a string da BD está no fuso horário de referência ou pode ser interpretada como tal.
                    // Se a string da BD já estiver em UTC, new DateTimeZone(REFERENCE_TIMEZONE) é redundante mas inofensivo.
                    // Se a string da BD estiver num fuso local e REFERENCE_TIMEZONE for UTC,
                    // precisa garantir que a string foi guardada corretamente como UTC em forgot_password.php.
                    $expires_at_dt = new DateTime($expiration_string_from_db, new DateTimeZone(REFERENCE_TIMEZONE));
                    if ($expires_at_dt < $now) {
                        $token_expired = true;
                    } else {
                        $token_expired = false;
                    }
                } catch (Exception $e) {
                    // Falha ao criar DateTime, assume expirado ou inválido
                    error_log("Erro ao parsear reset_token_expires_at: " . $e->getMessage() . " para valor: " . $expiration_string_from_db);
                    $token_expired = true;
                    $message = "Formato da data de expiração do token inválido.";
                    $message_type = 'danger';
                }
            }


            if ($token_expired) {
                if (empty($message)) { // Só define mensagem de expiração se não houver outra de formato inválido
                    $message = "O token de recuperação expirou. Por favor, solicite um novo.";
                    $message_type = 'danger';
                }
                // Optionally clear the expired token
                $clear_stmt = $pdo->prepare("UPDATE users SET reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?");
                $clear_stmt->execute([$user['id']]);
            } else {
                // Token is valid and not expired
                $show_form = true;
                $user_id = $user['id']; // Store user ID for the form processing
            }
        } else {
            $message = "Token de recuperação inválido ou já utilizado.";
            $message_type = 'danger';
        }
    } catch (PDOException $e) {
        $message = "Erro na base de dados ao validar o token. Tente novamente mais tarde.";
        $message_type = 'danger';
        error_log("Database error during token validation: " . $e->getMessage());
    }
}

// Handle the form submission for changing the password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $submitted_token = $_POST['token'] ?? null;
    $new_password = $_POST['new_password'] ?? null;
    $confirm_password = $_POST['confirm_password'] ?? null;

    // Re-validate the token on POST for security
    if (empty($submitted_token)) {
         $message = "Token ausente no formulário.";
         $message_type = 'danger';
         $show_form = false; // Hide form if token is missing on post
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, reset_token_expires_at FROM users WHERE reset_token = ?");
            $stmt->execute([$submitted_token]);
            $user = $stmt->fetch();

            $token_still_valid_on_post = false;
            if ($user && $user['reset_token_expires_at']) {
                try {
                    $expiration_string_from_db_post = $user['reset_token_expires_at'];
                    $now_post = new DateTime("now", new DateTimeZone(REFERENCE_TIMEZONE));
                    $expires_at_dt_post = new DateTime($expiration_string_from_db_post, new DateTimeZone(REFERENCE_TIMEZONE));

                    if ($expires_at_dt_post >= $now_post) {
                        $token_still_valid_on_post = true;
                    }
                } catch (Exception $e) {
                    error_log("Erro ao parsear reset_token_expires_at no POST: " . $e->getMessage() . " para valor: " . $expiration_string_from_db_post);
                    // Mantém $token_still_valid_on_post como false
                }
            }

            if (!$token_still_valid_on_post) {
                $message = "Token inválido, expirado ou já utilizado ao submeter. Por favor, solicite um novo.";
                $message_type = 'danger';
                $show_form = false; // Hide form if token invalid on post
            } else {
                 // Token is still valid, proceed with password check
                 $user_id = $user['id']; // Get user ID again

                if (empty($new_password) || empty($confirm_password)) {
                    $message = "Por favor, preencha ambos os campos de senha.";
                    $message_type = 'danger';
                    $show_form = true; // Keep form visible
                } elseif ($new_password !== $confirm_password) {
                    $message = "As senhas não coincidem.";
                    $message_type = 'danger';
                    $show_form = true; // Keep form visible
                } else {
                    // Passwords match, hash the new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                    // Update the password and clear the reset token
                    $update_stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires_at = NULL, first_login = 0 WHERE id = ?");
                    if ($update_stmt->execute([$hashed_password, $user_id])) {
                        $message = "Senha redefinida com sucesso! Pode agora fazer login com a nova senha.";
                        $message_type = 'success';
                        $show_form = false; // Hide form on success
                    } else {
                         $message = "Ocorreu um erro ao atualizar a sua senha. Tente novamente.";
                         $message_type = 'danger';
                         $show_form = true; // Keep form visible on error
                    }
                }
            }

        } catch (PDOException $e) {
             $message = "Erro na base de dados ao redefinir a senha. Tente novamente mais tarde.";
             $message_type = 'danger';
             error_log("Database error during password reset update: " . $e->getMessage());
             $show_form = true; // Keep form visible on DB error
        }
    }
}

?>

<?php include 'header.php'; // Inclui o cabeçalho ?>

<style>
    /* Estilos para o corpo da página (mantidos, com ajuste no overlay) */
    /* O body já é estilizado pelo header.php ou CSS global, mas podemos sobrepor se necessário */
    body.reset-password-page { /* Adiciona uma classe específica para evitar conflitos */
        font-family: 'Poppins', sans-serif;
        background: url('assets/img/login_background.jpg') no-repeat center center fixed;
        background-size: cover;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
        position: relative;
        color: var(--bs-body-color, #212529);
    }
    body.reset-password-page::before { /* Overlay escuro */
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        z-index: 1;
    }

    /* Container principal do formulário (Modificado para usar a classe do forgot_password.php) */
    .forgot-container { /* Usando a classe do forgot_password.php para consistência */
        position: relative;
        z-index: 2;
        background-color: rgba(255, 255, 255, 0.98);
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        width: 100%;
        max-width: 450px;
        animation: fadeInUp 0.5s ease-out;
    }

    /* Animação de entrada suave */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Título (Modificado para usar a classe do forgot_password.php) */
    .forgot-container h2 {
        margin-bottom: 25px;
        color: var(--bs-primary, #0d6efd);
        font-weight: 700;
        text-align: center;
        font-size: 2rem;
    }

    /* Estilo para o parágrafo descritivo (Modificado) */
    .forgot-container p.text-muted {
        text-align: center;
        margin-bottom: 30px;
        font-size: 1rem;
        color: var(--bs-secondary-color, #6c757d);
    }

    /* Estilos dos labels (Modificado) */
    .forgot-container .form-label { /* Adicionado .forgot-container para especificidade */
        font-weight: 600;
        color: var(--bs-emphasis-color, #212529);
        margin-bottom: 0.5rem;
    }

    /* Estilos dos inputs (Modificado) */
    .forgot-container .form-control { /* Adicionado .forgot-container para especificidade */
        border-radius: 8px;
        border: 1px solid var(--bs-border-color, #ced4da);
        padding: 0.85rem 1.2rem;
        font-size: 1rem;
        transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }

    .forgot-container .form-control:focus { /* Adicionado .forgot-container para especificidade */
        border-color: var(--bs-primary-border-subtle, #b9d0ff);
        box-shadow: 0 0 0 0.25rem var(--bs-primary-bg-subtle, rgba(13, 110, 253, 0.25));
        outline: 0;
    }

    /* Estilos do botão (Melhorado) */
    .forgot-container .btn-primary {
        padding: 0.9rem;
        font-weight: 600;
        border-radius: 8px;
        background-color: var(--bs-primary, #0d6efd);
        border-color: var(--bs-primary, #0d6efd);
        transition: background-color 0.2s ease, border-color 0.2s ease, transform 0.1s ease, box-shadow 0.2s ease;
    }

    .forgot-container .btn-primary:hover {
        background-color: var(--bs-primary-hover, #0b5ed7);
        border-color: var(--bs-primary-hover, #0b5ed7);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .forgot-container .btn-primary:active {
         transform: translateY(0);
         box-shadow: none;
    }

    /* Links no rodapé do container (Modificado) */
    .forgot-container .text-center a {
        color: var(--bs-link-color, #0d6efd);
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s ease;
    }
    .forgot-container .text-center a:hover {
        color: var(--bs-link-hover-color, #0a58ca);
        text-decoration: underline;
    }

    /* Estilos para a mensagem de alerta (Bootstrap padrão, mas garantindo visibilidade) */
    .forgot-container .alert {
        margin-bottom: 20px;
        border-radius: 8px;
    }

    /* Ajustes para modo escuro (se usar a classe .dark-mode no body) */
    .dark-mode .forgot-container {
        background-color: rgba(52, 58, 64, 0.98);
        color: var(--bs-gray-300, #dee2e6);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    }
    .dark-mode .forgot-container h2 { color: var(--bs-primary-text-emphasis, #6ea8fe); } /* Ajustado para usar cor de texto de destaque primário */
    .dark-mode .forgot-container p.text-muted { color: var(--bs-secondary-text-emphasis, #a1a8b0); } /* Ajustado */
    .dark-mode .forgot-container .form-label { color: var(--bs-body-color, #dee2e6); } /* Ajustado */
    .dark-mode .forgot-container .form-control {
        background-color: var(--bs-tertiary-bg, #3A4045); /* Fundo mais sutil */
        color: var(--bs-body-color, #dee2e6);
        border-color: var(--bs-border-color-translucent, rgba(255,255,255,0.15)); /* Borda translúcida */
    }
    .dark-mode .forgot-container .form-control:focus {
        border-color: var(--bs-primary, #0d6efd); /* Mantém a cor primária no foco */
        box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25); /* Usa RGB para sombra */
    }
    .dark-mode .forgot-container .btn-primary {
        background-color: var(--bs-primary, #0d6efd);
        border-color: var(--bs-primary, #0d6efd);
        color: var(--bs-light, #f8f9fa); /* Garante texto claro no botão primário */
    }
    .dark-mode .forgot-container .btn-primary:hover {
        background-color: var(--bs-primary-hover, #0b5ed7); /* Cor primária mais escura no hover */
        border-color: var(--bs-primary-hover, #0b5ed7);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.4);
    }
    .dark-mode .forgot-container .text-center a { color: var(--bs-link-color, #6ea8fe); } /* Ajustado */
    .dark-mode .forgot-container .text-center a:hover { color: var(--bs-link-hover-color, #8bb9fe); } /* Ajustado */

</style>

<!-- Adiciona a classe ao body para que os estilos específicos da página sejam aplicados -->
<script>document.body.classList.add('reset-password-page');</script>

<div class="forgot-container"> <!-- Usando a classe do forgot_password.php -->
    <div class="row justify-content-center">
        <div class="col-md-12"> <!-- Ajustado para 12 colunas para ocupar a largura máxima do container -->
            <h2 class="text-center">Redefinir Senha</h2>

            <?php if ($message): ?>
                <div class="alert alert-<?= htmlspecialchars($message_type) ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($show_form): ?>
                <form action="reset_password.php?token=<?= htmlspecialchars($token) ?>" method="POST">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nova Senha</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirmar Nova Senha</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <input type="hidden" name="reset_password" value="1">
                    <button type="submit" class="btn btn-primary w-100">Redefinir Senha</button>
                </form>
            <?php endif; ?>

             <div class="text-center mt-3">
                <?php if ($message_type === 'success' || ($message_type === 'danger' && !$show_form)): ?>
                     <p><a href="login.php">Ir para Login</a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; // Inclui o rodapé ?>
