<?php
// A sessão geralmente é iniciada nos arquivos que incluem este header (ex: dashboard.php, trainer.php, etc.)
// Se não for o caso, descomente a linha abaixo:
// session_start();

// Define o fuso horário padrão para Portugal
date_default_timezone_set('Europe/Lisbon');

// ADICIONADO: Verifica se a variável $hide_layout_elements está definida e é verdadeira
// Se não for definida, assume-se que é falso (ou seja, os elementos do layout devem aparecer)
$hide_layout_elements = isset($hide_layout_elements) && $hide_layout_elements === true;

// --- INÍCIO: CÓDIGO DO ÍCONE DE PERFIL ---
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
    <title>MyWellness</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/main.min.css' rel='stylesheet' />

    <?php if (!$hide_layout_elements): // APENAS INCLUI SE NÃO ESTIVERMOS A ESCONDER ELEMENTOS DO LAYOUT ?>
    <link rel="stylesheet" href="assets/css/dashboard_cliente.css">
    <?php endif; ?>

    <link rel="stylesheet" href="assets/css/personalizacao_chat.css">

    <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon">

    <?php if (!$hide_layout_elements): // APENAS INCLUI O CSS DO ÍCONE DE PERFIL SE NÃO ESTIVERMOS A ESCONDER ELEMENTOS DO LAYOUT ?>
    <style>
        .profile-icon-container {
            position: fixed; /* Fixo na janela */
            top: 15px;      /* Distância do topo */
            right: 25px;    /* Distância da direita */
            z-index: 1050;  /* Garante que fica acima da maioria dos elementos */
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

        /* Estilos para o Dropdown do Perfil */
        .profile-dropdown-menu {
            display: none; /* Escondido por padrão */
            position: absolute;
            top: 50px; /* Abaixo do ícone */
            right: 0;
            background-color: var(--bs-body-bg, #fff);
            border: 1px solid var(--bs-border-color, #dee2e6);
            border-radius: 0.375rem;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
            z-index: 1051; /* Acima do ícone, mas abaixo do ícone quando arrastado */
            min-width: 180px; /* Largura mínima aumentada */
            padding: 0.5rem 0;
        }
        .profile-dropdown-menu.show {
            display: block;
        }
        .profile-dropdown-menu .dropdown-item {
            display: block; width: 100%; padding: 0.5rem 1rem; clear: both;
            font-weight: 400; color: var(--bs-body-color, #212529); text-align: inherit;
            white-space: nowrap; background-color: transparent; border: 0; text-decoration: none;
        }
        .profile-dropdown-menu .dropdown-item:hover { background-color: var(--bs-tertiary-bg, #e9ecef); color: var(--bs-emphasis-color, #000); }
        .profile-dropdown-menu .dropdown-item i { color: var(--bs-secondary-color); width: 20px; /* Alinhar ícones */ }

        /* Ajustes para modo escuro no dropdown */
        .dark-mode .profile-dropdown-menu { background-color: var(--bs-gray-800, #343a40); border-color: var(--bs-gray-700, #495057); }
        .dark-mode .profile-dropdown-menu .dropdown-item { color: var(--bs-gray-300, #dee2e6); }
        .dark-mode .profile-dropdown-menu .dropdown-item:hover { background-color: var(--bs-gray-700, #495057); color: var(--bs-light, #f8f9fa); }
        .dark-mode .profile-dropdown-menu .dropdown-item i { color: var(--bs-gray-500); }
    </style>
    <?php endif; ?>

</head>

<body class="<?php if (!$hide_layout_elements) echo 'dashboard-body'; ?>">

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
            const savedColors = JSON.parse(localStorage.getItem('chatColors_whatsapp') || '{}');
            const root = document.documentElement; // Seleciona o elemento raiz (<html>)

            // Define as propriedades CSS customizadas (variáveis) se existirem no localStorage
            if(savedColors.sentBg) root.style.setProperty('--chat-sent-bg-whatsapp', savedColors.sentBg);
            if(savedColors.sentText) root.style.setProperty('--chat-sent-text-whatsapp', savedColors.sentText);
            if(savedColors.receivedBg) root.style.setProperty('--chat-received-bg-whatsapp', savedColors.receivedBg);
            if(savedColors.receivedText) root.style.setProperty('--chat-received-text-whatsapp', savedColors.receivedText);
        })();
    </script>
    <?php if (!$hide_layout_elements && isset($_SESSION['user_id'])): // APENAS MOSTRA O ÍCONE DE PERFIL SE NÃO ESTIVER A ESCONDER ELEMENTOS DO LAYOUT E O UTILIZADOR ESTIVER LOGADO ?>
    <div class="profile-icon-container">
        <a href="profile.php" class="profile-icon" title="Ver Perfil / Opções" id="profileIconLink">
            <?php if ($profile_pic_src): // Se existe foto e o ficheiro foi encontrado ?>
                <img src="<?= htmlspecialchars($profile_pic_src) ?>?t=<?= time() ?>" alt="Foto de Perfil"> <?php elseif ($user_name_initials): // Senão, se existem iniciais ?>
                <span class="initials"><?= htmlspecialchars($user_name_initials) ?></span>
            <?php else: // Senão, mostra um ícone padrão ?>
                <i class="fas fa-user"></i>
            <?php endif; ?>
        </a>
        <div class="profile-dropdown-menu" id="profileDropdown">
            <a href="profile.php" class="dropdown-item"><i class="fas fa-user-circle me-2"></i>Ver Perfil</a>
            <a href="#" class="dropdown-item" id="reset-profile-icon-position"><i class="fas fa-undo me-2"></i>Redefinir Posição</a>
            <a href="logout.php" class="dropdown-item"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
        </div>
    </div>
    <?php endif; ?>
    <?php
// --- INÍCIO: Lógica para Notificação de Acesso Delegado ---
$info_delegacao_ativa_para_header = null;
// APENAS VERIFICA SE NÃO ESTIVER A ESCONDER ELEMENTOS DO LAYOUT
if (!$hide_layout_elements && isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] !== 'admin') { // Só verifica para não-admins
    // É importante que 'db.php' já tenha sido incluído e $pdo esteja disponível.
    // E que 'includes/auth_utils.php' (ou onde a função estiver) já tenha sido incluído.
    if (file_exists(__DIR__ . '/includes/auth_utils.php')) { // Verifica se o arquivo existe
        include_once __DIR__ . '/includes/auth_utils.php'; // Usa include_once e __DIR__
        if (function_exists('obter_info_delegacao_ativa')) { // Verifica se a função existe
            if (isset($pdo)) { // Verifica se $pdo está definido
                $info_delegacao_ativa_para_header = obter_info_delegacao_ativa($pdo, $_SESSION['user_id']);
            } else {
                error_log("Variável \$pdo não definida ao tentar chamar obter_info_delegacao_ativa em header.php");
            }
        } else {
            error_log("Função obter_info_delegacao_ativa não encontrada em header.php (após incluir auth_utils.php).");
        }
    } else {
        error_log("Arquivo includes/auth_utils.php não encontrado em header.php.");
    }
}
// --- FIM: Lógica para Notificação de Acesso Delegado ---
?>

<?php if (!$hide_layout_elements && isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] !== 'admin'): // APENAS MOSTRA SE NÃO ESTIVER A ESCONDER ELEMENTOS DO LAYOUT ?>
    <?php if (isset($info_delegacao_ativa_para_header) && $info_delegacao_ativa_para_header): ?>
    <style>
        /* Estilos para o pop-up de notificação */
        .delegation-notification {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #f8d7da; /* Cor de fundo suavemente vermelha */
            color: #721c24; /* Texto vermelho escuro */
            border: 1px solid #f5c6cb;
            padding: 10px 20px;
            border-radius: 5px;
            z-index: 1050; /* Garante que fica acima de outros elementos */
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            display: none; /* Esconde inicialmente */
        }
        .delegation-notification .close-button {
            float: right;
            cursor: pointer;
            font-size: 1.2em;
            color: #721c24;
            text-decoration: none;
            margin-left: 10px;
        }
        .delegation-notification .close-button:hover {
            color: #000;
        }
    </style>
    <div id="delegationNotification" class="delegation-notification">
        <span class="close-button" onclick="closeDelegationNotification()">&times;</span>
        <i class="fas fa-user-shield me-2"></i>Acesso administrativo delegado por <strong><?= htmlspecialchars($info_delegacao_ativa_para_header['admin_concedente_name']) ?></strong>. Expira em: <?= htmlspecialchars(date('d/m/Y H:i', strtotime($info_delegacao_ativa_para_header['expires_at']))) ?>.
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (!localStorage.getItem('delegationNotificationClosed')) {
                document.getElementById('delegationNotification').style.display = 'block';
            }
        });
        function closeDelegationNotification() {
            document.getElementById('delegationNotification').style.display = 'none';
            localStorage.setItem('delegationNotificationClosed', 'true');
        }
    </script>
    <?php endif; ?>
<?php endif; ?>