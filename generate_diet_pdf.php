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
require 'includes/DietPDF.php'; // Inclui a nova classe PDF personalizada

$user_id = $_SESSION['user_id'];
$diet_plan_id = (int)$_GET['plan_id'];
$client_id = null;
$diet_plan_data = null;
$daily_meals_data = [];

try {
    // Verifica se este plano pertence ao utilizador logado e está ativo
    $stmt_verify = $pdo->prepare("
        SELECT c.id 
        FROM clients c
        JOIN diet_plans dp ON c.id = dp.client_id
        WHERE c.user_id = :user_id AND dp.id = :plan_id AND dp.is_active = 1
    ");
    $stmt_verify->execute(['user_id' => $user_id, 'plan_id' => $diet_plan_id]);
    $client_id = $stmt_verify->fetchColumn();

    if (!$client_id) {
        die("Acesso negado. Este plano nao pertence a voce ou nao esta ativo.");
    }

    // 2. Busca todos os dados necessários para o PDF
    $stmt_plan = $pdo->prepare("
        SELECT dp.title, dp.plan_details, dp.created_at, u_nutri.name as nutritionist_name, u_client.name as client_name
        FROM diet_plans dp
        JOIN users u_nutri ON dp.nutritionist_id = u_nutri.id
        JOIN clients c ON dp.client_id = c.id
        JOIN users u_client ON c.user_id = u_client.id
        WHERE dp.id = :plan_id
    ");
    $stmt_plan->execute(['plan_id' => $diet_plan_id]);
    $diet_plan_data = $stmt_plan->fetch();

    // Busca os detalhes das refeições e alimentos para este plano
    $stmt_meals = $pdo->prepare("
        SELECT dpm.meal_name, dpm.meal_order, dpmf.quantity, dpmf.notes, f.name AS food_name, f.unit
        FROM diet_plan_meals dpm
        JOIN diet_plan_meal_foods dpmf ON dpm.id = dpmf.diet_plan_meal_id
        JOIN foods f ON dpmf.food_id = f.id
        WHERE dpm.diet_plan_id = ?
        ORDER BY dpm.meal_order ASC, dpmf.id ASC
    ");
    $stmt_meals->execute([$diet_plan_id]);
    $meals_data_raw = $stmt_meals->fetchAll(PDO::FETCH_ASSOC);

    // Organiza os dados brutos (função copiada de nutrition_hub.php)
    function organizeMealData($raw_data) {
        $organized = [];
        foreach ($raw_data as $item) {
            $parts = explode(' - ', $item['meal_name'], 2);
            $day_name = $parts[0] ?? 'Dia Desconhecido';
            $meal_name = $parts[1] ?? 'Refeição Desconhecida';
            if (!isset($organized[$day_name])) $organized[$day_name] = [];
            if (!isset($organized[$day_name][$meal_name])) $organized[$day_name][$meal_name] = ['name' => $meal_name, 'order' => $item['meal_order'], 'foods' => []];
            $organized[$day_name][$meal_name]['foods'][] = ['name' => $item['food_name'], 'quantity' => $item['quantity'], 'unit' => $item['unit'], 'notes' => $item['notes']];
        }
        uksort($organized, fn($a, $b) => (int)filter_var($a, FILTER_SANITIZE_NUMBER_INT) <=> (int)filter_var($b, FILTER_SANITIZE_NUMBER_INT));
        foreach ($organized as &$day_meals) { uasort($day_meals, fn($a, $b) => $a['order'] <=> $b['order']); }
        return $organized;
    }
    $daily_meals_data = organizeMealData($meals_data_raw);

} catch (PDOException $e) {
    die("Erro ao buscar dados do plano: " . $e->getMessage());
}

// 3. Gerar o PDF
$pdf = new DietPDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// --- Secção: Informações Gerais ---
$pdf->ChapterTitle('Informacoes Gerais do Plano Alimentar');
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(40, 7, $pdf->DecodeText('Cliente:'));
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 7, $pdf->DecodeText($diet_plan_data['client_name']));
$pdf->Ln();

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(40, 7, $pdf->DecodeText('Nutricionista:'));
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 7, $pdf->DecodeText($diet_plan_data['nutritionist_name']));
$pdf->Ln();

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(40, 7, $pdf->DecodeText('Data de Criação:'));
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 7, date('d/m/Y', strtotime($diet_plan_data['created_at'])));
$pdf->Ln();

if (!empty($diet_plan_data['plan_details'])) {
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 7, $pdf->DecodeText('Observações Gerais:'), 0, 1);
    $pdf->SetFont('Arial', '', 11);
    $pdf->MultiCell(0, 5, $pdf->DecodeText($diet_plan_data['plan_details']));
}
$pdf->Ln(5);

// --- Secção: Plano Diário ---
$pdf->ChapterTitle('Plano Alimentar Diario');
$pdf->PrintDailyMeals($daily_meals_data);

// 4. Enviar o PDF para o browser
$pdf->Output('D', 'plano_alimentar_mywellness.pdf');

