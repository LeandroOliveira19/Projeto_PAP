<?php
// filepath: c:\xampp\htdocs\Projeto_PAP\ficha_pessoal.php
session_start();

// Verifica se o usuário está logado e se é um cliente
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

include 'db.php';
include 'header.php';

$user_id = $_SESSION['user_id'];
$page_error = '';
$client_data = []; // Array que conterá todos os dados (user, client, latest_assessment)

// Define a página ativa para a sidebar
$active_page = basename($_SERVER['PHP_SELF']);

// --- Fetch All Client Data (Including Latest Assessment) ---
try {
    // Query para buscar dados do user, dados básicos do client, e a ÚLTIMA avaliação completa
    // Esta query busca especificamente a avaliação mais recente (ORDER BY ... LIMIT 1)
    $stmt = $pdo->prepare("
        SELECT
            u.name, u.email, u.profile_picture, u.numero_telemovel, -- User data
            c.id as client_table_id, c.activity_level, -- client_table_id é o ID da tabela clients
            c.profession, c.gym_frequency, c.health_issues, -- Outros campos do cliente (membership_number foi removido)
            latest_assessment.* -- Seleciona todas as colunas da subquery (última avaliação)
        FROM users u
        JOIN clients c ON u.id = c.user_id
        LEFT JOIN (
            -- Subquery para encontrar a avaliação mais recente deste cliente
            SELECT ha.*
            FROM history_assessments ha
            WHERE ha.client_id = (SELECT id FROM clients WHERE user_id = :user_id_for_subquery LIMIT 1) -- Encontra client_id baseado no user_id
            ORDER BY ha.assessment_date DESC, ha.id DESC -- Ordena pela data mais recente, depois ID
            LIMIT 1 -- Pega APENAS a mais recente
        ) AS latest_assessment ON c.id = latest_assessment.client_id -- Condição de JOIN
        WHERE u.id = :user_id;
    ");
    $stmt->execute(['user_id_for_subquery' => $user_id, 'user_id' => $user_id]);
    $client_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client_data) {
        throw new Exception("Dados do utilizador não encontrados.");
    }
    // Se não houver avaliação (latest_assessment.* serão NULL), a função displayInfo tratará disso.

} catch (Exception $e) {
    $page_error = 'Erro ao carregar dados da ficha pessoal: ' . $e->getMessage();
    error_log("Error fetching personal record data for user $user_id: " . $e->getMessage());
    $client_data = [];
}

// Helper function to display stats or N/D
// Esta função retorna "N/D" se o valor for null, '', ou não estiver definido.
function displayInfo($value, $unit = '', $decimals = 1, $na_text = '<span class="text-muted fst-italic">N/D</span>') {
    if (isset($value) && $value !== null && $value !== '') {
        if (is_numeric($value) && !empty($unit)) {
             return htmlspecialchars(number_format((float)$value, $decimals, ',', '.')) . $unit;
        }
        return htmlspecialchars($value);
    }
    return $na_text;
}

?>

<?php include 'sidebar_client.php'; ?>


<!-- Conteúdo Principal -->
<main class="main-content">
    <div class="container mt-5">
        <h2><i class="fas fa-address-card me-2"></i>Ficha Pessoal</h2>
        <p class="text-white">Resumo das suas informações pessoais e da sua última avaliação física.</p>

        <?php if (!empty($page_error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($page_error) ?></div>
        <?php elseif (empty($client_data)): ?>
             <div class="alert alert-warning">Não foi possível carregar os dados da ficha pessoal.</div>
        <?php else: ?>

            <!-- Card: Informações Básicas -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>Informações Básicas</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Nome Completo:</dt>
                        <dd class="col-sm-9"><?= displayInfo($client_data['name'] ?? null) ?></dd>

                        <dt class="col-sm-3">Email:</dt>
                        <dd class="col-sm-9"><?= displayInfo($client_data['email'] ?? null) ?></dd>

                        <dt class="col-sm-3">Telemóvel:</dt>
                        <dd class="col-sm-9"><?= displayInfo($client_data['numero_telemovel'] ?? null) ?></dd>

                        <!-- Altura e Peso agora vêm da ÚLTIMA avaliação -->
                        <dt class="col-sm-3">Altura (última):</dt>
                        <dd class="col-sm-9"><?= displayInfo($client_data['height'] ?? null, ' m', 2) ?></dd>

                        <dt class="col-sm-3">Peso (último):</dt>
                        <dd class="col-sm-9"><?= displayInfo($client_data['weight'] ?? null, ' kg', 1) ?></dd>

                        <dt class="col-sm-3">Nível Atividade:</dt>
                        <dd class="col-sm-9"><?= displayInfo($client_data['activity_level'] ?? null) ?></dd>

                        <dt class="col-sm-3">Número de Sócio:</dt>
                        <dd class="col-sm-9"><?= displayInfo($client_data['client_table_id'] ?? null) ?></dd>

                        <dt class="col-sm-3">Profissão:</dt>
                        <dd class="col-sm-9"><?= displayInfo($client_data['profession'] ?? null) ?></dd>

                        <dt class="col-sm-3">Frequência no Ginásio:</dt>
                        <dd class="col-sm-9"><?= displayInfo($client_data['gym_frequency'] ?? null) ?></dd>

                        <dt class="col-sm-3">Problemas de Saúde:</dt>
                        <dd class="col-sm-9"><?= displayInfo($client_data['health_issues'] ?? null) ?></dd>
                    </dl>
                    <small class="d-block text-end text-muted mt-2">Para editar estas informações, vá a <a href="profile.php">Ver/Editar Perfil</a>.</small>
                </div>
            </div>

            <!-- Card: Avaliação Corporal (Usa dados da última avaliação) -->
            <div class="card shadow-sm mb-4">
                 <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-body me-2"></i>Última Avaliação Corporal</h5>
                </div>
                <div class="card-body">
                     <?php if (!empty($client_data['assessment_date'])): ?>
                        <p class="text-muted small mb-3">Data da avaliação: <?= htmlspecialchars(date('d/m/Y', strtotime($client_data['assessment_date']))) ?></p>
                    <?php else: ?>
                        <p class="text-muted small mb-3 fst-italic">Nenhuma avaliação física registada.</p>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Coluna 1 -->
                        <div class="col-md-6 col-lg-4 mb-3">
                            <p class="mb-1"><strong>% Gordura Corporal:</strong></p> <p class="fs-5"><?= displayInfo($client_data['fat_mass_percentage'] ?? null, ' %', 1) ?></p>
                            <p class="mb-1"><strong>Massa Gorda (Kg):</strong></p> <p class="fs-5"><?= displayInfo($client_data['fat_mass_kg'] ?? null, ' kg', 2) ?></p>
                            <p class="mb-1"><strong>% Gordura Subcutânea:</strong></p> <p class="fs-5"><?= displayInfo($client_data['gordura_subcutanea'] ?? null, ' %', 1) ?></p>
                            <p class="mb-1"><strong>Gordura Visceral (Índice):</strong></p> <p class="fs-5"><?= displayInfo($client_data['visceral_fat_index'] ?? null, '', 0) ?></p>
                        </div>
                        <!-- Coluna 2 -->
                        <div class="col-md-6 col-lg-4 mb-3">
                            <p class="mb-1"><strong>Massa Muscular (Kg):</strong></p> <p class="fs-5"><?= displayInfo($client_data['muscle_mass_kg'] ?? null, ' kg', 2) ?></p>
                            <p class="mb-1"><strong>Massa Magra (Kg):</strong></p> <p class="fs-5"><?= displayInfo($client_data['lean_mass_kg'] ?? null, ' kg', 2) ?></p>
                             <p class="mb-1"><strong>Proteínas (Kg):</strong></p> <p class="fs-5"><?= displayInfo($client_data['proteinas'] ?? null, ' kg', 2) ?></p>
                             <p class="mb-1"><strong>Massa Óssea (Kg):</strong></p> <p class="fs-5"><?= displayInfo($client_data['bone_mass_kg'] ?? null, ' kg', 2) ?></p>
                        </div>
                         <!-- Coluna 3 -->
                        <div class="col-md-6 col-lg-4 mb-3">
                            <p class="mb-1"><strong>% Água Corporal:</strong></p> <p class="fs-5"><?= displayInfo($client_data['water_percentage'] ?? null, ' %', 1) ?></p>
                            <p class="mb-1"><strong>Idade Metabólica:</strong></p> <p class="fs-5"><?= displayInfo($client_data['idade_metabolica'] ?? null, ' anos', 0) ?></p>
                            <!-- Campo Morfotipo -->
                            <p class="mb-1"><strong>Morfotipo:</strong></p>
                            <p class="fs-5">
                                <?php
                                    // Tenta exibir o morfotipo da última avaliação.
                                    // Se $client_data['morfotipo'] for null ou '', displayInfo() retornará "N/D".
                                    echo displayInfo($client_data['morfotipo'] ?? null);
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card: Medidas (Perímetros) (Usa dados da última avaliação) -->
            <div class="card shadow-sm mb-4">
                 <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-ruler-combined me-2"></i>Últimas Medidas Corporais (Perímetros)</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Ombros:</strong></p> <p class="fs-5"><?= displayInfo($client_data['ombros'] ?? null, ' cm', 1) ?></p> </div>
                        <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Peitoral/Dorsal:</strong></p> <p class="fs-5"><?= displayInfo($client_data['peitoral_dorsal'] ?? null, ' cm', 1) ?></p> </div>
                        <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Cintura:</strong></p> <p class="fs-5"><?= displayInfo($client_data['cintura'] ?? null, ' cm', 1) ?></p> </div>
                        <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Abdominal:</strong></p> <p class="fs-5"><?= displayInfo($client_data['abdominal'] ?? null, ' cm', 1) ?></p> </div>
                        <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Quadril:</strong></p> <p class="fs-5"><?= displayInfo($client_data['quadril'] ?? null, ' cm', 1) ?></p> </div>
                        <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Braço Direito:</strong></p> <p class="fs-5"><?= displayInfo($client_data['braco_direito'] ?? null, ' cm', 1) ?></p> </div>
                        <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Braço Esquerdo:</strong></p> <p class="fs-5"><?= displayInfo($client_data['braco_esquerdo'] ?? null, ' cm', 1) ?></p> </div>
                        <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Coxa Direita:</strong></p> <p class="fs-5"><?= displayInfo($client_data['coxa_direita'] ?? null, ' cm', 1) ?></p> </div>
                        <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Coxa Esquerda:</strong></p> <p class="fs-5"><?= displayInfo($client_data['coxa_esquerda'] ?? null, ' cm', 1) ?></p> </div>
                        <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Perna Direita:</strong></p> <p class="fs-5"><?= displayInfo($client_data['perna_direita'] ?? null, ' cm', 1) ?></p> </div>
                        <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Perna Esquerda:</strong></p> <p class="fs-5"><?= displayInfo($client_data['perna_esquerda'] ?? null, ' cm', 1) ?></p> </div>
                    </div>
                </div>
            </div>

             <!-- Card: Pregas Cutâneas (Plicometria) (Usa dados da última avaliação) -->
            <div class="card shadow-sm mb-4">
                 <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-compress-alt me-2"></i>Últimas Pregas Cutâneas (Plicometria)</h5>
                </div>
                <div class="card-body">
                     <div class="row">
                        <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Tricipital:</strong></p> <p class="fs-5"><?= displayInfo($client_data['tricipital'] ?? null, ' mm', 1) ?></p> </div>
                        <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Subescapular:</strong></p> <p class="fs-5"><?= displayInfo($client_data['subescapular'] ?? null, ' mm', 1) ?></p> </div>
                        <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Bicipital:</strong></p> <p class="fs-5"><?= displayInfo($client_data['bicipital'] ?? null, ' mm', 1) ?></p> </div>
                        <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Peitoral:</strong></p> <p class="fs-5"><?= displayInfo($client_data['peitoral'] ?? null, ' mm', 1) ?></p> </div>
                        <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Axilar Média:</strong></p> <p class="fs-5"><?= displayInfo($client_data['axilar_media'] ?? null, ' mm', 1) ?></p> </div>
                        <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Abdominal:</strong></p> <p class="fs-5"><?= displayInfo($client_data['abdominal_plicometria'] ?? null, ' mm', 1) ?></p> </div>
                        <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Supra-ilíaca:</strong></p> <p class="fs-5"><?= displayInfo($client_data['supra_iliaca'] ?? null, ' mm', 1) ?></p> </div>
                        <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Coxa:</strong></p> <p class="fs-5"><?= displayInfo($client_data['coxa'] ?? null, ' mm', 1) ?></p> </div>
                        <div class="col-md-6 col-lg-3 mb-3"> <p class="mb-1"><strong>Geminal (Perna):</strong></p> <p class="fs-5"><?= displayInfo($client_data['geminal'] ?? null, ' mm', 1) ?></p> </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                 <!-- Link para o histórico -->
                 <a href="assessment_history.php?client_id=<?= $client_data['client_table_id'] ?? '' ?>" class="btn btn-info"><i class="fas fa-history me-1"></i>Ver Histórico de Avaliações</a>
                 <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar ao Dashboard</a>
            </div>

        <?php endif; // End else for page_error/empty data check ?>
    </div><!-- /.container -->
</main> <!-- /.main-content -->

<?php
include 'footer.php';
?>
