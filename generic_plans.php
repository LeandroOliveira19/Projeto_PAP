<?php
session_start();

// Verificar se o user é admin, trainer ou client
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

include 'db.php';
include 'header.php';

// Busca todos os planos genéricos para mostrar
$stmt = $pdo->query("SELECT * FROM generic_workout_plans");
$generic_plans = $stmt->fetchAll();
?>

<div class="container mt-5">
    <h2>Planos Genéricos</h2>

    <!-- Lista de Planos Genéricos -->
    <h3 class="mt-4">Lista de Planos Genéricos</h3>
    <?php if (count($generic_plans) > 0): ?>
        <?php foreach ($generic_plans as $plan): ?>
            <div class="mb-5">
                <h4><?= htmlspecialchars($plan['name']) ?></h4>
                <?php
                // Dividir a descrição por dia
                $days = explode("Dia ", $plan['description']);
                // Remover o primeiro elemento vazio, pois é antes do primeiro "Dia"
                array_shift($days);

                // Exibe os dias divididos
                foreach ($days as $day) {
                    // Dividir cada dia em linhas
                    $lines = explode("\n", $day);
                    ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <?php
                            $first = true;
                            foreach ($lines as $line) {
                                if ($first) {
                                    $first = false;
                                    echo "<h5 class='card-title'>Dia " . htmlspecialchars(trim($line)) . "</h5>";
                                }
                                else{
                                    echo "<p class='card-text'>" . htmlspecialchars(trim($line)) . "</p>";
                                }
                            }
                            ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Não existem planos genéricos</p>
    <?php endif; ?>
    <a href="dashboard.php" class="btn btn-secondary mt-3">Voltar</a>
</div>

<?php include 'footer.php'; ?>
