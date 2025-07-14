<?php
// O erro "Failed to open stream" acontece porque o caminho relativo '../fpdf/fpdf.php'
// é interpretado a partir do script que o chama (generate_workout_pdf.php), e não a partir deste ficheiro.
// A solução é usar um caminho absoluto baseado na localização deste ficheiro (__DIR__).
require_once __DIR__ . '/../fpdf/fpdf.php';

class WorkoutPDF extends FPDF
{
    // Adicionar um construtor para garantir que o construtor da classe pai (FPDF) é chamado.
    function __construct($orientation = 'P', $unit = 'mm', $size = 'A4')
    {
        parent::__construct($orientation, $unit, $size);
    }
    // Cabeçalho da Página
    function Header()
    {
        // Opcional: Adicionar um logo
        // $this->Image('path/to/logo.png', 10, 6, 30);
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(80); // Mover para a direita
        $this->Cell(30, 10, 'MyWellness - Plano de Treino', 0, 0, 'C');
        $this->Ln(20); // Quebra de linha
    }

    // Rodapé da Página
    function Footer()
    {
        $this->SetY(-15); // Posição a 1.5 cm do fundo
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    // Título de um capítulo/secção
    function ChapterTitle($label)
    {
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor(220, 230, 240); // Cor de fundo azul claro
        $this->Cell(0, 7, $this->DecodeText($label), 0, 1, 'L', true);
        $this->Ln(4);
    }

    // Corpo de um capítulo/secção
    function ChapterBody($txt)
    {
        $this->SetFont('Times', '', 12);
        $this->MultiCell(0, 5, $this->DecodeText($txt));
        $this->Ln();
    }

    // Função para imprimir as ações de um dia a partir do JSON
    function PrintActions($json_details)
    {
        $actions = json_decode($json_details, true);
        if (!is_array($actions) || empty($actions)) {
            $this->SetFont('Arial', 'I', 10);
            $this->Cell(0, 5, $this->DecodeText('Nenhuma ação detalhada para este dia.'), 0, 1);
            $this->Ln(2);
            return;
        }

        $this->SetFont('Arial', '', 10);
        foreach ($actions as $action) {
            $line = '';
            switch ($action['type']) {
                case 'sets_reps':
                    $line = "Sets x Reps: " . ($action['data']['sets'] ?? '') . " x " . ($action['data']['reps'] ?? '');
                    if (!empty($action['data']['notes'])) {
                        $line .= " (" . $action['data']['notes'] . ")";
                    }
                    break;
                case 'rest_time':
                    $line = "Descanso: " . ($action['data']['time'] ?? '') . " " . ($action['data']['unit'] ?? '');
                    break;
                case 'tempo':
                    $line = "Tempo: " . ($action['data']['tempo'] ?? '');
                    break;
                case 'rpe':
                    $line = "RPE: " . ($action['data']['value'] ?? '');
                     if (!empty($action['data']['notes'])) {
                        $line .= " (" . $action['data']['notes'] . ")";
                    }
                    break;
                case 'note':
                    $line = "Nota: " . ($action['data']['text'] ?? '');
                    break;
                case 'cardio':
                     $line = "Cardio: " . ($action['data']['type'] ?? '') . " (" . ($action['data']['duration'] ?? '') . " min)";
                     break;
                default:
                    $line = "Acao desconhecida";
                    break;
            }
            // Imprime a linha com um marcador de bullet point
            $this->MultiCell(0, 5, chr(149) . " " . $this->DecodeText($line));
        }
        $this->Ln(4);
    }
    
    // Função auxiliar para converter texto UTF-8 para o formato que o FPDF espera
    function DecodeText($txt) {
        return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $txt);
    }
}
