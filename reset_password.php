<?php
session_start();
include 'db.php'; // Include database connection
include 'header.php'; // Include the header

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
            // Check if the token has expired
            if (strtotime($user['reset_token_expires_at']) < time()) {
                $message = "O token de recuperação expirou. Por favor, solicite um novo.";
                $message_type = 'danger';
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

            if (!$user || strtotime($user['reset_token_expires_at']) < time()) {
                $message = "Token inválido, expirado ou já utilizado. Por favor, solicite um novo.";
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

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2 class="text-center">Redefinir Senha</h2>

            <?php if ($message): ?>
                <div class="alert alert-<?= htmlspecialchars($message_type) ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if ($show_form): ?>
                <form action="reset_password.php?token=<?= htmlspecialchars($token) ?>" method="POST">
                    <!-- Include token in the form for re-validation on POST -->
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                    <div class="form-group mb-3">
                        <label for="new_password">Nova Senha</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="confirm_password">Confirmar Nova Senha</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <input type="hidden" name="reset_password" value="1">
                    <button type="submit" class="btn btn-primary w-100">Redefinir Senha</button>
                </form>
            <?php endif; ?>

             <div class="text-center mt-3">
                <?php if ($message_type === 'success' || $message_type === 'danger' && !$show_form): ?>
                     <p><a href="login.php">Ir para Login</a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
include 'footer.php'; // Include the footer
?>
