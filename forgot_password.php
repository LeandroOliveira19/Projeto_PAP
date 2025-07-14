<?php
session_start();
// Definir o fuso horário de referência. Use 'UTC' ou o fuso horário padrão da sua aplicação.
// Se já tiver date_default_timezone_set() num ficheiro de configuração global, pode não ser necessário aqui.
// Para este exemplo, vamos usar 'UTC' explicitamente.
const REFERENCE_TIMEZONE_FORGOT = 'UTC'; // Deve ser o mesmo que em reset_password.php

// ***** ADICIONADO: Incluir header.php *****
// O header.php deve ser incluído ANTES de qualquer saída HTML,
// especialmente se os estilos dependem de CSS carregado por ele (como Bootstrap).
include 'header.php';
// *****************************************

include 'db.php'; // Include database connection
include 'send_email.php'; // Include the email sending function


$message = '';
$message_type = ''; // 'success' or 'danger'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? null;

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Por favor, insira um endereço de e-mail válido.";
        $message_type = 'danger';
    } else {
        try {
            // Find the user by email
            $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Generate a unique, secure token
                $token = bin2hex(random_bytes(32)); // 64 characters long hex token
                
                // Set expiry time (e.g., 1 hour from now) using DateTime for robust timezone handling
                $dt = new DateTime("now", new DateTimeZone(REFERENCE_TIMEZONE_FORGOT));
                $dt->modify('+1 hour');
                $expires_at = $dt->format('Y-m-d H:i:s');

                // Store the token and expiry time in the database
                $update_stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?");
                $update_stmt->execute([$token, $expires_at, $user['id']]);

                // Send the password reset email
                if (sendConfirmationEmail($email, $user['name'], $token, "reset")) {
                    $message = "Se existir uma conta associada a este e-mail, receberá um link para redefinir a sua senha.";
                    $message_type = 'success';
                } else {
                    $message = "Ocorreu um erro ao tentar enviar o e-mail de recuperação. Tente novamente mais tarde.";
                    $message_type = 'danger';
                    error_log("Password reset email failed to send for email: " . $email);
                }
            } else {
                 $message = "Se existir uma conta associada a este e-mail, receberá um link para redefinir a sua senha.";
                 $message_type = 'success';
            }
        } catch (PDOException $e) {
            $message = "Erro na base de dados. Tente novamente mais tarde.";
            $message_type = 'danger';
            error_log("Database error during password reset request: " . $e->getMessage());
        } catch (Exception $e) {
            $message = "Ocorreu um erro inesperado. Tente novamente mais tarde.";
            $message_type = 'danger';
            error_log("General error during password reset request: " . $e->getMessage());
        }
    }
}
?>

<?php
// O header.php já foi incluído acima.
// Os estilos específicos para esta página podem vir aqui.
?>
<style>
    /* Estilos para o corpo da página (mantidos, com ajuste no overlay) */
    /* O body já é estilizado pelo header.php ou CSS global, mas podemos sobrepor se necessário */
    body.forgot-password-page { /* Adiciona uma classe específica para evitar conflitos */
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
    body.forgot-password-page::before { /* Overlay escuro */
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        z-index: 1;
    }

    /* Container principal do formulário (Modificado) */
    .forgot-container {
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
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Título (Modificado) */
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
<script>document.body.classList.add('forgot-password-page');</script>

<div class="forgot-container">
    <h2>Recuperar Senha</h2>
    <p class="text-muted">Insira o seu e-mail para receber um link de recuperação.</p>
            <?php if ($message): ?>
                <div class="alert alert-<?= htmlspecialchars($message_type) ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($message_type !== 'success'): // Hide form after success message ?>
                <form action="forgot_password.php" method="POST">
                    <div class="mb-3"> 
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" class="form-control" id="email" name="email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Enviar Link de Recuperação</button>
                </form>
            <?php endif; ?>

    <div class="text-center mt-3">
        <p><a href="login.php">Voltar ao Login</a></p>
    </div>
</div>
<?php
include 'footer.php'; // Include the footer
?>
