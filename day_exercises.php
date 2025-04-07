<?php
session_start();

// Verifica se o usuário está logado e se é um cliente
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

// Inclui a conexão com o banco de dados
include 'db.php';

// Inclui o cabeçalho da página
include 'header.php';

// Verifica se o dia foi fornecido
if (!isset($_GET['day_name']) || !isset($_GET['workout_plan_id'])) {
    echo "<script>alert('Dia ou plano de treino não especificado.'); window.location.href = 'workout_hub.php';</script>";
    exit;
}

$day_name = $_GET['day_name'];
$workout_plan_id = $_GET['workout_plan_id'];

// Busca o ID da opção do dia (day_option_id) e o nome do dia
$stmt = $pdo->prepare("
    SELECT do.id AS day_option_id, do.name AS day_option_name
    FROM workout_plan_days wpd
    JOIN day_options do ON wpd.day_option_id = do.id
    WHERE wpd.workout_plan_id = ? AND wpd.day_name = ?
");
$stmt->execute([$workout_plan_id, $day_name]);
$day = $stmt->fetch();

if (!$day) {
    echo "<script>alert('Detalhes do dia não encontrados.'); window.location.href = 'workout_hub.php';</script>";
    exit;
}

// Busca os exercícios correspondentes ao dia selecionado
$stmt = $pdo->prepare("
    SELECT e.name, e.description, e.image, e.video_url
    FROM workout_plan_exercises wpe
    JOIN exercises e ON wpe.exercise_id = e.id
    WHERE wpe.workout_plan_id = ? AND wpe.exercise_id IN (
        SELECT e.id
        FROM exercises e
        WHERE e.muscle_group = ?
    )
");
$stmt->execute([$workout_plan_id, $day['day_option_name']]);
$exercises = $stmt->fetchAll();
?>

<div class="container mt-5">
    <h2>Exercícios para <?= htmlspecialchars($day_name) ?></h2>
    <p><strong>Dia:</strong> <?= htmlspecialchars($day['day_option_name']) ?></p>

    <?php if (!empty($exercises)): ?>
        <ul class="list-group">
            <?php foreach ($exercises as $exercise): ?>
                <li class="list-group-item d-flex align-items-center">
                    <div class='me-3' style='width: 90px; height: 90px; overflow: hidden; border-radius: 5px;'>
                        <?php if ($exercise['image'] && file_exists("assets/img/exercises/" . $exercise['image'])): ?>
                            <img src='assets/img/exercises/<?= htmlspecialchars($exercise['image']) ?>' alt='<?= htmlspecialchars($exercise['name']) ?>' class='img-fluid h-110 w-110 object-fit-cover' style='object-position: center;'>
                        <?php else: ?>
                            <img src='assets/img/exercises/default.jpg' alt='Default Exercise Image' class='img-fluid h-100 w-100 object-fit-cover' style='object-position: center;'>
                        <?php endif; ?>
                    </div>
                    <div>
                        <strong><?= htmlspecialchars($exercise['name']) ?></strong>
                        <p><?= htmlspecialchars($exercise['description']) ?></p>
                    </div>
                    <?php if ($exercise['video_url']): ?>
                        <a href='<?= htmlspecialchars($exercise['video_url']) ?>' target='_blank' class='btn btn-sm btn-outline-primary ms-auto'>Ver Vídeo</a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Nenhum exercício encontrado para este dia.</p>
    <?php endif; ?>
    <a href="workout_hub.php" class="btn btn-secondary mt-3">Voltar</a>
</div>

<?php
// Inclui o rodapé da página
include 'footer.php';
?>