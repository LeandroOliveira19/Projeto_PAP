<?php
// filepath: c:\xampp\htdocs\Projeto_PAP\evolution.php
session_start();

// Verifica se o usuário está logado e se é um cliente
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

include 'db.php';
include 'header.php';

$user_id = $_SESSION['user_id'];
$page_error = '';
$assessments = [];
$first_assessment = null;
$latest_assessment = null;
$evolution_summary = null; // Array para guardar a análise
$chart_data_js = 'null'; // JSON para os gráficos

// Define a página ativa para a sidebar
$active_page = basename($_SERVER['PHP_SELF']); // evolution.php

// Inclui a sidebar do cliente
include 'sidebar_client.php';

// --- Buscar Histórico de Avaliações ---
try {
    // Primeiro, obter o client_id
    $stmt_get_client_id = $pdo->prepare("SELECT id FROM clients WHERE user_id = ? LIMIT 1");
    $stmt_get_client_id->execute([$user_id]);
    $client_id = $stmt_get_client_id->fetchColumn();

    if (!$client_id) {
        throw new Exception("Registo de cliente não encontrado.");
    }

    // Buscar todas as avaliações ordenadas da mais antiga para a mais recente
    $stmt = $pdo->prepare("
        SELECT * FROM history_assessments
        WHERE client_id = :client_id
        ORDER BY assessment_date ASC, id ASC
    ");
    $stmt->execute(['client_id' => $client_id]);
    $assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Verificar se há dados suficientes para análise
    if (count($assessments) >= 2) {
        $first_assessment = $assessments[0];
        $latest_assessment = end($assessments); // Pega o último elemento

        // --- Calcular Diferenças ---
        $weight_change = ($latest_assessment['weight'] ?? 0) - ($first_assessment['weight'] ?? 0);
        $fat_perc_change = ($latest_assessment['fat_mass_percentage'] ?? 0) - ($first_assessment['fat_mass_percentage'] ?? 0);
        $muscle_kg_change = ($latest_assessment['muscle_mass_kg'] ?? 0) - ($first_assessment['muscle_mass_kg'] ?? 0);
        $visc_fat_change = ($latest_assessment['visceral_fat_index'] ?? 0) - ($first_assessment['visceral_fat_index'] ?? 0);
        $waist_change = ($latest_assessment['cintura'] ?? 0) - ($first_assessment['cintura'] ?? 0);

        // --- Análise Qualitativa Simples ---
        $analysis = [];
        $recommendations = [];
        $overall_status = "neutral";

        if ($fat_perc_change < -0.5) {
            $analysis['fat'] = 'positive';
            $recommendations[] = "Excelente redução na percentagem de gordura! Continue focado na alimentação e nos treinos.";
        } elseif ($fat_perc_change > 0.5) {
            $analysis['fat'] = 'negative';
            $recommendations[] = "Houve um aumento na percentagem de gordura. Reveja a consistência dos treinos e a dieta. Pequenos ajustes podem ajudar.";
        } else {
            $analysis['fat'] = 'neutral';
            $recommendations[] = "A percentagem de gordura manteve-se estável. Mantenha a rotina ou discuta novos objetivos com os seus profissionais.";
        }

        if ($muscle_kg_change > 0.2) {
            $analysis['muscle'] = 'positive';
            $recommendations[] = "Bom aumento na massa muscular! Garanta proteína suficiente na dieta para suportar o crescimento.";
        } elseif ($muscle_kg_change < -0.2) {
            $analysis['muscle'] = 'negative';
            $recommendations[] = "Houve uma ligeira perda de massa muscular. Verifique se a intensidade do treino e a ingestão de proteína estão adequadas.";
        } else {
            $analysis['muscle'] = 'neutral';
        }

        if ($visc_fat_change < 0) {
            $analysis['visceral'] = 'positive';
            $recommendations[] = "Redução da gordura visceral é muito positiva para a saúde. Continue com os hábitos saudáveis.";
        } elseif ($visc_fat_change > 0) {
            $analysis['visceral'] = 'negative';
            $recommendations[] = "A gordura visceral aumentou ligeiramente. Foque em cardio e alimentação equilibrada.";
        } else {
            $analysis['visceral'] = 'neutral';
        }

        if ($waist_change < -1) {
             $analysis['waist'] = 'positive';
             $recommendations[] = "Ótima redução no perímetro da cintura!";
        } elseif ($waist_change > 1) {
             $analysis['waist'] = 'negative';
             $recommendations[] = "O perímetro da cintura aumentou. Atenção à dieta e exercícios focados na zona abdominal.";
        } else {
             $analysis['waist'] = 'neutral';
        }

        if ($analysis['fat'] === 'positive' && $analysis['muscle'] !== 'negative') {
            $overall_status = 'positive';
        } elseif ($analysis['fat'] === 'negative' || $analysis['muscle'] === 'negative') {
            $overall_status = 'negative';
        }

        $evolution_summary = [
            'start_date' => $first_assessment['assessment_date'],
            'end_date' => $latest_assessment['assessment_date'],
            'weight_change' => $weight_change,
            'fat_perc_change' => $fat_perc_change,
            'muscle_kg_change' => $muscle_kg_change,
            'visc_fat_change' => $visc_fat_change,
            'waist_change' => $waist_change,
            'analysis' => $analysis,
            'recommendations' => $recommendations,
            'overall_status' => $overall_status
        ];

        // --- Preparar Dados para Gráficos ---
        $chart_labels = [];
        // Existing
        $chart_weight_data = [];
        $chart_fat_perc_data = [];
        $chart_muscle_kg_data = [];
        // New
        $chart_fat_kg_data = [];
        $chart_lean_kg_data = [];
        $chart_bone_kg_data = [];
        $chart_lean_perc_data = [];
        $chart_water_perc_data = [];
        $chart_visc_fat_data = [];


        foreach ($assessments as $assessment) {
            $chart_labels[] = date('d/m/Y', strtotime($assessment['assessment_date']));
            // Existing
            $chart_weight_data[] = $assessment['weight'] ?? null;
            $chart_fat_perc_data[] = $assessment['fat_mass_percentage'] ?? null;
            $chart_muscle_kg_data[] = $assessment['muscle_mass_kg'] ?? null;
            // New
            $chart_fat_kg_data[] = $assessment['fat_mass_kg'] ?? null;
            $chart_lean_kg_data[] = $assessment['lean_mass_kg'] ?? null;
            $chart_bone_kg_data[] = $assessment['bone_mass_kg'] ?? null;
            $chart_lean_perc_data[] = $assessment['lean_mass_percentage'] ?? null;
            $chart_water_perc_data[] = $assessment['water_percentage'] ?? null;
            $chart_visc_fat_data[] = $assessment['visceral_fat_index'] ?? null;
        }

        // *** ATUALIZADO: Adiciona os novos arrays ao JSON ***
        $chart_data_js = json_encode([
            'labels' => $chart_labels,
            'weight' => $chart_weight_data,
            'fat_perc' => $chart_fat_perc_data,
            'muscle_kg' => $chart_muscle_kg_data,
            'fat_kg' => $chart_fat_kg_data,         // NEW
            'lean_kg' => $chart_lean_kg_data,       // NEW
            'bone_kg' => $chart_bone_kg_data,       // NEW
            'lean_perc' => $chart_lean_perc_data,   // NEW
            'water_perc' => $chart_water_perc_data, // NEW
            'visc_fat' => $chart_visc_fat_data      // NEW
        ]);

    } elseif (count($assessments) === 1) {
        $page_error = "É necessária pelo menos mais uma avaliação para analisar a evolução.";
        $latest_assessment = $assessments[0];
    } else {
        $page_error = "Nenhuma avaliação física encontrada no seu histórico.";
    }

} catch (Exception $e) {
    $page_error = "Erro ao carregar dados de evolução: " . $e->getMessage();
    error_log($page_error);
}

// Helper para formatar mudanças com cor e ícone
function formatChange($change, $unit = '', $decimals = 1, $positive_is_good = true) {
    if ($change == 0) {
        return '<span class="text-muted">0' . $unit . ' <i class="fas fa-equals"></i></span>';
    }
    $formatted_change = number_format($change, $decimals, ',', '.') . $unit;
    if ($change > 0) {
        $color = $positive_is_good ? 'text-success' : 'text-danger';
        $icon = $positive_is_good ? 'fa-arrow-up' : 'fa-arrow-down';
        return '<span class="' . $color . '">' . $formatted_change . ' <i class="fas ' . $icon . '"></i></span>';
    } else {
        $color = !$positive_is_good ? 'text-success' : 'text-danger';
        $icon = !$positive_is_good ? 'fa-arrow-down' : 'fa-arrow-up';
        return '<span class="' . $color . '">' . $formatted_change . ' <i class="fas ' . $icon . '"></i></span>';
    }
}

// *** FUNÇÃO displayInfo ADICIONADA AQUI ***
// Helper function to display stats or N/D
function displayInfo($value, $unit = '', $decimals = 1, $na_text = '<span class="text-muted fst-italic">N/D</span>') {
    if (isset($value) && $value !== null && $value !== '') {
        if (is_numeric($value) && !empty($unit)) {
             return htmlspecialchars(number_format((float)$value, $decimals, ',', '.')) . $unit;
        }
        return htmlspecialchars($value);
    }
    return $na_text;
}
// *** FIM DA FUNÇÃO displayInfo ***

?>

<!-- Conteúdo Principal -->
<main class="main-content">
    <div class="container mt-5">
        <h2><i class="fas fa-chart-line me-2"></i>Minha Evolução</h2>

        <?php if (!empty($page_error)): ?>
            <div class="alert alert-warning"><?= htmlspecialchars($page_error) ?></div>
        <?php endif; ?>

        <?php if ($evolution_summary): ?>
            <p class="text-muted">Análise do progresso entre <?= htmlspecialchars(date('d/m/Y', strtotime($evolution_summary['start_date']))) ?> e <?= htmlspecialchars(date('d/m/Y', strtotime($evolution_summary['end_date']))) ?>.</p>

            <!-- Card: Resumo das Mudanças -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Resumo das Mudanças</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4 col-6 mb-3">
                            <p class="mb-1"><strong>Peso</strong></p>
                            <p class="fs-4"><?= formatChange($evolution_summary['weight_change'], ' kg', 1, false) ?></p>
                        </div>
                        <div class="col-md-4 col-6 mb-3">
                            <p class="mb-1"><strong>% Gordura</strong></p>
                            <p class="fs-4"><?= formatChange($evolution_summary['fat_perc_change'], ' %', 1, false) ?></p>
                        </div>
                        <div class="col-md-4 col-6 mb-3">
                            <p class="mb-1"><strong>Massa Muscular</strong></p>
                            <p class="fs-4"><?= formatChange($evolution_summary['muscle_kg_change'], ' kg', 1, true) ?></p>
                        </div>
                        <div class="col-md-4 col-6 mb-3">
                            <p class="mb-1"><strong>Gordura Visceral</strong></p>
                            <p class="fs-4"><?= formatChange($evolution_summary['visc_fat_change'], '', 0, false) ?></p>
                        </div>
                         <div class="col-md-4 col-6 mb-3">
                            <p class="mb-1"><strong>Cintura</strong></p>
                            <p class="fs-4"><?= formatChange($evolution_summary['waist_change'], ' cm', 1, false) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card: Análise e Recomendações -->
            <div class="card shadow-sm mb-4">
                 <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-comment-dots me-2"></i>Análise e Recomendações</h5>
                </div>
                <div class="card-body">
                    <?php
                        $status_text = '';
                        $status_icon = '';
                        $status_class = '';
                        switch ($evolution_summary['overall_status']) {
                            case 'positive':
                                $status_text = 'Progresso Positivo!';
                                $status_icon = 'fa-thumbs-up';
                                $status_class = 'text-success';
                                break;
                            case 'negative':
                                $status_text = 'Atenção Necessária';
                                $status_icon = 'fa-triangle-exclamation';
                                $status_class = 'text-warning';
                                break;
                            default:
                                $status_text = 'Manutenção/Estável';
                                $status_icon = 'fa-circle-info';
                                $status_class = 'text-info';
                                break;
                        }
                    ?>
                    <h6 class="mb-3"><strong class="me-2">Status Geral:</strong> <span class="<?= $status_class ?>"><i class="fas <?= $status_icon ?> me-1"></i><?= $status_text ?></span></h6>

                    <p><strong>Recomendações:</strong></p>
                    <?php if (!empty($evolution_summary['recommendations'])): ?>
                        <ul>
                            <?php foreach ($evolution_summary['recommendations'] as $rec): ?>
                                <li><?= htmlspecialchars($rec) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted">Nenhuma recomendação específica gerada.</p>
                    <?php endif; ?>
                    <p class="mt-3"><small class="text-muted">Lembre-se que esta é uma análise geral. Converse com seu treinador e nutricionista para um feedback mais detalhado e ajustes nos seus planos.</small></p>
                </div>
            </div>

             <!-- Card: Gráficos de Evolução -->
            <div class="card shadow-sm mb-4">
                 <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-area me-2"></i>Gráficos de Evolução</h5>
                </div>
                <div class="card-body">
                    <!-- *** INÍCIO: Estrutura HTML Atualizada para Gráficos *** -->
                    <h6 class="text-muted mb-3">Geral e Índices</h6>
                    <div class="row">
                        <div class="col-lg-6 mb-4">
                            <div class="chart-container" style="position: relative; height:300px; width:100%">
                                <canvas id="weightChart"></canvas> <!-- Peso (kg) -->
                            </div>
                        </div>
                         <div class="col-lg-6 mb-4">
                            <div class="chart-container" style="position: relative; height:300px; width:100%">
                                <canvas id="visceralFatChart"></canvas> <!-- Gordura Visceral (Índice) -->
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="text-muted mb-3">Percentagens (%)</h6>
                    <div class="row">
                        <div class="col-lg-6 mb-4">
                            <div class="chart-container" style="position: relative; height:300px; width:100%">
                                <canvas id="fatPercentageChart"></canvas> <!-- % Gordura Corporal -->
                            </div>
                        </div>
                         <div class="col-lg-6 mb-4">
                            <div class="chart-container" style="position: relative; height:300px; width:100%">
                                <canvas id="leanPercentageChart"></canvas> <!-- % Massa Magra -->
                            </div>
                        </div>
                         <div class="col-lg-6 mb-4">
                            <div class="chart-container" style="position: relative; height:300px; width:100%">
                                <canvas id="waterPercentageChart"></canvas> <!-- % Água Corporal -->
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="text-muted mb-3">Massas (kg)</h6>
                    <div class="row">
                        <div class="col-lg-6 mb-4">
                            <div class="chart-container" style="position: relative; height:300px; width:100%">
                                <canvas id="fatMassKgChart"></canvas> <!-- Massa Gorda (kg) -->
                            </div>
                        </div>
                         <div class="col-lg-6 mb-4">
                            <div class="chart-container" style="position: relative; height:300px; width:100%">
                                <canvas id="leanMassKgChart"></canvas> <!-- Massa Magra (kg) -->
                            </div>
                        </div>
                         <div class="col-lg-6 mb-4">
                            <div class="chart-container" style="position: relative; height:300px; width:100%">
                                <canvas id="muscleMassChart"></canvas> <!-- Massa Muscular (kg) -->
                            </div>
                        </div>
                         <div class="col-lg-6 mb-4">
                            <div class="chart-container" style="position: relative; height:300px; width:100%">
                                <canvas id="boneMassKgChart"></canvas> <!-- Massa Óssea (kg) -->
                            </div>
                        </div>
                    </div>
                    <!-- *** FIM: Estrutura HTML Atualizada para Gráficos *** -->
                </div>
            </div>


        <?php elseif ($latest_assessment): // Caso tenha apenas 1 avaliação ?>
             <p class="text-muted">Dados da sua única avaliação em <?= htmlspecialchars(date('d/m/Y', strtotime($latest_assessment['assessment_date']))) ?>.</p>
             <!-- Mostrar dados da última avaliação (similar à ficha pessoal) -->
             <div class="card shadow-sm mb-4">
                <div class="card-body">
                     <div class="row">
                        <div class="col-md-4"><strong>Peso:</strong> <?= displayInfo($latest_assessment['weight'] ?? null, ' kg', 1) ?></div>
                        <div class="col-md-4"><strong>% Gordura:</strong> <?= displayInfo($latest_assessment['fat_mass_percentage'] ?? null, ' %', 1) ?></div>
                        <div class="col-md-4"><strong>Massa Muscular:</strong> <?= displayInfo($latest_assessment['muscle_mass_kg'] ?? null, ' kg', 1) ?></div>
                        <!-- Adicione mais campos se desejar -->
                    </div>
                </div>
             </div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="ficha_pessoal.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar à Ficha Pessoal</a>
        </div>

    </div><!-- /.container -->
</main> <!-- /.main-content -->

<!-- Chart.js (já deve estar no footer.php) -->
<!-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chartData = <?= $chart_data_js ?>; // Pega os dados JSON do PHP

    if (chartData && chartData.labels && chartData.labels.length >= 2) { // Precisa de pelo menos 2 pontos

        // Função auxiliar para criar gráficos de linha
        const createLineChart = (canvasId, label, data, yLabel, suggestedMin = undefined, suggestedMax = undefined, color = 'rgb(75, 192, 192)') => { // Adicionado parâmetro de cor
            const ctx = document.getElementById(canvasId);
            if (!ctx) {
                console.warn(`Canvas element with ID "${canvasId}" not found.`); // Aviso se o canvas não existir
                return;
            }

            // Filtra pontos nulos para evitar linhas quebradas incorretamente
            const filteredData = data.map(value => (value === null || value === undefined || value === '') ? NaN : parseFloat(value)); // Usa NaN para Chart.js ignorar e garante número

            // Destrói gráfico existente se houver
            let existingChart = Chart.getChart(ctx);
            if (existingChart) {
                existingChart.destroy();
            }

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: label,
                        data: filteredData,
                        borderColor: color, // Usa a cor passada como parâmetro
                        backgroundColor: color.replace('rgb(', 'rgba(').replace(')', ', 0.1)'), // Cor de fundo levemente transparente
                        tension: 0.1,
                        fill: false, // Pode mudar para true se quiser área preenchida
                        spanGaps: true // Conecta pontos mesmo com NaN no meio (opcional)
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false, // Não força começar em zero, a menos que os dados sugiram
                            suggestedMin: suggestedMin,
                            suggestedMax: suggestedMax,
                            title: {
                                display: true,
                                text: yLabel
                            }
                        },
                        x: {
                             title: {
                                display: true,
                                text: 'Data da Avaliação'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                // Formata o valor no tooltip
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null && !isNaN(context.parsed.y)) {
                                        // Adapta a formatação baseada no yLabel
                                        let decimals = 1;
                                        if (yLabel.toLowerCase().includes('kg')) decimals = 2;
                                        if (yLabel.toLowerCase().includes('índice')) decimals = 0;
                                        label += context.parsed.y.toLocaleString('pt-PT', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
                                        if (yLabel) label += ` ${yLabel.split(' ')[1] || ''}`; // Adiciona unidade
                                    } else {
                                        label += 'N/D';
                                    }
                                    return label;
                                }
                            }
                        },
                         title: {
                            display: true,
                            text: `Evolução de ${label}`
                        },
                        legend: { // Oculta a legenda se houver apenas um dataset
                            display: false
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            });
        };

        // *** ATUALIZADO: Criar todos os gráficos com cores diferentes ***
        // Grupo 1: Geral e Índices
        createLineChart('weightChart', 'Peso', chartData.weight, 'Peso (kg)', undefined, undefined, 'rgb(54, 162, 235)'); // Azul
        createLineChart('visceralFatChart', 'Gordura Visceral', chartData.visc_fat, 'Índice', 0, undefined, 'rgb(255, 99, 132)'); // Vermelho

        // Grupo 2: Percentagens
        createLineChart('fatPercentageChart', '% Gordura Corporal', chartData.fat_perc, '% Gordura', 0, undefined, 'rgb(255, 159, 64)'); // Laranja
        createLineChart('leanPercentageChart', '% Massa Magra', chartData.lean_perc, '% Massa Magra', 0, undefined, 'rgb(75, 192, 192)'); // Verde Água (original)
        createLineChart('waterPercentageChart', '% Água Corporal', chartData.water_perc, '% Água', 0, undefined, 'rgb(153, 102, 255)'); // Roxo

        // Grupo 3: Massas (kg)
        createLineChart('fatMassKgChart', 'Massa Gorda (kg)', chartData.fat_kg, 'Massa (kg)', 0, undefined, 'rgb(255, 205, 86)'); // Amarelo
        createLineChart('leanMassKgChart', 'Massa Magra (kg)', chartData.lean_kg, 'Massa (kg)', 0, undefined, 'rgb(100, 220, 150)'); // Verde Claro
        createLineChart('muscleMassChart', 'Massa Muscular (kg)', chartData.muscle_kg, 'Massa (kg)', 0, undefined, 'rgb(201, 203, 207)'); // Cinza
        createLineChart('boneMassKgChart', 'Massa Óssea (kg)', chartData.bone_kg, 'Massa (kg)', 0, undefined, 'rgb(0, 100, 150)'); // Azul Escuro


    } else {
        // Opcional: Esconder ou mostrar mensagem se não houver dados suficientes para gráficos
        const chartCardBody = document.querySelector('#boneMassKgChart')?.closest('.card-body'); // Encontra o card-body dos gráficos
        if (chartCardBody) {
             // Remove todos os containers de gráfico se não houver dados
             const chartContainers = chartCardBody.querySelectorAll('.chart-container');
             chartContainers.forEach(container => container.parentElement.remove()); // Remove a coluna que contém o container
             // Adiciona mensagem
             chartCardBody.innerHTML = '<p class="text-muted fst-italic text-center">Dados insuficientes para gerar gráficos de evolução (necessário pelo menos 2 avaliações).</p>';
        }
    }
});
</script>


<?php
include 'footer.php';
?>
