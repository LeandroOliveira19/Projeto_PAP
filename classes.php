<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redireciona para a página de login se não estiver logado
    exit;
}

// Inclui a conexão com o banco de dados
include 'db.php';

// Inclui o cabeçalho da página
include 'header.php';
?>

<div class="container mt-5">
    <h2>Aulas Disponíveis</h2>
    <?php
    // Busca todas as aulas disponíveis
    $stmt = $pdo->query("
        SELECT c.id, c.name, c.description, c.schedule, c.duration, c.max_participants, u.name AS trainer_name 
        FROM classes c 
        JOIN users u ON c.trainer_id = u.id 
        ORDER BY c.schedule
    ");
    $classes = $stmt->fetchAll();

    if ($classes) {
        foreach ($classes as $class) {
            echo "<div class='card mb-3'>";
            echo "<div class='card-body'>";
            echo "<h5 class='card-title'>" . htmlspecialchars($class['name']) . "</h5>";
            echo "<p class='card-text'><strong>Descrição:</strong> " . htmlspecialchars($class['description']) . "</p>";
            echo "<p class='card-text'><strong>Horário:</strong> " . htmlspecialchars($class['schedule']) . "</p>";
            echo "<p class='card-text'><strong>Duração:</strong> " . htmlspecialchars($class['duration']) . " minutos</p>";
            echo "<p class='card-text'><strong>Treinador:</strong> " . htmlspecialchars($class['trainer_name']) . "</p>";
            echo "<p class='card-text'><strong>Vagas disponíveis:</strong> " . htmlspecialchars($class['max_participants']) . "</p>";
            echo "<a href='enroll.php?class_id=" . $class['id'] . "' class='btn btn-primary'>Inscrever-se</a>";
            echo "</div>";
            echo "</div>";
        }
    } else {
        echo "<p>Nenhuma aula disponível no momento.</p>";
    }
    ?>
</div>

<?php
// Inclui o rodapé da página
include 'footer.php';
?>