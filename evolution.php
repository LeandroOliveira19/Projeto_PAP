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
$chronological_age = null; // Added to store chronological age
$client_activity_level = null; // To store client's activity level
$client_gender = null; // To store client's gender (if available)
$metabolic_age_gauge_data = null; // Added for the new gauge chart
$evolution_summary = null; // Array para guardar a análise
$chart_data_js = 'null'; // JSON para os gráficos

// Define a página ativa para a sidebar
$active_page = basename($_SERVER['PHP_SELF']); // evolution.php

// Inclui a sidebar do cliente
include 'sidebar_client.php';

// Helper function to calculate age (copied from ficha_pessoal.php if not global)
if (!function_exists('calculateAge')) {
    function calculateAge($dob) {
        if (!$dob || $dob === '0000-00-00') { return null; }
        try { $birthDate = new DateTime($dob); $today = new DateTime('today'); return $birthDate->diff($today)->y; } catch (Exception $e) { return null; }
    }
}

// Helper function for advanced estimated metabolic age
if (!function_exists('calculateAdvancedMetabolicAge')) {
    function calculateAdvancedMetabolicAge($latestAssessmentData, $chronologicalAge, $activityLevel, $gender = null) {
        if ($chronologicalAge === null || !isset($latestAssessmentData['weight']) || !isset($latestAssessmentData['fat_mass_percentage'])) {
            return null;
        }

        $weight_kg = (float)$latestAssessmentData['weight'];
        $fat_percentage = (float)$latestAssessmentData['fat_mass_percentage'] / 100;

        if ($weight_kg <= 0 || $fat_percentage <= 0 || $fat_percentage >= 1) {
            return null; // Invalid data for LBM calculation
        }

        // 1. Calculate Lean Body Mass (LBM)
        $lbm_kg = $weight_kg * (1 - $fat_percentage);

        // 2. Calculate BMR (Katch-McArdle)
        $bmr = 370 + (21.6 * $lbm_kg);

        // 3. Compare BMR with population data (example data, ideally more granular and sourced)
        // These are illustrative. Real data should be used.
        // Assuming $gender can be 'male', 'female', or null/other.
        $bmr_ranges = [];
        if (strtolower($gender ?? '') === 'female') {
            $bmr_ranges = [ // kcal/day for females (example)
                18 => ['min' => 1300, 'max' => 1450], // Age group lower bound
                25 => ['min' => 1250, 'max' => 1400],
                35 => ['min' => 1200, 'max' => 1350],
                45 => ['min' => 1150, 'max' => 1300],
                55 => ['min' => 1100, 'max' => 1250],
                65 => ['min' => 1050, 'max' => 1200],
            ];
        } else { // Default to male or a combined table if gender is not 'female'
            $bmr_ranges = [ // kcal/day for males (as per user example, extended)
                18 => ['min' => 1750, 'max' => 2000],
                25 => ['min' => 1700, 'max' => 1950],
                35 => ['min' => 1650, 'max' => 1850],
                45 => ['min' => 1550, 'max' => 1800],
                55 => ['min' => 1450, 'max' => 1700],
                65 => ['min' => 1350, 'max' => 1600],
            ];
        }

        $baseMetabolicAge = $chronologicalAge; // Default to chronological age
        $min_diff = PHP_INT_MAX;

        // Find the age group whose BMR range is closest to the calculated BMR
        // This is a simplified approach; more sophisticated interpolation or regression could be used.
        foreach ($bmr_ranges as $age => $range) {
            if ($bmr >= $range['min'] && $bmr <= $range['max']) {
                $baseMetabolicAge = $age; // Exact match or within range
                break;
            }
            // If BMR is outside ranges, find the closest one
            $current_avg_bmr = ($range['min'] + $range['max']) / 2;
            if (abs($bmr - $current_avg_bmr) < $min_diff) {
                $min_diff = abs($bmr - $current_avg_bmr);
                $baseMetabolicAge = $age; // Assign age of the closest BMR range
            }
        }
        // If BMR is very high, it might correspond to a younger age; if very low, to an older age.
        // The above loop finds an age. A more refined approach might adjust further.
        // For instance, if BMR is higher than the youngest age group's max, metabolic age is younger.
        // If BMR is lower than the oldest age group's min, metabolic age is older.

        $estimatedMetabolicAge = (float)$baseMetabolicAge;

        // 4. Adjust for Activity Level
        // Normalize activity level string (example: "Levemente ativo" -> "leve")
        $normalizedActivity = strtolower(str_replace([' ', 'mente'], '', iconv('UTF-8', 'ASCII//TRANSLIT', $activityLevel ?? '')));

        $activity_adjustment_factors = [
            'sedentario' => 0,
            'leveativo' => -1.5,  // "Levemente ativo"
            'moderadamenteativo' => -3.5, // "Moderadamente ativo"
            'ativo' => -5.5,      // "Ativo" (maps to "Muito ativo" in user suggestion)
            'muitoativo' => -8,   // "Muito ativo" (maps to "Extremamente ativo")
        ];
        $activityAdjustment = $activity_adjustment_factors[$normalizedActivity] ?? 0;
        $estimatedMetabolicAge += $activityAdjustment;

        // 5. Adjust for Waist-to-Height Ratio (WHtR)
        if (isset($latestAssessmentData['cintura']) && isset($latestAssessmentData['height']) && (float)$latestAssessmentData['height'] > 0) {
            $waist_cm = (float)$latestAssessmentData['cintura'];
            $height_cm = (float)$latestAssessmentData['height'] * 100; // Assuming height is in meters
            if ($height_cm > 0) { // Avoid division by zero
                $whtr = $waist_cm / $height_cm;

                $ideal_whtr = (strtolower($gender ?? '') === 'female') ? 0.49 : 0.50;

                if ($whtr > $ideal_whtr) {
                    $whtr_penalty = ($whtr - $ideal_whtr) * 100; // Each 0.01 above ideal = +1 year
                    $estimatedMetabolicAge += $whtr_penalty;
                }
                // Optional: reward for very good WHtR, e.g., if $whtr < ($ideal_whtr - 0.05)
            }
        }

        // Ensure metabolic age is not unrealistically low (e.g., < 15)
        $estimatedMetabolicAge = max(15, round($estimatedMetabolicAge));

        return (int)$estimatedMetabolicAge;
    }
}

// --- Buscar Histórico de Avaliações ---
try {
    // Primeiro, obter o client_id, data de nascimento, activity_level e gender
    $stmt_get_client_info = $pdo->prepare("
        SELECT c.id as client_table_id, c.date_of_birth, c.activity_level, u.gender 
        FROM clients c
        LEFT JOIN users u ON c.user_id = u.id
        WHERE c.user_id = ? LIMIT 1
    ");
    $stmt_get_client_info->execute([$user_id]);
    $client_info = $stmt_get_client_info->fetch(PDO::FETCH_ASSOC);

    if (!$client_info || !isset($client_info['client_table_id'])) {
        throw new Exception("Registo de cliente não encontrado.");
    }
    $client_id = $client_info['client_table_id'];
    $chronological_age = calculateAge($client_info['date_of_birth'] ?? null); // Calculate chronological age
    $client_activity_level = $client_info['activity_level'] ?? 'Sedentário'; // Default if null
    $client_gender = $client_info['gender'] ?? null; // Will be null if 'gender' column doesn't exist or is null

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

        // --- Preparar Dados para Gráfico de Idade Metabólica (Gauge) ---
        // Calcula a idade metabólica usando a nova função
        $calculated_metabolic_age = null;
        if ($latest_assessment && $chronological_age !== null) {
            $calculated_metabolic_age = calculateAdvancedMetabolicAge($latest_assessment, $chronological_age, $client_activity_level, $client_gender);
        }

        if ($calculated_metabolic_age !== null) {
            $latest_assessment['idade_metabolica_estimada'] = $calculated_metabolic_age; // Guardar para exibição
            $age_difference = $calculated_metabolic_age - $chronological_age;

            // Define ranges, colors, and labels based on the difference
            // Estes valores e rótulos podem precisar de ajuste fino com base na sensibilidade desejada
            $gauge_values = [5, 5, 5, 5, 10, 10]; // Adjusted segments for more granularity around ideal
            $gauge_colors = ['#2ecc71', '#3498db', '#f1c40f', '#e67e22', '#e74c3c', '#c0392b']; // Verde a Vermelho
            $gauge_labels = [
                'Excelente (<-10)', 'Boa (-10 a -6)', 'Ideal (-5 a +5)',
                'Atenção (+6 a +10)', 'Elevada (+11 a +15)', 'Muito Elevada (>+15)'
            ];
            
            // Determina a categoria e a classe de cor para o texto
            $metabolic_category = 'N/D';
            $metabolic_category_class = 'text-muted';
            if ($age_difference <= -10) { // Ajustado para corresponder aos labels
                $metabolic_category = 'Excelente'; $metabolic_category_class = 'text-success';
            } elseif ($age_difference <= -6) { // Ajustado
                $metabolic_category = 'Boa'; $metabolic_category_class = 'text-primary';
            } elseif ($age_difference <= 5) { // Ideal range 
                $metabolic_category = 'Ideal'; $metabolic_category_class = 'text-warning'; // Ideal pode ser warning se não for perfeito
            } elseif ($age_difference <= 10) { // Ajustado
                $metabolic_category = 'Atenção'; $metabolic_category_class = 'text-orange';
            } elseif ($age_difference <= 15) { // Adjusted upper ranges
                $metabolic_category = 'Elevada'; $metabolic_category_class = 'text-danger';
            } else {
                $metabolic_category = 'Muito Elevada'; $metabolic_category_class = 'text-danger fw-bold';
            }

            $metabolic_age_gauge_data = [
                'values' => $gauge_values, 'colors' => $gauge_colors, 'labels' => $gauge_labels,
                'current_difference' => $age_difference, // A diferença ainda é útil para a categoria
                'calculated_metabolic_age' => $calculated_metabolic_age, // O valor calculado
                'category' => $metabolic_category, 'category_class' => $metabolic_category_class
            ];
        }

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
            'visc_fat' => $chart_visc_fat_data,     // NEW
        ]);

    } elseif (count($assessments) === 1) {
        $page_error = "É necessária pelo menos mais uma avaliação para analisar a evolução.";
        $latest_assessment = $assessments[0];
         // Tenta calcular a idade metabólica mesmo com uma avaliação
        if ($latest_assessment && $chronological_age !== null) {
            $calculated_metabolic_age = calculateAdvancedMetabolicAge($latest_assessment, $chronological_age, $client_activity_level, $client_gender);
            if ($calculated_metabolic_age !== null) {
                $latest_assessment['idade_metabolica_estimada'] = $calculated_metabolic_age;
                $age_difference = $calculated_metabolic_age - $chronological_age;
                // Reutilizar lógica de categoria do gauge
                $gauge_values = [5, 5, 5, 5, 10, 10];
                $gauge_colors = ['#2ecc71', '#3498db', '#f1c40f', '#e67e22', '#e74c3c', '#c0392b'];
                $gauge_labels = ['Excelente (<-10)', 'Boa (-10 a -6)', 'Ideal (-5 a +5)', 'Atenção (+6 a +10)', 'Elevada (+11 a +15)', 'Muito Elevada (>+15)'];
                $metabolic_category = 'N/D'; $metabolic_category_class = 'text-muted';
                if ($age_difference <= -10) { $metabolic_category = 'Excelente'; $metabolic_category_class = 'text-success'; }
                elseif ($age_difference <= -6) { $metabolic_category = 'Boa'; $metabolic_category_class = 'text-primary'; }
                elseif ($age_difference <= 5) { $metabolic_category = 'Ideal'; $metabolic_category_class = 'text-warning'; }
                elseif ($age_difference <= 10) { $metabolic_category = 'Atenção'; $metabolic_category_class = 'text-orange'; }
                elseif ($age_difference <= 15) { $metabolic_category = 'Elevada'; $metabolic_category_class = 'text-danger'; }
                else { $metabolic_category = 'Muito Elevada'; $metabolic_category_class = 'text-danger fw-bold'; }
                $metabolic_age_gauge_data = [
                    'values' => $gauge_values, 'colors' => $gauge_colors, 'labels' => $gauge_labels,
                    'current_difference' => $age_difference, 'calculated_metabolic_age' => $calculated_metabolic_age,
                    'category' => $metabolic_category, 'category_class' => $metabolic_category_class
                ];
            }
        }
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

<!-- CSS Adicional para Gauge e Cor Laranja (COM AJUSTE) -->
<style>
    .text-orange { color: #e67e22 !important; }
    .metabolic-age-gauge-container { /* Adjusted class name */
        position: relative;
        width: 100%;
        max-width: 350px; /* Limita largura */
        margin: 1rem auto; /* Centraliza e adiciona margem */
    }
    .metabolic-age-value-display { /* Adjusted class name */
        position: absolute;
        top: 45%;
        left: 50%;
        transform: translateX(-50%);
        text-align: center;
        width: 100%;
    }
    .metabolic-age-value { /* Adjusted class name */
        font-size: 2.5rem;
        font-weight: bold;
        line-height: 1;
        display: block;
    }
    .metabolic-age-category { /* Adjusted class name */
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
    <div class="container mt-5">
        <h2><i class="fas fa-chart-line me-2"></i>Minha Evolução</h2>

        <?php if (!empty($page_error)): ?>
            <div class="alert alert-warning"><?= htmlspecialchars($page_error) ?></div>
        <?php endif; ?>

        <?php if ($evolution_summary): ?>
            <p class="text-white">Análise do progresso entre <?= htmlspecialchars(date('d/m/Y', strtotime($evolution_summary['start_date']))) ?> e <?= htmlspecialchars(date('d/m/Y', strtotime($evolution_summary['end_date']))) ?>.</p>

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
        <?php endif; ?> <!-- Fim do if ($evolution_summary) -->

            <!-- Card: Gráfico Idade Metabólica (Gauge) -->
            <!-- Movido para fora do if ($evolution_summary) para mostrar mesmo com 1 avaliação -->
            <?php if ($metabolic_age_gauge_data): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-heartbeat me-2"></i>Idade Metabólica Estimada</h5>
                </div>
                <div class="card-body">
                <p class="text-muted small mb-1 text-center">
                    Idade Metabólica Estimada (<?= htmlspecialchars($metabolic_age_gauge_data['calculated_metabolic_age'] ?? 'N/D') ?> anos) vs. Idade Cronológica (<?= htmlspecialchars($chronological_age ?? 'N/D') ?> anos).
                </p>
                <p class="alert alert-warning small p-2 text-center">
                    <i class="fas fa-exclamation-triangle me-1"></i><strong>Atenção:</strong> Este valor é uma estimativa e não substitui uma avaliação profissional detalhada.
                </p>
                    <div class="metabolic-age-gauge-container">
                        <canvas id="metabolicAgeGaugeChart"></canvas>
                        <div class="metabolic-age-value-display">
                            <span id="metabolicAgeActualValue" class="metabolic-age-value <?= $metabolic_age_gauge_data['category_class'] ?>">
                                <?= htmlspecialchars($metabolic_age_gauge_data['calculated_metabolic_age'] ?? 'N/D') ?>
                            </span>
                            <span id="metabolicAgeCategory" class="metabolic-age-category <?= $metabolic_age_gauge_data['category_class'] ?>">
                                <?= htmlspecialchars($metabolic_age_gauge_data['category']) ?>
                            </span>
                        </div>
                    </div>
                    <p class="mt-3 mb-0 small text-muted text-center">
                        A idade metabólica é uma estimativa da idade do seu corpo baseada na sua taxa metabólica basal (TMB) e outros fatores.
                        Idealmente, deve ser igual ou inferior à sua idade cronológica. Consulte um profissional para uma interpretação correta.
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($evolution_summary): // Só mostra gráficos de linha se houver evolução ?>
             <!-- Card: Outros Gráficos de Evolução -->
            <div class="card shadow-sm mb-4">
                 <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-area me-2"></i>Outros Gráficos de Evolução</h5>
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
            <?php elseif (!$evolution_summary && $latest_assessment && !$page_error): // Caso tenha apenas 1 avaliação e não houve erro fatal ?>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chartData = <?= $chart_data_js ?>;

    // --- GRÁFICO GAUGE IDADE METABÓLICA ---
    const metabolicAgeGaugeData = <?= $metabolic_age_gauge_data ? json_encode($metabolic_age_gauge_data) : 'null' ?>;
    const canvasElementMetabolicGauge = document.getElementById('metabolicAgeGaugeChart');

    if (metabolicAgeGaugeData !== null && canvasElementMetabolicGauge) {
        const ctxMetabolicGauge = canvasElementMetabolicGauge.getContext('2d');
        let existingMetabolicGaugeChart = Chart.getChart(ctxMetabolicGauge);
        if (existingMetabolicGaugeChart) { existingMetabolicGaugeChart.destroy(); }

        new Chart(ctxMetabolicGauge, {
            type: 'doughnut',
            data: {
                labels: metabolicAgeGaugeData.labels,
                datasets: [{
                    label: 'Diferença Idade Metabólica vs Cronológica',
                    data: metabolicAgeGaugeData.values,
                    backgroundColor: metabolicAgeGaugeData.colors, // These are the segment colors
                    borderColor: '#ffffff', borderWidth: 2, circumference: 180, rotation: -90, cutout: '65%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: true, position: 'bottom', labels: { boxWidth: 12, padding: 10 } }, tooltip: { enabled: false }, title: { display: false } },
                events: []
            }
        });
    } else if (canvasElementMetabolicGauge) { // Se o canvas existe mas não há dados para o gauge
        const gaugeContainerParent = canvasElementMetabolicGauge.closest('.card-body');
        if (gaugeContainerParent) {
            const metabolicGaugeContainer = gaugeContainerParent.querySelector('.metabolic-age-gauge-container');
            if (metabolicGaugeContainer) metabolicGaugeContainer.style.display = 'none';
            
            const pElement = document.createElement('p');
            pElement.className = 'text-center text-muted fst-italic my-auto';
            pElement.textContent = 'Idade metabólica não pôde ser estimada (dados insuficientes).';
            
            const existingNote = gaugeContainerParent.querySelector('p.small.text-muted.text-center');
            if (existingNote) {
                gaugeContainerParent.insertBefore(pElement, existingNote);
            } else {
                gaugeContainerParent.appendChild(pElement);
            }
        }
    }


    if (chartData && chartData.labels && chartData.labels.length >= 2) { // Precisa de pelo menos 2 pontos para gráficos de linha

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
                                        if (yLabel.toLowerCase().includes('índice') || yLabel.toLowerCase().includes('anos')) decimals = 0; // Para Idade Metabólica e Índice Visceral
                                        label += context.parsed.y.toLocaleString('pt-PT', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
                                        if (yLabel) {
                                            const unitPart = yLabel.split(' ')[1]; // Pega a parte da unidade (ex: (kg), (%) )
                                            if (unitPart) label += ` ${unitPart.replace(/[()]/g, '')}`; // Remove parênteses e adiciona
                                        }
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
        createLineChart('fatPercentageChart', '% Gordura Corporal', chartData.fat_perc, '% Gordura (%)', 0, undefined, 'rgb(255, 159, 64)'); // Laranja
        createLineChart('leanPercentageChart', '% Massa Magra', chartData.lean_perc, '% Massa Magra (%)', 0, undefined, 'rgb(75, 192, 192)'); // Verde Água (original)
        createLineChart('waterPercentageChart', '% Água Corporal', chartData.water_perc, '% Água (%)', 0, undefined, 'rgb(153, 102, 255)'); // Roxo

        // Grupo 3: Massas (kg)
        createLineChart('fatMassKgChart', 'Massa Gorda (kg)', chartData.fat_kg, 'Massa (kg)', 0, undefined, 'rgb(255, 205, 86)'); // Amarelo
        createLineChart('leanMassKgChart', 'Massa Magra (kg)', chartData.lean_kg, 'Massa (kg)', 0, undefined, 'rgb(100, 220, 150)'); // Verde Claro
        createLineChart('muscleMassChart', 'Massa Muscular (kg)', chartData.muscle_kg, 'Massa (kg)', 0, undefined, 'rgb(201, 203, 207)'); // Cinza
        createLineChart('boneMassKgChart', 'Massa Óssea (kg)', chartData.bone_kg, 'Massa (kg)', 0, undefined, 'rgb(0, 100, 150)'); // Azul Escuro


    } else if (chartData && chartData.labels && chartData.labels.length < 2) { // Se tem dados, mas menos de 2 avaliações
         // Remove todos os cards de gráfico de linha se não houver dados suficientes
         const lineChartCards = document.querySelectorAll('.card h5 i.fa-chart-area');
         lineChartCards.forEach(icon => {
             const card = icon.closest('.card');
             if (card) {
                 card.style.display = 'none';
             }
         });
         // A mensagem de "dados insuficientes" já é tratada pelo PHP ($page_error)
         // e o gauge da idade metabólica é tratado separadamente acima.
    }
});
</script>

<?php
include 'footer.php';
?>
