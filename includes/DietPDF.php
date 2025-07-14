<?php
// O erro "Failed to open stream" acontece porque o caminho para o ficheiro fpdf.php está incorreto.
// A solução é usar um caminho absoluto baseado na localização deste ficheiro (__DIR__),
// garantindo que o caminho para fpdf.php está sempre correto.
// A estrutura de pastas esperada é: /Projeto_PAP/fpdf/fpdf.php
require_once __DIR__ . '/../fpdf/fpdf.php';

class DietPDF extends FPDF
{
    // Cabeçalho da página
    function Header()
    {
        $logoPath = 'assets/img/logo.png'; // Caminho relativo à raiz do projeto
        // Tenta encontrar o logo. Se não encontrar, ignora.
        if (file_exists($logoPath)) {
            $this->Image($logoPath, 10, 6, 30);
        }
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(80); // Mover para a direita
        $this->Cell(30, 10, $this->DecodeText('Plano Alimentar'), 0, 0, 'C');
        $this->Ln(20); // Quebra de linha
    }

    // Rodapé da página
    function Footer()
    {
        $this->SetY(-15); // Posição a 1.5 cm do fundo
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, $this->DecodeText('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    // Título de um capítulo
    function ChapterTitle($title)
    {
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor(220, 240, 220); // Verde claro
        $this->Cell(0, 6, $this->DecodeText($title), 0, 1, 'L', true);
        $this->Ln(4);
    }

    // Função para imprimir as refeições diárias
    function PrintDailyMeals($daily_meals_data)
    {
        if (empty($daily_meals_data)) {
            $this->SetFont('Arial', 'I', 10);
            $this->Cell(0, 7, $this->DecodeText('Nenhum detalhe diário encontrado para este plano.'), 0, 1);
            return;
        }

        foreach ($daily_meals_data as $day_name => $meals_in_day) {
            // Verifica se precisa de uma nova página
            if ($this->GetY() > 240) {
                $this->AddPage();
            }

            $this->SetFont('Arial', 'B', 11);
            $this->SetFillColor(240, 240, 240);
            $this->Cell(0, 7, $this->DecodeText($day_name), 1, 1, 'L', true);
            $this->Ln(2);

            if (empty($meals_in_day)) {
                $this->SetFont('Arial', 'I', 10);
                $this->Cell(0, 7, $this->DecodeText('Nenhuma refeição definida para este dia.'), 0, 1);
                $this->Ln(4);
                continue;
            }

            foreach ($meals_in_day as $meal_data) {
                $this->SetFont('Arial', 'BI', 10); // Negrito e Itálico para o nome da refeição
                $this->Cell(0, 6, $this->DecodeText($meal_data['name']), 0, 1);

                $this->SetFont('Arial', '', 10);
                foreach ($meal_data['foods'] as $food) {
                    $food_line = "- " . number_format((float)$food['quantity'], 1, ',', '.') . " " . $food['unit'] . " de " . $food['name'];
                    if (!empty($food['notes'])) {
                        $food_line .= " (" . $food['notes'] . ")";
                    }
                    $this->MultiCell(0, 5, $this->DecodeText($food_line));
                }
                $this->Ln(3);
            }
        }
    }

    // Função auxiliar para converter texto UTF-8 para o formato do PDF
    function DecodeText($text) {
        return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
    }
}
