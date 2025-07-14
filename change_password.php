<?php
session_start();
$hide_layout_elements = true; // ADICIONADO: Esta variável vai dizer ao header para adaptar o layout
include 'db.php';
include 'header.php'; // Inclui o header, que agora será condicional

// Verifica se um token é fornecido no URL
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Busca o utilizador associado ao token
    $stmt = $pdo->prepare("SELECT id FROM users WHERE token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        // Token é válido, armazena user_id na sessão
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['token'] = $token;
    } else {
        // Token inválido
        echo "<script>alert('Token inválido.'); window.location.href = 'login.php';</script>";
        exit;
    }
}

// Verifica se user_id está definido na sessão (significando que um token válido foi fornecido)
if (!isset($_SESSION['user_id'])) {
    // Se não houver token ou for inválido, redireciona para o login
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
            // Limpa as variáveis de sessão após a alteração bem-sucedida
            unset($_SESSION['user_id']);
            unset($_SESSION['token']);
            echo "<script>alert('Senha alterada com sucesso! Faça o login para continuar.'); window.location.href = 'login.php';</script>";
            exit;
        } else {
            // Esta mensagem é agora menos provável de ser vista devido à validação do lado do cliente
            echo "<script>alert('As senhas novas não coincidem.'); window.history.back();</script>";
            exit;
        }
    } catch (Exception $e) {
        echo "<script>alert('Erro ao alterar a senha: " . $e->getMessage() . "'); window.history.back();</script>";
        exit;
    }
}
?>
<style>
    body, html {
        height: 100%;
        margin: 0; /* Garante que não há margens padrão do body */
        padding: 0; /* Garante que não há padding padrão do body */
    }
    .main-container {
        background-color: #f0f2f5;
        min-height: 100vh;
        width: 100%; /* Ocupa toda a largura disponível */
        padding: 20px; /* Adiciona algum padding interno para o conteúdo */
    }
    .dark-mode .main-container {
        background-color: #18191a;
    }
    /* Adicione estilos para o body no modo escuro se o fundo não estiver a cobrir tudo */
    body.dark-mode {
        background-color: #18191a;
        color: #e0e0e0;
    }
</style>
<div class="main-container d-flex justify-content-center align-items-center">
    <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-4">
        <div class="card shadow-lg border-0 rounded-lg">
            <div class="card-header bg-primary text-white text-center">
                <h3 class="fw-light my-3">Definir Nova Password</h3>
            </div>
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <i class="fas fa-key fa-3x text-primary"></i>
                </div>
                <form method="POST" id="changePasswordForm">
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Nova password" required minlength="6">
                        <label for="new_password">Nova password</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirmar Nova Senha" required minlength="6">
                        <label for="confirm_password">Confirmar Nova Senha</label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="showPassword">
                        <label class="form-check-label" for="showPassword">
                            Mostrar Password
                        </label>
                    </div>
                    <div id="password-error" class="text-danger small mb-3" style="display: none;">As senhas não coincidem.</div>
                    <input type="hidden" name="change_password" value="1">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>Alterar Senha</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center py-3">
                <div class="small"><a href="login.php">Lembrou-se da senha? Fazer Login</a></div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const newPasswordField = document.getElementById('new_password');
    const confirmPasswordField = document.getElementById('confirm_password');
    const showPasswordCheckbox = document.getElementById('showPassword');
    const changePasswordForm = document.getElementById('changePasswordForm');
    const passwordErrorDiv = document.getElementById('password-error');

    if (showPasswordCheckbox) {
        showPasswordCheckbox.addEventListener('change', function() {
            const type = this.checked ? 'text' : 'password';
            if (newPasswordField) newPasswordField.type = type;
            if (confirmPasswordField) confirmPasswordField.type = type;
        });
    }

    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', function(event) {
            newPasswordField.classList.remove('is-invalid', 'is-valid');
            confirmPasswordField.classList.remove('is-invalid', 'is-valid');
            passwordErrorDiv.style.display = 'none';

            if (newPasswordField.value !== confirmPasswordField.value) {
                event.preventDefault();
                passwordErrorDiv.textContent = 'As senhas não coincidem.';
                passwordErrorDiv.style.display = 'block';
                newPasswordField.classList.add('is-invalid');
                confirmPasswordField.classList.add('is-invalid');
            } else if (newPasswordField.value.length < 6) {
                event.preventDefault();
                passwordErrorDiv.textContent = 'A senha deve ter pelo menos 6 caracteres.';
                passwordErrorDiv.style.display = 'block';
                newPasswordField.classList.add('is-invalid');
                confirmPasswordField.classList.add('is-invalid');
            }
        });
    }

    function validatePasswords() {
        if (confirmPasswordField.value.length > 0) {
            if (newPasswordField.value !== confirmPasswordField.value) {
                passwordErrorDiv.textContent = 'As senhas não coincidem.';
                passwordErrorDiv.style.display = 'block';
                confirmPasswordField.classList.add('is-invalid');
                confirmPasswordField.classList.remove('is-valid');
            } else {
                passwordErrorDiv.style.display = 'none';
                confirmPasswordField.classList.remove('is-invalid');
                confirmPasswordField.classList.add('is-valid');
            }
        } else {
            passwordErrorDiv.style.display = 'none';
            confirmPasswordField.classList.remove('is-invalid');
            confirmPasswordField.classList.remove('is-valid');
        }
    }

    if (newPasswordField) newPasswordField.addEventListener('input', validatePasswords);
    if (confirmPasswordField) confirmPasswordField.addEventListener('input', validatePasswords);
});
</script>

<?php
include 'footer.php';
?>