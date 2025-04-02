<?php
session_start();

// Verifica se o usuário já está logado
if (isset($_SESSION['user_id'])) {
    // Redireciona com base no perfil do usuário
    switch ($_SESSION['role']) {
        case 'client':
            header('Location: dashboard.php');
            break;
        case 'trainer':
            header('Location: trainer.php');
            break;
        case 'nutritionist':
            header('Location: nutritionist.php');
            break;
        case 'admin':
            header('Location: admin.php');
            break;
        default:
            header('Location: login.php');
            break;
    }
    exit;
}

// Inclui a conexão com o banco de dados
include 'db.php';

// Processa o formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica se os campos foram enviados
    $email = $_POST['email'] ?? null;
    $password = $_POST['password'] ?? null;

    if (!$email || !$password) {
        $error_message = "Por favor, preencha todos os campos.";
    } else {
        // Busca o usuário no banco de dados
        $stmt = $pdo->prepare("SELECT id, name, password, role, first_login FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Verifica se o usuário existe
        if ($user) {
            // Verifica se a senha está correta
            if (password_verify($password, $user['password'])) {
                // Senha hash: usa password_verify
                $is_password_correct = true;
            } elseif ($user['password'] === $password) {
                // Senha em texto puro: compara diretamente
                $is_password_correct = true;
            } else {
                $is_password_correct = false;
            }

            if ($is_password_correct) {
                // Define as variáveis de sessão
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];

                // Check if it's the first login
                if ($user['first_login'] == 1) {
                    // Redirect to change password page
                    header('Location: change_password.php?first_login=true');
                    exit;
                }

                // Redireciona para a página correta com base no perfil
                switch ($user['role']) {
                    case 'client':
                        header('Location: dashboard.php');
                        break;
                    case 'trainer':
                        header('Location: trainer.php');
                        break;
                    case 'nutritionist':
                        header('Location: nutritionist.php');
                        break;
                    case 'admin':
                        header('Location: admin.php');
                        break;
                    default:
                        header('Location: login.php');
                        break;
                }
                exit;
            } else {
                $error_message = "Senha incorreta.";
            }
        } else {
            $error_message = "E-mail não encontrado.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Health & Wellness</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h2 class="text-center">Login</h2>
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?= $error_message ?></div>
                <?php endif; ?>
                <form action="login.php" method="POST">
                    <div class="form-group mb-3">
                        <label for="email">E-mail</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="password">Senha</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Entrar</button>
                </form>
                <div class="text-center mt-3">
                    <p>Não tem uma conta? <a href="register.php">Registre-se aqui</a>.</p>
                    <p>Esqueceu a password? <a href="forgot_password.php">Recuperar password</a>.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS e dependências -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>
