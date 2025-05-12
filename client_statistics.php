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

try {
    // Busca todos os dados necessários numa única query
    $stmt = $pdo->prepare("
        SELECT
            u.name, u.email, u.profile_picture, -- Dados básicos
            c.height, c.weight, c.activity_level,
            c.water_percentage, c.visceral_fat_index, c.bone_mass_kg,
            c.lean_mass_kg, c.muscle_mass_kg, c.fat_mass_kg,
            c.fat_mass_percentage, c.lean_mass_percentage, c.last_assessment_date
        FROM users u
        LEFT JOIN clients c ON u.id = c.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $profile_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile_data) { throw new Exception("Dados do utilizador não encontrados."); }

    // Calcula o IMC (sem alterações na lógica de cálculo)
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

             // *** NOVO: Lógica para categoria e classe do GRÁFICO GAUGE ***
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
             // *** FIM NOVO ***
        }
    }

    // *** Prepara dados para os GRÁFICOS ORIGINAIS (doughnut/bar) ***
    // *** REVERTIDO: Mantém a estrutura original com 'water' ***
    if (isset($profile_data['fat_mass_percentage']) || isset($profile_data['lean_mass_percentage']) || isset($profile_data['water_percentage']) ||
        isset($profile_data['fat_mass_kg']) || isset($profile_data['lean_mass_kg']) || isset($profile_data['muscle_mass_kg']) || isset($profile_data['bone_mass_kg']))
    {
        $chart_data = [ // Usa a variável $chart_data original
            'percentage' => [
                'fat' => (float)($profile_data['fat_mass_percentage'] ?? 0),
                'lean' => (float)($profile_data['lean_mass_percentage'] ?? 0),
                'water' => (float)($profile_data['water_percentage'] ?? 0), // Mantém a água aqui
            ],
            'kg' => [
                'fat' => (float)($profile_data['fat_mass_kg'] ?? 0),
                'lean' => (float)($profile_data['lean_mass_kg'] ?? 0),
                'muscle' => (float)($profile_data['muscle_mass_kg'] ?? 0),
                'bone' => (float)($profile_data['bone_mass_kg'] ?? 0),
            ]
        ];
    }

    // *** NOVO: Prepara dados para o GRÁFICO GAUGE (apenas se BMI foi calculado) ***
    if ($bmi !== null) {
        $bmi_gauge_data = [
            'values' => [18.5, 6.4, 4.9, 4.9, 4.9, 5.0], // Tamanhos relativos
            'colors' => ['#3498db', '#2ecc71', '#f1c40f', '#e67e22', '#e74c3c', '#c0392b'], // Cores
            'labels' => [ // Labels para a legenda
                'Abaixo do Peso (<18.5)', 'Peso Normal (18.5-24.9)', 'Acima do Peso (25-29.9)',
                'Obesidade I (30-34.9)', 'Obesidade II (35-39.9)', 'Obesidade III (≥40)'
            ],
            'current_bmi' => $bmi // Valor atual
        ];
    }
    // *** FIM NOVO ***

} catch (Exception $e) {
    $page_error = 'Erro ao carregar dados das estatísticas: ' . $e->getMessage();
    error_log("Error fetching statistics data for user $user_id: " . $e->getMessage());
    $profile_data = [];
    $chart_data = null;
    $bmi_gauge_data = null;
}

// Define active page for sidebar
$active_page = basename($_SERVER['PHP_SELF']);

// Helper function original
function displayStat($value, $unit = '', $decimals = 1, $na_text = '<span class="text-muted fst-italic">N/D</span>') {
    if (isset($value) && $value !== null && $value !== '') {
        return htmlspecialchars(number_format((float)$value, $decimals, ',', '.')) . $unit;
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
    /* ***** AJUSTE APLICADO AQUI ***** */
    .bmi-value-display {
        position: absolute;
        top: 45%; /* Posição vertical (ajuste conforme necessário) */
        left: 50%;
        transform: translateX(-50%); /* Apenas centraliza horizontalmente */
        text-align: center;
        width: 100%;
    }
    /* ***** FIM DO AJUSTE ***** */
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
    .chart-container canvas { /* Garante responsividade dos canvas */
        max-width: 100%;
        height: auto;
    }
</style>

<!-- Conteúdo Principal -->
<main class="main-content">
    <h2>Minhas Estatísticas</h2>

    <?php if (!empty($page_error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($page_error) ?></div>
    <?php elseif (empty($profile_data)): ?>
         <div class="alert alert-warning">Não foi possível carregar os dados do perfil.</div>
    <?php else: ?>

        <!-- Secção Resumo Geral (Original) -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title mb-4"><i class="fas fa-chart-pie me-2"></i>Resumo Geral</h5>
                <div class="row">
                    <div class="col-sm-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Altura:</strong></p> <p class="fs-5"><?= displayStat($profile_data['height'] ?? null, ' m', 2) ?></p> </div>
                    <div class="col-sm-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Peso Atual:</strong></p> <p class="fs-5"><?= displayStat($profile_data['weight'] ?? null, ' kg', 1) ?></p> </div>
                    <!-- Exibição textual original do IMC -->
                    <div class="col-sm-6 col-lg-3 mb-3">
                        <p class="mb-1"><strong>IMC:</strong></p>
                        <?php if ($bmi !== null): ?>
                            <p class="fs-5 mb-0"><?= htmlspecialchars($bmi) ?></p>
                            <small class="text-muted">(<?= htmlspecialchars($bmi_status) // Usa o status textual original ?>)</small>
                        <?php else: ?>
                            <p class="text-muted fst-italic">N/D</p>
                        <?php endif; ?>
                    </div>
                    <div class="col-sm-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Nível Atividade:</strong></p> <p class="fs-5"><?= !empty($profile_data['activity_level']) ? htmlspecialchars($profile_data['activity_level']) : '<span class="text-muted fst-italic">N/D</span>' ?></p> </div>
                </div>
            </div>
        </div>

        <!-- *** NOVA SECÇÃO: Gráfico Gauge IMC *** -->
        <?php if ($bmi_gauge_data): // Só mostra se o IMC foi calculado ?>
        <!-- ***** ALTERAÇÃO AQUI: Removido justify-content-center ***** -->
        <div class="row mb-4">
            <!-- ***** ALTERAÇÃO AQUI: Classe da coluna alterada para col-lg-12 ***** -->
            <div class="col-lg-12">
                 <div class="card shadow-sm h-100">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i>Visualização do IMC</h5>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-center text-center">
                        <!-- O container do gráfico em si mantém o max-width para não ficar gigante -->
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
            <!-- ***** FIM DAS ALTERAÇÕES ***** -->
        </div>
        <?php endif; ?>
        <!-- *** FIM NOVA SECÇÃO *** -->


        <!-- Secção Composição Corporal (Original) -->
        <div class="card shadow-sm mb-4">
             <div class="card-body">
                <h5 class="card-title mb-3"><i class="fas fa-percentage me-2"></i>Composição Corporal</h5>
                <?php if (!empty($profile_data['last_assessment_date'])): ?> <p class="text-muted small mb-3">Última avaliação: <?= htmlspecialchars(date('d/m/Y', strtotime($profile_data['last_assessment_date']))) ?></p> <?php else: ?> <p class="text-muted small mb-3 fst-italic">Nenhuma data de avaliação registada.</p> <?php endif; ?>
                <div class="row">
                    <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Massa Gorda:</strong></p> <p class="fs-5"><?= displayStat($profile_data['fat_mass_kg'] ?? null, ' kg', 2) ?></p> </div>
                    <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Massa Magra:</strong></p> <p class="fs-5"><?= displayStat($profile_data['lean_mass_kg'] ?? null, ' kg', 2) ?></p> </div>
                    <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Massa Muscular:</strong></p> <p class="fs-5"><?= displayStat($profile_data['muscle_mass_kg'] ?? null, ' kg', 2) ?></p> </div>
                    <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Massa Óssea:</strong></p> <p class="fs-5"><?= displayStat($profile_data['bone_mass_kg'] ?? null, ' kg', 2) ?></p> </div>
                    <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>% Massa Gorda:</strong></p> <p class="fs-5"><?= displayStat($profile_data['fat_mass_percentage'] ?? null, ' %', 1) ?></p> </div>
                    <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>% Massa Magra:</strong></p> <p class="fs-5"><?= displayStat($profile_data['lean_mass_percentage'] ?? null, ' %', 1) ?></p> </div>
                    <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>% Água Corporal:</strong></p> <p class="fs-5"><?= displayStat($profile_data['water_percentage'] ?? null, ' %', 1) ?></p> </div>
                    <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Gordura Visceral:</strong></p> <p class="fs-5"><?= displayStat($profile_data['visceral_fat_index'] ?? null, '', 0) ?></p> </div>
                </div>
            </div>
        </div>

        <!-- Secção Gráficos Originais -->
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3"><i class="fas fa-chart-bar me-2"></i>Visualização Gráfica (Composição)</h5>
                <?php if ($chart_data): // Usa a variável $chart_data original ?>
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
                    <p class="text-muted fst-italic">Não há dados suficientes de composição corporal para gerar gráficos.</p>
                <?php endif; ?>
            </div>
        </div>

    <?php endif; // End else for page_error check ?>

     <div class="text-center mt-4">
        <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar ao Dashboard</a>
    </div>

</main> <!-- Fim .main-content -->

<!-- Scripts para os Gráficos -->
<script>
document.addEventListener('DOMContentLoaded', function() {

    // *** GRÁFICO GAUGE IMC (NOVO) ***
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
    } else if (canvasElementGauge) {
        // Opcional: Mostrar mensagem se não houver dados para o gauge
        // canvasElementGauge.closest('.card-body').innerHTML = '<p class="text-center text-muted fst-italic my-auto">IMC não calculado.</p>';
    }
    // *** FIM GRÁFICO GAUGE IMC ***


    // *** GRÁFICOS DE COMPOSIÇÃO (Doughnut/Bar) - ATUALIZADO COM CORES ***
    const chartDataOriginal = <?= $chart_data ? json_encode($chart_data) : 'null' ?>;

    // --- Definição de Cores Consistentes ---
    const colorFat = 'rgba(255, 99, 132, 0.7)'; // Vermelho/Rosa para Gordura
    const colorLean = 'rgba(75, 192, 192, 0.7)'; // Verde Água para Magra
    const colorWater = 'rgba(54, 162, 235, 0.7)'; // Azul para Água
    const colorMuscle = 'rgba(153, 102, 255, 0.7)'; // Roxo para Muscular (usado na barra)
    const colorBone = 'rgba(201, 203, 207, 0.7)'; // Cinza para Óssea (usado na barra)
    // Cores das Bordas (mais opacas)
    const borderFat = colorFat.replace('0.7', '1');
    const borderLean = colorLean.replace('0.7', '1');
    const borderWater = colorWater.replace('0.7', '1');
    const borderMuscle = colorMuscle.replace('0.7', '1');
    const borderBone = colorBone.replace('0.7', '1');
    // ----------------------------------------

    // Gráfico de Percentagem (Doughnut) - REVERTIDO para dados originais, com cores consistentes
    const ctxPercentage = document.getElementById('compositionPercentageChart');
    if (ctxPercentage && chartDataOriginal && chartDataOriginal.percentage && (chartDataOriginal.percentage.fat > 0 || chartDataOriginal.percentage.lean > 0 || chartDataOriginal.percentage.water > 0)) {
        let existingPercentageChart = Chart.getChart(ctxPercentage);
        if (existingPercentageChart) { existingPercentageChart.destroy(); }

        new Chart(ctxPercentage, {
            type: 'doughnut',
            data: {
                // REVERTIDO: Labels originais
                labels: ['% Gordura', '% Magra', '% Água'],
                datasets: [{
                    label: 'Composição (%)',
                    // REVERTIDO: Dados originais
                    data: [chartDataOriginal.percentage.fat, chartDataOriginal.percentage.lean, chartDataOriginal.percentage.water],
                    // ATUALIZADO: Cores consistentes
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
                    legend: { // Mantém legenda para clarificar as cores
                        display: true,
                        position: 'bottom'
                    }
                }
            }
        });
    } else if(ctxPercentage) {
        ctxPercentage.closest('.chart-container').innerHTML = '<p class="text-center text-muted fst-italic">Dados de percentagem insuficientes.</p>';
    }

    // Gráfico de Massa (Bar) - ATUALIZADO com cores consistentes
    const ctxKg = document.getElementById('compositionKgChart');
    if (ctxKg && chartDataOriginal && chartDataOriginal.kg && (chartDataOriginal.kg.fat > 0 || chartDataOriginal.kg.lean > 0 || chartDataOriginal.kg.muscle > 0 || chartDataOriginal.kg.bone > 0)) {
        let existingKgChart = Chart.getChart(ctxKg);
        if (existingKgChart) { existingKgChart.destroy(); }

        new Chart(ctxKg, {
            type: 'bar',
            data: {
                labels: ['Gorda', 'Magra', 'Muscular', 'Óssea'], // Mantém labels originais
                datasets: [{
                    label: 'Massa (kg)',
                    // Mantém dados originais
                    data: [chartDataOriginal.kg.fat, chartDataOriginal.kg.lean, chartDataOriginal.kg.muscle, chartDataOriginal.kg.bone],
                    // ATUALIZADO: Cores consistentes na ordem correta
                    backgroundColor: [colorFat, colorLean, colorMuscle, colorBone],
                    borderColor: [borderFat, borderLean, borderMuscle, borderBone],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y', // Mantém barras horizontais
                scales: {
                    x: { beginAtZero: true, title: { display: true, text: 'Massa (kg)' } },
                    y: { ticks: { autoSkip: false } }
                },
                plugins: {
                    title: { display: true, text: 'Composição Corporal (kg)' },
                    legend: { display: false }, // Legenda não necessária para gráfico de barras com uma série
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
        ctxKg.closest('.chart-container').innerHTML = '<p class="text-center text-muted fst-italic">Dados de massa (kg) insuficientes.</p>';
    }
    // *** FIM GRÁFICOS DE COMPOSIÇÃO ***
});
</script>



<?php
// Inclui o rodapé da página
include 'footer.php';
?>
