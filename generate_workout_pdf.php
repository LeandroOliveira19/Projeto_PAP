<?php
session_start();

// 1. Verificações de Segurança
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    die("Acesso negado. Por favor, faca login.");
}

if (!isset($_GET['plan_id']) || !filter_var($_GET['plan_id'], FILTER_VALIDATE_INT)) {
    die("ID do plano invalido.");
}

include 'db.php';
require 'includes/WorkoutPDF.php'; // Inclui a classe PDF personalizada

$user_id = $_SESSION['user_id'];
$workout_plan_id = (int)$_GET['plan_id'];
$client_id = null;
$workout_plan = null;
$workout_plan_days = [];
$selected_exercises = [];

try {
    // Verifica se este plano pertence ao utilizador logado e está ativo
    $stmt_verify = $pdo->prepare("
        SELECT c.id 
        FROM clients c
        JOIN workout_plans wp ON c.id = wp.client_id
        WHERE c.user_id = :user_id AND wp.id = :plan_id AND wp.is_active = 1
    ");
    $stmt_verify->execute(['user_id' => $user_id, 'plan_id' => $workout_plan_id]);
    $client_id = $stmt_verify->fetchColumn();

    if (!$client_id) {
        die("Acesso negado. Este plano nao pertence a voce ou nao esta ativo.");
    }

    // 2. Busca todos os dados necessários para o PDF
    $stmt_plan = $pdo->prepare("
        SELECT wp.plan_details, wp.created_at, u_trainer.name as trainer_name, u_client.name as client_name
        FROM workout_plans wp
        JOIN users u_trainer ON wp.trainer_id = u_trainer.id
        JOIN clients c ON wp.client_id = c.id
        JOIN users u_client ON c.user_id = u_client.id
        WHERE wp.id = :plan_id
    ");
    $stmt_plan->execute(['plan_id' => $workout_plan_id]);
    $workout_plan = $stmt_plan->fetch();

    $stmt_days = $pdo->prepare("
        SELECT wpd.day_name, wpd.day_details, do.name AS day_option_name
        FROM workout_plan_days wpd
        LEFT JOIN day_options do ON wpd.day_option_id = do.id
        WHERE wpd.workout_plan_id = ?
        ORDER BY FIELD(wpd.day_name, 'Dia 1', 'Dia 2', 'Dia 3', 'Dia 4', 'Dia 5', 'Dia 6', 'Dia 7')
    ");
    $stmt_days->execute([$workout_plan_id]);
    $workout_plan_days = $stmt_days->fetchAll();

    $stmt_exercises = $pdo->prepare("
        SELECT e.name, e.description, e.muscle_group, e.equipment
        FROM workout_plan_exercises wpe
        JOIN exercises e ON wpe.exercise_id = e.id
        WHERE wpe.workout_plan_id = ?
        ORDER BY e.muscle_group ASC, e.name ASC
    ");
    $stmt_exercises->execute([$workout_plan_id]);
    $selected_exercises = $stmt_exercises->fetchAll();

} catch (PDOException $e) {
    die("Erro ao buscar dados do plano: " . $e->getMessage());
}

// 3. Gerar o PDF
$pdf = new WorkoutPDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// --- Secção: Informações Gerais ---
$pdf->ChapterTitle('Informacoes Gerais do Plano');
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(40, 7, $pdf->DecodeText('Cliente:'));
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 7, $pdf->DecodeText($workout_plan['client_name']));
$pdf->Ln();

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(40, 7, $pdf->DecodeText('Treinador:'));
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 7, $pdf->DecodeText($workout_plan['trainer_name']));
$pdf->Ln();

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(40, 7, $pdf->DecodeText('Data de Criação:'));
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 7, date('d/m/Y', strtotime($workout_plan['created_at'])));
$pdf->Ln();

if (!empty($workout_plan['plan_details'])) {
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 7, $pdf->DecodeText('Observações Gerais:'), 0, 1);
    $pdf->SetFont('Arial', '', 11);
    $pdf->MultiCell(0, 5, $pdf->DecodeText($workout_plan['plan_details']));
}
$pdf->Ln(5);

// --- Secção: Plano Diário ---
$pdf->ChapterTitle('Plano Diario (Acoes e Instrucoes)');
foreach ($workout_plan_days as $day) {
    $pdf->SetFont('Arial', 'B', 11);
    $day_title = $day['day_name'] . ': ' . ($day['day_option_name'] ?: 'Descanso / Outro');
    $pdf->Cell(0, 7, $pdf->DecodeText($day_title), 0, 1, 'L');
    $pdf->PrintActions($day['day_details']);
}
$pdf->Ln(5);

// --- Secção: Lista de Exercícios ---
if (!empty($selected_exercises)) {
    $pdf->AddPage(); // Adiciona uma nova página para a lista de exercícios
    $pdf->ChapterTitle('Lista de Exercicios do Plano');
    $current_group = null;
    foreach ($selected_exercises as $exercise) {
        $muscle_group = $exercise['muscle_group'] ?: 'Outros';
        if ($current_group !== $muscle_group) {
            if ($current_group !== null) $pdf->Ln(4);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell(0, 6, $pdf->DecodeText($muscle_group), 0, 1, 'L', true);
            $current_group = $muscle_group;
        }
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, $pdf->DecodeText($exercise['name']), 0, 1);
        $pdf->SetFont('Arial', '', 10);
        $pdf->MultiCell(0, 5, $pdf->DecodeText($exercise['description']));
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->Cell(0, 5, $pdf->DecodeText('Equipamento: ' . $exercise['equipment']), 0, 1);
        $pdf->Ln(2);
    }
}

// 4. Enviar o PDF para o browser
$pdf->Output('D', 'plano_de_treino_mywellness.pdf');

