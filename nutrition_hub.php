<?php
// Inicia a sessão
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

// *** ADICIONADO: Define qual link da sidebar deve estar ativo ***
$active_page = basename($_SERVER['PHP_SELF']);

$user_id = $_SESSION['user_id'];
$page_error = '';
$client_id = null; // Adicionado para clareza

// Variáveis para guardar os dados do plano e detalhes
$diet_plan = null; // Agora vai incluir created_at
$nutritionist_name = 'N/D';
$plan_details_general = 'Nenhum plano alimentar disponível.';
$daily_meals_data = []; // Array para guardar os dados organizados por dia e refeição

try {
    // 1. Obter o client_id para o utilizador logado (consistente com outras páginas)
    $stmt_client = $pdo->prepare("SELECT id FROM clients WHERE user_id = ? LIMIT 1");
    $stmt_client->execute([$user_id]);
    $client_data = $stmt_client->fetch();

    if (!$client_data) {
        throw new Exception("Registo de cliente não encontrado.");
    }
    $client_id = $client_data['id'];

    // 2. Consultar o plano alimentar ATIVO do cliente usando o client_id
    $stmt_plan = $pdo->prepare("
        SELECT dp.id, dp.title, dp.plan_details, dp.created_at, dp.updated_at, u.name AS nutritionist_name
        FROM diet_plans dp
        JOIN users u ON dp.nutritionist_id = u.id
        WHERE dp.client_id = ? AND dp.is_active = 1
        LIMIT 1
    ");
    $stmt_plan->execute([$client_id]);
    $diet_plan = $stmt_plan->fetch();
} catch (Exception $e) {
    $page_error = "Erro ao carregar o plano: " . $e->getMessage();
    error_log("Erro ao carregar plano alimentar em nutrition_hub.php: " . $e->getMessage());
}

if ($diet_plan) {
    $nutritionist_name = $diet_plan['nutritionist_name'];
    $plan_title = $diet_plan['title']; // Pega o título do plano
    $plan_details_general = $diet_plan['plan_details'];
    $plan_created_at = $diet_plan['created_at']; // Pega a data de criação
    $plan_updated_at = $diet_plan['updated_at']; // Pega a data da última atualização
    $diet_plan_id = $diet_plan['id'];

    // Busca os detalhes das refeições e alimentos para este plano
    $stmt_meals = $pdo->prepare("
        SELECT
            dpm.id AS meal_id,
            dpm.meal_name,
            dpm.meal_order,
            dpmf.id AS food_item_id,
            dpmf.quantity,
            dpmf.notes,
            f.id AS food_id,
            f.name AS food_name,
            f.unit,
            f.serving_size
        FROM diet_plan_meals dpm
        JOIN diet_plan_meal_foods dpmf ON dpm.id = dpmf.diet_plan_meal_id
        JOIN foods f ON dpmf.food_id = f.id
        WHERE dpm.diet_plan_id = ?
        ORDER BY dpm.meal_order ASC, dpmf.id ASC -- Ordena por ordem da refeição e depois por ordem do alimento
    ");
    $stmt_meals->execute([$diet_plan_id]);
    $meals_data_raw = $stmt_meals->fetchAll(PDO::FETCH_ASSOC);

    // Organiza os dados brutos em uma estrutura aninhada (Dia -> Refeição -> Alimentos)
    $daily_meals_data = organizeMealData($meals_data_raw);

} else {
    $page_error = "Nenhum plano alimentar ativo encontrado. Contacte o seu nutricionista.";
}
?>

<?php include 'sidebar_client.php'; ?>

<?php
// Função auxiliar para organizar os dados brutos das refeições
function organizeMealData($raw_data) {
    $organized = [];
    foreach ($raw_data as $item) {
        // Extrai o dia e o nome da refeição do meal_name (ex: "Dia 1 - Pequeno-almoço")
        $parts = explode(' - ', $item['meal_name'], 2);
        $day_name = $parts[0] ?? 'Dia Desconhecido';
        $meal_name = $parts[1] ?? 'Refeição Desconhecida';

        // Garante que o dia existe no array organizado
        if (!isset($organized[$day_name])) {
            $organized[$day_name] = [];
        }

        // Garante que a refeição existe dentro do dia
        if (!isset($organized[$day_name][$item['meal_id']])) {
            $organized[$day_name][$item['meal_id']] = [
                'name' => $meal_name,
                'order' => $item['meal_order'],
                'foods' => []
            ];
        }

        // Adiciona o alimento à refeição
        $organized[$day_name][$item['meal_id']]['foods'][] = [
            'id' => $item['food_id'],
            'name' => $item['food_name'],
            'quantity' => $item['quantity'],
            'unit' => $item['unit'],
            'serving_size' => $item['serving_size'],
            'notes' => $item['notes']
        ];
    }

    // Opcional: Ordenar os dias (ex: Dia 1, Dia 2...)
    uksort($organized, function($a, $b) {
        $num_a = (int) filter_var($a, FILTER_SANITIZE_NUMBER_INT);
        $num_b = (int) filter_var($b, FILTER_SANITIZE_NUMBER_INT);
        return $num_a <=> $num_b;
    });

    // Opcional: Ordenar as refeições dentro de cada dia pela ordem definida
    foreach ($organized as &$day_meals) {
        uasort($day_meals, function($a, $b) {
            return $a['order'] <=> $b['order'];
        });
    }
    unset($day_meals); // Quebra a referência do último elemento

    return $organized;
}
?>

<!-- *** ADICIONADO: Wrapper <main> para o conteúdo principal *** -->
<main class="main-content">
    <div class="container mt-5"> <!-- Container original mantido dentro do main -->
        <h2>Central de Nutrição</h2>

        <?php if ($page_error): ?>
            <div class="alert alert-warning"><?= htmlspecialchars($page_error) ?></div>
        <?php else: ?>

            <!-- Informações Gerais do Plano -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light"><h4 class="mb-0 text-dark">Plano Alimentar Ativo</h4></div>
                <div class="card-body">
                    <?php if (!empty($plan_title)): ?>
                        <h5 class="card-title text-primary"><?= htmlspecialchars($plan_title) ?></h5>
                    <?php endif; ?>
                    <p><strong>Data de Criação:</strong> <?= htmlspecialchars(date('d/m/Y H:i', strtotime($plan_created_at))) ?></p>
                    <?php
                    // Mostra "Última Alteração" apenas se for diferente da data de criação (com uma tolerância, ex: 1 minuto)
                    if (strtotime($plan_updated_at) > strtotime($plan_created_at) + 60): ?>
                        <p><strong>Última Alteração:</strong> <?= htmlspecialchars(date('d/m/Y H:i', strtotime($plan_updated_at))) ?></p>
                    <?php endif; ?>
                    <p><strong>Nutricionista:</strong> <?= htmlspecialchars($nutritionist_name) ?></p>
                    <h6>Observações Gerais:</h6>
                    <?php if (!empty($plan_details_general)): ?>
                        <p><?= nl2br(htmlspecialchars($plan_details_general)) ?></p>
                    <?php else: ?>
                        <p class="text-muted fst-italic">Nenhuma observação geral.</p>
                    <?php endif; ?>
                    <!-- Botão de Download PDF -->
                    <?php if (isset($diet_plan['id'])): ?>
                        <div class="mt-3"><a href="generate_diet_pdf.php?plan_id=<?= $diet_plan['id'] ?>" class="btn btn-danger" target="_blank"><i class="fas fa-file-pdf me-2"></i>Baixar Plano (PDF)</a></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Detalhes Diários do Plano (Acordeão) -->
            <h4 class="mb-3 mt-5">Estrutura Diária do Plano</h4>
            <?php if (!empty($daily_meals_data)): ?>
                <div class="accordion" id="dietPlanAccordion">
                    <?php $day_index = 0; foreach ($daily_meals_data as $day_name => $meals_in_day): $day_index++; ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?= $day_index ?>">
                                <button class="accordion-button <?= $day_index > 1 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $day_index ?>" aria-expanded="<?= $day_index === 1 ? 'true' : 'false' ?>" aria-controls="collapse<?= $day_index ?>">
                                    <i class="fas fa-calendar-day me-2"></i><?= htmlspecialchars($day_name) ?>
                                </button>
                            </h2>
                            <div id="collapse<?= $day_index ?>" class="accordion-collapse collapse <?= $day_index === 1 ? 'show' : '' ?>" aria-labelledby="heading<?= $day_index ?>" data-bs-parent="#dietPlanAccordion">
                                <div class="accordion-body">
                                    <?php if (!empty($meals_in_day)): ?>
                                        <?php foreach ($meals_in_day as $meal_id => $meal_data): ?>
                                            <div class="card mb-3 border-light">
                                                <div class="card-header p-2 bg-white">
                                                    <h6 class="mb-0"><?= htmlspecialchars($meal_data['name']) ?></h6>
                                                </div>
                                                <ul class="list-group list-group-flush">
                                                    <?php foreach ($meal_data['foods'] as $food): ?>
                                                        <li class="list-group-item py-2 px-3">
                                                            <?= htmlspecialchars(number_format((float)$food['quantity'], 1, ',', '.')) ?> <?= htmlspecialchars($food['unit']) ?> de <strong><?= htmlspecialchars($food['name']) ?></strong>
                                                            <?php if (!empty($food['notes'])): ?>
                                                                <small class="text-muted ms-2">(<?= htmlspecialchars($food['notes']) ?>)</small>
                                                            <?php endif; ?>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted fst-italic">Nenhuma refeição definida para este dia.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                 <p class="text-muted fst-italic">Nenhum detalhe diário configurado para este plano.</p>
            <?php endif; ?>

            <!-- A secção "Outros Recursos Nutricionais" foi removida daqui, pois os links estão agora na sidebar -->

        </div>

        <?php endif; // Fim do if ($page_error) ?>

        
    </div> <!-- Fim do container original -->
</main> <!-- *** FIM: Wrapper <main> *** -->
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
