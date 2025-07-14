<?php
session_start();

// Verifica se o usuário está logado e se é um cliente
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

include 'db.php';
include 'header.php'; // Inclui o cabeçalho

$user_id = $_SESSION['user_id'];
$client_id = null;
$all_achievements = [];
$earned_achievement_ids = [];
$page_error = '';

// Define a página ativa para a sidebar
$active_page = 'all_achievements.php'; // Ou pode ser 'dashboard.php' se quiser que o dashboard fique ativo
include 'sidebar_client.php'; // Inclui a sidebar do cliente

try {
    // 1. Obter o client_id (da tabela clients) para o user_id atual
    $stmt_get_client_id = $pdo->prepare("SELECT id FROM clients WHERE user_id = ? LIMIT 1");
    $stmt_get_client_id->execute([$user_id]);
    $client_id_result = $stmt_get_client_id->fetchColumn();

    if ($client_id_result) {
        $client_id = $client_id_result;

        // 2. Buscar todas as conquistas disponíveis
        $stmt_all = $pdo->query("SELECT id, name, description, icon, points FROM achievements ORDER BY points DESC, name ASC");
        $all_achievements = $stmt_all->fetchAll(PDO::FETCH_ASSOC);

        // 3. Buscar os IDs das conquistas ganhas pelo cliente
        $stmt_earned = $pdo->prepare("SELECT achievement_id FROM client_achievements WHERE client_id = ?");
        $stmt_earned->execute([$client_id]);
        $earned_achievement_ids = $stmt_earned->fetchAll(PDO::FETCH_COLUMN);

    } else {
        $page_error = "Não foi possível encontrar o registo de cliente.";
    }

} catch (PDOException $e) {
    $page_error = "Erro ao carregar dados das conquistas: " . $e->getMessage();
    error_log($page_error);
}
?>

<main class="main-content">
    <div class="container mt-5">
        <h2><i class="fas fa-trophy me-2"></i>Todas as Conquistas</h2>
        <p>Veja todas as conquistas disponíveis e as que você já desbloqueou!</p>

        <?php if ($page_error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($page_error) ?></div>
        <?php elseif (empty($all_achievements)): ?>
            <div class="alert alert-info">Ainda não há conquistas configuradas no sistema.</div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($all_achievements as $achievement): ?>
                    <?php $is_earned = in_array($achievement['id'], $earned_achievement_ids); ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 achievement-card <?= $is_earned ? 'earned' : 'locked' ?>">
                            <div class="card-body text-center">
                                <i class="<?= htmlspecialchars($achievement['icon']) ?> fa-3x mb-3 <?= $is_earned ? 'text-warning' : 'text-muted' ?>"></i>
                                <h5 class="card-title"><?= htmlspecialchars($achievement['name']) ?></h5>
                                <p class="card-text small"><?= htmlspecialchars($achievement['description']) ?></p>
                                <?php if ($achievement['points'] > 0): ?>
                                    <p class="card-text"><small class="text-muted"><?= htmlspecialchars($achievement['points']) ?> pontos</small></p>
                                <?php endif; ?>
                                <?php if ($is_earned): ?>
                                    <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Conquistada!</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><i class="fas fa-lock me-1"></i>Bloqueada</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
         <div class="text-center mt-4">
            <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar ao Dashboard</a>
        </div>
    </div>
</main>
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
<style>
    .achievement-card {
        border: 1px solid var(--bs-border-color);
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .achievement-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    .achievement-card.earned {
        border-left: 5px solid var(--bs-success); /* Verde para conquistada */
    }
    .achievement-card.locked {
        border-left: 5px solid var(--bs-secondary); /* Cinza para bloqueada */
        opacity: 0.8;
    }
    .achievement-card.locked .card-body i,
    .achievement-card.locked .card-body h5 {
        color: var(--bs-gray-600) !important;
    }
    .dark-mode .achievement-card.locked .card-body i,
    .dark-mode .achievement-card.locked .card-body h5 {
        color: var(--bs-gray-500) !important;
    }
</style>

<?php include 'footer.php'; ?>
