<?php
// filepath: c:\xampp\htdocs\Projeto_PAP\client_statistics.php
session_start();

// Verifica se o usuário está logado e se é um cliente
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

// Inclui a conexão com o banco de dados
include 'db.php';

// Inclui o cabeçalho da página
include 'header.php'; // header.php includes the profile icon

$user_id = $_SESSION['user_id'];
$page_error = ''; // Para erros de fetch

// --- Fetch User and Client Data (Incluindo TODAS as colunas de composição) ---
$profile_data = []; // Inicializa como array vazio
$chart_data = null; // Inicializa dados do gráfico original como null
$bmi = null;        // Inicializa IMC
$bmi_category = 'N/D'; // Categoria do IMC
$bmi_category_class = 'text-muted'; // Classe CSS para a categoria
$bmi_gauge_data = null; // Inicializa dados do gráfico gauge
$client_id = null; // Para guardar o ID do cliente da tabela 'clients'

try {
    // Passo 1: Obter o client_id da tabela clients usando o user_id da sessão
    $stmt_get_client_id = $pdo->prepare("SELECT id FROM clients WHERE user_id = ? LIMIT 1");
    $stmt_get_client_id->execute([$user_id]);
    $client_id_result = $stmt_get_client_id->fetchColumn();

    if (!$client_id_result) {
        throw new Exception("Registo de cliente não encontrado para o utilizador.");
    }
    $client_id = $client_id_result; // Armazena o client_id

    // Passo 2: Buscar dados do utilizador e os dados da ÚLTIMA avaliação
    $stmt = $pdo->prepare("
        SELECT
            u.name, u.email, u.profile_picture, -- Dados básicos do user
            c.activity_level, -- Dados específicos do cliente da tabela 'clients'
            
            -- Dados da última avaliação (da subquery 'latest_assessment')
            latest_assessment.assessment_date as last_assessment_date,
            latest_assessment.height, 
            latest_assessment.weight,
            latest_assessment.water_percentage, 
            latest_assessment.visceral_fat_index, 
            latest_assessment.bone_mass_kg,
            latest_assessment.lean_mass_kg, 
            latest_assessment.muscle_mass_kg, 
            latest_assessment.fat_mass_kg,
            latest_assessment.fat_mass_percentage, 
            latest_assessment.lean_mass_percentage
            -- Adicione aqui quaisquer outras colunas da tabela history_assessments que queira usar
            -- Ex: latest_assessment.gordura_subcutanea, latest_assessment.idade_metabolica, etc.

        FROM users u
        JOIN clients c ON u.id = c.user_id -- JOIN porque o registo em 'clients' é esperado
        LEFT JOIN (
            -- Subquery para encontrar a avaliação mais recente deste cliente
            SELECT ha.*
            FROM history_assessments ha
            WHERE ha.client_id = :client_id_for_subquery -- Usa o client_id obtido no Passo 1
            ORDER BY ha.assessment_date DESC, ha.id DESC -- Ordena pela data mais recente, depois ID
            LIMIT 1 -- Pega APENAS a mais recente
        ) AS latest_assessment ON c.id = latest_assessment.client_id -- Condição de JOIN com a subquery
        WHERE u.id = :user_id AND c.id = :client_id_main_query -- Garante que estamos a buscar para o utilizador e cliente corretos
    ");
    $stmt->execute([
        'client_id_for_subquery' => $client_id, // Para a subquery
        'user_id' => $user_id,                 // Para a query principal
        'client_id_main_query' => $client_id   // Para a query principal (join com clients)
    ]);
    $profile_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile_data) { 
        // Esta exceção pode ocorrer se o user_id não tiver um client_id correspondente,
        // ou se algo correr mal na query. O erro do Passo 1 já deve ter apanhado o caso de client_id em falta.
        throw new Exception("Dados do perfil ou da última avaliação não encontrados."); 
    }

    // Calcula o IMC (usando 'weight' e 'height' da última avaliação)
    $bmi_status = ''; // Status textual original
    if (!empty($profile_data['height']) && $profile_data['height'] > 0 && !empty($profile_data['weight'])) {
        $bmi_height = (float)$profile_data['height'];
        $bmi_weight = (float)$profile_data['weight'];
        if ($bmi_height > 0) {
             $bmi = round($bmi_weight / ($bmi_height * $bmi_height), 1);

             // Lógica original para status textual
             if ($bmi < 18.5) $bmi_status = "Abaixo do peso";
             elseif ($bmi < 25) $bmi_status = "Peso normal";
             elseif ($bmi < 30) $bmi_status = "Sobrepeso";
             else $bmi_status = "Obesidade";

             // Lógica para categoria e classe do GRÁFICO GAUGE
             if ($bmi < 18.5) {
                 $bmi_category = 'Abaixo do Peso';
                 $bmi_category_class = 'text-primary';
             } elseif ($bmi < 25) {
                 $bmi_category = 'Peso Normal';
                 $bmi_category_class = 'text-success';
             } elseif ($bmi < 30) {
                 $bmi_category = 'Acima do Peso';
                 $bmi_category_class = 'text-warning';
             } elseif ($bmi < 35) {
                 $bmi_category = 'Obesidade Grau I';
                 $bmi_category_class = 'text-orange';
             } elseif ($bmi < 40) {
                 $bmi_category = 'Obesidade Grau II';
                 $bmi_category_class = 'text-danger';
             } else {
                 $bmi_category = 'Obesidade Grau III';
                 $bmi_category_class = 'text-danger fw-bold';
             }
        }
    }

    // Prepara dados para os GRÁFICOS ORIGINAIS (doughnut/bar)
    // Estes dados agora vêm da última avaliação
    if (isset($profile_data['fat_mass_percentage']) || isset($profile_data['lean_mass_percentage']) || isset($profile_data['water_percentage']) ||
        isset($profile_data['fat_mass_kg']) || isset($profile_data['lean_mass_kg']) || isset($profile_data['muscle_mass_kg']) || isset($profile_data['bone_mass_kg']))
    {
        $chart_data = [
            'percentage' => [
                'fat' => (float)($profile_data['fat_mass_percentage'] ?? 0),
                'lean' => (float)($profile_data['lean_mass_percentage'] ?? 0),
                'water' => (float)($profile_data['water_percentage'] ?? 0),
            ],
            'kg' => [
                'fat' => (float)($profile_data['fat_mass_kg'] ?? 0),
                'lean' => (float)($profile_data['lean_mass_kg'] ?? 0),
                'muscle' => (float)($profile_data['muscle_mass_kg'] ?? 0),
                'bone' => (float)($profile_data['bone_mass_kg'] ?? 0),
            ]
        ];
    }

    // Prepara dados para o GRÁFICO GAUGE (apenas se BMI foi calculado)
    if ($bmi !== null) {
        $bmi_gauge_data = [
            'values' => [18.5, 6.4, 4.9, 4.9, 4.9, 5.0], 
            'colors' => ['#3498db', '#2ecc71', '#f1c40f', '#e67e22', '#e74c3c', '#c0392b'],
            'labels' => [ 
                'Abaixo do Peso (<18.5)', 'Peso Normal (18.5-24.9)', 'Acima do Peso (25-29.9)',
                'Obesidade I (30-34.9)', 'Obesidade II (35-39.9)', 'Obesidade III (≥40)'
            ],
            'current_bmi' => $bmi
        ];
    }

} catch (Exception $e) {
    $page_error = 'Erro ao carregar dados das estatísticas: ' . $e->getMessage();
    error_log("Error fetching statistics data for user $user_id (client_id: $client_id): " . $e->getMessage());
    $profile_data = []; // Garante que está vazio em caso de erro
    $chart_data = null;
    $bmi_gauge_data = null;
}

// Define active page for sidebar
$active_page = basename($_SERVER['PHP_SELF']);

// Helper function original (sem alterações)
function displayStat($value, $unit = '', $decimals = 1, $na_text = '<span class="text-muted fst-italic">N/D</span>') {
    if (isset($value) && $value !== null && $value !== '') {
        // Se for numérico e tiver unidade, formata. Senão, apenas htmlspecialchars.
        if (is_numeric($value) && !empty($unit)) {
            return htmlspecialchars(number_format((float)$value, $decimals, ',', '.')) . $unit;
        }
        return htmlspecialchars($value); // Para valores não numéricos ou sem unidade (ex: nível de atividade)
    }
    return $na_text;
}

?>

<?php include 'sidebar_client.php'; ?>

<!-- CSS Adicional para Gauge e Cor Laranja (COM AJUSTE) -->
<style>
    .text-orange { color: #e67e22 !important; }
    .bmi-gauge-container {
        position: relative;
        width: 100%;
        max-width: 350px; /* Limita largura */
        margin: 1rem auto; /* Centraliza e adiciona margem */
    }
    .bmi-value-display {
        position: absolute;
        top: 45%; 
        left: 50%;
        transform: translateX(-50%); 
        text-align: center;
        width: 100%;
    }
    .bmi-value {
        font-size: 2.5rem;
        font-weight: bold;
        line-height: 1;
        display: block;
    }
    .bmi-category {
        font-size: 1rem;
        font-weight: 500;
        display: block;
        margin-top: 0.25rem;
    }
    .chart-container canvas { 
        max-width: 100%;
        height: auto;
    }
</style>

<!-- Conteúdo Principal -->
<main class="main-content">
    <h2>Minhas Estatísticas</h2>

    <?php if (!empty($page_error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($page_error) ?></div>
    <?php elseif (empty($profile_data) && !$client_id): // Erro mais específico se client_id não foi encontrado ?>
        <div class="alert alert-warning">Não foi possível carregar os dados do perfil do cliente.</div>
    <?php elseif (empty($profile_data['last_assessment_date']) && $client_id): // Se temos client_id mas não há avaliação ?>
        <div class="alert alert-info">
            Ainda não tem uma avaliação física registada. As estatísticas serão preenchidas após a sua primeira avaliação.
        </div>
    <?php elseif (empty($profile_data)): // Fallback genérico se $profile_data estiver vazio por outra razão ?>
         <div class="alert alert-warning">Não foi possível carregar os dados do perfil.</div>
    <?php else: ?>

        <!-- Secção Resumo Geral (Original) -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title mb-4"><i class="fas fa-chart-pie me-2"></i>Resumo Geral</h5>
                <div class="row">
                    <div class="col-sm-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Altura (última):</strong></p> <p class="fs-5"><?= displayStat($profile_data['height'] ?? null, ' m', 2) ?></p> </div>
                    <div class="col-sm-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Peso Atual (último):</strong></p> <p class="fs-5"><?= displayStat($profile_data['weight'] ?? null, ' kg', 1) ?></p> </div>
                    <div class="col-sm-6 col-lg-3 mb-3">
                        <p class="mb-1"><strong>IMC (último):</strong></p>
                        <?php if ($bmi !== null): ?>
                            <p class="fs-5 mb-0"><?= htmlspecialchars($bmi) ?></p>
                            <small class="text-muted">(<?= htmlspecialchars($bmi_status) ?>)</small>
                        <?php else: ?>
                            <p class="text-muted fst-italic">N/D</p>
                        <?php endif; ?>
                    </div>
                    <div class="col-sm-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Nível Atividade:</strong></p> <p class="fs-5"><?= displayStat($profile_data['activity_level'] ?? null) ?></p> </div>
                </div>
            </div>
        </div>

        <!-- NOVA SECÇÃO: Gráfico Gauge IMC -->
        <?php if ($bmi_gauge_data): ?>
        <div class="row mb-4">
            <div class="col-lg-12">
                 <div class="card shadow-sm h-100">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i>Visualização do IMC (Baseado na Última Avaliação)</h5>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-center text-center">
                        <div class="bmi-gauge-container">
                            <canvas id="bmiGaugeChart"></canvas>
                            <div class="bmi-value-display">
                                <span id="bmiValue" class="bmi-value <?= $bmi_category_class ?>">
                                    <?= htmlspecialchars(number_format($bmi, 1, ',', '.')) ?>
                                </span>
                                <span id="bmiCategory" class="bmi-category <?= $bmi_category_class ?>">
                                    <?= htmlspecialchars($bmi_category) ?>
                                </span>
                            </div>
                        </div>
                        <p class="mt-3 mb-0 small text-muted">O IMC é uma referência geral. Consulte profissionais para uma análise completa.</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>


        <!-- Secção Composição Corporal (Dados da última avaliação) -->
        <div class="card shadow-sm mb-4">
             <div class="card-body">
                <h5 class="card-title mb-3"><i class="fas fa-percentage me-2"></i>Composição Corporal (Última Avaliação)</h5>
                <?php if (!empty($profile_data['last_assessment_date'])): ?> 
                    <p class="text-muted small mb-3">Data da avaliação: <?= htmlspecialchars(date('d/m/Y', strtotime($profile_data['last_assessment_date']))) ?></p> 
                <?php else: ?> 
                    <p class="text-muted small mb-3 fst-italic">Nenhuma avaliação física registada.</p> 
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Massa Gorda:</strong></p> <p class="fs-5"><?= displayStat($profile_data['fat_mass_kg'] ?? null, ' kg', 2) ?></p> </div>
                    <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Massa Magra:</strong></p> <p class="fs-5"><?= displayStat($profile_data['lean_mass_kg'] ?? null, ' kg', 2) ?></p> </div>
                    <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Massa Muscular:</strong></p> <p class="fs-5"><?= displayStat($profile_data['muscle_mass_kg'] ?? null, ' kg', 2) ?></p> </div>
                    <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Massa Óssea:</strong></p> <p class="fs-5"><?= displayStat($profile_data['bone_mass_kg'] ?? null, ' kg', 2) ?></p> </div>
                    <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>% Massa Gorda:</strong></p> <p class="fs-5"><?= displayStat($profile_data['fat_mass_percentage'] ?? null, ' %', 1) ?></p> </div>
                    <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>% Massa Magra:</strong></p> <p class="fs-5"><?= displayStat($profile_data['lean_mass_percentage'] ?? null, ' %', 1) ?></p> </div>
                    <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>% Água Corporal:</strong></p> <p class="fs-5"><?= displayStat($profile_data['water_percentage'] ?? null, ' %', 1) ?></p> </div>
                    <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Gordura Visceral:</strong></p> <p class="fs-5"><?= displayStat($profile_data['visceral_fat_index'] ?? null, '', 0) ?></p> </div>
                    <!-- Adicione aqui outros campos da avaliação que queira mostrar, usando $profile_data['nome_da_coluna_em_history_assessments'] -->
                    <!-- Ex:
                    <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Gordura Subcutânea:</strong></p> <p class="fs-5"><?= displayStat($profile_data['gordura_subcutanea'] ?? null, ' %', 1) ?></p> </div>
                    <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Idade Metabólica:</strong></p> <p class="fs-5"><?= displayStat($profile_data['idade_metabolica'] ?? null, ' anos', 0) ?></p> </div>
                    -->
                </div>
            </div>
        </div>

        <!-- Secção Gráficos Originais (Dados da última avaliação) -->
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3"><i class="fas fa-chart-bar me-2"></i>Visualização Gráfica (Composição da Última Avaliação)</h5>
                <?php if ($chart_data): ?>
                    <div class="row">
                        <div class="col-lg-6 mb-4">
                            <div class="chart-container" style="position: relative; height:300px; width:100%">
                                <canvas id="compositionPercentageChart"></canvas>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-4">
                             <div class="chart-container" style="position: relative; height:300px; width:100%">
                                <canvas id="compositionKgChart"></canvas>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-muted fst-italic">Não há dados suficientes de composição corporal da última avaliação para gerar gráficos.</p>
                <?php endif; ?>
            </div>
        </div>

    <?php endif; ?>

     <div class="text-center mt-4">
        <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar ao Dashboard</a>
    </div>

</main> <!-- Fim .main-content -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Script para arrastar, duplo clique e dropdown do ícone de perfil
    const profileIconContainer = document.querySelector('.profile-icon-container');
    if (!profileIconContainer) return;

    const profileIconLink = document.getElementById('profileIconLink');
    const profileDropdown = document.getElementById('profileDropdown');

    let isDragging = false;
    let dragStarted = false; 
    let offsetX, offsetY;

    let lastTapTime = 0;
    const doubleTapDelay = 300; 
    let tapCount = 0;
    let tapTimer = null;

    if (profileIconLink && profileDropdown) {
        profileIconLink.addEventListener('click', function(e) {
            e.preventDefault(); 
            if (dragStarted) { dragStarted = false; return; }
            tapCount++;
            const currentTime = new Date().getTime();
            if (tapCount === 1) {
                lastTapTime = currentTime;
                tapTimer = setTimeout(function() {
                    profileDropdown.classList.toggle('show');
                    tapCount = 0; 
                }, doubleTapDelay);
            } else if (tapCount === 2) {
                if (currentTime - lastTapTime < doubleTapDelay) {
                    clearTimeout(tapTimer);
                    window.location.href = profileIconLink.href; 
                    tapCount = 0; 
                    lastTapTime = 0; 
                    profileDropdown.classList.remove('show'); 
                } else {
                    tapCount = 1;
                    lastTapTime = currentTime;
                    clearTimeout(tapTimer); 
                    tapTimer = setTimeout(function() {
                        profileDropdown.classList.toggle('show');
                        tapCount = 0;
                    }, doubleTapDelay);
                }
            }
        });
        document.addEventListener('click', function(event) {
            if (!profileIconContainer.contains(event.target) && profileDropdown.classList.contains('show')) {
                profileDropdown.classList.remove('show');
                tapCount = 0; 
            }
        });
    }

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
        if (e.type === 'mousedown' && e.button !== 0) return;
        if (profileDropdown && profileDropdown.classList.contains('show')) { return; }
        isDragging = true;
        dragStarted = false; 
        const clientX = e.clientX || e.touches[0].clientX;
        const clientY = e.clientY || e.touches[0].clientY;
        const rect = profileIconContainer.getBoundingClientRect();
        offsetX = clientX - rect.left;
        offsetY = clientY - rect.top;
        profileIconContainer.style.position = 'fixed';
        profileIconContainer.style.right = 'auto';
        profileIconContainer.style.bottom = 'auto';
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
                tapCount = 0; 
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
        document.removeEventListener('mousemove', drag);
        document.removeEventListener('mouseup', endDrag);
        document.removeEventListener('touchmove', drag);
        document.removeEventListener('touchend', endDrag);
        const finalPosition = { left: profileIconContainer.style.left, top: profileIconContainer.style.top };
        localStorage.setItem('profileIconPosition', JSON.stringify(finalPosition));
    }
    profileIconContainer.addEventListener('mousedown', startDrag, true);
    profileIconContainer.addEventListener('touchstart', startDrag, { passive: false, capture: true });
    profileIconContainer.querySelector('a').addEventListener('dragstart', function(e) { e.preventDefault(); });
});
</script>
<!-- Scripts para os Gráficos -->
<script>
document.addEventListener('DOMContentLoaded', function() {

    // GRÁFICO GAUGE IMC
    const gaugeData = <?= $bmi_gauge_data ? json_encode($bmi_gauge_data) : 'null' ?>;
    const bmiValueGauge = gaugeData ? gaugeData.current_bmi : null;
    const canvasElementGauge = document.getElementById('bmiGaugeChart');

    if (bmiValueGauge !== null && canvasElementGauge) {
        const ctxGauge = canvasElementGauge.getContext('2d');
        let existingGaugeChart = Chart.getChart(ctxGauge);
        if (existingGaugeChart) { existingGaugeChart.destroy(); }

        new Chart(ctxGauge, {
            type: 'doughnut',
            data: {
                labels: gaugeData.labels,
                datasets: [{
                    label: 'Categorias IMC', data: gaugeData.values, backgroundColor: gaugeData.colors,
                    borderColor: '#ffffff', borderWidth: 2, circumference: 180, rotation: -90, cutout: '65%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: 'bottom', labels: { boxWidth: 12, padding: 10 } },
                    tooltip: { enabled: false }, title: { display: false }
                },
                events: []
            }
        });
    } else if (canvasElementGauge && !gaugeData) { // Se o canvas existe mas não há dados para o gauge
        const gaugeContainerParent = canvasElementGauge.closest('.card-body');
        if (gaugeContainerParent) {
            // Limpa o container do gauge e mostra mensagem
            const bmiGaugeContainer = gaugeContainerParent.querySelector('.bmi-gauge-container');
            if (bmiGaugeContainer) bmiGaugeContainer.style.display = 'none'; // Esconde o container do canvas
            
            const pElement = document.createElement('p');
            pElement.className = 'text-center text-muted fst-italic my-auto';
            pElement.textContent = 'IMC não calculado (dados de peso/altura da última avaliação em falta).';
            
            // Encontra o parágrafo de nota existente para inserir antes dele, ou adiciona ao final
            const existingNote = gaugeContainerParent.querySelector('p.small.text-muted');
            if (existingNote) {
                gaugeContainerParent.insertBefore(pElement, existingNote);
            } else {
                gaugeContainerParent.appendChild(pElement);
            }
        }
    }


    // GRÁFICOS DE COMPOSIÇÃO (Doughnut/Bar)
    const chartDataOriginal = <?= $chart_data ? json_encode($chart_data) : 'null' ?>;

    const colorFat = 'rgba(255, 99, 132, 0.7)'; 
    const colorLean = 'rgba(75, 192, 192, 0.7)'; 
    const colorWater = 'rgba(54, 162, 235, 0.7)'; 
    const colorMuscle = 'rgba(153, 102, 255, 0.7)'; 
    const colorBone = 'rgba(201, 203, 207, 0.7)'; 
    const borderFat = colorFat.replace('0.7', '1');
    const borderLean = colorLean.replace('0.7', '1');
    const borderWater = colorWater.replace('0.7', '1');
    const borderMuscle = colorMuscle.replace('0.7', '1');
    const borderBone = colorBone.replace('0.7', '1');

    const ctxPercentage = document.getElementById('compositionPercentageChart');
    if (ctxPercentage && chartDataOriginal && chartDataOriginal.percentage && (chartDataOriginal.percentage.fat > 0 || chartDataOriginal.percentage.lean > 0 || chartDataOriginal.percentage.water > 0)) {
        let existingPercentageChart = Chart.getChart(ctxPercentage);
        if (existingPercentageChart) { existingPercentageChart.destroy(); }

        new Chart(ctxPercentage, {
            type: 'doughnut',
            data: {
                labels: ['% Gordura', '% Magra', '% Água'],
                datasets: [{
                    label: 'Composição (%)',
                    data: [chartDataOriginal.percentage.fat, chartDataOriginal.percentage.lean, chartDataOriginal.percentage.water],
                    backgroundColor: [colorFat, colorLean, colorWater],
                    borderColor: [borderFat, borderLean, borderWater],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: { display: true, text: 'Composição Corporal (%)' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) { label += ': '; }
                                if (context.parsed !== null) {
                                    label += context.parsed.toLocaleString('pt-PT', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%';
                                }
                                return label;
                            }
                        }
                    },
                    legend: { display: true, position: 'bottom' }
                }
            }
        });
    } else if(ctxPercentage) {
        ctxPercentage.closest('.chart-container').innerHTML = '<p class="text-center text-muted fst-italic">Dados de percentagem insuficientes da última avaliação.</p>';
    }

    const ctxKg = document.getElementById('compositionKgChart');
    if (ctxKg && chartDataOriginal && chartDataOriginal.kg && (chartDataOriginal.kg.fat > 0 || chartDataOriginal.kg.lean > 0 || chartDataOriginal.kg.muscle > 0 || chartDataOriginal.kg.bone > 0)) {
        let existingKgChart = Chart.getChart(ctxKg);
        if (existingKgChart) { existingKgChart.destroy(); }

        new Chart(ctxKg, {
            type: 'bar',
            data: {
                labels: ['Gorda', 'Magra', 'Muscular', 'Óssea'], 
                datasets: [{
                    label: 'Massa (kg)',
                    data: [chartDataOriginal.kg.fat, chartDataOriginal.kg.lean, chartDataOriginal.kg.muscle, chartDataOriginal.kg.bone],
                    backgroundColor: [colorFat, colorLean, colorMuscle, colorBone],
                    borderColor: [borderFat, borderLean, borderMuscle, borderBone],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y', 
                scales: {
                    x: { beginAtZero: true, title: { display: true, text: 'Massa (kg)' } },
                    y: { ticks: { autoSkip: false } }
                },
                plugins: {
                    title: { display: true, text: 'Composição Corporal (kg)' },
                    legend: { display: false }, 
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) { label += ': '; }
                                if (context.parsed.x !== null) {
                                    label += context.parsed.x.toLocaleString('pt-PT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' kg';
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    } else if(ctxKg) {
        ctxKg.closest('.chart-container').innerHTML = '<p class="text-center text-muted fst-italic">Dados de massa (kg) insuficientes da última avaliação.</p>';
    }
});
</script>



<?php
// Inclui o rodapé da página
include 'footer.php';
?>
