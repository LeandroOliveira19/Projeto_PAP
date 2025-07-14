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
                // ESTE FALLBACK É ALTAMENTE NÃO RECOMENDADO E DEVE SER REMOVIDO ASSIM QUE POSSÍVEL
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

<?php include 'header.php'; // Inclui o cabeçalho (que tem o início do body e o ícone de perfil) ?>

<style>
    /* Estilos para o corpo da página (mantidos, com ajuste no overlay) */
    /* O body já é estilizado pelo header.php ou CSS global, mas podemos sobrepor se necessário */
    body.login-page { /* Adiciona uma classe específica para evitar conflitos */
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
    body.login-page::before { /* Overlay escuro */
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
<script>document.body.classList.add('login-page');</script>

<div class="forgot-container"> <!-- Usando a classe do forgot_password.php -->
    <div class="row justify-content-center">
        <div class="col-md-12"> <!-- Ajustado para 12 colunas para ocupar a largura máxima do container -->
            <h2 class="text-center">Login</h2>
            <?php if (!empty($error_message)): // Verifica se a mensagem de erro não está vazia ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_message) // Usa htmlspecialchars por segurança ?></div>
            <?php endif; ?>
            <form action="login.php" method="POST">
                <div class="mb-3"> <!-- Usando mb-3 para espaçamento Bootstrap 5 -->
                    <label for="email" class="form-label">E-mail</label> <!-- Usando form-label para Bootstrap 5 -->
                    <input type="email" class="form-control" id="email" name="email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' // Mantém o email preenchido em caso de erro ?>">
                </div>
                <div class="mb-3"> <!-- Usando mb-3 para espaçamento Bootstrap 5 -->
                    <label for="password" class="form-label">Senha</label> <!-- Usando form-label para Bootstrap 5 -->
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

<?php include 'footer.php'; // Inclui o rodapé ?>
