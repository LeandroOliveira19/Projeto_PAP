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

// Inclui o cabeçalho da página (que agora linka dashboard_cliente.css)
include 'header.php'; // header.php deve ter <link rel="stylesheet" href="assets/css/dashboard_cliente.css">

// Pega o nome do usuário da sessão
$client_name = $_SESSION['name'] ?? 'Cliente'; // Usa 'Cliente' como fallback

// Define qual link da sidebar deve estar ativo
$active_page = basename($_SERVER['PHP_SELF']); // Pega o nome do arquivo atual (ex: dashboard.php)

?>

<!-- A classe dashboard-body já está no <body> incluído pelo header.php -->

<?php include 'sidebar_client.php'; ?>

<!-- Conteúdo Principal (Sem alterações) -->
<main class="main-content">
    <h2>Bem-vindo, <?= htmlspecialchars($client_name) ?>!</h2>

    <div class="row">
        <!-- Card Plano de Treino -->
        <div class="col-xl-4 col-lg-6 col-md-6">
            <div class="dashboard-card">
                <h4><i class="fas fa-dumbbell card-icon"></i>Plano de Treino</h4>
                <p>Acesse seu plano de treino personalizado, veja os exercícios e acompanhe seu progresso.</p>
                <a href="workout_hub.php" class="btn btn-primary">Ver Plano</a>
            </div>
        </div>
        <!-- Card Nutrição -->
        <div class="col-xl-4 col-lg-6 col-md-6">
            <div class="dashboard-card">
                <h4><i class="fas fa-utensils card-icon"></i>Nutrição</h4>
                <p>Consulte seu plano alimentar, recomendações nutricionais e dicas saudáveis.</p>
                <a href="nutrition_hub.php" class="btn btn-primary">Aceder Nutrição</a>
            </div>
        </div>
        <!-- Card Aulas/Marcações -->
        <div class="col-xl-4 col-lg-6 col-md-6">
            <div class="dashboard-card">
                <h4><i class="fas fa-calendar-check card-icon"></i>Aulas</h4>
                <p>Veja as aulas disponíveis, horários e inscreva-se nas suas preferidas online.</p>
                <a href="classes.php" class="btn btn-primary">Ver Aulas</a>
            </div>
        </div>
        <!-- Card Perfil -->
         <div class="col-xl-4 col-lg-6 col-md-6">
            <div class="dashboard-card">
                <h4><i class="fas fa-user-edit card-icon"></i>Meu Perfil</h4>
                <p>Atualize suas informações pessoais, preferências e dados de contato.</p>
                <a href="profile.php" class="btn btn-primary">Editar Perfil</a>
            </div>
        </div>
         <!-- Card Chat -->
         <div class="col-xl-4 col-lg-6 col-md-6">
            <div class="dashboard-card">
                <h4><i class="fas fa-comments card-icon"></i>Chat Direto</h4>
                <p>Fale com seu treinador ou nutricionista para tirar dúvidas rapidamente.</p>
                <a href="chat.php" class="btn btn-primary">Abrir Chat</a>
            </div>
        </div>
         <!-- Card Histórico (Exemplo) -->
         <div class="col-xl-4 col-lg-6 col-md-6">
            <div class="dashboard-card">
                <h4><i class="fas fa-history card-icon"></i>Histórico</h4>
                <p>Reveja seus planos de treino e nutrição anteriores.</p>
                <a href="workout_plan_history.php" class="btn btn-primary">Ver Histórico</a>
            </div>
        </div>
    </div> <!-- Fim da .row -->
</main> <!-- Fim .main-content -->

<?php
// Inclui o rodapé da página
include 'footer.php';
?>
