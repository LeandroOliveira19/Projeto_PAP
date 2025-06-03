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
        case 'receptionist':
            header('Location: receptionist.php');
            break;
        case 'admin': // ADICIONADO: Redirecionamento para admin se já logado
            header('Location: admin.php');
            break;
        default:
            // Se o role for desconhecido, desloga por segurança
            session_unset();
            session_destroy();
            header('Location: login.php?error=unknown_role');
            break;
    }
    exit;
}

// Inclui a conexão com o banco de dados
include 'db.php';

$error_message = ''; // Inicializa a variável de erro

// Processa o formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica se os campos foram enviados
    $email = $_POST['email'] ?? null;
    $password = $_POST['password'] ?? null;

    if (!$email || !$password) {
        $error_message = "Por favor, preencha todos os campos.";
    } else {
        try {
            // Busca o usuário no banco de dados
            $stmt = $pdo->prepare("SELECT id, name, password, role, first_login, profile_picture FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // Verifica se o usuário existe
            if ($user) {
                $is_password_correct = false;

                // 1. Verifica com password_verify (método preferencial e seguro)
                if (password_verify($password, $user['password'])) {
                    $is_password_correct = true;
                }
                // 2. Fallback para SHA256 (se ainda tiver senhas antigas assim) - Menos seguro
                elseif (hash('sha256', $password) === $user['password']) {
                     $is_password_correct = true;
                     // Opcional: Atualizar a senha para o hash mais seguro aqui
                     // $new_hash = password_hash($password, PASSWORD_DEFAULT);
                     // $update_hash_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                     // $update_hash_stmt->execute([$new_hash, $user['id']]);
                     // error_log("User ID {$user['id']} logged in with SHA256 fallback. Consider rehashing.");
                }
                // 3. Fallback para texto puro (MUITO INSEGURO - REMOVER ASSIM QUE POSSÍVEL)
                elseif ($user['password'] === $password) {
                    $is_password_correct = true;
                    // Opcional: Atualizar a senha para o hash mais seguro aqui
                    // $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    // $update_hash_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    // $update_hash_stmt->execute([$new_hash, $user['id']]);
                    error_log("User ID {$user['id']} logged in with PLAIN TEXT password. SECURITY RISK!");
                }

                if ($is_password_correct) {
                    // Define as variáveis de sessão padrão
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['profile_picture'] = $user['profile_picture']; // Pode ser NULL se não houver foto

                    // Verifica se é o primeiro login (para forçar mudança de senha)
                    if ($user['first_login'] == 1) {
                        // Redireciona para a página de alteração de senha
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
                        case 'receptionist':
                            header('Location: receptionist.php');
                            break;
                        case 'admin': // ADICIONADO: Redirecionamento para admin após login
                            header('Location: admin.php');
                            break;
                        default:
                            // Role desconhecido após login bem-sucedido? Improvável, mas seguro deslogar.
                            session_unset();
                            session_destroy();
                            header('Location: login.php?error=login_role_issue');
                            break;
                    }
                    exit;
                } else {
                    $error_message = "Senha incorreta.";
                }
            } else {
                $error_message = "E-mail não encontrado.";
            }
        } catch (PDOException $e) {
            // Em caso de erro de banco de dados
            $error_message = "Erro ao tentar fazer login. Tente novamente mais tarde.";
            // Logar o erro real para depuração (não mostrar ao usuário)
            error_log("Erro de PDO no login: " . $e->getMessage());
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUFVL" crossorigin="anonymous">
    <!-- Google Fonts (Poppins - já usado no header, bom para consistência) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Estilos para o corpo da página */
        body {
            font-family: 'Poppins', sans-serif; /* Usa a fonte Poppins */
            background: url('assets/img/login_background.jpg') no-repeat center center fixed; /* Imagem de fundo */
            background-size: cover; /* Cobre toda a área */
            display: flex; /* Usa flexbox para centralizar */
            justify-content: center; /* Centraliza horizontalmente */
            align-items: center; /* Centraliza verticalmente */
            min-height: 100vh; /* Altura mínima de 100% da viewport */
            margin: 0; /* Remove margem padrão */
            position: relative; /* Necessário para o overlay */
            color: #333; /* Cor de texto padrão */
        }

        /* Overlay para escurecer a imagem de fundo e melhorar a legibilidade */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6); /* Overlay preto semi-transparente */
            z-index: 1; /* Garante que o overlay fica por cima da imagem */
        }

        /* Container principal do formulário */
        .login-container {
            position: relative; /* Para ficar acima do overlay */
            z-index: 2; /* Garante que o conteúdo fica por cima do overlay */
            background-color: rgba(255, 255, 255, 0.95); /* Fundo branco semi-transparente para o formulário */
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3); /* Sombra suave */
            width: 100%;
            max-width: 400px; /* Largura máxima do formulário */
        }

        /* Título */
        .login-container h2 {
            margin-bottom: 25px;
            color: #007bff; /* Cor primária do Bootstrap */
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h2 class="text-center">Login</h2>
                <?php if (!empty($error_message)): // Verifica se a mensagem de erro não está vazia ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error_message) // Usa htmlspecialchars por segurança ?></div>
                <?php endif; ?>
                <form action="login.php" method="POST">
                    <div class="form-group mb-3">
                        <label for="email">E-mail</label>
                        <input type="email" class="form-control" id="email" name="email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' // Mantém o email preenchido em caso de erro ?>">
                    </div>
                    <div class="form-group mb-3">
                        <label for="password">Senha</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Entrar</button>
                </form>
                <div class="text-center mt-3">
                    <p>Esqueceu a password? <a href="forgot_password.php">Recuperar password</a>.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle JS (inclui Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigSz/r+8KxI7VNBgh+z" crossorigin="anonymous"></script>
</body>
</html>
