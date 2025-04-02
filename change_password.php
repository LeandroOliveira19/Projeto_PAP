<?php
session_start();
include 'db.php';
include 'header.php';

// Check if a token is provided in the URL
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Fetch the user associated with the token
    $stmt = $pdo->prepare("SELECT id FROM users WHERE token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        // Token is valid, store user_id in session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['token'] = $token;
    } else {
        // Invalid token
        echo "<script>alert('Token inválido.'); window.location.href = 'login.php';</script>";
        exit;
    }
}

// Check if user_id is set in session (meaning a valid token was provided)
if (!isset($_SESSION['user_id'])) {
    // If no token or invalid token, redirect to login
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $user_id = $_SESSION['user_id'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    try {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE users SET password = ?, token = NULL, first_login = 0 WHERE id = ?");
            $update_stmt->execute([$hashed_password, $user_id]);
            echo "<script>alert('Senha alterada com sucesso!'); window.location.href = 'login.php';</script>";
            exit;
        } else {
            echo "<script>alert('As senhas novas não coincidem.'); window.history.back();</script>";
            exit;
        }
    } catch (Exception $e) {
        echo "<script>alert('Erro ao alterar a senha: " . $e->getMessage() . "'); window.history.back();</script>";
        exit;
    }
}
?>
<div class="container mt-5">
    <h2>Definir Password</h2>
    <form method="POST">
        <div class="form-group mb-3">
            <label for="new_password">Nova password</label>
            <input type="password" class="form-control" id="new_password" name="new_password" required>
        </div>
        <div class="form-group mb-3">
            <label for="confirm_password">Confirmar Nova Senha</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
        </div>
        <input type="hidden" name="change_password" value="1">
        <button type="submit" class="btn btn-primary">Alterar Senha</button>
    </form>
</div>
<?php
include 'footer.php';
?>
