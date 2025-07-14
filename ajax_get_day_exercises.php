<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    echo json_encode(['status' => 'error', 'message' => 'Acesso não autorizado.']);
    exit;
}

include 'db.php';

if (!isset($_GET['plan_id']) || !isset($_GET['day_name'])) {
    echo json_encode(['status' => 'error', 'message' => 'Dados em falta.']);
    exit;
}

$workout_plan_id = filter_var($_GET['plan_id'], FILTER_VALIDATE_INT);
$day_name = $_GET['day_name'];

if (!$workout_plan_id) {
    echo json_encode(['status' => 'error', 'message' => 'ID do plano inválido.']);
    exit;
}

// Função para normalizar strings (copiada de workout_plan_details.php)
function normalize_string_for_comparison($str) {
    if ($str === null) return '';
    $str = trim((string)$str);
    $str = mb_strtolower($str, 'UTF-8');
    $unwanted_array = ['á'=>'a', 'à'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'ae', 'é'=>'e', 'è'=>'e', 'ê'=>'e', 'ë'=>'e', 'í'=>'i', 'ì'=>'i', 'î'=>'i', 'ï'=>'i', 'ó'=>'o', 'ò'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'œ'=>'oe', 'ú'=>'u', 'ù'=>'u', 'û'=>'u', 'ü'=>'u', 'ý'=>'y', 'ÿ'=>'y', 'ç'=>'c', 'ñ'=>'n'];
    $str = strtr($str, $unwanted_array);
    $portuguese_to_english_map = ['pernas' => 'legs', 'ombros' => 'shoulders', 'peito' => 'chest', 'costas' => 'back', 'braços' => 'arms', 'bicep' => 'biceps', 'tricep' => 'triceps', 'abdominal' => 'core', 'abdominais' => 'core', 'gluteos' => 'glutes', 'glúteos' => 'glutes', 'cardio' => 'cardio', 'full body' => 'full body', 'corpo inteiro' => 'full body', 'descanso' => 'descanso'];
    $str = strtr($str, $portuguese_to_english_map);
    return $str;
}

try {
    // 1. Obter o foco do dia (day_option_name)
    $stmt_day = $pdo->prepare("SELECT do.name AS day_option_name FROM workout_plan_days wpd LEFT JOIN day_options do ON wpd.day_option_id = do.id WHERE wpd.workout_plan_id = ? AND wpd.day_name = ?");
    $stmt_day->execute([$workout_plan_id, $day_name]);
    $day_option_name_original = $stmt_day->fetchColumn();

    if (!$day_option_name_original) {
        echo json_encode(['status' => 'success', 'exercises' => []]); // Dia sem foco definido
        exit;
    }

    // 2. Obter todos os exercícios do plano
    $stmt_all_exercises = $pdo->prepare("SELECT e.id, e.name, e.muscle_group FROM workout_plan_exercises wpe JOIN exercises e ON wpe.exercise_id = e.id WHERE wpe.workout_plan_id = ?");
    $stmt_all_exercises->execute([$workout_plan_id]);
    $all_plan_exercises = $stmt_all_exercises->fetchAll(PDO::FETCH_ASSOC);

    // 3. Filtrar exercícios com base no foco do dia (lógica de workout_plan_details.php)
    $day_option_name_normalized = normalize_string_for_comparison($day_option_name_original);
    $exercises_for_day = [];

    if ($day_option_name_normalized === 'full body') {
        $exercises_for_day = $all_plan_exercises;
    } elseif ($day_option_name_normalized !== 'descanso') {
        $focus_parts = preg_split('/(\s+e\s+|\s*&\s*|\s*\/\s*)/i', $day_option_name_original);
        $normalized_focus_parts = array_map('normalize_string_for_comparison', $focus_parts);
        $normalized_focus_parts = array_unique(array_filter($normalized_focus_parts));

        foreach ($all_plan_exercises as $exercise) {
            $exercise_muscle_group_normalized = normalize_string_for_comparison($exercise['muscle_group']);
            if (in_array($exercise_muscle_group_normalized, $normalized_focus_parts)) {
                $exercises_for_day[] = $exercise;
            }
        }
    }

    echo json_encode(['status' => 'success', 'exercises' => $exercises_for_day]);

} catch (PDOException $e) {
    error_log("Erro em ajax_get_day_exercises.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Erro de base de dados.']);
}
?>

