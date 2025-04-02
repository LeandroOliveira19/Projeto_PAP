<?php
// Inicia a sessão
session_start();

// Verifica se o usuário está logado e se é um cliente
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php'); // Redireciona para a página de login se não estiver logado
    exit;
}

// Inclui a conexão com o banco de dados
include 'db.php';

// Inclui o cabeçalho da página
include 'header.php';
?>

<div class="container mt-5">
    <h2>Bem-vindo, Cliente!</h2>
    <div class="row">
        <!-- Plano de Treino -->
        <div class="col-md-6">
            <h4>Plano de Treino</h4>
            <a href="workout_hub.php" class="btn btn-primary">Ver Plano de Treino</a>
        </div>

        <!-- Nutrição -->
        <div class="col-md-6">
            <h4>Nutrição</h4>
            <a href="nutrition_hub.php" class="btn btn-primary">Aceder à Nutrição</a>
        </div>

        <!-- Marcações -->
        <div class="col-md-6">
            <h4>Marcações</h4>
            <a href="classes.php" class="btn btn-primary">Aulas</a>
        </div>
    </div>
</div>

<?php
// Inclui o rodapé da página
include 'footer.php';
?>
