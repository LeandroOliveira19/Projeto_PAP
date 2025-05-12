<?php
// A sessão geralmente é iniciada nos arquivos que incluem este header (ex: dashboard.php, trainer.php, etc.)
// Se não for o caso, descomente a linha abaixo:
// session_start();

// --- INÍCIO: CÓDIGO DO ÍCONE DE PERFIL (ADICIONADO) ---
$profile_pic_path = 'assets/img/profile_pics/'; // Define o caminho base das fotos
$profile_pic_file = null;
$user_name_initials = '';
$profile_pic_src = null; // Caminho completo da imagem a ser usada

// Verifica se o utilizador está logado para buscar dados
if (isset($_SESSION['user_id'])) {
    $profile_pic_file = $_SESSION['profile_picture'] ?? null; // Pega nome do ficheiro da sessão

    // Verifica se o ficheiro da foto existe
    if ($profile_pic_file && file_exists($profile_pic_path . $profile_pic_file)) {
        $profile_pic_src = $profile_pic_path . $profile_pic_file;
    }
    // Gera iniciais se não houver foto e houver nome
    elseif (isset($_SESSION['name'])) {
        $name_parts = explode(' ', trim($_SESSION['name']));
        $first_initial = !empty($name_parts[0]) ? strtoupper($name_parts[0][0]) : '';
        $last_initial = count($name_parts) > 1 ? strtoupper(end($name_parts)[0]) : '';
        $user_name_initials = $first_initial . $last_initial;
    }
}
// --- FIM: CÓDIGO DO ÍCONE DE PERFIL ---

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Título dinâmico pode ser adicionado aqui se necessário -->
    <title>MyWellness</title>

    <!-- Google Fonts (Poppins) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- FullCalendar CSS (v6) - Incluir se usado globalmente, senão apenas nas páginas necessárias -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/main.min.css' rel='stylesheet' />

    <!-- Estilos Principais e Tema (Variáveis CSS) -->
    <link rel="stylesheet" href="assets/css/dashboard_cliente.css">

    <!-- Estilos Específicos da Personalização do Chat -->
    <link rel="stylesheet" href="assets/css/personalizacao_chat.css">

    <!-- Favicon (Opcional) -->
    <!-- <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon"> -->

    <!-- *** INÍCIO: CSS DO ÍCONE DE PERFIL (ADICIONADO) *** -->
    <style>
        .profile-icon-container {
            position: fixed; /* Fixo na janela */
            top: 15px;       /* Distância do topo */
            right: 25px;      /* Distância da direita */
            z-index: 1050;    /* Garante que fica acima da maioria dos elementos */
        }
        .profile-icon {
            display: inline-block;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--bs-secondary-bg, #e9ecef); /* Fundo padrão claro */
            color: var(--bs-secondary-color, #6c757d); /* Cor do texto padrão */
            text-align: center;
            line-height: 40px; /* Centraliza verticalmente o texto/ícone */
            font-size: 1.1rem;
            font-weight: 500;
            overflow: hidden; /* Esconde partes da imagem que saem do círculo */
            text-decoration: none;
            border: 1px solid var(--bs-border-color, #dee2e6);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .profile-icon:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            color: var(--bs-primary, #0d6efd); /* Muda cor do texto/ícone no hover */
        }
        .profile-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover; /* Garante que a imagem cubra o círculo sem distorcer */
        }
        .profile-icon .initials {
             display: block; /* Garante que as iniciais ocupem o espaço */
             width: 100%;
             height: 100%;
        }
        /* Ajustes para modo escuro (se usar a classe .dark-mode no body) */
        .dark-mode .profile-icon {
            background-color: var(--bs-gray-700, #495057);
            color: var(--bs-gray-300, #dee2e6);
            border-color: var(--bs-gray-600, #6c757d);
        }
         .dark-mode .profile-icon:hover {
             color: var(--bs-primary-text-emphasis, #6ea8fe); /* Cor de destaque no modo escuro */
         }
    </style>
    <!-- *** FIM: CSS DO ÍCONE DE PERFIL *** -->

</head>

<body class="dashboard-body"> <!-- Classe importante para aplicar estilos base e tema -->

    <!-- ================================================== -->
    <!-- INLINE SCRIPT para aplicar tema/cores imediatamente -->
    <!-- (Evita o "flicker" ao carregar a página)         -->
    <!-- ================================================== -->
    <script>
        (function() {
            // Aplica tema Dark/Light guardado no localStorage
            const theme = localStorage.getItem('theme');
            if (theme === 'dark') {
                document.body.classList.add('dark-mode');
            } else {
                 // Garante que a classe não esteja presente se o tema for 'light' ou não definido
                 document.body.classList.remove('dark-mode');
            }

            // Aplica cores do chat guardadas no localStorage
             const savedColors = JSON.parse(localStorage.getItem('chatColors') || '{}');
             const root = document.documentElement; // Seleciona o elemento raiz (<html>)

             // Define as propriedades CSS customizadas (variáveis) se existirem no localStorage
             if(savedColors.sentBg) root.style.setProperty('--chat-sent-bg', savedColors.sentBg);
             if(savedColors.sentText) root.style.setProperty('--chat-sent-text', savedColors.sentText);
             if(savedColors.receivedBg) root.style.setProperty('--chat-received-bg', savedColors.receivedBg);
             if(savedColors.receivedText) root.style.setProperty('--chat-received-text', savedColors.receivedText);
        })();
    </script>
    <!-- ================================================== -->

    <!-- *** INÍCIO: HTML DO ÍCONE DE PERFIL (ADICIONADO) *** -->
    <?php if (isset($_SESSION['user_id'])): // Só mostra o ícone se o utilizador estiver logado ?>
    <div class="profile-icon-container">
        <a href="profile.php" class="profile-icon" title="Ver Perfil">
            <?php if ($profile_pic_src): // Se existe foto e o ficheiro foi encontrado ?>
                <img src="<?= htmlspecialchars($profile_pic_src) ?>?t=<?= time() ?>" alt="Foto de Perfil"> <!-- time() ajuda a evitar cache -->
            <?php elseif ($user_name_initials): // Senão, se existem iniciais ?>
                <span class="initials"><?= htmlspecialchars($user_name_initials) ?></span>
            <?php else: // Senão, mostra um ícone padrão ?>
                <i class="fas fa-user"></i>
            <?php endif; ?>
        </a>
    </div>
    <?php endif; ?>
    <!-- *** FIM: HTML DO ÍCONE DE PERFIL *** -->


    <!-- O conteúdo principal da página (incluindo sidebar e main-content)
         será inserido aqui pelos arquivos que incluem este header
         (ex: dashboard.php, trainer.php, chat.php, etc.) -->
