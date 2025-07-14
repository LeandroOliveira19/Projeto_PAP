<?php
session_start();

// Verifica se o usuário está logado e se é um cliente
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

// Inclui a conexão com o banco de dados
include 'db.php';

// Inclui o cabeçalho da página
include 'header.php';

// Define qual link da sidebar deve estar ativo
$active_page = basename($_SERVER['PHP_SELF']); // Pega o nome do arquivo atual (workout_plan_history.php)
// Inclui a sidebar do cliente
include 'sidebar_client.php';
?>

<!-- Conteúdo Principal -->
<main class="main-content">
    <div class="container mt-5">
        <h2>Histórico de Planos de Treino</h2>
        <?php
        // Fetch the client's ID
        $stmt = $pdo->prepare("SELECT id FROM clients WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $client = $stmt->fetch();

        if ($client) {
            $client_id = $client['id'];

            // Fetch all inactive workout plans for the client
            $stmt = $pdo->prepare("SELECT wp.plan_details, u.name AS trainer_name, wp.created_at, wp.id AS workout_plan_id
                                   FROM workout_plans wp
                                   JOIN users u ON wp.trainer_id = u.id
                                   WHERE wp.client_id = ? AND wp.is_active = 0
                                   ORDER BY wp.created_at DESC");
            $stmt->execute([$client_id]);
            $workout_plans = $stmt->fetchAll();

            if ($workout_plans) {
                echo "<p class='text-white'>Aqui pode consultar os seus planos de treino anteriores.</p>";
                echo "<div class='accordion' id='historyAccordion'>";
                foreach ($workout_plans as $plan) {
                    // Gerar um ID único para cada item do acordeão
                    $plan_accordion_id = 'collapse-' . $plan['workout_plan_id'];
                    $heading_id = 'heading-' . $plan['workout_plan_id'];
                    ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="<?= $heading_id ?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $plan_accordion_id ?>" aria-expanded="false" aria-controls="<?= $plan_accordion_id ?>">
                                <i class="fas fa-history me-2"></i>
                                Plano de <?= htmlspecialchars(date('d/m/Y', strtotime($plan['created_at']))) ?> (Treinador: <?= htmlspecialchars($plan['trainer_name']) ?>)
                            </button>
                        </h2>
                        <div id="<?= $plan_accordion_id ?>" class="accordion-collapse collapse" aria-labelledby="<?= $heading_id ?>" data-bs-parent="#historyAccordion">
                            <div class="accordion-body">
                                <h6>Observações Gerais do Plano:</h6>
                                <?php if (!empty($plan['plan_details'])): ?>
                                    <p><?= nl2br(htmlspecialchars($plan['plan_details'])) ?></p>
                                <?php else: ?>
                                    <p class="text-muted fst-italic">Nenhuma observação geral para este plano.</p>
                                <?php endif; ?>

                                <h6 class="mt-3">Exercícios Incluídos no Plano:</h6>
                                <ul class="list-unstyled">
                                    <?php
                                    // Fetch and display exercises for this workout plan
                                    $stmt_exercises = $pdo->prepare("SELECT e.name FROM workout_plan_exercises wpe JOIN exercises e ON wpe.exercise_id = e.id WHERE wpe.workout_plan_id = ? ORDER BY e.name ASC");
                                    $stmt_exercises->execute([$plan['workout_plan_id']]);
                                    $exercises = $stmt_exercises->fetchAll();
                                    if (count($exercises) > 0) {
                                        foreach ($exercises as $exercise) {
                                            echo "<li><i class='fas fa-check me-2 text-success'></i>" . htmlspecialchars($exercise['name']) . "</li>";
                                        }
                                    } else {
                                        echo "<p class='text-muted fst-italic'>Nenhum exercício associado a este plano.</p>";
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                echo "</div>"; // Fim do Accordion
            } else {
                echo "<p class='alert alert-info text-center'>Nenhum plano de treino antigo encontrado.</p>";
            }
        } else {
            echo "<p class='alert alert-danger text-center'>Erro: Cliente não encontrado.</p>";
        }
        ?>

        <div class="text-center mt-4">
            <a href="workout_hub.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar ao Plano Ativo</a>
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
<?php
// Inclui o rodapé da página
include 'footer.php';
?>
