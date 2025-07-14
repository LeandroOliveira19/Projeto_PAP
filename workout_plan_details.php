<?php
// filepath: c:\xampp\htdocs\Projeto_PAP\workout_plan_details.php
session_start();

// Verifica se o usuário está logado e se é um cliente
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php'); // Redireciona para a página de login se não estiver logado
    exit;
}

// Inclui a conexão com o banco de dados
include 'db.php';

// Inclui o cabeçalho da página
include 'header.php';

// Define qual link da sidebar deve estar ativo
$active_page = basename($_SERVER['PHP_SELF']); // Pega o nome do arquivo atual (workout_plan_details.php)

// Inclui a sidebar do cliente
include 'sidebar_client.php';

$user_id = $_SESSION['user_id'];
$workout_plan = null;
$workout_plan_days = []; // Details for each day (including JSON actions)
$selected_exercises = []; // Exercises selected for the entire plan
$page_error = '';

// Função para normalizar strings para comparação (grupos musculares, focos do dia)
function normalize_string_for_comparison($str) {
    if ($str === null) return '';
    $str = trim((string)$str);
    $str = mb_strtolower($str, 'UTF-8'); // Converte para minúsculas (multi-byte safe)

    // 1. Mapeamento para remover acentos comuns
    $unwanted_array = [
        'á'=>'a', 'à'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'ae',
        'é'=>'e', 'è'=>'e', 'ê'=>'e', 'ë'=>'e',
        'í'=>'i', 'ì'=>'i', 'î'=>'i', 'ï'=>'i',
        'ó'=>'o', 'ò'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'œ'=>'oe',
        'ú'=>'u', 'ù'=>'u', 'û'=>'u', 'ü'=>'u',
        'ý'=>'y', 'ÿ'=>'y',
        'ç'=>'c',
        'ñ'=>'n',
        // Adicionar outras substituições específicas se necessário
    ];
    $str = strtr($str, $unwanted_array);

    // 2. Mapeamento de termos comuns em Português para Inglês (para grupos musculares/focos)
    // Adicione MAIS mapeamentos conforme necessário com base nos seus dados e logs
    $portuguese_to_english_map = [
        'pernas' => 'legs',
        'ombros' => 'shoulders',
        'peito' => 'chest',
        'costas' => 'back',
        'braços' => 'arms',
        'bicep' => 'biceps',
        'tricep' => 'triceps',
        'abdominal' => 'core', // ou 'abs'
        'abdominais' => 'core', // variação
        'gluteos' => 'glutes',
        'glúteos' => 'glutes', // variação com acento (embora o passo anterior remova)
        'cardio' => 'cardio',
        'full body' => 'full body',
        'corpo inteiro' => 'full body',
        'descanso' => 'descanso', // para identificar dias de descanso
        // Adicione aqui outros mapeamentos! Ex:
        // 'quadriceps' => 'quadriceps',
        // 'isquiotibiais' => 'hamstrings', // ou 'posteriores' => 'hamstrings'
        // 'gemeos' => 'calves', // ou 'panturrilhas' => 'calves'
        // 'antebraço' => 'forearms',
        // 'trapézio' => 'traps',
        // 'lombar' => 'lower back',
        // 'abs' => 'abs', // se usar 'abs' em vez de 'core' para exercícios
    ];
    // Aplica o mapeamento português -> inglês
    $str = strtr($str, $portuguese_to_english_map);

    return $str;
}

try {
    // 1. Get the client_id for the logged-in user
    $stmt_client = $pdo->prepare("SELECT id FROM clients WHERE user_id = ? LIMIT 1");
    $stmt_client->execute([$user_id]);
    $client_data = $stmt_client->fetch();

    if (!$client_data) {
        throw new Exception("Registo de cliente não encontrado.");
    }
    $client_id = $client_data['id'];

    // 2. Fetch the client's ACTIVE workout plan
    $stmt_plan = $pdo->prepare("
        SELECT wp.id, wp.plan_details, wp.created_at, u.name as trainer_name
        FROM workout_plans wp
        JOIN users u ON wp.trainer_id = u.id
        WHERE wp.client_id = ? AND wp.is_active = 1
        LIMIT 1
    ");
    $stmt_plan->execute([$client_id]);
    $workout_plan = $stmt_plan->fetch();

    if ($workout_plan) {
        $workout_plan_id = $workout_plan['id'];

        // 3. Fetch day-by-day details for this plan (needed to get day_option_name)
        $stmt_days = $pdo->prepare("
            SELECT wpd.day_name, do.name AS day_option_name
            FROM workout_plan_days wpd
            LEFT JOIN day_options do ON wpd.day_option_id = do.id
            WHERE wpd.workout_plan_id = ?
            ORDER BY FIELD(wpd.day_name, 'Dia 1', 'Dia 2', 'Dia 3', 'Dia 4', 'Dia 5', 'Dia 6', 'Dia 7')
        ");
        $stmt_days->execute([$workout_plan_id]);
        $workout_plan_days = $stmt_days->fetchAll();

        // 4. Fetch ALL exercises selected for this plan
        $stmt_exercises = $pdo->prepare("
            SELECT e.id, e.name, e.description, e.muscle_group, e.equipment, e.image, e.video_url
            FROM workout_plan_exercises wpe
            JOIN exercises e ON wpe.exercise_id = e.id
            WHERE wpe.workout_plan_id = ?
            ORDER BY e.muscle_group ASC, e.name ASC
        ");
        $stmt_exercises->execute([$workout_plan_id]);
        $selected_exercises = $stmt_exercises->fetchAll();

    } else {
        // No active plan found
        $page_error = "Nenhum plano de treino ativo encontrado. Contacte o seu treinador.";
    }

} catch (PDOException $e) {
    $page_error = "Erro ao carregar os detalhes do treino: " . $e->getMessage();
    error_log("Erro BD ao carregar detalhes do treino (workout_plan_details.php): " . $e->getMessage());
} catch (Exception $e) {
    $page_error = "Erro: " . $e->getMessage();
    error_log("Erro geral ao carregar detalhes do treino (workout_plan_details.php): " . $e->getMessage());
}

?>

<style>
    /* Styles for the layout */
    .workout-details-accordion .accordion-item {
        border: 1px solid var(--bs-border-color);
        border-radius: var(--bs-border-radius);
        margin-bottom: 20px;
        overflow: hidden;
    }
     .workout-details-accordion .accordion-button {
        background-color: var(--bs-light);
        padding: 10px 15px;
        font-weight: bold;
        border-bottom: 1px solid var(--bs-border-color);
    }
    .day-card-body {
        padding: 15px;
    }
     .workout-details-accordion .accordion-button:not(.collapsed) {
        color: var(--bs-primary-text-emphasis);
        background-color: var(--bs-primary-bg-subtle);
        box-shadow: inset 0 -1px 0 var(--bs-border-color-translucent);
    }
    .exercise-list-item {
        border-bottom: 1px solid var(--bs-border-color-translucent);
        padding: 10px 0;
    }
    .exercise-list-item:last-child {
        border-bottom: none;
    }
    .exercise-image {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 4px;
        margin-right: 15px;
        flex-shrink: 0;
    }
    .exercise-details {
        flex-grow: 1;
    }
    .exercise-details h6 {
        margin-bottom: 5px;
    }
    .exercise-details p {
        margin-bottom: 5px;
        font-size: 0.9em;
        color: var(--bs-secondary-color);
    }
    .exercise-details .btn-link {
        padding: 0;
        font-size: 0.9em;
    }
</style>

<!-- Conteúdo Principal -->
<main class="main-content">
    <div class="container mt-5">
        <h2>Detalhes dos Exercícios por Dia</h2>

        <?php if ($page_error): ?>
            <div class="alert alert-warning"><?= htmlspecialchars($page_error) ?></div>
        <?php elseif ($workout_plan): ?>

            <p>Exercícios organizados de acordo com o foco de cada dia do seu plano ativo.</p>

            <?php
            /*
            // BLOCO DE DEPURAÇÃO OPCIONAL: Descomente para ver os dados
            if ($workout_plan && !empty($selected_exercises)) {
                echo "<div class='alert alert-info small my-3'><p><strong>Informação de Depuração:</strong></p>";
                // Mostrar grupos musculares únicos dos exercícios selecionados para este plano
                $unique_muscle_groups_in_plan = [];
                foreach ($selected_exercises as $ex_debug) {
                    $mg_debug = normalize_string_for_comparison($ex_debug['muscle_group']);
                    if (!empty($mg_debug) && !in_array($mg_debug, $unique_muscle_groups_in_plan)) {
                        $unique_muscle_groups_in_plan[] = $mg_debug;
                    }
                }
                if (!empty($unique_muscle_groups_in_plan)) {
                    echo "<p><strong>Grupos musculares (normalizados) presentes nos exercícios deste plano:</strong><br>" . implode(', ', array_map('htmlspecialchars', $unique_muscle_groups_in_plan)) . "</p>";
                } else { echo "<p>Nenhum grupo muscular encontrado nos exercícios selecionados.</p>";}
                echo "</div>";
            }
            */
            ?>

            <div class="accordion workout-details-accordion" id="workoutPlanAccordionDetails">
                <?php
                $has_any_day_with_exercises_overall = false; // Flag global
                if (!empty($workout_plan_days) && !empty($selected_exercises)):
                ?>
                    <?php foreach ($workout_plan_days as $index => $day): ?>
                        <?php
                            $day_id_suffix = preg_replace('/[^a-zA-Z0-9]/', '', $day['day_name']) . $index; // Unique ID for accordion
                            $day_option_name_original = trim($day['day_option_name'] ?? '');
                            $day_option_name_normalized = normalize_string_for_comparison($day_option_name_original);

                            // Se o dia for de descanso, podemos apresentar de forma diferente ou pular
                            if ($day_option_name_normalized === 'descanso') {
                                ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading<?= $day_id_suffix ?>">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $day_id_suffix ?>" aria-expanded="false" aria-controls="collapse<?= $day_id_suffix ?>">
                                            <i class="fas fa-bed me-2"></i>
                                            <strong><?= htmlspecialchars($day['day_name']) ?>:</strong>&nbsp;
                                            <span class="text-info"><?= htmlspecialchars($day_option_name_original ?: 'Descanso') ?></span>
                                        </button>
                                    </h2>
                                    <div id="collapse<?= $day_id_suffix ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $day_id_suffix ?>" data-bs-parent="#workoutPlanAccordionDetails">
                                        <div class="accordion-body day-card-body">
                                            <p class="text-muted fst-italic">Dia de descanso.</p>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                continue; // Pula para o próximo dia
                            }

                            // Obter as partes do foco do dia (ex: ["peito", "costas"] de "Peito e Costas")
                            $focus_parts = [];
                            if ($day_option_name_normalized === 'full body') {
                                $focus_parts = ['full body'];
                            } else {
                                // Divide por " e ", "&", ou "/" (case-insensitive) após normalização inicial
                                $raw_focus_parts = preg_split('/(\s+e\s+|\s*&\s*|\s*\/\s*)/i', $day_option_name_original); // Usa o original para split
                                foreach ($raw_focus_parts as $part) {
                                    $normalized_part = normalize_string_for_comparison(trim($part)); // Normaliza cada parte individualmente
                                    if (!empty($normalized_part) && $normalized_part !== 'descanso') {
                                        $focus_parts[] = $normalized_part;
                                    }
                                }
                            }
                            $focus_parts = array_unique(array_filter($focus_parts)); // Remove duplicados e vazios

                            /*
                            // DEBUG: Mostrar as focus_parts para este dia
                            echo "<div class='alert alert-secondary small my-2'><strong>Dia: " . htmlspecialchars($day['day_name']) . "</strong><br>Foco Original: '" . htmlspecialchars($day_option_name_original) . "'<br>Foco Normalizado (dia todo): '" . htmlspecialchars($day_option_name_normalized) . "'<br>Partes do Foco Procuradas (normalizadas): '" . implode("', '", array_map('htmlspecialchars', $focus_parts)) . "'</div>";
                            */

                            $has_exercises_for_this_day_local = false;
                        ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?= $day_id_suffix ?>">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $day_id_suffix ?>" aria-expanded="false" aria-controls="collapse<?= $day_id_suffix ?>">
                                     <i class="fas fa-calendar-day me-2"></i>
                                    <strong><?= htmlspecialchars($day['day_name']) ?>:</strong>&nbsp;
                                    <span class="text-primary"><?= htmlspecialchars($day_option_name_original ?: 'Não especificado') ?></span>
                                </button>
                            </h2>
                            <div id="collapse<?= $day_id_suffix ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $day_id_suffix ?>" data-bs-parent="#workoutPlanAccordionDetails">
                                <div class="accordion-body day-card-body">
                                <?php if ($day_option_name_normalized === 'full body'): ?>
                                    <?php if (!empty($selected_exercises)): ?>
                                        <?php $has_exercises_for_this_day_local = true; ?>
                                        <ul class="list-unstyled mb-0">
                                            <?php foreach ($selected_exercises as $exercise): ?>
                                                <li class="d-flex align-items-center exercise-list-item">
                                                    <img src="<?= !empty($exercise['image']) && file_exists('assets/img/exercises/' . $exercise['image']) ? htmlspecialchars('assets/img/exercises/' . $exercise['image']) : 'assets/img/exercises/default.jpg' ?>" alt="<?= htmlspecialchars($exercise['name']) ?>" class="exercise-image">
                                                    <div class="exercise-details">
                                                        <h6><?= htmlspecialchars($exercise['name']) ?></h6>
                                                        <p class="mb-1">Grupo: <?= htmlspecialchars($exercise['muscle_group'] ?? 'N/D') ?></p>
                                                        <p class="mb-1">Equipamento: <?= htmlspecialchars($exercise['equipment'] ?? 'N/D') ?></p>
                                                        <?php if (!empty($exercise['video_url'])): ?>
                                                            <a href="<?= htmlspecialchars($exercise['video_url']) ?>" target="_blank" class="btn btn-link p-0">Ver Vídeo <i class="fas fa-external-link-alt"></i></a>
                                                        <?php endif; ?>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                <?php elseif (!empty($focus_parts)): ?>
                                    <ul class="list-unstyled mb-0">
                                        <?php foreach ($focus_parts as $focus_part_normalized): ?>
                                            <?php
                                                $exercises_for_this_focus_part = [];
                                                foreach ($selected_exercises as $exercise) {
                                                    $exercise_muscle_group_normalized = normalize_string_for_comparison($exercise['muscle_group']);
                                                    // DEBUG: Descomente para ver cada comparação
                                                    // error_log('Comparando Dia: '.htmlspecialchars($day['day_name']).' | Grupo Exercício: ' . $exercise_muscle_group_normalized . ' === Parte Foco Dia: ' . $focus_part_normalized);
                                                    if ($exercise_muscle_group_normalized === $focus_part_normalized) {
                                                        $exercises_for_this_focus_part[] = $exercise;
                                                    }
                                                }
                                            ?>
                                            <?php if (!empty($exercises_for_this_focus_part)): ?>
                                                <?php $has_exercises_for_this_day_local = true; ?>
                                                <?php if (count($focus_parts) > 1): // Subtítulo se houver múltiplos focos e exercícios para este foco ?>
                                                    <h6 class="mt-3 mb-2 text-capitalize border-bottom pb-1"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $focus_part_normalized))) // Mostra o foco de forma mais legível ?></h6>
                                                <?php endif; ?>
                                                <?php foreach ($exercises_for_this_focus_part as $exercise): ?>
                                                    <li class="d-flex align-items-center exercise-list-item">
                                                        <img src="<?= !empty($exercise['image']) && file_exists('assets/img/exercises/' . $exercise['image']) ? htmlspecialchars('assets/img/exercises/' . $exercise['image']) : 'assets/img/exercises/default.jpg' ?>" alt="<?= htmlspecialchars($exercise['name']) ?>" class="exercise-image">
                                                        <div class="exercise-details">
                                                            <h6><?= htmlspecialchars($exercise['name']) ?></h6>
                                                            <p class="mb-1">Grupo: <?= htmlspecialchars($exercise['muscle_group'] ?? 'N/D') ?></p>
                                                            <p class="mb-1">Equipamento: <?= htmlspecialchars($exercise['equipment'] ?? 'N/D') ?></p>
                                                            <?php if (!empty($exercise['video_url'])): ?>
                                                                <a href="<?= htmlspecialchars($exercise['video_url']) ?>" target="_blank" class="btn btn-link p-0">Ver Vídeo <i class="fas fa-external-link-alt"></i></a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </li>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        <?php endforeach; // Fim do loop $focus_parts ?>
                                    </ul>
                                <?php endif; // Fim do if 'full body' / !empty($focus_parts) ?>

                                <?php if (!$has_exercises_for_this_day_local && $day_option_name_normalized !== 'descanso'): ?>
                                    <p class="text-muted fst-italic">Nenhum exercício selecionado para o plano corresponde ao foco deste dia (<?= htmlspecialchars($day_option_name_original) ?>).</p>
                                <?php elseif ($has_exercises_for_this_day_local):
                                    $has_any_day_with_exercises_overall = true;
                                endif; ?>
                            </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php
                    if (!$has_any_day_with_exercises_overall && !empty($workout_plan_days) && !empty($selected_exercises)):
                        // Verifica se todos os dias não-descanso não tiveram exercícios
                        $all_days_are_rest_or_no_match = true;
                        foreach($workout_plan_days as $d) {
                            if (normalize_string_for_comparison($d['day_option_name'] ?? '') !== 'descanso') {
                                // Encontrou um dia que não é descanso, então a mensagem abaixo é relevante se $has_any_day_with_exercises_overall for false
                                // (A lógica de $has_any_day_with_exercises_overall já cobre isso)
                            }
                        }
                    ?>
                         <p class="text-muted fst-italic mt-4">Nenhum dia do plano (exceto dias de descanso) tem exercícios correspondentes ao seu foco, ou os focos não estão corretamente mapeados.</p>
                    <?php endif; ?>

                <?php elseif (empty($selected_exercises) && $workout_plan): ?>
                    <p class="text-muted fst-italic mt-4">Nenhum exercício foi selecionado para este plano de treino.</p>
                <?php elseif (empty($workout_plan_days) && $workout_plan): ?>
                    <p class="text-muted fst-italic mt-4">Os dias deste plano de treino ainda não foram configurados.</p>
                <?php endif; ?>
            </div> <!-- Fim do Accordion -->

        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="workout_hub.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar ao Plano Geral</a>
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
