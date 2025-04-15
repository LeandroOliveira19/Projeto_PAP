<?php
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
$other_user_id = null; // ID do usuário com quem se está a conversar
$other_user_name = 'Conversa'; // Nome padrão para o cabeçalho do chat

// --- INÍCIO: Lógica para identificar o parceiro de chat ---
if ($user_role === 'client') {
    try {
        $stmt_client = $pdo->prepare("SELECT id FROM clients WHERE user_id = ? LIMIT 1");
        $stmt_client->execute([$user_id]);
        $client = $stmt_client->fetch();
        if ($client) {
            $client_id = $client['id'];
            // Tenta encontrar treinador ativo primeiro
            $stmt_trainer = $pdo->prepare("
                SELECT wp.trainer_id, u.name as trainer_name
                FROM workout_plans wp
                JOIN users u ON wp.trainer_id = u.id
                WHERE wp.client_id = ? AND wp.is_active = 1
                ORDER BY wp.created_at DESC LIMIT 1
            ");
            $stmt_trainer->execute([$client_id]);
            $trainer_info = $stmt_trainer->fetch();
            if ($trainer_info) {
                $other_user_id = $trainer_info['trainer_id'];
                $other_user_name = $trainer_info['trainer_name'];
            } else {
                // Se não houver treinador ativo, tenta encontrar nutricionista ativo
                 $stmt_nutritionist = $pdo->prepare("
                    SELECT dp.nutritionist_id, u.name as nutritionist_name
                    FROM diet_plans dp
                    JOIN users u ON dp.nutritionist_id = u.id
                    WHERE dp.client_id = ? AND dp.is_active = 1 -- Assumindo que diet_plans tem is_active
                    ORDER BY dp.created_at DESC LIMIT 1
                ");
                 $stmt_nutritionist->execute([$client_id]);
                 $nutritionist_info = $stmt_nutritionist->fetch();
                 if ($nutritionist_info) {
                     $other_user_id = $nutritionist_info['nutritionist_id'];
                     $other_user_name = $nutritionist_info['nutritionist_name'] . " (Nutri)";
                 } else {
                     $other_user_name = "Nenhum Profissional Atribuído";
                 }
            }
        } else {
             $other_user_name = "Erro: Registo de cliente não encontrado.";
        }
    } catch (PDOException $e) {
        error_log("Erro BD ao buscar profissional do cliente (chat.php): " . $e->getMessage());
        $other_user_name = "Erro ao buscar Profissional";
    }

} elseif ($user_role === 'trainer') {
     if (isset($_GET['user_id']) && filter_var($_GET['user_id'], FILTER_VALIDATE_INT)) {
        $potential_client_user_id = $_GET['user_id'];
        try {
            // Verifica se o ID pertence a um cliente válido
            $stmt_check_client = $pdo->prepare("SELECT u.name FROM users u JOIN clients c ON u.id = c.user_id WHERE u.id = ? AND u.role = 'client' LIMIT 1");
            $stmt_check_client->execute([$potential_client_user_id]);
            $other_user_info = $stmt_check_client->fetch();

            if ($other_user_info) {
                 // Verifica se este treinador tem um plano ATIVO para este cliente
                 $stmt_check_relation = $pdo->prepare("
                    SELECT 1 FROM workout_plans wp
                    JOIN clients c ON wp.client_id = c.id
                    WHERE c.user_id = :client_user_id AND wp.trainer_id = :trainer_id AND wp.is_active = 1
                    LIMIT 1
                 ");
                 $stmt_check_relation->execute(['client_user_id' => $potential_client_user_id, 'trainer_id' => $user_id]);

                 if ($stmt_check_relation->fetch()) {
                    $other_user_id = $potential_client_user_id;
                    $other_user_name = $other_user_info['name'];
                 } else {
                     $other_user_id = null;
                     $other_user_name = "Não tem plano ativo com este cliente";
                 }
            } else {
                 $other_user_id = null;
                 $other_user_name = "Cliente Inválido ou não encontrado";
            }
        } catch (PDOException $e) {
             error_log("Erro BD ao buscar nome/relação do cliente para chat (trainer): " . $e->getMessage());
             $other_user_name = "Erro ao buscar Cliente";
             $other_user_id = null;
        }
    } else {
        $other_user_name = "Selecione um cliente na lista para conversar";
    }
} elseif ($user_role === 'nutritionist') {
     if (isset($_GET['user_id']) && filter_var($_GET['user_id'], FILTER_VALIDATE_INT)) {
        $potential_client_user_id = $_GET['user_id'];
         try {
             // Verifica se o ID pertence a um cliente válido
             $stmt_check_client = $pdo->prepare("SELECT u.name FROM users u JOIN clients c ON u.id = c.user_id WHERE u.id = ? AND u.role = 'client' LIMIT 1");
             $stmt_check_client->execute([$potential_client_user_id]);
             $other_user_info = $stmt_check_client->fetch();

             if ($other_user_info) {
                  // Verifica se este nutricionista tem um plano ATIVO para este cliente
                 $stmt_check_relation = $pdo->prepare("
                    SELECT 1 FROM diet_plans dp
                    JOIN clients c ON dp.client_id = c.id
                    WHERE c.user_id = :client_user_id AND dp.nutritionist_id = :nutritionist_id AND dp.is_active = 1 -- Assumindo is_active
                    LIMIT 1
                 ");
                 $stmt_check_relation->execute(['client_user_id' => $potential_client_user_id, 'nutritionist_id' => $user_id]);

                 if ($stmt_check_relation->fetch()) {
                    $other_user_id = $potential_client_user_id;
                    $other_user_name = $other_user_info['name'];
                 } else {
                     $other_user_id = null;
                     $other_user_name = "Não tem plano ativo com este cliente";
                 }
             } else {
                 $other_user_id = null;
                 $other_user_name = "Cliente Inválido ou não encontrado";
             }
        } catch (PDOException $e) {
             error_log("Erro BD ao buscar nome/relação do cliente para chat (nutri): " . $e->getMessage());
             $other_user_name = "Erro ao buscar Cliente";
             $other_user_id = null;
        }
     } else {
         $other_user_name = "Selecione um cliente na lista para conversar";
     }
} else {
    // Admin ou outro role não previsto para chat direto
    $other_user_name = "Chat não disponível para sua função";
}
// --- FIM: Lógica para identificar o parceiro de chat ---


// Define qual link da sidebar deve estar ativo
$active_page = basename($_SERVER['PHP_SELF']);

?>
<!-- Sidebar (Estrutura adaptada - SEM ALTERAÇÕES AQUI) -->
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="<?= ($user_role == 'client') ? 'dashboard.php' : (($user_role == 'trainer') ? 'trainer.php' : (($user_role == 'nutritionist') ? 'nutritionist.php' : '#')) ?>" class="logo">
            MyWellness<?= ($user_role == 'trainer') ? ' - Trainer' : (($user_role == 'nutritionist') ? ' - Nutri' : '') ?>
        </a>
    </div>
    <nav>
        <ul class="sidebar-nav">
            <?php if ($user_role == 'client'): ?>
                <li><a href="dashboard.php" class="<?= ($active_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt fa-fw"></i><span>Dashboard</span></a></li>
                <li><a href="workout_hub.php" class="<?= ($active_page == 'workout_hub.php') ? 'active' : ''; ?>"><i class="fas fa-dumbbell fa-fw"></i><span>Plano de Treino</span></a></li>
                <li><a href="nutrition_hub.php" class="<?= ($active_page == 'nutrition_hub.php') ? 'active' : ''; ?>"><i class="fas fa-utensils fa-fw"></i><span>Nutrição</span></a></li>
                <li><a href="classes.php" class="<?= ($active_page == 'classes.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-check fa-fw"></i><span>Aulas</span></a></li>
                <li><a href="profile.php" class="<?= ($active_page == 'profile.php') ? 'active' : ''; ?>"><i class="fas fa-user fa-fw"></i><span>Perfil</span></a></li>
                <li><a href="chat.php" class="<?= ($active_page == 'chat.php') ? 'active' : ''; ?>"><i class="fas fa-comments fa-fw"></i><span>Chat</span></a></li>
            <?php elseif ($user_role == 'trainer'): ?>
                <li><a href="trainer.php" class="<?= ($active_page == 'trainer.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt fa-fw"></i><span>Painel Principal</span></a></li>
                <li><a href="add_workout_plan.php" class="<?= ($active_page == 'add_workout_plan.php') ? 'active' : ''; ?>"><i class="fas fa-plus-square fa-fw"></i><span>Adicionar Plano</span></a></li>
                <li><a href="client_history.php" class="<?= ($active_page == 'client_history.php') ? 'active' : ''; ?>"><i class="fas fa-history fa-fw"></i><span>Histórico Clientes</span></a></li>
                <li><a href="profile.php" class="<?= ($active_page == 'profile.php') ? 'active' : ''; ?>"><i class="fas fa-user fa-fw"></i><span>Perfil</span></a></li>
                <li><a href="chat.php" class="<?= ($active_page == 'chat.php') ? 'active' : ''; ?>"><i class="fas fa-comments fa-fw"></i><span>Chat</span></a></li>
            <?php elseif ($user_role == 'nutritionist'): ?>
                <li><a href="nutritionist.php" class="<?= ($active_page == 'nutritionist.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt fa-fw"></i><span>Painel Principal</span></a></li>
                <li><a href="add_diet_plan.php" class="<?= ($active_page == 'add_diet_plan.php') ? 'active' : ''; ?>"><i class="fas fa-plus-square fa-fw"></i><span>Adicionar Plano</span></a></li>
                 <li><a href="client_list_nutritionist.php" class="<?= ($active_page == 'client_list_nutritionist.php') ? 'active' : ''; ?>"><i class="fas fa-users fa-fw"></i><span>Clientes</span></a></li>
                <li><a href="profile.php" class="<?= ($active_page == 'profile.php') ? 'active' : ''; ?>"><i class="fas fa-user fa-fw"></i><span>Perfil</span></a></li>
                <li><a href="chat.php" class="<?= ($active_page == 'chat.php') ? 'active' : ''; ?>"><i class="fas fa-comments fa-fw"></i><span>Chat</span></a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php"><i class="fas fa-sign-out-alt fa-fw"></i><span>Sair</span></a>
    </div>
</aside>

<!-- Conteúdo Principal do Chat -->
<main class="main-content">
    <div class="container-fluid mt-4">
        <div class="card chat-container">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <!-- Título com nome do interlocutor -->
                <h5 class="mb-0">Chat com <?= htmlspecialchars($other_user_name) ?></h5>

                 <!-- Botões de Ação -->
                <div>
                    <!-- Botão para Abrir Modal de Fundo -->
                    <button class="btn btn-sm btn-light me-2" type="button" data-bs-toggle="modal" data-bs-target="#backgroundModal" title="Personalizar Fundo da Conversa">
                        <i class="fas fa-image"></i>
                    </button>

                    <!-- Botão e Dropdown de Cores/Tema -->
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-sm btn-light" type="button" id="chatSettingsDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Personalizar Cores das Mensagens">
                            <i class="fas fa-palette"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end p-3" aria-labelledby="chatSettingsDropdown" style="min-width: 250px;">
                            <!-- REMOVIDO: Opção de Modo Escuro -->
                            <!-- Opções de Cores das Bolhas -->
                            <li class="mb-2">
                                <label for="chatSentBg" class="form-label small mb-1">Fundo (Enviadas):</label>
                                <input type="color" class="form-control form-control-color" id="chatSentBg" onchange="updateChatColor('--chat-sent-bg', this.value)" title="Escolher cor de fundo das mensagens enviadas">
                            </li>
                             <li class="mb-2">
                                <label for="chatSentText" class="form-label small mb-1">Texto (Enviadas):</label>
                                <input type="color" class="form-control form-control-color" id="chatSentText" onchange="updateChatColor('--chat-sent-text', this.value)" title="Escolher cor do texto das mensagens enviadas">
                            </li>
                             <li class="mb-2">
                                <label for="chatReceivedBg" class="form-label small mb-1">Fundo (Recebidas):</label>
                                <input type="color" class="form-control form-control-color" id="chatReceivedBg" onchange="updateChatColor('--chat-received-bg', this.value)" title="Escolher cor de fundo das mensagens recebidas">
                            </li>
                             <li class="mb-2">
                                <label for="chatReceivedText" class="form-label small mb-1">Texto (Recebidas):</label>
                                <input type="color" class="form-control form-control-color" id="chatReceivedText" onchange="updateChatColor('--chat-received-text', this.value)" title="Escolher cor do texto das mensagens recebidas">
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <button type="button" class="btn btn-secondary btn-sm w-100" onclick="resetChatColors()">Restaurar Cores Padrão</button>
                            </li>
                        </ul>
                    </div>
                    <!-- Fim do Dropdown -->
                </div>
                <!-- Fim dos Botões de Ação -->
            </div>
            <div class="card-body chat-messages" id="chat-messages">
                <!-- Mensagens serão carregadas aqui via JavaScript -->
                <?php if (!$other_user_id): // Se não foi possível determinar o parceiro de chat ?>
                    <div class="text-center text-muted mt-5 pt-5">
                        <i class="fas fa-comments fa-3x mb-3"></i><br>
                        <?php
                        echo htmlspecialchars($other_user_name);
                        // Links específicos para voltar
                        if ($user_role == 'trainer') {
                            echo ' <a href="trainer.php">Voltar ao painel principal</a>.';
                        } elseif ($user_role == 'nutritionist') {
                             echo ' <a href="nutritionist.php">Voltar ao painel principal</a>.';
                        } elseif ($user_role == 'client' && strpos($other_user_name, 'Erro') === false) {
                            echo ". Contacte o suporte se achar que isto é um erro.";
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($other_user_id): // Só mostra o formulário se houver um destinatário válido ?>
            <div class="card-footer chat-input-area">
                <form id="chat-form" autocomplete="off">
                    <div class="input-group">
                        <!-- Botão para Anexar Arquivo -->
                        <button type="button" class="btn btn-secondary" id="attach-button" title="Anexar ficheiro (imagem, áudio, vídeo)">
                            <i class="fas fa-paperclip"></i>
                        </button>
                        <!-- Input de Arquivo Escondido -->
                        <input type="file" id="file-input" accept="image/*,audio/*,video/*" style="display: none;">

                        <!-- Input de Texto (agora pode ser legenda) -->
                        <input type="text" id="message-input" class="form-control" placeholder="Digite sua mensagem ou legenda..." aria-label="Mensagem ou legenda">

                        <!-- ID do Destinatário (oculto) -->
                        <input type="hidden" id="receiver_id" value="<?= htmlspecialchars($other_user_id) ?>">

                        <!-- Botão de Envio -->
                        <button type="submit" class="btn btn-primary" id="send-button" aria-label="Enviar Mensagem">
                            <i class="fas fa-paper-plane"></i> Enviar
                        </button>
                    </div>
                     <!-- Área para mostrar ficheiro selecionado (opcional) -->
                    <div id="file-preview-area" class="mt-2" style="display: none;">
                        <span class="badge bg-light text-dark p-2">
                            <i class="fas fa-file me-1"></i>
                            <span id="selected-file-name"></span>
                            <button type="button" class="btn-close btn-sm ms-2" aria-label="Remover ficheiro" id="remove-file-button"></button>
                        </span>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- CSS ATUALIZADO -->
<style>
    /* Regras para Imagens e Vídeos com tamanho controlado */
    .message-bubble img,
    .message-bubble video {
        display: block;
        max-width: 300px;
        max-height: 250px;
        width: auto;
        height: auto;
        object-fit: cover;
        border-radius: 8px;
        margin-top: 5px;
        cursor: pointer; /* Indica que são clicáveis */
    }

    /* Opcional: Tamanho ligeiramente maior para vídeos */
    .message-bubble video {
        max-width: 350px;
        max-height: 280px;
    }

    /* Regras para Áudio (Removido .message-bubble audio, agora é customizado) */

    /* Regras existentes (manter) */
    .message-content {
        display: flex;
        flex-direction: column;
    }
    .message-caption {
        font-size: 0.9em;
        margin-bottom: 5px;
    }
    #file-preview-area .btn-close {
        vertical-align: middle;
    }

    /* --- INÍCIO: CSS para Background do Chat --- */
    .chat-messages {
        flex-grow: 1;
        overflow-y: auto;
        padding: 15px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        position: relative; /* Necessário para o overlay */

        /* Estilos do Background */
        background-size: cover; /* Cobre toda a área */
        background-position: center center; /* Centraliza a imagem */
        background-repeat: no-repeat; /* Não repete a imagem */
        background-image: none; /* Começa sem imagem (será definido por JS) */
    }

    /* Opcional: Overlay para melhorar legibilidade do texto sobre a imagem */
    .chat-messages::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: transparent; /* <<< ALTERADO: Transparente por padrão */
        z-index: 1;
        pointer-events: none;
        opacity: 0; /* Começa invisível */
        transition: opacity 0.3s ease, background-color 0.3s ease; /* Adiciona transição para cor */
    }

    /* Aplica cor e opacidade SOMENTE quando .has-custom-background está presente */
    .chat-messages.has-custom-background::before {
        background-color: rgba(255, 255, 255, 0.1); /* Overlay claro padrão */
        opacity: 1; /* Torna visível */
    }

    /* Sobrescreve a cor para modo escuro SOMENTE quando AMBOS .dark-mode e .has-custom-background estão presentes */
    .dark-mode .chat-messages.has-custom-background::before {
        background-color: rgba(0, 0, 0, 0.3); /* Overlay escuro */
    }

    /* Garante que as bolhas fiquem acima do overlay */
    .message-bubble {
        position: relative;
        z-index: 2;
    }
    /* --- FIM: CSS para Background do Chat --- */

    /* --- INÍCIO: CSS para Opções de Fundo Predefinidas (NO MODAL) --- */
    /* Removido .dropdown-header.small */

    .predefined-bg-options-modal { /* Classe atualizada */
        max-width: 100%;
    }

    .predefined-bg-thumb-modal { /* Classe atualizada */
        width: 60px; /* Miniaturas um pouco maiores no modal */
        height: 60px;
        object-fit: cover;
        border-radius: 6px;
        cursor: pointer;
        border: 3px solid transparent; /* Borda mais grossa para destaque */
        transition: border-color 0.2s ease, transform 0.1s ease;
    }

    .predefined-bg-thumb-modal:hover { /* Classe atualizada */
        border-color: var(--bs-primary);
        transform: scale(1.05);
    }

    /* Estilo para o label que funciona como botão de upload */
    label[for="chat-background-input-modal"] { /* Seletor atualizado */
        cursor: pointer;
    }
    /* --- FIM: CSS para Opções de Fundo Predefinidas (NO MODAL) --- */


    /* --- INÍCIO: CSS para Modal Estilo iMessage --- */

    /* Fundo do Modal (Backdrop) - Mais escuro e translúcido */
    .modal-backdrop.show {
        opacity: 0.85 !important; /* Aumenta a opacidade do fundo */
        background-color: #000; /* Garante fundo preto */
    }

    /* Remove estilos padrão do modal-content quando em fullscreen */
    #mediaPreviewModal .modal-content { /* ID do modal atualizado */
        background: transparent !important; /* Fundo totalmente transparente */
        border: none !important;
        box-shadow: none !important;
    }

    /* Container da Mídia - Centraliza */
    #mediaPreviewModal .modal-media-container { /* Classe do container atualizada */
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        box-sizing: border-box;
    }

    /* Imagem ou Vídeo dentro do Modal */
    #mediaPreviewModal .modal-media-container img,
    #mediaPreviewModal .modal-media-container video { /* Aplica a ambos */
        display: block;
        max-width: 95vw;
        max-height: 90vh;
        object-fit: contain;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
    }

    /* Botão Fechar (X) */
    #mediaPreviewModal .modal-close-button { /* ID do modal atualizado */
        position: absolute;
        top: 20px;
        right: 20px;
        z-index: 1060;
        background: rgba(30, 30, 30, 0.6) url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23fff'%3e%3cpath d='M.293.293a1 1 0 0 1 1.414 0L8 6.586 14.293.293a1 1 0 1 1 1.414 1.414L9.414 8l6.293 6.293a1 1 0 0 1-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 0 1-1.414-1.414L6.586 8 .293 1.707a1 1 0 0 1 0-1.414z'/%3e%3c/svg%3e") center/1em auto no-repeat;
        border-radius: 50%;
        width: 36px;
        height: 36px;
        opacity: 0.8;
        transition: opacity 0.2s ease, background-color 0.2s ease;
        border: none;
        padding: 0;
    }
    #mediaPreviewModal .modal-close-button:hover { /* ID do modal atualizado */
        opacity: 1;
        background-color: rgba(50, 50, 50, 0.8);
    }
    #mediaPreviewModal .btn-close { /* ID do modal atualizado */
        filter: none;
    }


    /* Botão Download (Ícone) - COR ALTERADA */
    #mediaPreviewModal .modal-download-action { /* ID do modal atualizado */
        position: absolute;
        bottom: 25px;
        right: 25px;
        z-index: 1060;
        color: white;
        background-color: #007AFF; /* Azul estilo iOS/Bootstrap */
        border-radius: 50%;
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-size: 1.3rem;
        opacity: 0.9;
        transition: opacity 0.2s ease, background-color 0.2s ease;
        border: none;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
    }
    #mediaPreviewModal .modal-download-action:hover { /* ID do modal atualizado */
        opacity: 1;
        background-color: #0056b3; /* Azul um pouco mais escuro no hover */
        color: white;
    }

    /* --- FIM: CSS para Modal Estilo iMessage --- */

    /* --- INÍCIO: CSS para Player de Áudio Customizado (Estilo Dribbble) --- */

    .custom-audio-player.waveform-player {
        display: flex;
        align-items: center;
        padding: 8px 15px; /* Menos padding vertical, mais horizontal */
        border-radius: 8px;  /* MAIS RETANGULAR */
        max-width: 300px;
        margin-top: 5px;
        background-color: #2C2C2E; /* Fundo cinza escuro (iOS/Dribbble) */
        overflow: hidden;
        /* Remove dependência das cores da bolha */
    }

    .waveform-player .play-pause-btn {
        background-color: #5E5CE6; /* Azul/Roxo do Dribbble */
        color: white; /* Ícone branco */
        border: none;
        border-radius: 50%;
        width: 34px; /* Ligeiramente maior */
        height: 34px;
        font-size: 0.9rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px; /* Mais espaço */
        flex-shrink: 0;
        transition: transform 0.1s ease, background-color 0.2s ease;
    }
    .waveform-player .play-pause-btn:hover {
        background-color: #4946e0; /* Tom ligeiramente mais escuro no hover */
    }
    .waveform-player .play-pause-btn:active {
        transform: scale(0.9);
    }
    .waveform-player .play-pause-btn i {
        line-height: 1;
    }

    /* Wrapper para waveform e overlay de progresso */
    .waveform-player .waveform-wrapper {
        flex-grow: 1;
        height: 35px; /* Área da waveform mais alta */
        margin-right: 12px;
        position: relative;
        cursor: pointer;
        background-color: transparent; /* Wrapper não precisa de fundo */
    }

    /* Container das barras da waveform */
    .waveform-player .waveform-container {
        display: flex;
        align-items: center; /* Alinha barras verticalmente ao centro */
        height: 100%;
        width: 100%;
        position: absolute;
        top: 0;
        left: 0;
    }

    /* Barras individuais da waveform */
    .waveform-player .waveform-bar {
        flex-grow: 1;
        width: 2.5px; /* Barras ligeiramente mais grossas */
        margin-right: 1.5px; /* Espaço ligeiramente maior */
        border-radius: 1.5px;
        background-color: #8E8E93; /* Cinza claro das barras (iOS/Dribbble) */
        /* Altura definida por JS */
    }
    .waveform-player .waveform-bar:last-child {
        margin-right: 0;
    }

    /* Overlay de progresso (cobre as barras) */
    .waveform-player .waveform-progress-overlay {
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        width: 0%; /* Começa em 0, atualizado por JS */
        background-color: #5E5CE6; /* Azul/Roxo do Dribbble para o progresso */
        /* mix-blend-mode removido para simples sobreposição */
        opacity: 1; /* Totalmente opaco */
        pointer-events: none;
        border-radius: 3px; /* Mantém leve arredondamento */
    }

    .waveform-player .duration {
        font-size: 0.8rem;
        white-space: nowrap;
        flex-shrink: 0;
        color: #EBEBF5; /* Texto cinza claro/branco (iOS/Dribbble) */
        opacity: 0.7; /* Ligeiramente transparente */
    }

    /* --- FIM: CSS para Player de Áudio Customizado (Estilo Dribbble) --- */

</style>

<!-- *** INÍCIO: HTML DO MODAL ATUALIZADO (para Imagem e Vídeo) *** -->
<div class="modal fade" id="mediaPreviewModal" tabindex="-1" aria-labelledby="mediaPreviewModalLabel" aria-hidden="true"> <!-- ID alterado -->
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content" style="background: transparent; border: none;">
        <!-- Botão Fechar (X) -->
        <button type="button" class="btn-close modal-close-button" data-bs-dismiss="modal" aria-label="Close"></button>

        <!-- Container para a Mídia (Imagem ou Vídeo) -->
        <div class="modal-media-container d-flex align-items-center justify-content-center h-100"> <!-- Classe alterada -->
            <!-- O elemento img ou video será inserido aqui via JS -->
        </div>

        <!-- Botão Download (ícone) -->
        <a href="#" id="modal-download-action" class="modal-download-action" download>
            <i class="fas fa-download"></i>
        </a>
    </div>
  </div>
</div>
<!-- *** FIM: HTML DO MODAL ATUALIZADO *** -->

<!-- *** INÍCIO: Modal para Personalização do Fundo *** -->
<div class="modal fade" id="backgroundModal" tabindex="-1" aria-labelledby="backgroundModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg"> <!-- Modal maior -->
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="backgroundModalLabel">Personalizar Fundo da Conversa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Opção Padrão -->
        <div class="mb-3">
            <button type="button" class="btn btn-outline-secondary w-100" id="set-default-background-button-modal"> <!-- ID ligeiramente diferente para evitar conflito se o antigo não for removido -->
                <i class="fas fa-ban fa-fw me-2"></i>Usar Fundo Padrão
            </button>
        </div>

        <hr>

        <!-- Opções Predefinidas -->
        <h6 class="text-muted mb-2">Escolha um fundo predefinido:</h6>
        <div class="predefined-bg-options-modal d-flex flex-wrap gap-2 justify-content-center mb-3"> <!-- Classe diferente -->
            <!-- AJUSTE OS CAMINHOS ABAIXO PARA AS SUAS IMAGENS REAIS -->
            <img src="/Projeto_PAP/assets/img/chat_backgrounds/img1.png" class="predefined-bg-thumb-modal" data-bg-path="/Projeto_PAP/assets/img/chat_backgrounds/img1.png" alt="Fundo 1" title="Fundo 1">
            <img src="/Projeto_PAP/assets/img/chat_backgrounds/img2.png" class="predefined-bg-thumb-modal" data-bg-path="/Projeto_PAP/assets/img/chat_backgrounds/img2.png" alt="Fundo 2" title="Fundo 2">
            <img src="/Projeto_PAP/assets/img/chat_backgrounds/img3.png" class="predefined-bg-thumb-modal" data-bg-path="/Projeto_PAP/assets/img/chat_backgrounds/img3.png" alt="Padrão Claro" title="Padrão Claro">
            <img src="/Projeto_PAP/assets/img/chat_backgrounds/img4.png" class="predefined-bg-thumb-modal" data-bg-path="/Projeto_PAP/assets/img/chat_backgrounds/img4.png" alt="Padrão Escuro" title="Padrão Escuro">
            <!-- Adicione mais imagens conforme necessário -->
        </div>

        <hr>

        <!-- Opção de Upload -->
        <div class="text-center">
            <label for="chat-background-input-modal" class="btn btn-primary"> <!-- ID ligeiramente diferente -->
                <i class="fas fa-upload fa-fw me-2"></i>Carregar Imagem do Computador
            </label>
            <!-- Input de ficheiro escondido -->
            <input type="file" id="chat-background-input-modal" accept="image/*" style="display: none;">
        </div>

      </div>
      <!-- Opcional: Footer do Modal -->
      <!-- <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div> -->
    </div>
  </div>
</div>
<!-- *** FIM: Modal para Personalização do Fundo *** -->


<!-- JAVASCRIPT ATUALIZADO (Com Logs de Depuração) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Seletores de Elementos (ATUALIZADOS para o Modal de fundo) ---
    const chatMessagesContainer = document.getElementById('chat-messages');
    const chatBackgroundInputModal = document.getElementById('chat-background-input-modal'); // NOVO ID
    const setDefaultBackgroundButtonModal = document.getElementById('set-default-background-button-modal'); // NOVO ID
    const predefinedBgOptionsContainerModal = document.querySelector('.predefined-bg-options-modal'); // NOVA CLASSE
    const chatForm = document.getElementById('chat-form');
    const messageInput = document.getElementById('message-input');
    const sendButton = document.getElementById('send-button');
    const receiverIdInput = document.getElementById('receiver_id');
    const attachButton = document.getElementById('attach-button');
    const fileInput = document.getElementById('file-input'); // Input para anexos da mensagem
    const filePreviewArea = document.getElementById('file-preview-area');
    const selectedFileNameSpan = document.getElementById('selected-file-name');
    const removeFileButton = document.getElementById('remove-file-button');
    const sentBgInput = document.getElementById('chatSentBg');
    const sentTextInput = document.getElementById('chatSentText');
    const receivedBgInput = document.getElementById('chatReceivedBg');
    const receivedTextInput = document.getElementById('chatReceivedText');

    // --- Variáveis Globais do Chat ---
    const currentUserId = <?= json_encode($user_id) ?>;
    const otherUserId = receiverIdInput ? parseInt(receiverIdInput.value, 10) : null;
    let isFetching = false;
    let pollingInterval;
    let selectedFile = null; // Para anexos da mensagem

    // --- Constantes ---
    const CHAT_BACKGROUND_KEY = 'chatBackground'; // Chave do localStorage
    const BASE_IMG_URL = '/Projeto_PAP'; // Ajuste se o projeto não estiver na raiz do localhost

    // --- Funções do Chat ---

    // Rola a área de mensagens para o final
    function scrollToBottom() {
        setTimeout(() => {
            if(chatMessagesContainer) {
                chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
            }
        }, 100);
    }

    // Formata a data/hora da mensagem
    function formatTimestamp(sqlTimestamp) {
        try {
            const date = new Date(sqlTimestamp.includes('Z') || sqlTimestamp.includes('+') || sqlTimestamp.includes('-') ? sqlTimestamp : sqlTimestamp.replace(' ', 'T') + 'Z');
            if (isNaN(date)) {
                console.warn("Timestamp inválido recebido:", sqlTimestamp);
                const parts = sqlTimestamp.split(/[- :]/);
                if (parts.length >= 5) {
                    const fallbackDate = new Date(parts[0], parts[1] - 1, parts[2], parts[3], parts[4]);
                    if (!isNaN(fallbackDate)) return fallbackDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                }
                return sqlTimestamp;
            }
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        } catch (e) {
            console.error("Erro ao formatar timestamp:", sqlTimestamp, e);
            return sqlTimestamp;
        }
    }

    // *** ATUALIZADO: displayMessages com player de áudio estilo waveform simulada ***
    function displayMessages(messages) {
        if (!chatMessagesContainer) return;

        const scrollThreshold = 50;
        const isNearBottom = chatMessagesContainer.scrollHeight - chatMessagesContainer.scrollTop - chatMessagesContainer.clientHeight < scrollThreshold;
        let newMessagesAdded = false;

        messages.forEach(msg => {
            if (!document.getElementById(`msg-${msg.id}`)) {
                newMessagesAdded = true;
                const messageDiv = document.createElement('div');
                messageDiv.id = `msg-${msg.id}`;
                messageDiv.classList.add('message-bubble');
                const isSent = parseInt(msg.sender_id, 10) === currentUserId;
                messageDiv.classList.add(isSent ? 'sent' : 'received');

                const messageContentDiv = document.createElement('div');
                messageContentDiv.classList.add('message-content');

                // 1. Adiciona Legenda
                if (msg.message && msg.message.trim() !== '') {
                    const captionDiv = document.createElement('div');
                    captionDiv.classList.add('message-caption');
                    captionDiv.textContent = msg.message;
                    messageContentDiv.appendChild(captionDiv);
                }

                // 2. Adiciona Mídia
                const fileUrlPrefix = ''; // Ajuste se necessário

                switch (msg.message_type) {
                    case 'image':
                        const img = document.createElement('img');
                        img.src = fileUrlPrefix + msg.file_url;
                        img.alt = msg.file_name || 'Imagem enviada';
                        img.style.cursor = 'pointer';
                        // Chama openMediaModal para imagem
                        img.addEventListener('click', () => {
                            openMediaModal('image', fileUrlPrefix + msg.file_url, msg.file_name);
                        });
                        messageContentDiv.appendChild(img);
                        break;

                    case 'audio':
                        // --- INÍCIO: Player de Áudio Estilo Waveform Simulado ---
                        const audioPlayerContainer = document.createElement('div');
                        audioPlayerContainer.classList.add('custom-audio-player', 'waveform-player'); // Nova classe
                        audioPlayerContainer.id = `audio-player-${msg.id}`;

                        const audioElement = document.createElement('audio');
                        audioElement.src = fileUrlPrefix + msg.file_url;
                        audioElement.preload = 'metadata';
                        audioElement.id = `audio-element-${msg.id}`;
                        audioPlayerContainer.appendChild(audioElement);

                        // Botão Play/Pause (igual)
                        const playPauseButton = document.createElement('button');
                        playPauseButton.classList.add('play-pause-btn');
                        playPauseButton.innerHTML = '<i class="fas fa-play"></i>';
                        playPauseButton.setAttribute('aria-label', 'Play/Pause');
                        audioPlayerContainer.appendChild(playPauseButton);

                        // Container para a Waveform Simulada e Progresso
                        const waveformWrapper = document.createElement('div');
                        waveformWrapper.classList.add('waveform-wrapper');
                        audioPlayerContainer.appendChild(waveformWrapper);

                        // Container das Barras da Waveform (serão geradas por JS)
                        const waveformContainer = document.createElement('div');
                        waveformContainer.classList.add('waveform-container');
                        waveformWrapper.appendChild(waveformContainer);

                        // Overlay de Progresso (para colorir a parte tocada)
                        const progressOverlay = document.createElement('div');
                        progressOverlay.classList.add('waveform-progress-overlay');
                        waveformWrapper.appendChild(progressOverlay); // Adiciona dentro do wrapper

                        // Duração (igual)
                        const durationSpan = document.createElement('span');
                        durationSpan.classList.add('duration');
                        durationSpan.textContent = '0:00';
                        audioPlayerContainer.appendChild(durationSpan);

                        messageContentDiv.appendChild(audioPlayerContainer);

                        // Chama setup para gerar barras e adicionar lógica
                        setTimeout(() => setupAudioPlayer(msg.id), 0);
                        // --- FIM: Player de Áudio Estilo Waveform Simulado ---
                        break;

                    case 'video':
                        const videoThumb = document.createElement('video');
                        videoThumb.src = fileUrlPrefix + msg.file_url + '#t=0.1';
                        videoThumb.muted = true;
                        videoThumb.preload = 'metadata';
                        videoThumb.style.cursor = 'pointer';
                        videoThumb.alt = msg.file_name || 'Vídeo enviado';
                        videoThumb.addEventListener('click', () => {
                            openMediaModal('video', fileUrlPrefix + msg.file_url, msg.file_name);
                        });
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

                // 3. Adiciona Timestamp
                const timestampDiv = document.createElement('div');
                timestampDiv.classList.add('timestamp');
                timestampDiv.textContent = formatTimestamp(msg.timestamp);
                messageDiv.appendChild(timestampDiv);

                chatMessagesContainer.appendChild(messageDiv);
            }
        });

        if (isNearBottom && newMessagesAdded) {
            scrollToBottom();
        }
    }
    // *** FIM: displayMessages ***

    // *** ATUALIZADO: Função genérica para abrir modal de Imagem ou Vídeo ***
    function openMediaModal(mediaType, mediaUrl, fileName) {
        const modalMediaContainer = document.querySelector('#mediaPreviewModal .modal-media-container'); // Container atualizado
        const modalDownloadButton = document.getElementById('modal-download-action');
        const mediaModalElement = document.getElementById('mediaPreviewModal'); // ID do modal atualizado

        if (modalMediaContainer && modalDownloadButton && mediaModalElement) {
            // Limpa o conteúdo anterior do container
            modalMediaContainer.innerHTML = '';

            let mediaElement;

            // Cria o elemento apropriado (img ou video)
            if (mediaType === 'image') {
                mediaElement = document.createElement('img');
                mediaElement.id = 'modal-image'; // Mantém ID se necessário para CSS específico
                mediaElement.alt = fileName || 'Imagem Ampliada';
            } else if (mediaType === 'video') {
                mediaElement = document.createElement('video');
                mediaElement.id = 'modal-video'; // ID específico para vídeo
                mediaElement.controls = true; // Adiciona controlos de vídeo
                mediaElement.autoplay = true; // Opcional: Inicia a reprodução automaticamente
                mediaElement.preload = 'auto'; // Carrega o vídeo
                mediaElement.alt = fileName || 'Vídeo Ampliado';
            } else {
                console.error("Tipo de mídia inválido para o modal:", mediaType);
                return; // Sai se o tipo não for suportado
            }

            // Define o src e adiciona ao container
            mediaElement.src = mediaUrl;
            modalMediaContainer.appendChild(mediaElement);

            // Configura o botão de download
            modalDownloadButton.href = mediaUrl;
            modalDownloadButton.download = fileName || (mediaType === 'image' ? 'imagem' : 'video'); // Nome de download padrão

            // Cria e mostra a instância do modal Bootstrap
            const mediaModal = new bootstrap.Modal(mediaModalElement);
            mediaModal.show();

            // Opcional: Pausar vídeo quando o modal for fechado
            if (mediaType === 'video') {
                mediaModalElement.addEventListener('hidden.bs.modal', () => {
                    if (mediaElement && typeof mediaElement.pause === 'function') { // Verifica se o elemento ainda existe e tem pause
                        mediaElement.pause();
                    }
                }, { once: true }); // Executa o listener apenas uma vez após ser mostrado
            }

        } else {
            console.error("Elementos do modal não encontrados! Verifique IDs: #mediaPreviewModal, .modal-media-container, #modal-download-action");
        }
    }
    // *** FIM: openMediaModal ***

    // *** ATUALIZADO: Função para configurar player com waveform simulada ***
    function setupAudioPlayer(messageId) {
        const playerContainer = document.getElementById(`audio-player-${messageId}`);
        if (!playerContainer) return;

        const audio = playerContainer.querySelector(`#audio-element-${messageId}`);
        const playPauseBtn = playerContainer.querySelector('.play-pause-btn');
        const waveformWrapper = playerContainer.querySelector('.waveform-wrapper'); // Wrapper
        const waveformContainer = playerContainer.querySelector('.waveform-container'); // Container das barras
        const progressOverlay = playerContainer.querySelector('.waveform-progress-overlay'); // Overlay
        const durationSpan = playerContainer.querySelector('.duration');

        if (!audio || !playPauseBtn || !waveformWrapper || !waveformContainer || !progressOverlay || !durationSpan) {
            console.error("Elementos do player de áudio (waveform) não encontrados para msg:", messageId);
            return;
        }

        const numBars = 40; // Número de barras para simular a waveform

        // --- Geração das Barras (Executa apenas uma vez por player) ---
        if (waveformContainer.children.length === 0) { // Gera só se estiver vazio
            waveformContainer.innerHTML = ''; // Limpa por segurança
            for (let i = 0; i < numBars; i++) {
                const bar = document.createElement('div');
                bar.classList.add('waveform-bar');
                // Altura aleatória simples (pode ser melhorada com padrões)
                const heightPercentage = Math.random() * 70 + 20; // Altura entre 20% e 90%
                bar.style.height = `${heightPercentage}%`;
                waveformContainer.appendChild(bar);
            }
        }
        // --- Fim da Geração das Barras ---

        // --- Funções internas do player ---
        function formatTime(seconds) {
            if (isNaN(seconds) || !isFinite(seconds)) return '-:--';
            const minutes = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${minutes}:${secs < 10 ? '0' : ''}${secs}`;
        }

        function updateDurationDisplay() {
             if (isFinite(audio.duration)) {
                durationSpan.textContent = formatTime(audio.duration);
            } else {
                setTimeout(() => {
                    if (isFinite(audio.duration)) {
                        durationSpan.textContent = formatTime(audio.duration);
                    } else {
                        durationSpan.textContent = '-:--';
                    }
                }, 200);
            }
        }

        function togglePlayPause() {
            if (audio.paused || audio.ended) {
                const playPromise = audio.play();
                if (playPromise !== undefined) {
                    playPromise.catch(error => {
                        console.error("Erro ao tocar áudio:", error);
                        updatePlayPauseIcon();
                    });
                }
            } else {
                audio.pause();
            }
        }

        function updatePlayPauseIcon() {
            const icon = playPauseBtn.querySelector('i');
            if (!icon) return;
            if (audio.paused || audio.ended) {
                icon.classList.remove('fa-pause');
                icon.classList.add('fa-play');
            } else {
                icon.classList.remove('fa-play');
                icon.classList.add('fa-pause');
            }
        }

        // --- ATUALIZADO: updateProgress para controlar o overlay ---
        function updateProgress() {
            if (isFinite(audio.duration) && audio.duration > 0) {
                const percentage = (audio.currentTime / audio.duration) * 100;
                progressOverlay.style.width = `${percentage}%`; // Atualiza largura do overlay
            } else {
                 progressOverlay.style.width = '0%'; // Reseta overlay
            }
        }

        // --- ATUALIZADO: seek para usar o wrapper ---
        function seek(event) {
             if (!isFinite(audio.duration) || audio.duration <= 0) return;

            const wrapperRect = waveformWrapper.getBoundingClientRect(); // Usa o wrapper
            if (wrapperRect.width === 0) return;

            const clickPosition = event.clientX - wrapperRect.left;
            const percentage = Math.max(0, Math.min(1, clickPosition / wrapperRect.width));
            const seekTime = percentage * audio.duration;

            audio.currentTime = seekTime;
            updateProgress(); // Atualiza imediatamente o overlay após seek
        }

        // --- Adiciona Event Listeners ---
        audio.addEventListener('loadedmetadata', updateDurationDisplay);
        if (audio.readyState >= 1) { updateDurationDisplay(); }
        else { audio.addEventListener('canplay', updateDurationDisplay, { once: true }); }

        playPauseBtn.addEventListener('click', togglePlayPause);
        audio.addEventListener('play', updatePlayPauseIcon);
        audio.addEventListener('pause', updatePlayPauseIcon);
        audio.addEventListener('ended', () => {
            updatePlayPauseIcon();
            progressOverlay.style.width = '0%'; // Reseta overlay no final
            audio.currentTime = 0;
        });
        audio.addEventListener('timeupdate', updateProgress);
        waveformWrapper.addEventListener('click', seek); // Listener de seek no wrapper

    }
    // *** FIM: setupAudioPlayer ***

    // Busca novas mensagens
    async function fetchNewMessages() {
        if (!otherUserId || isFetching) return;
        isFetching = true;
        try {
            const response = await fetch(`fetch_messages.php?other_user_id=${otherUserId}`);
            if (!response.ok) {
                console.error('Erro de rede ao buscar mensagens:', response.status, response.statusText);
                return;
            }
            const data = await response.json();
            if (data.status === 'success') {
                displayMessages(data.messages);
            } else {
                console.error('Erro ao buscar mensagens (servidor):', data.message);
            }
        } catch (error) {
            console.error('Erro na requisição fetch (fetchNewMessages):', error);
        } finally {
            isFetching = false;
        }
    }

    // Envia mensagem
    async function sendMessage(event) {
        event.preventDefault();
        if (!otherUserId || !messageInput || !sendButton) return;

        const messageText = messageInput.value.trim();
        if (messageText === '' && !selectedFile) return;

        messageInput.disabled = true;
        sendButton.disabled = true;
        if(attachButton) attachButton.disabled = true;
        sendButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

        const formData = new FormData();
        formData.append('receiver_id', otherUserId);
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
            } else {
                console.error('Erro ao enviar mensagem (servidor):', data.message);
                alert('Erro ao enviar mensagem: ' + data.message);
                messageInput.disabled = false;
                sendButton.disabled = false;
                if(attachButton) attachButton.disabled = false;
                sendButton.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar';
            }
        } catch (error) {
            console.error('Erro na requisição fetch (sendMessage):', error);
            alert('Erro de rede ao enviar mensagem. Tente novamente.');
            messageInput.disabled = false;
            sendButton.disabled = false;
            if(attachButton) attachButton.disabled = false;
            sendButton.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar';
        } finally {
            if (messageInput.disabled) { // Reabilita se não caiu em erro
                 messageInput.disabled = false;
                 sendButton.disabled = false;
                 if(attachButton) attachButton.disabled = false;
                 sendButton.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar';
            }
            messageInput.focus();
            await fetchNewMessages();
            scrollToBottom(); // Força scroll após enviar
        }
    }

    // --- Funções para Ficheiros (Anexos da Mensagem) ---
    function showSelectedFile(file) {
        if (file && filePreviewArea && selectedFileNameSpan) {
            selectedFile = file;
            selectedFileNameSpan.textContent = file.name;
            filePreviewArea.style.display = 'block';
            messageInput.placeholder = "Adicione uma legenda (opcional)...";
        }
    }
    function clearSelectedFile() {
        selectedFile = null;
        if (fileInput) fileInput.value = ''; // Limpa o input de anexos da mensagem
        if (filePreviewArea) filePreviewArea.style.display = 'none';
        if (selectedFileNameSpan) selectedFileNameSpan.textContent = '';
        if (messageInput) messageInput.placeholder = "Digite sua mensagem ou legenda...";
    }
    if (attachButton && fileInput) {
        attachButton.addEventListener('click', () => { fileInput.click(); });
    }
    if (fileInput) {
        fileInput.addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (file) {
                const maxSizeMB = 50;
                if (file.size > maxSizeMB * 1024 * 1024) {
                    alert(`O ficheiro é muito grande (máx ${maxSizeMB}MB).`);
                    clearSelectedFile(); return;
                }
                showSelectedFile(file);
            } else { clearSelectedFile(); }
        });
    }
    if (removeFileButton) {
        removeFileButton.addEventListener('click', clearSelectedFile);
    }

    // --- Funções de Personalização de Cores (sem alterações) ---
    window.updateChatColor = function(variableName, colorValue) {
        document.documentElement.style.setProperty(variableName, colorValue);
        const savedColors = JSON.parse(localStorage.getItem('chatColors') || '{}');
        const keyMap = {'--chat-sent-bg': 'sentBg', '--chat-sent-text': 'sentText', '--chat-received-bg': 'receivedBg', '--chat-received-text': 'receivedText'};
        const storageKey = keyMap[variableName];
        if (storageKey) { savedColors[storageKey] = colorValue; localStorage.setItem('chatColors', JSON.stringify(savedColors)); }
    }
    window.resetChatColors = function() {
        const root = document.documentElement;
        const defaultColors = { sentBg: '#0B93F6', sentText: '#FFFFFF', receivedBg: '#E5E5EA', receivedText: '#000000' };
        root.style.setProperty('--chat-sent-bg', defaultColors.sentBg); root.style.setProperty('--chat-sent-text', defaultColors.sentText);
        root.style.setProperty('--chat-received-bg', defaultColors.receivedBg); root.style.setProperty('--chat-received-text', defaultColors.receivedText);
        if (sentBgInput) sentBgInput.value = defaultColors.sentBg; if (sentTextInput) sentTextInput.value = defaultColors.sentText;
        if (receivedBgInput) receivedBgInput.value = defaultColors.receivedBg; if (receivedTextInput) receivedTextInput.value = defaultColors.receivedText;
        localStorage.removeItem('chatColors'); console.log('Chat colors reset to defaults.');
    }
    function loadInitialColorPickerValues() {
        const savedColors = JSON.parse(localStorage.getItem('chatColors') || '{}');
        const rootStyles = getComputedStyle(document.documentElement);
        const defaultSentBg = rootStyles.getPropertyValue('--chat-sent-bg').trim() || '#0B93F6'; const defaultSentText = rootStyles.getPropertyValue('--chat-sent-text').trim() || '#FFFFFF';
        const defaultReceivedBg = rootStyles.getPropertyValue('--chat-received-bg').trim() || '#E5E5EA'; const defaultReceivedText = rootStyles.getPropertyValue('--chat-received-text').trim() || '#000000';
        if (sentBgInput) sentBgInput.value = savedColors.sentBg || defaultSentBg; if (sentTextInput) sentTextInput.value = savedColors.sentText || defaultSentText;
        if (receivedBgInput) receivedBgInput.value = savedColors.receivedBg || defaultReceivedBg; if (receivedTextInput) receivedTextInput.value = savedColors.receivedText || defaultReceivedText;
    }

    // --- LÓGICA ATUALIZADA: Background do Chat (usando elementos do Modal) ---

    // Função para aplicar o background (ATUALIZADA com console.log)
    function applyChatBackground(value) {
        console.log("[applyChatBackground] Valor recebido:", value); // DEBUG
        if (!chatMessagesContainer) {
            console.error("[applyChatBackground] Erro: Container #chat-messages não encontrado.");
            return;
        }

        let imageUrl = null;
        let backgroundStyle = 'none'; // Estilo padrão (sem imagem)
        let hasCustomBgClass = false;

        if (value && value !== 'none') {
            if (value.startsWith('data:image/')) {
                // Data URL (upload)
                imageUrl = value;
                console.log("[applyChatBackground] Tipo: Data URL");
            } else if (value.startsWith('/')) {
                // Caminho absoluto da raiz do site (predefinido)
                imageUrl = value;
                console.log("[applyChatBackground] Tipo: Caminho URL");
            } else {
                console.warn("[applyChatBackground] Tipo de valor não reconhecido:", value);
            }
        } else {
             console.log("[applyChatBackground] Tipo: Nenhum (removendo fundo)");
        }

        // Monta o estilo CSS apenas se houver uma URL válida
        if (imageUrl) {
            // IMPORTANTE: Garantir que as aspas estão corretas dentro de url()
            backgroundStyle = `url('${imageUrl.replace(/'/g, "\\'")}')`; // Escapa aspas simples no nome do ficheiro, se houver
            hasCustomBgClass = true;
            console.log("[applyChatBackground] Definindo estilo:", backgroundStyle);
        } else {
            console.log("[applyChatBackground] Removendo estilo de fundo.");
        }

        // Aplica o estilo ao container
        chatMessagesContainer.style.backgroundImage = backgroundStyle;

        // Adiciona/Remove a classe para o overlay
        if (hasCustomBgClass) {
            chatMessagesContainer.classList.add('has-custom-background');
        } else {
            chatMessagesContainer.classList.remove('has-custom-background');
        }
    }


    // Carrega o background salvo ao iniciar (INALTERADO)
    const savedBackground = localStorage.getItem(CHAT_BACKGROUND_KEY);
    applyChatBackground(savedBackground);

    // Listener para o botão "Padrão" (NO MODAL)
    if (setDefaultBackgroundButtonModal) { // Seletor atualizado
        setDefaultBackgroundButtonModal.addEventListener('click', function() {
            localStorage.removeItem(CHAT_BACKGROUND_KEY);
            applyChatBackground(null);
            // Opcional: Fechar o modal após a seleção
            const modalElement = document.getElementById('backgroundModal');
            if (modalElement) {
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) modal.hide();
            }
        });
    }

    // Listener para as miniaturas predefinidas (NO MODAL - ATUALIZADO com console.log)
    if (predefinedBgOptionsContainerModal) { // Seletor atualizado
        predefinedBgOptionsContainerModal.addEventListener('click', function(event) {
            console.log("Click detected inside predefined options container."); // DEBUG
            // Verifica se o clique foi numa imagem thumbnail DENTRO DO MODAL
            if (event.target.classList.contains('predefined-bg-thumb-modal')) { // Classe atualizada
                console.log("Clicked element IS a thumbnail:", event.target); // DEBUG
                const imagePath = event.target.dataset.bgPath; // Pega o caminho da imagem real
                console.log("Attempting to get data-bg-path:", imagePath); // DEBUG
                if (imagePath) {
                    localStorage.setItem(CHAT_BACKGROUND_KEY, imagePath); // Salva o caminho
                    console.log("Saved to localStorage:", imagePath); // DEBUG
                    applyChatBackground(imagePath); // Aplica
                    // Opcional: Fechar o modal após a seleção
                    const modalElement = document.getElementById('backgroundModal');
                    if (modalElement) {
                        const modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) modal.hide();
                    }
                } else {
                     console.error("data-bg-path attribute is missing or empty on clicked thumbnail!"); // DEBUG
                }
            } else {
                 console.log("Clicked element is NOT a thumbnail:", event.target); // DEBUG
            }
        });
    }


    // Listener para o input de ficheiro do background (NO MODAL - INALTERADO)
    if (chatBackgroundInputModal) { // Seletor atualizado
        chatBackgroundInputModal.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const maxSizeMB = 2;
                if (file.size > maxSizeMB * 1024 * 1024) {
                    alert(`A imagem é muito grande (máx ${maxSizeMB}MB). Escolha uma menor.`);
                    chatBackgroundInputModal.value = ''; // Limpa o input do modal
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    const imageUrl = e.target.result;
                    localStorage.setItem(CHAT_BACKGROUND_KEY, imageUrl);
                    applyChatBackground(imageUrl);
                     // Opcional: Fechar o modal após a seleção
                    const modalElement = document.getElementById('backgroundModal');
                    if (modalElement) {
                        const modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) modal.hide();
                    }
                }
                reader.onerror = function(e) {
                    console.error("Erro ao ler o ficheiro:", e);
                    alert("Ocorreu um erro ao processar a imagem.");
                }
                reader.readAsDataURL(file);
                chatBackgroundInputModal.value = ''; // Limpa o input do modal
            } else if (file) {
                alert("Por favor, selecione um ficheiro de imagem válido.");
                chatBackgroundInputModal.value = ''; // Limpa o input do modal
            }
        });
    }

    // --- FIM: LÓGICA ATUALIZADA: Background do Chat ---


    // --- Inicialização ---
    loadInitialColorPickerValues(); // Carrega cores personalizadas
    // Carregamento do background já está acima

    if (otherUserId && chatMessagesContainer) {
        fetchNewMessages().then(() => { scrollToBottom(); });
        pollingInterval = setInterval(fetchNewMessages, 5000);
        if(chatForm) { chatForm.addEventListener('submit', sendMessage); }
    } else {
        if(messageInput) messageInput.disabled = true; if(sendButton) sendButton.disabled = true; if(attachButton) attachButton.disabled = true;
    }
    window.addEventListener('beforeunload', () => { if (pollingInterval) { clearInterval(pollingInterval); } });
});
</script>

<?php
include 'footer.php';
?>