<?php
// filepath: c:\xampp\htdocs\Projeto_PAP\chat.php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'db.php';
include 'header.php'; // Inclui cabeçalho e início do body/sidebar

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? null; // Garante que role existe
$user_name = $_SESSION['name'] ?? 'Utilizador';
$page_error = '';
$conversations = []; // Array para guardar a lista de conversas

// --- ETAPA 1: Popular conversas com base no papel (relações cliente-profissional) ---
try {
    if ($user_role === 'client') {
        $stmt_client = $pdo->prepare("SELECT id FROM clients WHERE user_id = ? LIMIT 1");
        $stmt_client->execute([$user_id]);
        $client = $stmt_client->fetch();

        if ($client) {
            $client_table_id = $client['id']; // Usar client_table_id para clareza

            // Buscar Treinador(es) com plano ativo
            $stmt_trainers = $pdo->prepare("
                SELECT DISTINCT u.id, u.name, u.profile_picture
                FROM users u
                JOIN workout_plans wp ON u.id = wp.trainer_id
                WHERE wp.client_id = ? AND wp.is_active = 1 AND u.role = 'trainer'
            "); // client_id aqui refere-se ao id da tabela clients
            $stmt_trainers->execute([$client_table_id]);
            $trainers = $stmt_trainers->fetchAll();
            foreach ($trainers as $trainer) {
                $avatar_path = 'assets/img/profile_pics/' . $trainer['profile_picture'];
                $avatar_url = ($trainer['profile_picture'] && file_exists($avatar_path)) ? $avatar_path : 'assets/img/avatars/default.png';
                $conversations[$trainer['id']] = [
                    'user_id' => $trainer['id'],
                    'name' => $trainer['name'] . " (Trainer)",
                    'avatar' => $avatar_url,
                    'last_message' => '', // Placeholder
                    'timestamp' => null, // Placeholder
                    'unread_count' => 0 // Placeholder
                ];
            }

            // Buscar Nutricionista(s) com plano ativo
            $stmt_nutritionists = $pdo->prepare("
                SELECT DISTINCT u.id, u.name, u.profile_picture
                FROM users u
                JOIN diet_plans dp ON u.id = dp.nutritionist_id
                WHERE dp.client_id = ? AND dp.is_active = 1 AND u.role = 'nutritionist'
            "); // client_id aqui refere-se ao id da tabela clients
            $stmt_nutritionists->execute([$client_table_id]);
            $nutritionists = $stmt_nutritionists->fetchAll();
            foreach ($nutritionists as $nutritionist) {
                 $avatar_path = 'assets/img/profile_pics/' . $nutritionist['profile_picture'];
                 $avatar_url = ($nutritionist['profile_picture'] && file_exists($avatar_path)) ? $avatar_path : 'assets/img/avatars/default.png';
                 $conversations[$nutritionist['id']] = [
                    'user_id' => $nutritionist['id'],
                    'name' => $nutritionist['name'] . " (Nutri)",
                    'avatar' => $avatar_url,
                    'last_message' => '', // Placeholder
                    'timestamp' => null, // Placeholder
                    'unread_count' => 0 // Placeholder
                ];
            }
        } else {
            $page_error = "Erro: Registo de cliente não encontrado.";
        }

    } elseif (in_array($user_role, ['trainer', 'nutritionist'])) {
        // Buscar Clientes com planos ativos associados a este profissional
        $plan_table = ($user_role === 'trainer') ? 'workout_plans' : 'diet_plans';
        $professional_id_column = ($user_role === 'trainer') ? 'trainer_id' : 'nutritionist_id';

        $sql_clients = "
            SELECT DISTINCT u.id, u.name, u.profile_picture
            FROM users u
            JOIN clients c ON u.id = c.user_id
            JOIN $plan_table p ON c.id = p.client_id
            WHERE p.$professional_id_column = ? AND u.role = 'client'
        ";
        // Adiciona a condição is_active se a coluna existir
        if ($plan_table === 'workout_plans' || $plan_table === 'diet_plans') {
             $sql_clients .= " AND p.is_active = 1";
        }
        $sql_clients .= " ORDER BY u.name";

        $stmt_clients = $pdo->prepare($sql_clients);
        $stmt_clients->execute([$user_id]);
        $clients_data = $stmt_clients->fetchAll(); // Renomeado para evitar conflito

        foreach ($clients_data as $client_item) { // Renomeado para evitar conflito
             $avatar_path = 'assets/img/profile_pics/' . $client_item['profile_picture'];
             $avatar_url = ($client_item['profile_picture'] && file_exists($avatar_path)) ? $avatar_path : 'assets/img/avatars/default.png';
             $conversations[$client_item['id']] = [
                'user_id' => $client_item['id'],
                'name' => $client_item['name'],
                'avatar' => $avatar_url,
                'last_message' => '', // Placeholder
                'timestamp' => null, // Placeholder
                'unread_count' => 0 // Placeholder
            ];
        }
    } else {
        $page_error = "Chat não disponível para sua função.";
    }
} catch (PDOException $e) {
    $page_error = "Erro de Banco de Dados ao carregar conversas.";
    error_log("Erro BD ao buscar conversas (chat.php): " . $e->getMessage());
}
// --- FIM ETAPA 1 ---

// --- ETAPA 1.5: Adicionar conversas baseadas em mensagens existentes ---
try {
    $stmt_existing_chats = $pdo->prepare("
        SELECT DISTINCT
            CASE
                WHEN m.sender_id = :current_user_id THEN m.receiver_id
                ELSE m.sender_id
            END as other_user_id,
            u.name as other_user_name,
            u.profile_picture as other_user_avatar,
            u.role as other_user_role
        FROM messages m
        JOIN users u ON u.id = (CASE WHEN m.sender_id = :current_user_id THEN m.receiver_id ELSE m.sender_id END)
        WHERE (m.sender_id = :current_user_id OR m.receiver_id = :current_user_id)
          AND u.id != :current_user_id
    ");
    $stmt_existing_chats->execute(['current_user_id' => $user_id]);
    $existing_chat_partners = $stmt_existing_chats->fetchAll();

    foreach ($existing_chat_partners as $partner) {
        if (!isset($conversations[$partner['other_user_id']])) { // Adiciona apenas se não estiver já lá
            $avatar_path_partner = 'assets/img/profile_pics/' . $partner['other_user_avatar'];
            $avatar_url_partner = ($partner['other_user_avatar'] && file_exists($avatar_path_partner)) ? $avatar_path_partner : 'assets/img/avatars/default.png';

            $conversations[$partner['other_user_id']] = [
                'user_id' => $partner['other_user_id'],
                'name' => $partner['other_user_name'] . ($partner['other_user_role'] !== 'client' ? " (".ucfirst($partner['other_user_role']).")" : ""),
                'avatar' => $avatar_url_partner,
                'last_message' => '', // Placeholder, pode ser preenchido depois com a última mensagem real
                'timestamp' => null,  // Placeholder
                'unread_count' => 0 // Placeholder, pode ser preenchido depois
            ];
        }
    }
} catch (PDOException $e) {
    error_log("Erro BD ao buscar parceiros de chat existentes (chat.php): " . $e->getMessage());
    // Não definir $page_error aqui, pois pode haver outras conversas válidas na Etapa 1.
}
// --- FIM ETAPA 1.5 ---

// --- ETAPA 2: Adicionar utilizador de $_GET['user_id'] se não estiver já na lista ---
$target_user_id_from_get = isset($_GET['user_id']) ? filter_var($_GET['user_id'], FILTER_VALIDATE_INT) : null;

if ($target_user_id_from_get && $target_user_id_from_get != $user_id) { // Não conversar consigo mesmo
    if (!isset($conversations[$target_user_id_from_get])) {
        // O utilizador alvo não está na lista de conversas "naturais" (ex: cliente-profissional)
        // Vamos buscá-lo diretamente para permitir o chat.
        try {
            $stmt_target_user = $pdo->prepare("SELECT id, name, profile_picture, role FROM users WHERE id = ?");
            $stmt_target_user->execute([$target_user_id_from_get]);
            $target_user_data = $stmt_target_user->fetch();

            if ($target_user_data) {
                $avatar_path_target = 'assets/img/profile_pics/' . $target_user_data['profile_picture'];
                $avatar_url_target = ($target_user_data['profile_picture'] && file_exists($avatar_path_target)) ? $avatar_path_target : 'assets/img/avatars/default.png';
                
                // Adiciona à lista de conversas
                $conversations[$target_user_data['id']] = [
                    'user_id' => $target_user_data['id'],
                    'name' => $target_user_data['name'] . ($target_user_data['role'] !== 'client' ? " (".ucfirst($target_user_data['role']).")" : ""),
                    'avatar' => $avatar_url_target,
                    'last_message' => '', // Placeholder
                    'timestamp' => null, // Placeholder
                    'unread_count' => 0 // Placeholder
                ];
            }
        } catch (PDOException $e) {
            error_log("Erro BD ao buscar utilizador alvo para chat (chat.php): " . $e->getMessage());
            // Não definir $page_error aqui, pois pode haver outras conversas válidas.
        }
    }
}
// --- FIM ETAPA 2 ---

// --- ETAPA 3: Determinar chat ativo ---
$active_chat_user_id = null;
$active_chat_user_name = 'Selecione uma conversa';
$active_chat_user_avatar = 'assets/img/avatars/default.png';

if ($target_user_id_from_get && isset($conversations[$target_user_id_from_get])) {
    $active_chat_user_id = $target_user_id_from_get;
    $active_chat_user_name = $conversations[$active_chat_user_id]['name'];
    $active_chat_user_avatar = $conversations[$active_chat_user_id]['avatar'];
} elseif (!empty($conversations)) {
    $first_conversation = reset($conversations);
    $active_chat_user_id = $first_conversation['user_id'];
    $active_chat_user_name = $first_conversation['name'];
    $active_chat_user_avatar = $first_conversation['avatar'];
} else {
    if ($target_user_id_from_get) { $active_chat_user_name = "Utilizador para chat não encontrado."; }
    elseif ($user_role === 'client') { $active_chat_user_name = "Nenhum Profissional Atribuído"; }
    elseif (in_array($user_role, ['trainer', 'nutritionist'])) { $active_chat_user_name = "Nenhum cliente ativo"; }
    else { $active_chat_user_name = "Nenhuma conversa disponível"; }
}

if (!empty($page_error) && !$active_chat_user_id) { // Se houve erro na Etapa 1 e não há chat ativo
    $active_chat_user_name = $page_error;
}
// --- FIM ETAPA 3 ---

// Define qual link da sidebar deve estar ativo
$active_page = basename($_SERVER['PHP_SELF']);

// Inclui a sidebar correta
if ($user_role == 'client') {
    include 'sidebar_client.php';
} elseif ($user_role == 'trainer') {
    include 'sidebar_trainer.php';
} elseif ($user_role == 'nutritionist') {
    include 'sidebar_nutritionist.php';
} elseif ($user_role == 'admin') {
    include 'sidebar_admin.php';
} else {
    // Fallback
}
?>

<!-- Estilos CSS para o novo layout -->
<style>
    :root {
        /* Cores base (Light Mode - Fundo Cinza Claro Global) */
        --chat-bg: #F0F2F5;
        --chat-text: #1F2937;
        --chat-border: #D1D5DB;
        --chat-list-bg: var(--chat-bg);
        --chat-list-hover-bg: #E5E7EB;
        --chat-list-active-bg: #DBEAFE; /* Azul claro para ativo na lista */
        --chat-list-active-border: #3B82F6; /* Borda azul para ativo */
        --chat-header-bg: var(--chat-bg);
        --chat-input-bg: var(--chat-bg);
        --chat-input-border: #D1D5DB;
        /* *** ALTERADO: Cores das Bolhas Enviadas para Azul *** */
        --chat-sent-bg-whatsapp: #DBEAFE; /* Azul claro */
        --chat-sent-text-whatsapp: #1E40AF; /* Texto azul escuro para contraste */
        /* Cores das Bolhas Recebidas (Mantidas) */
        --chat-received-bg-whatsapp: #FFFFFF;
        --chat-received-text-whatsapp: #1F2937;
        /* Outras cores */
        --chat-placeholder-text: #6B7280;
        --chat-icon-color: #54656F;
        --chat-online-indicator: #10B981;
        --chat-avatar-size: 40px;
        --chat-header-avatar-size: 48px;
        --chat-message-avatar-size: 32px;
        /* Fundo padrão da área de mensagens (light) */
        /* Removida --chat-messages-default-bg-light, usaremos --chat-bg diretamente */
    }

    .dark-mode {
        /* Fundo Cinza Escuro Global */
        --chat-bg: #111B21;
        --chat-text: #E1E2E5;
        --chat-border: #374151;
        --chat-list-bg: var(--chat-bg);
        --chat-list-hover-bg: #202C33;
        --chat-list-active-bg: #2A3942;
        --chat-list-active-border: #008069; /* Mantido verde escuro para lista ativa dark */
        --chat-header-bg: #202C33;
        --chat-input-bg: #202C33;
        --chat-input-border: #374151;
        /* *** ALTERADO: Cores das Bolhas Enviadas para Azul (Dark) *** */
        --chat-sent-bg-whatsapp: #2563EB; /* Azul mais escuro */
        --chat-sent-text-whatsapp: #E0E7FF; /* Texto claro azulado */
        /* Cores das Bolhas Recebidas (Mantidas) */
        --chat-received-bg-whatsapp: #202C33;
        --chat-received-text-whatsapp: #E1E2E5;
        /* Outras cores */
        --chat-placeholder-text: #8696A0;
        --chat-icon-color: #AEBAC1;
        /* Fundo padrão da área de mensagens (dark) */
        /* Removida --chat-messages-default-bg-dark, usaremos --chat-bg diretamente */
    }

    /* Estrutura Principal do Chat */
    .main-content {
        padding: 0;
        height: calc(100vh - 60px);
        overflow: hidden;
    }

    /* Garante que o wrapper principal use a cor de fundo definida */
    .chat-wrapper {
        display: flex;
        height: 100%;
        background-color: var(--chat-bg); /* Aplica a cor de fundo global */
        color: var(--chat-text);
    }

    /* Coluna da Lista de Conversas */
    .conversation-list-column {
        width: 320px;
        border-right: 1px solid var(--chat-border);
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
        background-color: var(--chat-list-bg); /* Usa a variável da lista */
    }

    /* Ajusta fundo dos cabeçalhos */
    .conversation-list-header,
    .chat-header {
        padding: 1rem;
        border-bottom: 1px solid var(--chat-border);
        background-color: var(--chat-header-bg); /* Usa a variável do header */
    }
    .chat-header { padding: 0.75rem 1.5rem; } /* Padding específico do header do chat */

    /* Ajusta o fundo do input de busca na lista */
    .conversation-search input {
        background-color: #FFFFFF; /* Input branco no modo claro */
        border: 1px solid var(--chat-input-border);
        color: var(--chat-text);
    }
    .dark-mode .conversation-search input {
        background-color: #2A3942; /* Input cinza claro no modo escuro */
        border-color: var(--chat-input-border);
        color: var(--chat-text);
    }
     .conversation-search input::placeholder {
        color: var(--chat-placeholder-text);
    }
     .conversation-search .input-group-text {
        background-color: #FFFFFF; /* Ícone com fundo branco no modo claro */
        border-color: var(--chat-input-border);
        color: var(--chat-icon-color);
    }
     .dark-mode .conversation-search .input-group-text {
        background-color: #2A3942; /* Ícone com fundo cinza claro no modo escuro */
        border-color: var(--chat-input-border);
        color: var(--chat-icon-color);
    }

    .conversation-list {
        flex-grow: 1;
        overflow-y: auto;
        padding: 0.5rem 0;
    }

    .conversation-item {
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
        cursor: pointer;
        border-left: 3px solid transparent;
        transition: background-color 0.2s ease, border-color 0.2s ease;
        text-decoration: none;
        color: inherit;
    }

    .conversation-item:hover {
        background-color: var(--chat-list-hover-bg);
    }

    .conversation-item.active {
        background-color: var(--chat-list-active-bg);
        border-left-color: var(--chat-list-active-border);
    }

    .conversation-item .avatar {
        width: var(--chat-avatar-size);
        height: var(--chat-avatar-size);
        border-radius: 50%;
        margin-right: 0.75rem;
        object-fit: cover;
    }

    .conversation-details {
        flex-grow: 1;
        overflow: hidden;
    }

    .conversation-name {
        font-weight: 600;
        margin-bottom: 0.1rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .conversation-last-message {
        font-size: 0.85em;
        color: var(--chat-placeholder-text);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .conversation-meta {
        text-align: right;
        font-size: 0.75em;
        color: var(--chat-placeholder-text);
        margin-left: 0.5rem;
        flex-shrink: 0;
    }

    .conversation-time {
        display: block;
        margin-bottom: 0.25rem;
    }

    .conversation-unread-badge {
        background-color: var(--chat-list-active-border);
        color: var(--chat-bg); /* Contraste com fundo global */
        font-size: 0.7em;
        padding: 0.15rem 0.4rem;
        border-radius: 10px;
        font-weight: bold;
    }
    .dark-mode .conversation-unread-badge {
         color: var(--chat-text);
    }


    /* Coluna do Chat Ativo */
    .active-chat-column {
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        height: 100%;
    }

    .chat-header .avatar {
        width: var(--chat-header-avatar-size);
        height: var(--chat-header-avatar-size);
        border-radius: 50%;
        margin-right: 1rem;
        object-fit: cover;
    }

    .chat-header-info {
        flex-grow: 1;
    }

    .chat-header-name {
        font-size: 1.1em;
        font-weight: 600;
        margin-bottom: 0;
        color: var(--chat-text);
    }

    .chat-header-status {
        font-size: 0.8em;
        color: var(--chat-online-indicator);
    }
    .chat-header-status.offline {
        color: var(--chat-placeholder-text);
    }

    .chat-header-actions .btn {
        color: var(--chat-icon-color);
        font-size: 1.1rem;
        padding: 0.5rem;
        margin-left: 0.5rem;
    }
     .chat-header-actions .btn:hover {
        color: var(--chat-text);
    }

    /* Área de Mensagens */
    .chat-messages {
        flex-grow: 1;
        overflow-y: auto;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        position: relative;
        background-color: var(--chat-bg); /* Usa a cor de fundo principal do chat */
        background-size: cover; /* Ou 'auto', 'contain', dependendo do efeito */
        background-position: center;
        background-repeat: no-repeat; /* Ou 'repeat' se for um padrão pequeno */
    }
    .dark-mode .chat-messages {
        background-color: var(--chat-bg); /* Continua usando a cor de fundo principal do chat no modo escuro */
    }

    /* Overlay para fundos personalizados */
    .chat-messages::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
        background-color: transparent; z-index: 1; pointer-events: none; opacity: 0;
        transition: opacity 0.3s ease, background-color 0.3s ease;
    }

    /* Regras para quando HÁ fundo personalizado */
    .chat-messages.has-custom-background {
        background-color: transparent; /* Torna transparente para ver imagem por baixo */
        /* A imagem personalizada será definida pelo JS via style.backgroundImage */
    }
    .chat-messages.has-custom-background::before {
        /* Overlay SUAVE para melhorar legibilidade sobre imagens customizadas */
        background-color: rgba(255, 255, 255, 0.05);
        opacity: 1;
    }
    .dark-mode .chat-messages.has-custom-background::before {
        background-color: rgba(0, 0, 0, 0.3);
    }


    /* Wrapper para Avatar + Bolha */
    .message-wrapper {
        display: flex;
        align-items: flex-end;
        gap: 0.6rem;
        max-width: 85%;
    }
    .sent-wrapper {
        justify-content: flex-end;
        margin-left: auto;
    }
    .received-wrapper {
        justify-content: flex-start;
        margin-right: auto;
    }

    /* Avatar da Mensagem */
    .message-avatar {
        width: var(--chat-message-avatar-size);
        height: var(--chat-message-avatar-size);
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
        margin-bottom: 5px;
    }

    /* Bolhas de Mensagem */
    .message-bubble {
        padding: 0.6rem 0.9rem;
        border-radius: 8px; /* Menos arredondado, como WhatsApp */
        max-width: 100%;
        word-wrap: break-word;
        position: relative;
        z-index: 2;
        line-height: 1.4;
        box-shadow: 0 1px 1px rgba(0, 0, 0, 0.08);
    }

    .message-bubble.sent {
        background-color: var(--chat-sent-bg-whatsapp); /* Usa a variável azul */
        color: var(--chat-sent-text-whatsapp);
    }

    .message-bubble.received {
        background-color: var(--chat-received-bg-whatsapp); /* Usa a variável branca/cinza */
        color: var(--chat-received-text-whatsapp);
    }

    /* Adiciona "rabicho" como no WhatsApp (opcional) */
    .message-bubble.sent::after, .message-bubble.received::before {
        content: "";
        position: absolute;
        bottom: 0px;
        width: 0;
        height: 0;
        border: 10px solid transparent;
    }
    .message-bubble.sent::after {
        right: -8px;
        border-left-color: var(--chat-sent-bg-whatsapp); /* Usa a variável azul */
        border-right: 0;
        border-bottom: 0;
    }
    .message-bubble.received::before {
        left: -8px;
        border-right-color: var(--chat-received-bg-whatsapp); /* Usa a variável branca/cinza */
        border-left: 0;
        border-bottom: 0;
    }
    /* Esconde rabicho se houver imagem/vídeo */
    .message-bubble:has(img)::after, .message-bubble:has(video)::after,
    .message-bubble:has(img)::before, .message-bubble:has(video)::before {
        display: none;
    }


    .message-content {
        display: flex; flex-direction: column;
    }
    .message-caption {
        font-size: 0.9em; margin-bottom: 5px;
    }
    /* Imagem/Vídeo */
    .message-bubble img, .message-bubble video {
        display: block; max-width: 300px; max-height: 250px; width: auto; height: auto;
        object-fit: cover; border-radius: 6px; margin-top: 5px; cursor: pointer;
    }
    .message-bubble video { max-width: 350px; max-height: 280px; }

    /* Player de Áudio */
    .custom-audio-player { display: flex; align-items: center; gap: 0.5rem; background-color: rgba(0,0,0,0.05); padding: 8px 12px; border-radius: 15px; max-width: 300px; }
    .dark-mode .custom-audio-player { background-color: rgba(255,255,255,0.1); }
    .custom-audio-player .play-pause-btn { background: none; border: none; color: var(--chat-icon-color); font-size: 1.2rem; cursor: pointer; padding: 0; }
    .custom-audio-player .duration { font-size: 0.8em; color: var(--chat-placeholder-text); margin-left: auto; }
    .waveform-wrapper { flex-grow: 1; height: 30px; position: relative; cursor: pointer; overflow: hidden; background-color: rgba(0,0,0,0.05); border-radius: 3px; }
    .dark-mode .waveform-wrapper { background-color: rgba(255,255,255,0.08); }
    .waveform-container { display: flex; align-items: flex-end; height: 100%; gap: 1px; }
    .waveform-bar { background-color: var(--chat-icon-color); width: 2px; flex-grow: 1; }
    .waveform-progress-overlay { position: absolute; top: 0; left: 0; height: 100%; width: 0%; background-color: var(--chat-list-active-border); opacity: 0.7; pointer-events: none; border-radius: 3px; }


    .timestamp {
        font-size: 0.7em;
        color: var(--chat-placeholder-text);
        margin-top: 0.3rem;
        text-align: right;
        float: right;
        margin-left: 10px;
        line-height: 1;
        clear: both;
    }
    .message-bubble.received .timestamp {
        text-align: right;
        color: var(--chat-placeholder-text);
    }
    .dark-mode .timestamp {
        color: #8696A0;
    }

    /* Área de Input */
    .chat-input-area {
        padding: 0.75rem 1.5rem;
        border-top: 1px solid var(--chat-border);
        background-color: var(--chat-input-bg); /* Usa a variável da área de input */
        flex-shrink: 0;
    }

    .chat-input-area .input-group {
        align-items: center;
    }

    /* Ajusta o fundo do input principal de mensagem */
    .chat-input-area #message-input {
        border-radius: 20px;
        border: 1px solid var(--chat-input-border);
        background-color: #FFFFFF; /* Input branco no modo claro */
        padding: 0.6rem 1rem;
        color: var(--chat-text);
        box-shadow: none;
    }
    .dark-mode .chat-input-area #message-input {
        background-color: #2A3942; /* Input cinza claro no modo escuro */
        border-color: var(--chat-input-border);
        color: var(--chat-text);
    }

    .chat-input-area #message-input::placeholder {
        color: var(--chat-placeholder-text);
    }
    .chat-input-area #message-input:focus {
         border-color: var(--chat-list-active-border);
         box-shadow: none;
    }

    .chat-input-area .btn {
        border-radius: 50%;
        width: 40px;
        height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-left: 0.5rem;
        font-size: 1.1rem;
        padding: 0;
        border: none;
        background-color: transparent;
        color: var(--chat-icon-color);
    }

     .chat-input-area #attach-button:hover,
     .chat-input-area #send-button:hover:not(:disabled) {
        background-color: var(--chat-list-hover-bg);
    }
    .dark-mode .chat-input-area #attach-button:hover,
    .dark-mode .chat-input-area #send-button:hover:not(:disabled) {
        background-color: var(--chat-list-hover-bg);
    }


    .chat-input-area #send-button:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    /* Preview de ficheiro */
    #file-preview-area { /* ... estilos ... */ }

    /* Placeholder para quando não há conversa selecionada */
    .no-chat-selected {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        text-align: center;
        color: var(--chat-placeholder-text);
        background-color: var(--chat-bg); /* Usa o fundo global */
    }
     .no-chat-selected i {
        font-size: 4rem;
        margin-bottom: 1rem;
    }

    /* Responsividade */
    @media (max-width: 768px) {
        .conversation-list-column {
            width: 100%;
            border-right: none;
            position: absolute;
            left: -100%;
            transition: left 0.3s ease;
            z-index: 10;
            height: 100%;
        }
        .conversation-list-column.open {
            left: 0;
        }
        .active-chat-column {
            width: 100%;
        }
        .chat-header .menu-toggle-btn { display: inline-block; margin-right: 1rem; }
        .conversation-list-header .close-list-btn { display: inline-block; }
    }
    @media (min-width: 769px) {
        .chat-header .menu-toggle-btn { display: none; }
        .conversation-list-header .close-list-btn { display: none; }
    }

    /* Estilos para Modal */
    .modal-close-button { position: absolute; top: 15px; right: 15px; font-size: 1.5rem; background: rgba(0,0,0,0.5); border-radius: 50%; color: white; opacity: 0.8; padding: 0.5rem 0.8rem; line-height: 1; }
    .modal-close-button:hover { opacity: 1; }
    .modal-media-container { /* ... */ }
    .modal-download-action { position: absolute; bottom: 20px; right: 20px; color: white; background: rgba(0,0,0,0.5); padding: 0.5rem 0.8rem; border-radius: 5px; text-decoration: none; font-size: 1.1rem; }
    .modal-download-action:hover { background: rgba(0,0,0,0.7); }
    .predefined-bg-options-modal { /* ... */ }
    .predefined-bg-thumb-modal { width: 100px; height: 100px; object-fit: cover; border-radius: 5px; cursor: pointer; border: 2px solid transparent; transition: border-color 0.2s ease; }
    .predefined-bg-thumb-modal:hover { border-color: var(--chat-list-active-border); }

</style>

<!-- Conteúdo Principal do Chat com Novo Layout -->
<main class="main-content">
    <div class="chat-wrapper">

        <!-- Coluna da Lista de Conversas -->
        <div class="conversation-list-column" id="conversation-list-column">
            <div class="conversation-list-header">
                <button class="btn btn-sm btn-icon float-end close-list-btn" onclick="toggleConversationList(false)">
                    <i class="fas fa-times"></i>
                </button>
                <h4 style="color: var(--bs-body-color);">Mensagens</h4>
                <div class="input-group mt-3 conversation-search">
                    <span class="input-group-text border-end-0"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control border-start-0" placeholder="Procurar conversa...">
                </div>
            </div>
            <div class="conversation-list" id="conversation-list">
                <?php if (!empty($conversations)): ?>
                    <?php foreach ($conversations as $conv_user_id => $conv_data): ?>
                        <a href="chat.php?user_id=<?= $conv_user_id ?>"
                           class="conversation-item <?= ($conv_user_id == $active_chat_user_id) ? 'active' : '' ?>"
                           data-user-id="<?= $conv_user_id ?>">
                            <img src="<?= htmlspecialchars($conv_data['avatar']) ?>" alt="<?= htmlspecialchars($conv_data['name']) ?>" class="avatar">
                            <div class="conversation-details">
                                <div class="conversation-name"><?= htmlspecialchars($conv_data['name']) ?></div>
                                <div class="conversation-last-message"><?= htmlspecialchars($conv_data['last_message'] ?: 'Nenhuma mensagem ainda') ?></div>
                            </div>
                            <div class="conversation-meta">
                                <span class="conversation-time"><?= $conv_data['timestamp'] ? date('H:i', strtotime($conv_data['timestamp'])) : '' ?></span>
                                <?php if ($conv_data['unread_count'] > 0): ?>
                                    <span class="badge conversation-unread-badge"><?= $conv_data['unread_count'] ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center text-muted p-3">Nenhuma conversa encontrada.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Coluna do Chat Ativo -->
        <div class="active-chat-column">
            <?php if ($active_chat_user_id): // Mostra chat se houver conversa ativa ?>
                <div class="chat-header">
                     <button class="btn btn-icon menu-toggle-btn" onclick="toggleConversationList(true)">
                         <i class="fas fa-bars"></i>
                     </button>
                    <img src="<?= htmlspecialchars($active_chat_user_avatar) ?>" alt="<?= htmlspecialchars($active_chat_user_name) ?>" class="avatar">
                    <div class="chat-header-info">
                        <h5 class="chat-header-name mb-0"><?= htmlspecialchars($active_chat_user_name) ?></h5>
                        <!-- <span class="chat-header-status">Online</span> -->
                    </div>
                    <div class="chat-header-actions">
                        <!-- <button class="btn btn-icon"><i class="fas fa-phone-alt"></i></button> -->
                        <!-- <button class="btn btn-icon"><i class="fas fa-video"></i></button> -->
                        <button class="btn btn-icon" type="button" data-bs-toggle="modal" data-bs-target="#backgroundModal" title="Personalizar Fundo"><i class="fas fa-image"></i></button>
                        <div class="dropdown d-inline-block">
                            <button class="btn btn-icon" type="button" id="chatSettingsDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Personalizar Cores">
                                <i class="fas fa-palette"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end p-3" aria-labelledby="chatSettingsDropdown" style="min-width: 250px;">
                                <!-- Inputs de cor usam as variáveis --chat-*-whatsapp -->
                                <li class="mb-2"><label for="chatSentBg" class="form-label small mb-1">Fundo (Enviadas):</label><input type="color" class="form-control form-control-color" id="chatSentBg" onchange="updateChatColor('--chat-sent-bg-whatsapp', this.value)" title="Escolher cor de fundo das mensagens enviadas"></li>
                                <li class="mb-2"><label for="chatSentText" class="form-label small mb-1">Texto (Enviadas):</label><input type="color" class="form-control form-control-color" id="chatSentText" onchange="updateChatColor('--chat-sent-text-whatsapp', this.value)" title="Escolher cor do texto das mensagens enviadas"></li>
                                <li class="mb-2"><label for="chatReceivedBg" class="form-label small mb-1">Fundo (Recebidas):</label><input type="color" class="form-control form-control-color" id="chatReceivedBg" onchange="updateChatColor('--chat-received-bg-whatsapp', this.value)" title="Escolher cor de fundo das mensagens recebidas"></li>
                                <li class="mb-2"><label for="chatReceivedText" class="form-label small mb-1">Texto (Recebidas):</label><input type="color" class="form-control form-control-color" id="chatReceivedText" onchange="updateChatColor('--chat-received-text-whatsapp', this.value)" title="Escolher cor do texto das mensagens recebidas"></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><button type="button" class="btn btn-secondary btn-sm w-100" onclick="resetChatColors()">Restaurar Cores Padrão</button></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body chat-messages" id="chat-messages">
                    <!-- Mensagens serão carregadas aqui -->
                </div>
                <div class="card-footer chat-input-area">
                    <form id="chat-form" autocomplete="off">
                        <div class="input-group">
                            <button type="button" class="btn btn-icon" id="attach-button" title="Anexar ficheiro">
                                <i class="fas fa-paperclip"></i>
                            </button>
                            <input type="file" id="file-input" accept="image/*,audio/*,video/*" style="display: none;">
                            <input type="text" id="message-input" class="form-control" placeholder="Digite sua mensagem..." aria-label="Mensagem">
                            <input type="hidden" id="receiver_id" value="<?= htmlspecialchars($active_chat_user_id) ?>">
                            <button type="submit" class="btn btn-icon" id="send-button" aria-label="Enviar Mensagem">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                        <div id="file-preview-area" class="mt-2" style="display: none;">
                            <span class="badge bg-light text-dark p-2">
                                <i class="fas fa-file me-1"></i>
                                <span id="selected-file-name"></span>
                                <button type="button" class="btn-close btn-sm ms-2" aria-label="Remover ficheiro" id="remove-file-button"></button>
                            </span>
                        </div>
                    </form>
                </div>
            <?php else: // Mostra placeholder se nenhuma conversa estiver ativa/selecionada ?>
                <div class="no-chat-selected">
                    <i class="fas fa-comments"></i>
                    <p><?= htmlspecialchars($active_chat_user_name) ?></p>
                    <?php if ($user_role !== 'client' && empty($conversations)): ?>
                         <p>Adicione planos aos seus clientes para iniciar conversas.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Modais (Mantidos) -->
<!-- Modal Media Preview -->
<div class="modal fade" id="mediaPreviewModal" tabindex="-1" aria-labelledby="mediaPreviewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content" style="background: transparent; border: none;">
        <button type="button" class="btn-close modal-close-button" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="modal-media-container d-flex align-items-center justify-content-center h-100"></div>
        <a href="#" id="modal-download-action" class="modal-download-action" download><i class="fas fa-download"></i></a>
    </div>
  </div>
</div>
<!-- Modal Background Customization -->
<div class="modal fade" id="backgroundModal" tabindex="-1" aria-labelledby="backgroundModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="backgroundModalLabel">Personalizar Fundo da Conversa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <button type="button" class="btn btn-outline-secondary w-100" id="set-default-background-button-modal">
                <i class="fas fa-ban fa-fw me-2"></i>Usar Fundo Padrão
            </button>
        </div>
        <hr>
        <h6 class="text-muted mb-2">Escolha um fundo predefinido:</h6>
        <div class="predefined-bg-options-modal d-flex flex-wrap gap-2 justify-content-center mb-3">
             <img src="/Projeto_PAP/assets/img/chat_backgrounds/whatsapp_default_light.png" class="predefined-bg-thumb-modal" data-bg-path="/Projeto_PAP/assets/img/chat_backgrounds/whatsapp_default_light.png" alt="Padrão Claro" title="Padrão Claro">
             <img src="/Projeto_PAP/assets/img/chat_backgrounds/whatsapp_default_dark.png" class="predefined-bg-thumb-modal" data-bg-path="/Projeto_PAP/assets/img/chat_backgrounds/whatsapp_default_dark.png" alt="Padrão Escuro" title="Padrão Escuro">
             <img src="/Projeto_PAP/assets/img/chat_backgrounds/img1.png" class="predefined-bg-thumb-modal" data-bg-path="/Projeto_PAP/assets/img/chat_backgrounds/img1.png" alt="Fundo 1" title="Fundo 1">
             <img src="/Projeto_PAP/assets/img/chat_backgrounds/img2.png" class="predefined-bg-thumb-modal" data-bg-path="/Projeto_PAP/assets/img/chat_backgrounds/img2.png" alt="Fundo 2" title="Fundo 2">
        </div>
        <hr>
        <div class="text-center">
            <label for="chat-background-input-modal" class="btn btn-primary">
                <i class="fas fa-upload fa-fw me-2"></i>Carregar Imagem
            </label>
            <input type="file" id="chat-background-input-modal" accept="image/*" style="display: none;">
        </div>
      </div>
    </div>
  </div>
</div>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Seletores de Elementos ---
    const chatMessagesContainer = document.getElementById('chat-messages');
    const chatForm = document.getElementById('chat-form');
    const messageInput = document.getElementById('message-input');
    const sendButton = document.getElementById('send-button');
    const receiverIdInput = document.getElementById('receiver_id');
    const attachButton = document.getElementById('attach-button');
    const fileInput = document.getElementById('file-input');
    const filePreviewArea = document.getElementById('file-preview-area');
    const selectedFileNameSpan = document.getElementById('selected-file-name');
    const removeFileButton = document.getElementById('remove-file-button');
    const conversationListContainer = document.getElementById('conversation-list');
    const conversationListColumn = document.getElementById('conversation-list-column');

    // Seletores para personalização
    const chatBackgroundInputModal = document.getElementById('chat-background-input-modal');
    const setDefaultBackgroundButtonModal = document.getElementById('set-default-background-button-modal');
    const predefinedBgOptionsContainerModal = document.querySelector('.predefined-bg-options-modal');
    const sentBgInput = document.getElementById('chatSentBg');
    const sentTextInput = document.getElementById('chatSentText');
    const receivedBgInput = document.getElementById('chatReceivedBg');
    const receivedTextInput = document.getElementById('chatReceivedText');

    // --- Variáveis Globais ---
    const currentUserId = <?= json_encode($user_id) ?>;
    const activeChatUserId = receiverIdInput ? parseInt(receiverIdInput.value, 10) : null;
    let isFetching = false;
    let pollingInterval;
    let selectedFile = null;

    // --- Constantes ---
    const CHAT_BACKGROUND_KEY = 'chatBackground';
    const CHAT_COLORS_KEY = 'chatColors_whatsapp'; // Chave para cores WhatsApp
    const PROFILE_PIC_BASE_PATH = 'assets/img/profile_pics/';
    const DEFAULT_AVATAR_PATH = 'assets/img/avatars/default.png';
    const BASE_IMG_URL = '/Projeto_PAP';

    // --- Funções Auxiliares ---
    function scrollToBottom() {
        setTimeout(() => {
            if(chatMessagesContainer) {
                chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
            }
        }, 100);
    }

    function formatTimestamp(sqlTimestamp) {
        try {
            const date = new Date(sqlTimestamp.includes('Z') || sqlTimestamp.includes('+') || sqlTimestamp.includes('-') ? sqlTimestamp : sqlTimestamp.replace(' ', 'T') + 'Z');
            if (isNaN(date)) { return sqlTimestamp; }
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        } catch (e) { return sqlTimestamp; }
    }

    // --- Funções de Exibição ---
    function displayMessages(messages) {
        if (!chatMessagesContainer) return;

        const scrollThreshold = 50;
        const isNearBottom = chatMessagesContainer.scrollHeight - chatMessagesContainer.scrollTop - chatMessagesContainer.clientHeight < scrollThreshold;
        let newMessagesAdded = false;

        messages.forEach(msg => {
            if (!document.getElementById(`msg-${msg.id}`)) {
                newMessagesAdded = true;
                const isSent = parseInt(msg.sender_id, 10) === currentUserId;

                const messageWrapper = document.createElement('div');
                messageWrapper.classList.add('message-wrapper');
                messageWrapper.classList.add(isSent ? 'sent-wrapper' : 'received-wrapper');

                if (!isSent) {
                    const avatarImg = document.createElement('img');
                    avatarImg.classList.add('message-avatar');
                    let avatarSrc = DEFAULT_AVATAR_PATH;
                    if (msg.sender_avatar) {
                        avatarSrc = PROFILE_PIC_BASE_PATH + msg.sender_avatar;
                    }
                    avatarImg.src = avatarSrc;
                    avatarImg.onerror = function() { this.src = DEFAULT_AVATAR_PATH; };
                    avatarImg.alt = msg.sender_name || 'Avatar';
                    messageWrapper.appendChild(avatarImg);
                }

                const messageDiv = document.createElement('div');
                messageDiv.id = `msg-${msg.id}`;
                messageDiv.classList.add('message-bubble');
                messageDiv.classList.add(isSent ? 'sent' : 'received');

                const messageContentDiv = document.createElement('div');
                messageContentDiv.classList.add('message-content');

                // Legenda (se houver)
                if (msg.message && msg.message.trim() !== '') {
                    const captionDiv = document.createElement('div');
                    captionDiv.classList.add('message-caption');
                    captionDiv.textContent = msg.message;
                    messageContentDiv.appendChild(captionDiv);
                }

                // Mídia (Imagem, Áudio, Vídeo)
                const fileUrlPrefix = '';
                switch (msg.message_type) {
                    case 'image':
                        const img = document.createElement('img');
                        img.src = fileUrlPrefix + msg.file_url;
                        img.alt = msg.file_name || 'Imagem enviada';
                        img.addEventListener('click', () => openMediaModal('image', fileUrlPrefix + msg.file_url, msg.file_name));
                        messageContentDiv.appendChild(img);
                        break;
                    case 'audio':
                        const audioPlayerContainer = document.createElement('div');
                        audioPlayerContainer.classList.add('custom-audio-player', 'waveform-player');
                        audioPlayerContainer.id = `audio-player-${msg.id}`;
                        audioPlayerContainer.innerHTML = `
                            <audio id="audio-element-${msg.id}" src="${fileUrlPrefix + msg.file_url}" preload="metadata"></audio>
                            <button class="play-pause-btn"><i class="fas fa-play"></i></button>
                            <div class="waveform-wrapper">
                                <div class="waveform-container"></div>
                                <div class="waveform-progress-overlay"></div>
                            </div>
                            <span class="duration">00:00</span>
                        `;
                        messageContentDiv.appendChild(audioPlayerContainer);
                        setTimeout(() => setupAudioPlayer(msg.id), 0);
                        break;
                    case 'video':
                        const videoThumb = document.createElement('video');
                        videoThumb.src = fileUrlPrefix + msg.file_url + '#t=0.1';
                        videoThumb.muted = true;
                        videoThumb.preload = 'metadata';
                        videoThumb.alt = msg.file_name || 'Vídeo enviado';
                        videoThumb.addEventListener('click', () => openMediaModal('video', fileUrlPrefix + msg.file_url, msg.file_name));
                        messageContentDiv.appendChild(videoThumb);
                        break;
                    case 'text':
                    default:
                        if (!msg.file_url && msg.message && messageContentDiv.children.length === 0) {
                             const textDiv = document.createElement('div');
                             textDiv.textContent = msg.message;
                             messageContentDiv.appendChild(textDiv);
                        }
                        break;
                }

                messageDiv.appendChild(messageContentDiv);

                // Timestamp
                const timestampDiv = document.createElement('div');
                timestampDiv.classList.add('timestamp');
                timestampDiv.textContent = formatTimestamp(msg.timestamp);
                messageDiv.appendChild(timestampDiv);

                messageWrapper.appendChild(messageDiv);
                chatMessagesContainer.appendChild(messageWrapper);
            }
        });

        if (isNearBottom && newMessagesAdded) {
            scrollToBottom();
        }
    }

    function openMediaModal(mediaType, mediaUrl, fileName) {
        const modalMediaContainer = document.querySelector('#mediaPreviewModal .modal-media-container');
        const modalDownloadButton = document.getElementById('modal-download-action');
        const mediaModalElement = document.getElementById('mediaPreviewModal');
        if (modalMediaContainer && modalDownloadButton && mediaModalElement) {
            modalMediaContainer.innerHTML = '';
            let mediaElement;
            if (mediaType === 'image') {
                mediaElement = document.createElement('img');
                mediaElement.style.maxWidth = '90vw'; mediaElement.style.maxHeight = '90vh'; mediaElement.style.objectFit = 'contain';
            } else if (mediaType === 'video') {
                mediaElement = document.createElement('video');
                mediaElement.controls = true; mediaElement.autoplay = true;
                mediaElement.style.maxWidth = '90vw'; mediaElement.style.maxHeight = '90vh';
            } else { return; }
            mediaElement.src = mediaUrl;
            modalMediaContainer.appendChild(mediaElement);
            modalDownloadButton.href = mediaUrl;
            modalDownloadButton.download = fileName || (mediaType === 'image' ? 'imagem' : 'video');
            const mediaModal = new bootstrap.Modal(mediaModalElement);
            mediaModal.show();
            if (mediaType === 'video') {
                mediaModalElement.addEventListener('hidden.bs.modal', () => { mediaElement.pause(); }, { once: true });
            }
        } else { console.error("Elementos do modal de mídia não encontrados."); }
    }

    function setupAudioPlayer(messageId) {
        const playerContainer = document.getElementById(`audio-player-${messageId}`);
        if (!playerContainer) return;
        const audio = playerContainer.querySelector(`#audio-element-${messageId}`);
        const playPauseBtn = playerContainer.querySelector('.play-pause-btn');
        const waveformWrapper = playerContainer.querySelector('.waveform-wrapper');
        const waveformContainer = playerContainer.querySelector('.waveform-container');
        const progressOverlay = playerContainer.querySelector('.waveform-progress-overlay');
        const durationSpan = playerContainer.querySelector('.duration');

        if (!audio || !playPauseBtn || !waveformWrapper || !waveformContainer || !progressOverlay || !durationSpan) {
            console.error(`Elementos do player de áudio não encontrados para msg ${messageId}`);
            return;
        }

        const numBars = 40;
        let isPlaying = false;
        let animationFrameId;

        if (waveformContainer.children.length === 0) {
            for (let i = 0; i < numBars; i++) {
                const bar = document.createElement('div');
                bar.classList.add('waveform-bar');
                bar.style.height = `${Math.random() * 80 + 10}%`;
                waveformContainer.appendChild(bar);
            }
        }

        function formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }

        function updateDurationDisplay() {
            if (audio.duration && isFinite(audio.duration)) {
                durationSpan.textContent = formatTime(audio.duration);
            } else {
                 durationSpan.textContent = '00:00';
            }
        }

        function updatePlayPauseIcon() {
            playPauseBtn.innerHTML = isPlaying ? '<i class="fas fa-pause"></i>' : '<i class="fas fa-play"></i>';
        }

        function updateProgress() {
            if (!audio.duration) return;
            const progress = (audio.currentTime / audio.duration) * 100;
            progressOverlay.style.width = `${progress}%`;
            animationFrameId = requestAnimationFrame(updateProgress);
        }

        function togglePlayPause() {
            if (isPlaying) {
                audio.pause();
                cancelAnimationFrame(animationFrameId);
            } else {
                audio.play().catch(e => console.error("Erro ao tocar áudio:", e));
                requestAnimationFrame(updateProgress);
            }
            isPlaying = !isPlaying;
            updatePlayPauseIcon();
        }

        function seek(event) {
            if (!audio.duration) return;
            const waveformRect = waveformWrapper.getBoundingClientRect();
            const clickX = event.clientX - waveformRect.left;
            const percentage = clickX / waveformRect.width;
            audio.currentTime = audio.duration * percentage;
            progressOverlay.style.width = `${percentage * 100}%`;
        }

        audio.addEventListener('loadedmetadata', updateDurationDisplay);
        audio.addEventListener('ended', () => {
            isPlaying = false;
            updatePlayPauseIcon();
            cancelAnimationFrame(animationFrameId);
            progressOverlay.style.width = '0%';
            audio.currentTime = 0;
        });
        playPauseBtn.addEventListener('click', togglePlayPause);
        waveformWrapper.addEventListener('click', seek);

        updateDurationDisplay();
    }

    // --- Funções de Comunicação ---
    async function fetchNewMessages() {
        if (!activeChatUserId || isFetching) return;
        isFetching = true;
        try {
            const response = await fetch(`fetch_messages.php?other_user_id=${activeChatUserId}`);
            if (!response.ok) { throw new Error(`HTTP error! status: ${response.status}`); }
            const data = await response.json();
            if (data.status === 'success') {
                displayMessages(data.messages);
            } else { console.error('Erro ao buscar mensagens (servidor):', data.message); }
        } catch (error) { console.error('Erro fetch (fetchNewMessages):', error); }
        finally { isFetching = false; }
    }

    async function sendMessage(event) {
        event.preventDefault();
        if (!activeChatUserId || !messageInput || !sendButton) return;
        const messageText = messageInput.value.trim();
        if (messageText === '' && !selectedFile) return;

        messageInput.disabled = true; sendButton.disabled = true;
        if(attachButton) attachButton.disabled = true;
        sendButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        const formData = new FormData();
        formData.append('receiver_id', activeChatUserId);
        formData.append('message', messageText);
        if (selectedFile) {
            formData.append('chat_file', selectedFile, selectedFile.name);
        }

        try {
            const response = await fetch('send_message.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.status === 'success') {
                messageInput.value = '';
                clearSelectedFile();
                await fetchNewMessages();
                scrollToBottom();
            } else {
                console.error('Erro ao enviar (servidor):', data.message);
                alert('Erro: ' + data.message);
            }
        } catch (error) {
            console.error('Erro fetch (sendMessage):', error);
            alert('Erro de rede. Tente novamente.');
        } finally {
            messageInput.disabled = false; sendButton.disabled = false;
            if(attachButton) attachButton.disabled = false;
            sendButton.innerHTML = '<i class="fas fa-paper-plane"></i>';
            messageInput.focus();
        }
    }

    // --- Funções para Anexos (Mantidas) ---
    function showSelectedFile(file) { if (!file) return; selectedFile = file; if (selectedFileNameSpan) selectedFileNameSpan.textContent = file.name; if (filePreviewArea) filePreviewArea.style.display = 'block'; if (messageInput) messageInput.placeholder = "Legenda (opcional)..."; }
    function clearSelectedFile() { selectedFile = null; if (fileInput) fileInput.value = ''; if (filePreviewArea) filePreviewArea.style.display = 'none'; if (selectedFileNameSpan) selectedFileNameSpan.textContent = ''; if (messageInput) messageInput.placeholder = "Digite sua mensagem..."; }
    if (attachButton && fileInput) { attachButton.addEventListener('click', () => fileInput.click()); }
    if (fileInput) { fileInput.addEventListener('change', (event) => { const file = event.target.files[0]; if (file) { showSelectedFile(file); } else { clearSelectedFile(); } }); }
    if (removeFileButton) { removeFileButton.addEventListener('click', clearSelectedFile); }

    // --- Funções de Personalização (Adaptadas para novas variáveis CSS e chave localStorage) ---
    window.updateChatColor = function(variableName, colorValue) {
        document.documentElement.style.setProperty(variableName, colorValue);
        const savedColors = JSON.parse(localStorage.getItem(CHAT_COLORS_KEY) || '{}');
        // Mapeia para chaves de armazenamento
        const keyMap = {
            '--chat-sent-bg-whatsapp': 'sentBg', '--chat-sent-text-whatsapp': 'sentText',
            '--chat-received-bg-whatsapp': 'receivedBg', '--chat-received-text-whatsapp': 'receivedText'
        };
        const storageKey = keyMap[variableName];
        if (storageKey) {
            savedColors[storageKey] = colorValue;
            localStorage.setItem(CHAT_COLORS_KEY, JSON.stringify(savedColors));
        }
    }

    window.resetChatColors = function() {
        const root = document.documentElement;
        // *** ALTERADO: Cores padrão com Azul para Enviadas ***
        const defaultColors = { sentBg: '#DBEAFE', sentText: '#1E40AF', receivedBg: '#FFFFFF', receivedText: '#1F2937' };
        const defaultColorsDark = { sentBg: '#2563EB', sentText: '#E0E7FF', receivedBg: '#202C33', receivedText: '#E1E2E5' };

        const currentDefaults = document.body.classList.contains('dark-mode') ? defaultColorsDark : defaultColors;

        root.style.setProperty('--chat-sent-bg-whatsapp', currentDefaults.sentBg);
        root.style.setProperty('--chat-sent-text-whatsapp', currentDefaults.sentText);
        root.style.setProperty('--chat-received-bg-whatsapp', currentDefaults.receivedBg);
        root.style.setProperty('--chat-received-text-whatsapp', currentDefaults.receivedText);

        // Atualiza pickers
        if (sentBgInput) sentBgInput.value = currentDefaults.sentBg;
        if (sentTextInput) sentTextInput.value = currentDefaults.sentText;
        if (receivedBgInput) receivedBgInput.value = currentDefaults.receivedBg;
        if (receivedTextInput) receivedTextInput.value = currentDefaults.receivedText;
        localStorage.removeItem(CHAT_COLORS_KEY);
    }

    function loadInitialColorPickerValues() {
        const savedColors = JSON.parse(localStorage.getItem(CHAT_COLORS_KEY) || '{}');
        const rootStyles = getComputedStyle(document.documentElement);
        const isDarkMode = document.body.classList.contains('dark-mode');

        // *** ALTERADO: Pega padrões com Azul para Enviadas ou do CSS atual ***
        const defaultSentBg = rootStyles.getPropertyValue('--chat-sent-bg-whatsapp').trim() || (isDarkMode ? '#2563EB' : '#DBEAFE');
        const defaultSentText = rootStyles.getPropertyValue('--chat-sent-text-whatsapp').trim() || (isDarkMode ? '#E0E7FF' : '#1E40AF');
        const defaultReceivedBg = rootStyles.getPropertyValue('--chat-received-bg-whatsapp').trim() || (isDarkMode ? '#202C33' : '#FFFFFF');
        const defaultReceivedText = rootStyles.getPropertyValue('--chat-received-text-whatsapp').trim() || (isDarkMode ? '#E1E2E5' : '#1F2937');

        // Define valores iniciais dos pickers e aplica estilos
        if (sentBgInput) sentBgInput.value = savedColors.sentBg || defaultSentBg; updateChatColor('--chat-sent-bg-whatsapp', savedColors.sentBg || defaultSentBg);
        if (sentTextInput) sentTextInput.value = savedColors.sentText || defaultSentText; updateChatColor('--chat-sent-text-whatsapp', savedColors.sentText || defaultSentText);
        if (receivedBgInput) receivedBgInput.value = savedColors.receivedBg || defaultReceivedBg; updateChatColor('--chat-received-bg-whatsapp', savedColors.receivedBg || defaultReceivedBg);
        if (receivedTextInput) receivedTextInput.value = savedColors.receivedText || defaultReceivedText; updateChatColor('--chat-received-text-whatsapp', savedColors.receivedText || defaultReceivedText);
    }

    // --- Lógica de Background (Ajustada para funcionar com CSS) ---
    function applyChatBackground(value) {
        if (!chatMessagesContainer) return;
        let imageUrl = null;

        if (value && value !== 'none') { // Se há um valor guardado (personalizado)
            if (value.startsWith('data:image/')) { imageUrl = value; }
            else if (value.startsWith('/')) { imageUrl = value; } // Caminho de imagem predefinida
        }

        if (imageUrl) { // Se temos uma imagem personalizada (base64 ou caminho)
            chatMessagesContainer.style.backgroundImage = `url('${imageUrl.replace(/'/g, "\\'")}')`;
            chatMessagesContainer.classList.add('has-custom-background');
        } else { // Se não há imagem personalizada (value é null, undefined ou 'none') -> Aplicar padrão via CSS
            chatMessagesContainer.style.backgroundImage = ''; // Limpa o estilo inline, CSS assume
            chatMessagesContainer.classList.remove('has-custom-background');
        }
    }
    const savedBackground = localStorage.getItem(CHAT_BACKGROUND_KEY);
    applyChatBackground(savedBackground); // Aplica na carga inicial

    if (setDefaultBackgroundButtonModal) {
        setDefaultBackgroundButtonModal.addEventListener('click', function() {
            localStorage.removeItem(CHAT_BACKGROUND_KEY);
            applyChatBackground(null); // Aplica o padrão correto (light/dark)
            const modal = bootstrap.Modal.getInstance(document.getElementById('backgroundModal'));
            if(modal) modal.hide();
        });
    }
    if (predefinedBgOptionsContainerModal) {
        predefinedBgOptionsContainerModal.addEventListener('click', function(event) {
            if (event.target.classList.contains('predefined-bg-thumb-modal')) {
                const imagePath = event.target.dataset.bgPath;
                if (imagePath) {
                    localStorage.setItem(CHAT_BACKGROUND_KEY, imagePath);
                    applyChatBackground(imagePath);
                    const modal = bootstrap.Modal.getInstance(document.getElementById('backgroundModal'));
                    if(modal) modal.hide();
                }
            }
        });
    }
    if (chatBackgroundInputModal) {
        chatBackgroundInputModal.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const imageUrl = e.target.result;
                    localStorage.setItem(CHAT_BACKGROUND_KEY, imageUrl);
                    applyChatBackground(imageUrl);
                    const modal = bootstrap.Modal.getInstance(document.getElementById('backgroundModal'));
                    if(modal) modal.hide();
                };
                reader.readAsDataURL(file);
                chatBackgroundInputModal.value = '';
            } else if (file) {
                alert("Imagem inválida.");
                chatBackgroundInputModal.value = '';
            }
        });
    }
     // Reaplica fundo padrão ao mudar tema (light/dark)
    const themeToggleButton = document.getElementById('theme-toggle-button'); // Assumindo que seu botão de tema tem este ID
    if (themeToggleButton) {
        themeToggleButton.addEventListener('click', () => {
            // Espera um pouco para a classe .dark-mode ser aplicada/removida
            setTimeout(() => {
                const currentSavedBg = localStorage.getItem(CHAT_BACKGROUND_KEY);
                applyChatBackground(currentSavedBg); // Reaplica (usará o padrão correto se currentSavedBg for null)
                loadInitialColorPickerValues(); // Recarrega cores para o novo tema
            }, 50);
        });
    }


    // --- Função para Toggle da Lista (Mobile) ---
    window.toggleConversationList = function(open) { if (conversationListColumn) { if (open) { conversationListColumn.classList.add('open'); } else { conversationListColumn.classList.remove('open'); } } }

    // --- Inicialização ---
    loadInitialColorPickerValues(); // Carrega cores

    if (activeChatUserId && chatMessagesContainer) {
        chatMessagesContainer.innerHTML = '';
        fetchNewMessages().then(() => { scrollToBottom(); });
        pollingInterval = setInterval(fetchNewMessages, 5000);
        if(chatForm) { chatForm.addEventListener('submit', sendMessage); }
    } else {
        if(messageInput) messageInput.disabled = true;
        if(sendButton) sendButton.disabled = true;
        if(attachButton) attachButton.disabled = true;
    }

    window.addEventListener('beforeunload', () => { if (pollingInterval) { clearInterval(pollingInterval); } });

    document.addEventListener('click', function(event) {
        if (conversationListColumn && conversationListColumn.classList.contains('open')) {
            const isClickInsideList = conversationListColumn.contains(event.target);
            const isMenuButton = event.target.closest('.menu-toggle-btn');
            if (!isClickInsideList && !isMenuButton) { toggleConversationList(false); }
        }
    });

});
</script>

<?php
include 'footer.php';
?>
