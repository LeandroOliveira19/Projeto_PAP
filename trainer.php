<?php
session_start();

// Verifica se o usuário está logado e se é um trainer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainer') {
    header('Location: login.php');
    exit;
}

// Inclui a conexão com o banco de dados
include 'db.php';

// Inclui o cabeçalho da página
include 'header.php'; // header.php deve ter <link rel="stylesheet" href="assets/css/dashboard_cliente.css"> ou similar

// Pega o nome do usuário da sessão
$trainer_name = $_SESSION['name'] ?? 'Treinador'; // Usa 'Treinador' como fallback

// Define qual link da sidebar deve estar ativo
$active_page = basename($_SERVER['PHP_SELF']); // Pega o nome do arquivo atual (ex: trainer.php)

?>

<!-- A classe dashboard-body já está no <body> incluído pelo header.php -->

<?php include 'sidebar_trainer.php'; ?>

<!-- Conteúdo Principal -->
<main class="main-content">
    <div class="container mt-5"> <!-- Mantém o container para espaçamento interno -->
        <h2>Bem-vindo, <?= htmlspecialchars($trainer_name) ?>!</h2>
        <div class="row">
            <div class="col-md-12">
                <h4>Clientes Sem Plano de Treino Ativo</h4>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Plano de Treino Ativo</th>
                                <th>Exercícios</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // ***** MODIFICAÇÃO: Buscar clientes SEM NENHUM plano ativo de QUALQUER trainer *****
                            $stmt = $pdo->prepare("
                                SELECT
                                    c.id AS client_id,
                                    u.id AS client_user_id,
                                    u.name AS client_name
                                FROM clients c
                                JOIN users u ON c.user_id = u.id
                                WHERE u.role = 'client' AND NOT EXISTS (
                                    SELECT 1 FROM workout_plans wp WHERE wp.client_id = c.id AND wp.is_active = 1
                                )
                                ORDER BY u.name
                            ");
                            $stmt->execute(); // Não precisa mais de trainer_id aqui para esta lógica específica
                            $clients = $stmt->fetchAll();

                            if (count($clients) > 0) {
                                foreach ($clients as $client) {
                                    echo "<tr>";
                                    echo "<td class='align-top'>" . htmlspecialchars($client['client_name']) . "</td>";

                                    // --- Coluna Plano de Treino (SEU CÓDIGO EXISTENTE) ---
                                    echo "<td class='align-top'><span class='text-muted'>Nenhum plano ativo</span></td>";
                                    // --- FIM Coluna Plano de Treino ---


                                    // --- Coluna Exercícios (SEU CÓDIGO EXISTENTE) ---
                                    echo "<td class='align-top'><span class='text-muted small'>N/A</span></td>";
                                    // --- FIM Coluna Exercícios ---


                                    // --- Coluna Ações (COM O NOVO BOTÃO INTEGRADO) ---
                                    echo "<td class='align-top text-center'>"; // Adicionado text-center para alinhar os botões

                                    // Botão Editar/Criar Plano
                                    // Agora sempre será "Criar Plano" pois só listamos clientes sem plano ativo
                                    echo "<a href='add_workout_plan.php?client_id=" . $client['client_id'] . "' class='btn btn-primary btn-sm mb-1 d-block text-nowrap' title='Criar Plano de Treino'><i class='fas fa-plus me-1'></i> <span class='d-none d-md-inline'>Criar Plano</span></a>";

                                    // *** NOVO BOTÃO AVALIAÇÃO ADICIONADO AQUI ***
                                    echo "<a href='edit_client_assessment.php?client_id=" . $client['client_id'] . "' class='btn btn-info btn-sm mb-1 d-block text-nowrap' title='Editar Avaliação Física'><i class='fas fa-clipboard-list me-1'></i> <span class='d-none d-md-inline'>Avaliação</span></a>";
                                    // ********************************************

                                    // Botão Chat
                                    if (isset($client['client_user_id'])) {
                                        echo "<a href='chat.php?user_id=" . $client['client_user_id'] . "' class='btn btn-success btn-sm mb-1 d-block text-nowrap' title='Conversar com Cliente'><i class='fas fa-comments me-1'></i> <span class='d-none d-md-inline'>Chat</span></a>";
                                    }

                                    echo "</td>";
                                    // --- FIM Coluna Ações ---

                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' class='text-center'>Todos os clientes já possuem um plano de treino ativo ou não existem clientes.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div> <!-- /.table-responsive -->
            </div>
        </div>
    </div> <!-- /.container -->
</main> <!-- /.main-content -->

<?php
// Inclui o rodapé da página
include 'footer.php';
?>
