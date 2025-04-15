<?php
session_start();
include 'db.php'; // Include database connection
include 'send_email.php'; // Include the email sending function
include 'header.php'; // Include the header

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
                // Set expiry time (e.g., 1 hour from now)
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Store the token and expiry time in the database
                $update_stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?");
                $update_stmt->execute([$token, $expires_at, $user['id']]);

                // Send the password reset email
                // Assuming sendConfirmationEmail can be adapted or you create a new function sendPasswordResetEmail
                if (sendConfirmationEmail($email, $user['name'], $token, "reset")) {
                     // Use a generic success message for security (don't reveal if email exists)
                    $message = "Se existir uma conta associada a este e-mail, receberá um link para redefinir a sua senha.";
                    $message_type = 'success';
                } else {
                    $message = "Ocorreu um erro ao tentar enviar o e-mail de recuperação. Tente novamente mais tarde.";
                    $message_type = 'danger';
                    // Log the error for debugging
                    error_log("Password reset email failed to send for email: " . $email);
                }
            } else {
                 // Use the same generic message even if user not found
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

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2 class="text-center">Recuperar Senha</h2>
            <p class="text-center text-muted">Insira o seu e-mail para receber um link de recuperação.</p>

            <?php if ($message): ?>
                <div class="alert alert-<?= htmlspecialchars($message_type) ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if ($message_type !== 'success'): // Hide form after success message ?>
                <form action="forgot_password.php" method="POST">
                    <div class="form-group mb-3">
                        <label for="email">E-mail</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Enviar Link de Recuperação</button>
                </form>
            <?php endif; ?>

            <div class="text-center mt-3">
                <p><a href="login.php">Voltar ao Login</a></p>
            </div>
        </div>
    </div>
</div>

<?php
include 'footer.php'; // Include the footer
?>
