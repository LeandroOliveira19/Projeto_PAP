<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

include 'db.php';
include 'header.php';

$user_id = $_SESSION['user_id'];
$client_id = null;
$displayable_achievements = []; // Irá guardar as conquistas que passam na verificação
$total_manual_achievements_in_system = 0; 
$page_error = '';
$page_success = '';

// Define a página ativa para a sidebar
$active_page = 'claim_achievements.php'; 
include 'sidebar_client.php';

try {
    // 1. Obter o client_id (da tabela clients) para o user_id atual
    $stmt_get_client_id = $pdo->prepare("SELECT id FROM clients WHERE user_id = ? LIMIT 1");
    $stmt_get_client_id->execute([$user_id]);
    $client_id = $stmt_get_client_id->fetchColumn();

    if ($client_id) {
        // Processar reivindicação de conquista
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_achievement_id'])) {
            $achievement_to_claim_id = filter_input(INPUT_POST, 'claim_achievement_id', FILTER_VALIDATE_INT);

            if ($achievement_to_claim_id) {
                // Verificar se a conquista é manual, se o cliente ainda não a tem E SE CUMPRE OS CRITÉRIOS (RE-VERIFICAÇÃO NO POST)
                $stmt_check_on_post = $pdo->prepare("
                    SELECT a.id, a.name, a.criteria_type, a.criteria_value
                    FROM achievements a
                    LEFT JOIN client_achievements ca ON a.id = ca.achievement_id AND ca.client_id = ?
                    WHERE a.id = ? AND a.is_manual_claim = 1 AND ca.id IS NULL
                ");
                $stmt_check_on_post->execute([$client_id, $achievement_to_claim_id]);
                $achievement_details_on_post = $stmt_check_on_post->fetch(PDO::FETCH_ASSOC);

                if ($achievement_details_on_post) {
                    // --- INÍCIO DA RE-VERIFICAÇÃO NO POST ---
                    $condition_met_on_post = false;
                    switch ($achievement_details_on_post['criteria_type']) {
                        case 'initial_goals_set': // Descomentado
                            $stmt_check_goals_post = $pdo->prepare("SELECT COUNT(id) FROM client_goals WHERE client_id = ? AND status = 'active'");
                            $stmt_check_goals_post->execute([$client_id]);
                            if ($stmt_check_goals_post->fetchColumn() > 0) {
                                $condition_met_on_post = true;
                            }
                            break;
                        case 'profile_info_completed':
                            // Corrigido: Buscar numero_telemovel da tabela users e date_of_birth da clients
                            $stmt_check_profile_post = $pdo->prepare("
                                SELECT u.numero_telemovel, c.date_of_birth 
                                FROM clients c 
                                JOIN users u ON c.user_id = u.id 
                                WHERE c.id = ?");
                            $stmt_check_profile_post->execute([$client_id]);
                            $profile_data_post = $stmt_check_profile_post->fetch(PDO::FETCH_ASSOC);
                            if ($profile_data_post && !empty($profile_data_post['numero_telemovel']) && !empty($profile_data_post['date_of_birth'])) {
                                $condition_met_on_post = true;
                            }
                            break;
                        case 'feedback_form_submitted':
                            try {
                                $stmt_check_feedback_post = $pdo->prepare("SELECT COUNT(id) FROM feedback_submissions WHERE client_id = ?");
                                $stmt_check_feedback_post->execute([$client_id]);
                                if ($stmt_check_feedback_post->fetchColumn() > 0) {
                                    $condition_met_on_post = true;
                                }
                            } catch (PDOException $e) { error_log("Erro ao verificar feedback_submissions (POST): " . $e->getMessage());}
                            break;
                        case 'manual_task_trust_based':
                            $condition_met_on_post = true;
                            break;
                        // Adicione mais 'case' se necessário
                    }
                    // --- FIM DA RE-VERIFICAÇÃO NO POST ---

                    if ($condition_met_on_post) {
                        // Atribuir a conquista
                        $stmt_award = $pdo->prepare("INSERT INTO client_achievements (client_id, achievement_id, earned_at) VALUES (?, ?, NOW())");
                        if ($stmt_award->execute([$client_id, $achievement_to_claim_id])) {
                            $page_success = "Conquista '" . htmlspecialchars($achievement_details_on_post['name']) . "' reivindicada com sucesso!";
                        } else {
                            $page_error = "Erro ao reivindicar a conquista. Tente novamente.";
                        }
                    } else {
                         $page_error = "Não foi possível reivindicar a conquista '" . htmlspecialchars($achievement_details_on_post['name']) . "'. Condições não cumpridas.";
                    }
                } else {
                    $page_error = "Conquista inválida, já reivindicada ou não pode ser reivindicada manualmente.";
                }
            } else {
                $page_error = "ID de conquista inválido.";
            }
        }


        // 2. Buscar TODAS as conquistas manuais que o cliente AINDA NÃO ganhou para verificação
        $stmt_potential_manual = $pdo->prepare("
            SELECT a.id, a.name, a.description, a.icon, a.points, a.criteria_type, a.criteria_value
            FROM achievements a
            LEFT JOIN client_achievements ca ON a.id = ca.achievement_id AND ca.client_id = ?
            WHERE a.is_manual_claim = 1 AND ca.id IS NULL
            ORDER BY a.points DESC, a.name ASC
        ");
        $stmt_potential_manual->execute([$client_id]);
        $potential_manual_achievements = $stmt_potential_manual->fetchAll(PDO::FETCH_ASSOC);

        foreach ($potential_manual_achievements as $achievement) {
            $condition_met = false;
            switch ($achievement['criteria_type']) {
                case 'initial_goals_set': // Descomentado
                    // Verifica se o cliente tem algum objetivo ativo
                    $stmt_check_goals = $pdo->prepare("SELECT COUNT(id) FROM client_goals WHERE client_id = ? AND status = 'active'");
                    $stmt_check_goals->execute([$client_id]);
                    if ($stmt_check_goals->fetchColumn() > 0) {
                        $condition_met = true;
                    }
                    break;
                case 'profile_info_completed':
                    // Corrigido: Buscar numero_telemovel da tabela users e date_of_birth da clients
                    $stmt_check_profile = $pdo->prepare("
                        SELECT u.numero_telemovel, c.date_of_birth 
                        FROM clients c 
                        JOIN users u ON c.user_id = u.id 
                        WHERE c.id = ?");
                    $stmt_check_profile->execute([$client_id]);
                    $profile_data = $stmt_check_profile->fetch(PDO::FETCH_ASSOC);
                    if ($profile_data && !empty($profile_data['numero_telemovel']) && !empty($profile_data['date_of_birth'])) {
                        $condition_met = true;
                    }
                    break;
                case 'feedback_form_submitted':
                    // Exemplo: Verifica se há um registo na tabela 'feedback_submissions'
                    try {
                        $stmt_check_feedback = $pdo->prepare("SELECT COUNT(id) FROM feedback_submissions WHERE client_id = ?");
                        $stmt_check_feedback->execute([$client_id]);
                        if ($stmt_check_feedback->fetchColumn() > 0) {
                            $condition_met = true;
                        }
                    } catch (PDOException $e) {
                        // Tabela pode não existir, tratar graciosamente ou logar
                        error_log("Erro ao verificar feedback_submissions: " . $e->getMessage());
                    }
                    break;
                case 'manual_task_trust_based':
                    // Para conquistas que são baseadas na palavra do utilizador (ex: "Li os termos")
                    $condition_met = true;
                    break;
                // Adicione mais 'case' para outros criteria_type de verificação manual
            }

            if ($condition_met) {
                $displayable_achievements[] = $achievement;
            }
        }

        // 3. Contar o total de conquistas manuais no sistema
        $stmt_total_manual = $pdo->query("SELECT COUNT(id) FROM achievements WHERE is_manual_claim = 1");
        $total_manual_achievements_in_system = (int)$stmt_total_manual->fetchColumn(); // Cast para int

    } else {
        $page_error = "Não foi possível encontrar o registo de cliente.";
    }

} catch (PDOException $e) {
    $page_error = "Erro ao carregar dados das conquistas: " . $e->getMessage();
    error_log("PDOException em claim_achievements.php: " . $e->getMessage());
}
?>

<main class="main-content">
    <div class="container mt-5">
        <h2><i class="fas fa-hand-sparkles me-2"></i>Reivindicar Conquistas Manuais</h2>
        <p>Algumas conquistas podem ser desbloqueadas ao confirmar que completou certas tarefas. Marque abaixo as que já realizou e para as quais cumpre os requisitos!</p>

        <?php if ($page_error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($page_error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if ($page_success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($page_success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php 
        // Lógica de exibição reordenada
        if (!empty($displayable_achievements)) : // 1. Se HÁ conquistas VERIFICADAS para o cliente reivindicar, mostra-as
        ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($displayable_achievements as $achievement): ?>
                    <div class="col">
                        <div class="card h-100 dashboard-card"> 
                            <div class="card-body text-center">
                                <form method="POST" action="claim_achievements.php">
                                    <input type="hidden" name="claim_achievement_id" value="<?= htmlspecialchars($achievement['id']) ?>">
                                    <i class="<?= htmlspecialchars($achievement['icon'] ?? 'fas fa-medal') ?> fa-3x mb-3 text-primary"></i>
                                    <h5 class="card-title"><?= htmlspecialchars($achievement['name']) ?></h5>
                                    <p class="card-text small"><?= htmlspecialchars($achievement['description']) ?></p>
                                    <?php if (isset($achievement['points']) && $achievement['points'] > 0): ?>
                                        <p class="card-text"><small class="text-white-50"><?= htmlspecialchars($achievement['points']) ?> pontos</small></p>
                                    <?php endif; ?>
                                    <button type="submit" class="btn btn-success mt-2"><i class="fas fa-check me-1"></i>Reivindicar esta Conquista</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif (!$client_id && $page_error): 
            // Esta condição é coberta pelo primeiro if($page_error) se $page_error for "Não foi possível encontrar o registo de cliente."
        ?>
             <!-- <div class="alert alert-warning">Não foi possível carregar as suas informações de cliente para reivindicar conquistas.</div> -->
        <?php elseif ($client_id && $total_manual_achievements_in_system === 0): // 3. Se client_id existe, mas NÃO HÁ conquistas manuais NO SISTEMA ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-1"></i> Ainda não há conquistas manuais para reivindicar no sistema.
                <br> <a href="all_achievements.php">Ver todas as minhas conquistas</a>.
            </div>
        <?php elseif ($client_id && empty($displayable_achievements) && $total_manual_achievements_in_system > 0): // 4. Se client_id existe, NÃO HÁ conquistas VERIFICADAS para o cliente reivindicar, MAS EXISTEM no sistema (implica que já reivindicou todas ou não cumpre critérios) ?>
            <div class="alert alert-info">
                <i class="fas fa-check-circle me-1"></i> Parabéns! Parece que já reivindicou todas as conquistas manuais para as quais cumpriu os critérios, ou ainda não cumpriu os critérios para outras conquistas manuais existentes.
                <br> <a href="all_achievements.php">Ver todas as minhas conquistas</a>.
            </div>
        <?php elseif (!$client_id && empty($page_error)): // 5. Caso raro: client_id não encontrado, mas nenhum $page_error foi setado (fallback) ?>
            <div class="alert alert-warning">Não foi possível carregar as informações do cliente.</div>
        <?php endif; ?>

         <div class="text-center mt-4">
            <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar ao Dashboard</a>
            <a href="all_achievements.php" class="btn btn-info ms-2"><i class="fas fa-trophy me-1"></i>Ver Todas as Minhas Conquistas</a>
        </div>
    </div>
</main>
<style>
    /* Estilo para os cards de conquista nesta página */
    .dashboard-card {
        background-color: #f8f9fa; /* Um cinza muito claro (bg-light do Bootstrap) */
        border: 1px solid #dee2e6;
        color: #212529; /* Texto escuro para contraste */
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    .dashboard-card .card-title {
        color: #0056b3; /* Um azul mais escuro para o título */
    }
    .dashboard-card .text-white-50 {
        color: #6c757d !important; /* Garante que o texto dos pontos seja visível */
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Script para arrastar, duplo clique e dropdown do ícone de perfil
    const profileIconContainer = document.querySelector('.profile-icon-container');
    if (!profileIconContainer) return;

    const profileIconLink = document.getElementById('profileIconLink');
    const profileDropdown = document.getElementById('profileDropdown');

    let isDragging = false;
    let dragStarted = false; // Flag para saber se o arrasto realmente começou
    let offsetX, offsetY;

    let lastTapTime = 0;
    const doubleTapDelay = 300; // ms
    let tapCount = 0;
    let tapTimer = null;

    // --- Lógica de Clique/Duplo Clique/Dropdown ---
    if (profileIconLink && profileDropdown) {
        profileIconLink.addEventListener('click', function(e) {
            e.preventDefault(); // Prevenir navegação padrão do link

            if (dragStarted) { // Se um arrasto acabou de acontecer, não processar como clique/duplo clique
                dragStarted = false;
                return;
            }

            tapCount++;
            const currentTime = new Date().getTime();

            if (tapCount === 1) {
                lastTapTime = currentTime;
                tapTimer = setTimeout(function() {
                    // Ação de clique único: Abrir/Fechar dropdown
                    profileDropdown.classList.toggle('show');
                    tapCount = 0; // Reset
                }, doubleTapDelay);
            } else if (tapCount === 2) {
                if (currentTime - lastTapTime < doubleTapDelay) {
                    // Duplo clique
                    clearTimeout(tapTimer);
                    window.location.href = profileIconLink.href; // Navegar para o perfil
                    tapCount = 0; // Reset
                    lastTapTime = 0; // Reset para evitar falso positivo
                    profileDropdown.classList.remove('show'); // Esconder dropdown se estiver aberto
                } else {
                    // Segundo clique muito espaçado, tratar como primeiro de uma nova sequência
                    tapCount = 1;
                    lastTapTime = currentTime;
                    clearTimeout(tapTimer); // Limpa o timer anterior
                    tapTimer = setTimeout(function() {
                        profileDropdown.classList.toggle('show');
                        tapCount = 0;
                    }, doubleTapDelay);
                }
            }
        });

        // Fechar dropdown se clicar fora
        document.addEventListener('click', function(event) {
            if (!profileIconContainer.contains(event.target) && profileDropdown.classList.contains('show')) {
                profileDropdown.classList.remove('show');
                tapCount = 0; // Reset tap count para evitar que um clique fora e depois um clique no ícone seja duplo clique
            }
        });
    }

    // --- Lógica de Arrastar (com a flag dragStarted) ---
    const savedPosition = localStorage.getItem('profileIconPosition');
    if (savedPosition) {
        try {
            const pos = JSON.parse(savedPosition);
            profileIconContainer.style.position = 'fixed';
            profileIconContainer.style.left = pos.left;
            profileIconContainer.style.top = pos.top;
            profileIconContainer.style.right = 'auto';
            profileIconContainer.style.bottom = 'auto';
        } catch (e) {
            console.error("Failed to parse saved profile icon position:", e);
            localStorage.removeItem('profileIconPosition');
        }
    }

    function startDrag(e) {
        // Apenas botão primário do rato ou toque
        if (e.type === 'mousedown' && e.button !== 0) return;

        // Se o dropdown estiver aberto, não iniciar arrasto para permitir interação com o dropdown
        if (profileDropdown && profileDropdown.classList.contains('show')) {
            return;
        }

        isDragging = true;
        dragStarted = false; // Reset no início de um potencial arrasto

        const clientX = e.clientX || e.touches[0].clientX;
        const clientY = e.clientY || e.touches[0].clientY;
        const rect = profileIconContainer.getBoundingClientRect();
        offsetX = clientX - rect.left;
        offsetY = clientY - rect.top;

        profileIconContainer.style.position = 'fixed';
        profileIconContainer.style.right = 'auto';
        profileIconContainer.style.bottom = 'auto';
        // Aumentar z-index durante o arrasto para ficar sobre outros elementos
        profileIconContainer.style.zIndex = '1055';

        document.addEventListener('mousemove', drag);
        document.addEventListener('mouseup', endDrag);
        document.addEventListener('touchmove', drag, { passive: false });
        document.addEventListener('touchend', endDrag);
    }

    function drag(e) {
        if (!isDragging) return;
        if (!dragStarted) {
            if (profileDropdown && profileDropdown.classList.contains('show')) {
                profileDropdown.classList.remove('show');
                tapCount = 0; // Reset tap count
            }
        }
        dragStarted = true;
        if (e.cancelable) e.preventDefault();

        const clientX = e.clientX || e.touches[0].clientX;
        const clientY = e.clientY || e.touches[0].clientY;
        let newLeft = clientX - offsetX;
        let newTop = clientY - offsetY;
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        const elementWidth = profileIconContainer.offsetWidth;
        const elementHeight = profileIconContainer.offsetHeight;
        newLeft = Math.max(0, Math.min(newLeft, viewportWidth - elementWidth));
        newTop = Math.max(0, Math.min(newTop, viewportHeight - elementHeight));
        profileIconContainer.style.left = `${newLeft}px`;
        profileIconContainer.style.top = `${newTop}px`;
    }

    function endDrag() {
        if (!isDragging) return;

        isDragging = false;

        // Remove event listeners
        document.removeEventListener('mousemove', drag);
        document.removeEventListener('mouseup', endDrag);
        document.removeEventListener('touchmove', drag);
        document.removeEventListener('touchend', endDrag);

        // Save the final position
        const finalPosition = {
            left: profileIconContainer.style.left,
            top: profileIconContainer.style.top
        };
        localStorage.setItem('profileIconPosition', JSON.stringify(finalPosition));
    }

    // Add event listeners for starting the drag
    // Use capture phase for mousedown/touchstart to ensure we get the event before the link
    profileIconContainer.addEventListener('mousedown', startDrag, true);
    profileIconContainer.addEventListener('touchstart', startDrag, { passive: false, capture: true });

    // Prevent the link from being dragged as an image
    profileIconContainer.querySelector('a').addEventListener('dragstart', function(e) {
        e.preventDefault();
    });
});
</script>
<?php include 'footer.php'; ?>
