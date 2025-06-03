<?php
// filepath: c:\xampp\htdocs\Projeto_PAP\mood_history.php
session_start();

// Verifica se o usuário está logado e se é um cliente
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

include 'db.php';
include 'header.php';

$user_id = $_SESSION['user_id'];
$client_name = $_SESSION['name'] ?? 'Cliente';
$page_error = '';
$mood_data_for_chart = 'null'; // JSON para o gráfico

// Define a página ativa para a sidebar
$active_page = basename($_SERVER['PHP_SELF']);
include 'sidebar_client.php';

// Buscar histórico de humor dos últimos 30 dias (ou outro período)
try {
    // Por defeito, últimos 30 dias. Pode adicionar filtros de data no futuro.
    $stmt = $pdo->prepare("
        SELECT mood_value, assessment_date
        FROM mood_logs
        WHERE user_id = :user_id
        ORDER BY assessment_date ASC
    "); // Busca todos, o JS pode filtrar por período se necessário, ou limitar aqui
    $stmt->execute(['user_id' => $user_id]);
    $mood_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($mood_logs) {
        $labels = [];
        $data_points = [];
        $point_colors = []; // Cores para os pontos

        $mood_colors = [
            1 => 'rgba(220, 53, 69, 1)',   // Vermelho (Péssimo)
            2 => 'rgba(255, 124, 100, 1)', // Laranja avermelhado (Mau)
            3 => 'rgba(255, 193, 7, 1)',   // Amarelo (Neutro)
            4 => 'rgba(100, 220, 150, 1)', // Verde claro (Bom)
            5 => 'rgba(25, 135, 84, 1)'    // Verde escuro (Ótimo)
        ];
        $mood_emojis = [1 => '😞', 2 => '🙁', 3 => '😐', 4 => '😊', 5 => '😄'];


        foreach ($mood_logs as $log) {
            $labels[] = date('d/m', strtotime($log['assessment_date'])); // Formato DD/MM
            $data_points[] = (int)$log['mood_value'];
            $point_colors[] = $mood_colors[(int)$log['mood_value']] ?? 'rgba(128, 128, 128, 1)'; // Cinza para default
        }

        $mood_data_for_chart = json_encode([
            'labels' => $labels,
            'data' => $data_points,
            'point_colors' => $point_colors,
            'mood_emojis' => $mood_emojis // Passa emojis para o tooltip
        ]);
    } else {
        $page_error = "Nenhum registo de humor encontrado.";
    }

} catch (PDOException $e) {
    $page_error = "Erro ao carregar histórico de humor: " . $e->getMessage();
    error_log($page_error);
}
?>

<main class="main-content">
    <div class="container mt-5">
        <h2><i class="fas fa-chart-line me-2"></i>Meu Histórico de Humor</h2>
        <p class="text-white">Acompanhe como se tem sentido ao longo do tempo.</p>

        <?php if (!empty($page_error) && $mood_data_for_chart === 'null'): ?>
            <div class="alert alert-info"><?= htmlspecialchars($page_error) ?></div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <?php if ($mood_data_for_chart !== 'null'): ?>
                    <div style="height: 400px;">
                        <canvas id="moodChart"></canvas>
                    </div>
                <?php elseif (empty($page_error)): // Se não há dados mas também não houve erro de BD ?>
                    <p class="text-muted text-center">Ainda não há registos de humor suficientes para exibir um gráfico.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar ao Dashboard</a>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const moodChartData = <?= $mood_data_for_chart ?>;
    const moodChartCtx = document.getElementById('moodChart')?.getContext('2d');

    if (moodChartCtx && moodChartData && moodChartData.labels && moodChartData.labels.length > 0) {
        new Chart(moodChartCtx, {
            type: 'line',
            data: {
                labels: moodChartData.labels,
                datasets: [{
                    label: 'Nível de Humor',
                    data: moodChartData.data,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    pointBackgroundColor: moodChartData.point_colors, // Cores dos pontos
                    pointBorderColor: moodChartData.point_colors,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    tension: 0.1,
                    fill: false // Pode mudar para true se quiser área preenchida
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 0.5, // Para dar espaço abaixo do 1
                        max: 5.5, // Para dar espaço acima do 5
                        ticks: {
                            stepSize: 1,
                            color: document.body.classList.contains('dark-mode') ? 'rgba(255,255,255,0.7)' : 'rgba(0,0,0,0.7)',
                            callback: function(value, index, values) {
                                // Mapear valor numérico para emoji ou texto
                                const emojiMap = moodChartData.mood_emojis || {1: '😞', 2: '🙁', 3: '😐', 4: '😊', 5: '😄'};
                                return emojiMap[value] || value;
                            }
                        },
                        grid: {
                            color: document.body.classList.contains('dark-mode') ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: document.body.classList.contains('dark-mode') ? 'rgba(255,255,255,0.7)' : 'rgba(0,0,0,0.7)'
                        },
                        grid: {
                            color: document.body.classList.contains('dark-mode') ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false // Legenda não é tão útil com um único dataset e Y-axis customizado
                    },
                    tooltip: {
                        backgroundColor: document.body.classList.contains('dark-mode') ? 'rgba(0,0,0,0.8)' : 'rgba(255,255,255,0.9)',
                        titleColor: document.body.classList.contains('dark-mode') ? '#fff' : '#000',
                        bodyColor: document.body.classList.contains('dark-mode') ? '#fff' : '#000',
                        borderColor: document.body.classList.contains('dark-mode') ? 'rgba(255,255,255,0.2)' : 'rgba(0,0,0,0.2)',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                const moodValue = context.parsed.y;
                                const emojiMap = moodChartData.mood_emojis || {1: '😞', 2: '🙁', 3: '😐', 4: '😊', 5: '😄'};
                                const moodDescription = {1: 'Péssimo', 2: 'Mau', 3: 'Neutro', 4: 'Bom', 5: 'Ótimo'};

                                if (moodValue !== null) {
                                    label += `${emojiMap[moodValue] || ''} ${moodDescription[moodValue] || moodValue}`;
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php include 'footer.php'; ?>
