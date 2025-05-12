<?php
// filepath: c:\xampp\htdocs\Projeto_PAP\assessment_history.php
session_start();

// Verifica se o usuário está logado (cliente ou profissional)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'db.php';
include 'header.php';

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$page_error = '';
$assessments = [];
$client_id_from_url = null;
$client_name = 'Desconhecido';

// --- Determinar o client_id ---
if ($user_role === 'client') {
    // Cliente só pode ver o seu próprio histórico
    try {
        $stmt_get_client_id = $pdo->prepare("SELECT id FROM clients WHERE user_id = ? LIMIT 1");
        $stmt_get_client_id->execute([$user_id]);
        $client_id_from_db = $stmt_get_client_id->fetchColumn();
        if ($client_id_from_db) {
            $client_id_from_url = $client_id_from_db; // Usa o ID do próprio cliente
            $client_name = $_SESSION['name']; // Usa o nome da sessão
        } else {
            $page_error = "Registo de cliente não encontrado para este utilizador.";
        }
    } catch (PDOException $e) {
         $page_error = "Erro ao buscar ID do cliente: " . $e->getMessage();
    }
} elseif (in_array($user_role, ['trainer', 'nutritionist'])) {
    // Profissional precisa do client_id na URL
    if (isset($_GET['client_id']) && filter_var($_GET['client_id'], FILTER_VALIDATE_INT)) {
        $client_id_from_url = (int)$_GET['client_id'];
        // Buscar nome do cliente para exibir no título
        try {
            $stmt_name = $pdo->prepare("SELECT u.name FROM users u JOIN clients c ON u.id = c.user_id WHERE c.id = ?");
            $stmt_name->execute([$client_id_from_url]);
            $name_result = $stmt_name->fetchColumn();
            if ($name_result) {
                $client_name = $name_result;
            } else {
                 $page_error = "Cliente com ID fornecido não encontrado.";
                 $client_id_from_url = null; // Invalida o ID se não encontrar nome
            }
        } catch (PDOException $e) {
             $page_error = "Erro ao buscar nome do cliente: " . $e->getMessage();
             $client_id_from_url = null;
        }
    } else {
        $page_error = "ID do cliente não fornecido ou inválido na URL.";
    }
} else {
    // Admin ou outro role não tem acesso (ou precisa de lógica diferente)
    $page_error = "Acesso não permitido para este tipo de utilizador.";
}

// --- Buscar Histórico de Avaliações ---
if ($client_id_from_url && !$page_error) {
    try {
        $stmt = $pdo->prepare("
            SELECT ha.*, u_recorder.name as recorder_name -- << NOME DA TABELA ATUALIZADO
            FROM history_assessments ha -- << NOME DA TABELA ATUALIZADO
            LEFT JOIN users u_recorder ON ha.recorded_by_user_id = u_recorder.id
            WHERE ha.client_id = :client_id
            ORDER BY ha.assessment_date DESC, ha.id DESC
        ");
        $stmt->execute(['client_id' => $client_id_from_url]);
        $assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $page_error = "Erro ao buscar histórico de avaliações: " . $e->getMessage();
        error_log($page_error);
    }
}

// Define a página ativa e inclui a sidebar correta
if ($user_role === 'client') {
    $active_page = 'ficha_pessoal.php'; // Destaca Ficha Pessoal na sidebar do cliente
    include 'sidebar_client.php';
} elseif ($user_role === 'trainer') {
    $active_page = 'my_clients.php'; // Destaca Meus Clientes na sidebar do trainer
    include 'sidebar_trainer.php';
} elseif ($user_role === 'nutritionist') {
    $active_page = 'client_list_nutritionist.php'; // Destaca Clientes na sidebar do nutri
    // Assumindo que existe sidebar_nutritionist.php
    if (file_exists('sidebar_nutritionist.php')) {
        include 'sidebar_nutritionist.php';
    } else {
        echo "<!-- Erro: sidebar_nutritionist.php não encontrado. -->";
    }
}
// Adicione mais 'elseif' para outros roles se necessário

// Helper function (pode ser a mesma de ficha_pessoal.php)
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

<!-- Conteúdo Principal -->
<main class="main-content">
    <div class="container mt-5">
        <h2><i class="fas fa-history me-2"></i>Histórico de Avaliações Físicas</h2>
        <h3 class="text-muted mb-4">Cliente: <?= htmlspecialchars($client_name) ?></h3>

        <?php if (!empty($page_error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($page_error) ?></div>
        <?php elseif (!$client_id_from_url): ?>
             <div class="alert alert-warning">Não foi possível determinar o cliente para exibir o histórico.</div>
        <?php elseif (empty($assessments)): ?>
            <div class="alert alert-info">Nenhum histórico de avaliações encontrado para este cliente.</div>
        <?php else: ?>
            <div class="accordion" id="assessmentHistoryAccordion">
                <?php foreach ($assessments as $index => $assessment): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading<?= $assessment['id'] ?>">
                            <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $assessment['id'] ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="collapse<?= $assessment['id'] ?>">
                                Avaliação de: <?= htmlspecialchars(date('d/m/Y', strtotime($assessment['assessment_date']))) ?>
                                <?php if ($assessment['recorder_name']): ?>
                                    <small class="text-muted ms-auto me-2">(Registado por: <?= htmlspecialchars($assessment['recorder_name']) ?>)</small>
                                <?php endif; ?>
                            </button>
                        </h2>
                        <div id="collapse<?= $assessment['id'] ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" aria-labelledby="heading<?= $assessment['id'] ?>" data-bs-parent="#assessmentHistoryAccordion">
                            <div class="accordion-body">
                                <h5 class="mb-3">Detalhes da Avaliação</h5>
                                <div class="row mb-3">
                                    <div class="col-md-4"><strong>Peso:</strong> <?= displayInfo($assessment['weight'] ?? null, ' kg', 1) ?></div>
                                    <div class="col-md-4"><strong>Altura:</strong> <?= displayInfo($assessment['height'] ?? null, ' m', 2) ?></div>
                                </div>
                                <hr>
                                <h6 class="mt-3 mb-2">Composição Corporal</h6>
                                <div class="row">
                                    <div class="col-md-6 col-lg-3 mb-2"> <small><strong>% Gordura:</strong></small> <br><?= displayInfo($assessment['fat_mass_percentage'] ?? null, ' %', 1) ?></div>
                                    <div class="col-md-6 col-lg-3 mb-2"> <small><strong>Massa Gorda:</strong></small> <br><?= displayInfo($assessment['fat_mass_kg'] ?? null, ' kg', 2) ?></div>
                                    <div class="col-md-6 col-lg-3 mb-2"> <small><strong>% Gord. Subcut.:</strong></small> <br><?= displayInfo($assessment['gordura_subcutanea'] ?? null, ' %', 1) ?></div>
                                    <div class="col-md-6 col-lg-3 mb-2"> <small><strong>Gord. Visceral:</strong></small> <br><?= displayInfo($assessment['visceral_fat_index'] ?? null, '', 0) ?></div>
                                    <div class="col-md-6 col-lg-3 mb-2"> <small><strong>Massa Muscular:</strong></small> <br><?= displayInfo($assessment['muscle_mass_kg'] ?? null, ' kg', 2) ?></div>
                                    <div class="col-md-6 col-lg-3 mb-2"> <small><strong>Massa Magra:</strong></small> <br><?= displayInfo($assessment['lean_mass_kg'] ?? null, ' kg', 2) ?></div>
                                    <div class="col-md-6 col-lg-3 mb-2"> <small><strong>Proteínas:</strong></small> <br><?= displayInfo($assessment['proteinas'] ?? null, ' kg', 2) ?></div>
                                    <div class="col-md-6 col-lg-3 mb-2"> <small><strong>Massa Óssea:</strong></small> <br><?= displayInfo($assessment['bone_mass_kg'] ?? null, ' kg', 2) ?></div>
                                    <div class="col-md-6 col-lg-3 mb-2"> <small><strong>% Água:</strong></small> <br><?= displayInfo($assessment['water_percentage'] ?? null, ' %', 1) ?></div>
                                    <div class="col-md-6 col-lg-3 mb-2"> <small><strong>Idade Metab.:</strong></small> <br><?= displayInfo($assessment['idade_metabolica'] ?? null, ' anos', 0) ?></div>
                                    <div class="col-md-6 col-lg-3 mb-2"> <small><strong>Morfotipo:</strong></small> <br><?= displayInfo($assessment['morfotipo'] ?? null) ?></div>
                                </div>
                                <hr>
                                <h6 class="mt-3 mb-2">Perímetros (cm)</h6>
                                <div class="row">
                                    <div class="col-md-4 col-lg-2 mb-2"><small><strong>Ombros:</strong></small><br><?= displayInfo($assessment['ombros'] ?? null, ' cm', 1) ?></div>
                                    <div class="col-md-4 col-lg-2 mb-2"><small><strong>Peitoral:</strong></small><br><?= displayInfo($assessment['peitoral_dorsal'] ?? null, ' cm', 1) ?></div>
                                    <div class="col-md-4 col-lg-2 mb-2"><small><strong>Cintura:</strong></small><br><?= displayInfo($assessment['cintura'] ?? null, ' cm', 1) ?></div>
                                    <div class="col-md-4 col-lg-2 mb-2"><small><strong>Abdominal:</strong></small><br><?= displayInfo($assessment['abdominal'] ?? null, ' cm', 1) ?></div>
                                    <div class="col-md-4 col-lg-2 mb-2"><small><strong>Quadril:</strong></small><br><?= displayInfo($assessment['quadril'] ?? null, ' cm', 1) ?></div>
                                    <div class="col-md-4 col-lg-2 mb-2"><small><strong>Braço D:</strong></small><br><?= displayInfo($assessment['braco_direito'] ?? null, ' cm', 1) ?></div>
                                    <div class="col-md-4 col-lg-2 mb-2"><small><strong>Braço E:</strong></small><br><?= displayInfo($assessment['braco_esquerdo'] ?? null, ' cm', 1) ?></div>
                                    <div class="col-md-4 col-lg-2 mb-2"><small><strong>Coxa D:</strong></small><br><?= displayInfo($assessment['coxa_direita'] ?? null, ' cm', 1) ?></div>
                                    <div class="col-md-4 col-lg-2 mb-2"><small><strong>Coxa E:</strong></small><br><?= displayInfo($assessment['coxa_esquerda'] ?? null, ' cm', 1) ?></div>
                                    <div class="col-md-4 col-lg-2 mb-2"><small><strong>Perna D:</strong></small><br><?= displayInfo($assessment['perna_direita'] ?? null, ' cm', 1) ?></div>
                                    <div class="col-md-4 col-lg-2 mb-2"><small><strong>Perna E:</strong></small><br><?= displayInfo($assessment['perna_esquerda'] ?? null, ' cm', 1) ?></div>
                                </div>
                                <hr>
                                <h6 class="mt-3 mb-2">Pregas Cutâneas (mm)</h6>
                                 <div class="row">
                                    <div class="col-md-4 col-lg-2 mb-2"><small><strong>Tricipital:</strong></small><br><?= displayInfo($assessment['tricipital'] ?? null, ' mm', 1) ?></div>
                                    <div class="col-md-4 col-lg-2 mb-2"><small><strong>Subescapular:</strong></small><br><?= displayInfo($assessment['subescapular'] ?? null, ' mm', 1) ?></div>
                                    <div class="col-md-4 col-lg-2 mb-2"><small><strong>Bicipital:</strong></small><br><?= displayInfo($assessment['bicipital'] ?? null, ' mm', 1) ?></div>
                                    <div class="col-md-4 col-lg-2 mb-2"><small><strong>Peitoral:</strong></small><br><?= displayInfo($assessment['peitoral'] ?? null, ' mm', 1) ?></div>
                                    <div class="col-md-4 col-lg-2 mb-2"><small><strong>Axilar Média:</strong></small><br><?= displayInfo($assessment['axilar_media'] ?? null, ' mm', 1) ?></div>
                                    <div class="col-md-4 col-lg-2 mb-2"><small><strong>Abdominal:</strong></small><br><?= displayInfo($assessment['abdominal_plicometria'] ?? null, ' mm', 1) ?></div>
                                    <div class="col-md-4 col-lg-2 mb-2"><small><strong>Supra-ilíaca:</strong></small><br><?= displayInfo($assessment['supra_iliaca'] ?? null, ' mm', 1) ?></div>
                                    <div class="col-md-4 col-lg-2 mb-2"><small><strong>Coxa:</strong></small><br><?= displayInfo($assessment['coxa'] ?? null, ' mm', 1) ?></div>
                                    <div class="col-md-4 col-lg-2 mb-2"><small><strong>Geminal:</strong></small><br><?= displayInfo($assessment['geminal'] ?? null, ' mm', 1) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <?php
            // Botão voltar dinâmico
            if ($user_role === 'client') {
                echo '<a href="ficha_pessoal.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar à Ficha Pessoal</a>';
            } elseif ($user_role === 'trainer') {
                 echo '<a href="my_clients.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar aos Meus Clientes</a>';
            } elseif ($user_role === 'nutritionist') {
                 echo '<a href="client_list_nutritionist.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar à Lista de Clientes</a>';
            }
            ?>
        </div>

    </div><!-- /.container -->
</main> <!-- /.main-content -->

<?php
include 'footer.php';
?>
