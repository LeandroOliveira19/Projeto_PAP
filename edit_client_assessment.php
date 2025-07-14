<?php
// filepath: c:\xampp\htdocs\Projeto_PAP\edit_client_assessment.php
session_start();

// Verifica se o usuário está logado e se é trainer OU nutritionist
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['trainer', 'nutritionist'])) {
    header('Location: login.php');
    exit;
}

$user_role = $_SESSION['role'];
$recorder_user_id = $_SESSION['user_id']; // ID de quem está a registar

include 'db.php';
include 'header.php';

// Define $active_page para destacar link na sidebar
if ($user_role === 'trainer') {
    $active_page = 'my_clients.php'; // Destaca 'Meus Clientes'
} elseif ($user_role === 'nutritionist') {
    // Assumindo que a página de lista de clientes do nutri é client_list_nutritionist.php
    $active_page = 'client_list_nutritionist.php'; // Destaca 'Clientes'
} else {
    $active_page = '';
}

// Inclui a sidebar correta
if ($user_role === 'trainer') {
    include 'sidebar_trainer.php';
} elseif ($user_role === 'nutritionist') {
    // Assumindo que existe sidebar_nutritionist.php
    if (file_exists('sidebar_nutritionist.php')) {
        include 'sidebar_nutritionist.php';
    } else {
        echo "<!-- Erro: sidebar_nutritionist.php não encontrado. -->";
    }
}

$page_error = '';
$success_message = '';
$client_data_for_form = []; // Dados para preencher o formulário (da última avaliação)
$client_id = null; // ID da tabela clients
$client_user_name = 'Desconhecido'; // Nome do cliente

// --- Obter ID do Cliente a Editar ---
if (isset($_GET['client_id']) && filter_var($_GET['client_id'], FILTER_VALIDATE_INT)) {
    $client_id = (int)$_GET['client_id'];

    // Buscar nome do cliente (necessário em GET e POST)
    try {
        $stmt_name = $pdo->prepare("SELECT u.name FROM users u JOIN clients c ON u.id = c.user_id WHERE c.id = ?");
        $stmt_name->execute([$client_id]);
        $name_result = $stmt_name->fetchColumn();
        if ($name_result) {
            $client_user_name = $name_result;
        } else {
             throw new Exception("Cliente não encontrado para obter nome.");
        }
    } catch (Exception $e) {
         $page_error = "Erro ao buscar nome do cliente: " . $e->getMessage();
         // Não continuar se não encontrar o cliente
    }

} else {
    $page_error = "ID do cliente inválido ou não fornecido.";
}


// --- Processar Submissão do Formulário (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $client_id && !$page_error) {
    // Validação da Data (Obrigatória)
    if (empty($_POST['assessment_date'])) {
        $page_error = "A data da avaliação é obrigatória.";
    } else {
        try {
            // Lista completa de campos da tabela history_assessments (exceto id e created_at)
            $assessment_fields = [
                'client_id', 'assessment_date', 'recorded_by_user_id', 'weight', 'height',
                'fat_mass_percentage', 'fat_mass_kg', 'gordura_subcutanea', 'visceral_fat_index',
                'muscle_mass_kg', 'lean_mass_kg', 'proteinas', 'bone_mass_kg', 'water_percentage',
                /* 'idade_metabolica', */ 'morfotipo', 'lean_mass_percentage', // idade_metabolica removido
                'ombros', 'peitoral_dorsal', 'cintura', 'abdominal', 'quadril', 'braco_direito',
                'braco_esquerdo', 'coxa_direita', 'coxa_esquerda', 'perna_direita', 'perna_esquerda',
                'tricipital', 'subescapular', 'bicipital', 'peitoral', 'axilar_media',
                'abdominal_plicometria', 'supra_iliaca', 'coxa', 'geminal'
            ];
            $sql_columns = [];
            $sql_placeholders = [];
            $params = [];

            // Adiciona valores fixos
            $params['client_id'] = $client_id;
            $params['recorded_by_user_id'] = $recorder_user_id;
            $params['assessment_date'] = $_POST['assessment_date']; // Já validado que não está vazio

            // Prepara colunas e placeholders para a query INSERT
            $sql_columns[] = '`client_id`'; $sql_placeholders[] = ':client_id';
            $sql_columns[] = '`assessment_date`'; $sql_placeholders[] = ':assessment_date';
            $sql_columns[] = '`recorded_by_user_id`'; $sql_placeholders[] = ':recorded_by_user_id';

            // Processa os campos do formulário
            foreach ($assessment_fields as $field) {
                // Ignora os campos já definidos (client_id, assessment_date, recorded_by_user_id)
                if (in_array($field, ['client_id', 'assessment_date', 'recorded_by_user_id'])) {
                    continue;
                }

                if (isset($_POST[$field])) {
                    $value = trim($_POST[$field]);

                    // Campos numéricos (decimais e inteiros)
                    $numeric_fields = [
                        'weight', 'height', 'fat_mass_percentage', 'fat_mass_kg', 'gordura_subcutanea',
                        'visceral_fat_index', 'muscle_mass_kg', 'lean_mass_kg', 'proteinas', 'bone_mass_kg', // idade_metabolica removido da lista de processamento numérico específico
                        'water_percentage', /*'idade_metabolica',*/ 'lean_mass_percentage',
                        'ombros', 'peitoral_dorsal', 'cintura', 'abdominal', 'quadril', 'braco_direito',
                        'braco_esquerdo', 'coxa_direita', 'coxa_esquerda', 'perna_direita', 'perna_esquerda',
                        'tricipital', 'subescapular', 'bicipital', 'peitoral', 'axilar_media',
                        'abdominal_plicometria', 'supra_iliaca', 'coxa', 'geminal'
                    ];

                    if (in_array($field, $numeric_fields)) {
                        $value = str_replace(',', '.', $value);
                        if ($value === '' || !is_numeric($value)) {
                            $params[$field] = null;
                        } else {
                            // Determina se é INT ou FLOAT
                        if (in_array($field, ['visceral_fat_index' /*, 'idade_metabolica'*/])) { // idade_metabolica removido
                                $params[$field] = (int)$value;
                            } else {
                                $params[$field] = (float)$value;
                            }
                        }
                    } else { // Campos de texto (morfotipo)
                        $params[$field] = ($value === '') ? null : $value;
                    }

                    // Adiciona à query INSERT
                    $sql_columns[] = "`" . $field . "`";
                    $sql_placeholders[] = ":" . $field;
                } else {
                    // Se o campo não veio no POST, define como NULL
                    $params[$field] = null;
                    $sql_columns[] = "`" . $field . "`";
                    $sql_placeholders[] = ":" . $field;
                }
            }

            // Monta e executa o INSERT na tabela history_assessments
            $sql = "INSERT INTO history_assessments (" . implode(', ', $sql_columns) . ") VALUES (" . implode(', ', $sql_placeholders) . ")";
            $stmt = $pdo->prepare($sql);

            if ($stmt->execute($params)) {
                $success_message = "Nova avaliação registada com sucesso!";

                // ** ATUALIZAÇÃO OPCIONAL DA TABELA clients **
                try {
                    $stmt_update_client = $pdo->prepare("UPDATE clients SET weight = :weight, height = :height, last_assessment_date = :assessment_date WHERE id = :client_id");
                    $stmt_update_client->execute([
                        'weight' => $params['weight'] ?? null, // Usa o valor já processado
                        'height' => $params['height'] ?? null, // Usa o valor já processado
                        'assessment_date' => $params['assessment_date'],
                        'client_id' => $client_id
                    ]);
                } catch (PDOException $eUpdate) {
                    // Loga o erro, mas não impede a mensagem de sucesso principal
                    error_log("Erro ao atualizar tabela clients após inserir avaliação: " . $eUpdate->getMessage());
                }
                // ** FIM DA ATUALIZAÇÃO OPCIONAL **

            } else {
                $page_error = "Erro ao registar a avaliação no banco de dados.";
                $errorInfo = $stmt->errorInfo();
                error_log("SQL Error inserting assessment for client_id $client_id: " . $errorInfo[2]);
            }

        } catch (PDOException $e) {
            $page_error = 'Erro de Banco de Dados: ' . $e->getMessage();
            error_log("PDOException inserting assessment data for client_id $client_id: " . $e->getMessage());
        } catch (Exception $e) {
            $page_error = 'Erro Geral: ' . $e->getMessage();
            error_log("Exception inserting assessment data for client_id $client_id: " . $e->getMessage());
        }
    } // Fim else validação data
}

// --- Buscar Dados da ÚLTIMA Avaliação (para preencher o form) ---
// Executa sempre que a página carrega (GET ou após POST) se não houver erro de ID
if ($client_id && !$page_error) {
    try {
        // Busca a avaliação mais recente para este cliente na tabela history_assessments
        $stmt_latest = $pdo->prepare("
            SELECT * FROM history_assessments -- << NOME DA TABELA ATUALIZADO
            WHERE client_id = :client_id
            ORDER BY assessment_date DESC, id DESC
            LIMIT 1
        ");
        $stmt_latest->execute(['client_id' => $client_id]);
        $client_data_for_form = $stmt_latest->fetch(PDO::FETCH_ASSOC);
        // Se não houver avaliação anterior, $client_data_for_form será false (ou vazio)

    } catch (PDOException $e) {
        // Não é um erro fatal para a página, apenas não pré-preenche
        error_log("Erro ao buscar última avaliação para preencher form (client_id $client_id): " . $e->getMessage());
        $client_data_for_form = []; // Garante que está vazio
    }
}

// *** FUNÇÃO HELPER ATUALIZADA ***
// Helper para preencher valor do input (ATUALIZADO com is_array)
function getFieldValue($field_name, $data_array, $decimals = null) {
    // Verifica se $data_array é um array e se a chave existe e não é nula
    if (is_array($data_array) && isset($data_array[$field_name]) && $data_array[$field_name] !== null) {
        $value = $data_array[$field_name];
        // Formata números com vírgula se $decimals for especificado
        if ($decimals !== null && is_numeric($value)) {
            return number_format((float)$value, $decimals, ',', '');
        }
        // Retorna o valor como string segura para HTML
        return htmlspecialchars($value);
    }
    // Retorna string vazia se $data_array não for um array ou a chave não existir/for nula
    return '';
}
// *** FIM DA FUNÇÃO HELPER ATUALIZADA ***
?>

<!-- Conteúdo Principal -->
<main class="main-content">
    <div class="container mt-4">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white"> <!-- Cor pode ser ajustada -->
                 <h2 class="mb-0 h4"><i class="fas fa-notes-medical me-2"></i>Registar Nova Avaliação Física</h2>
            </div>
            <div class="card-body">

                <?php if ($page_error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($page_error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($success_message) ?>
                         <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                         <p class="mt-2 mb-0"><a href="assessment_history.php?client_id=<?= $client_id ?>" class="alert-link">Ver Histórico de Avaliações</a></p>
                    </div>
                <?php endif; ?>

                <?php if ($client_id && empty($page_error)): // Mostra o formulário se o ID é válido e não houve erro fatal ?>
                    <p class="lead mb-4">Cliente: <strong><?= htmlspecialchars($client_user_name) ?></strong></p>

                    <!-- Formulário aponta para si mesmo -->
                    <form action="edit_client_assessment.php?client_id=<?= $client_id ?>" method="POST" id="assessmentForm">

                        <!-- Card: Avaliação Corporal -->
                        <div class="card mb-4 border-primary">
                            <div class="card-header bg-light border-primary"><h5 class="mb-0 text-primary"><i class="fas fa-body me-2"></i>Avaliação Corporal</h5></div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <!-- Campo Data agora é obrigatório -->
                                        <label for="assessment_date" class="form-label">Data da Avaliação <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="assessment_date" name="assessment_date" value="<?= date('Y-m-d') // Sugere data atual ?>" required>
                                    </div>
                                     <div class="col-md-4">
                                        <label for="weight" class="form-label">Peso (Kg)</label>
                                        <input type="text" inputmode="decimal" class="form-control" id="weight" name="weight" placeholder="Ex: 75,5" value="<?= getFieldValue('weight', $client_data_for_form, 1) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="height" class="form-label">Altura (m)</label>
                                        <input type="text" inputmode="decimal" class="form-control" id="height" name="height" placeholder="Ex: 1,80" value="<?= getFieldValue('height', $client_data_for_form, 2) ?>">
                                    </div>
                                    <!-- Restantes campos preenchidos com $client_data_for_form -->
                                    <div class="col-md-4">
                                        <label for="fat_mass_percentage" class="form-label">% Gordura Corporal</label>
                                        <input type="text" inputmode="decimal" class="form-control" id="fat_mass_percentage" name="fat_mass_percentage" placeholder="Ex: 22,5" value="<?= getFieldValue('fat_mass_percentage', $client_data_for_form, 1) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="fat_mass_kg" class="form-label">Massa Gorda (Kg)</label>
                                        <input type="text" inputmode="decimal" class="form-control" id="fat_mass_kg" name="fat_mass_kg" placeholder="Ex: 15,80" value="<?= getFieldValue('fat_mass_kg', $client_data_for_form, 2) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="gordura_subcutanea" class="form-label">% Gordura Subcutânea</label>
                                        <input type="text" inputmode="decimal" class="form-control" id="gordura_subcutanea" name="gordura_subcutanea" placeholder="Ex: 18,2" value="<?= getFieldValue('gordura_subcutanea', $client_data_for_form, 1) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="visceral_fat_index" class="form-label">Gordura Visceral (Índice)</label>
                                        <input type="number" class="form-control" id="visceral_fat_index" name="visceral_fat_index" placeholder="Ex: 5" value="<?= getFieldValue('visceral_fat_index', $client_data_for_form) ?>">
                                    </div>
                                     <div class="col-md-4">
                                        <label for="muscle_mass_kg" class="form-label">Massa Muscular (Kg)</label>
                                        <input type="text" inputmode="decimal" class="form-control" id="muscle_mass_kg" name="muscle_mass_kg" placeholder="Ex: 55,20" value="<?= getFieldValue('muscle_mass_kg', $client_data_for_form, 2) ?>">
                                    </div>
                                     <div class="col-md-4">
                                        <label for="lean_mass_kg" class="form-label">Massa Magra (Kg)</label>
                                        <input type="text" inputmode="decimal" class="form-control" id="lean_mass_kg" name="lean_mass_kg" placeholder="Ex: 60,50" value="<?= getFieldValue('lean_mass_kg', $client_data_for_form, 2) ?>">
                                    </div>
                                     <div class="col-md-4">
                                        <label for="lean_mass_percentage" class="form-label">% Massa Magra</label>
                                        <input type="text" inputmode="decimal" class="form-control" id="lean_mass_percentage" name="lean_mass_percentage" placeholder="Ex: 77,5" value="<?= getFieldValue('lean_mass_percentage', $client_data_for_form, 1) ?>">
                                    </div>
                                     <div class="col-md-4">
                                        <label for="proteinas" class="form-label">Proteínas (Kg)</label>
                                        <input type="text" inputmode="decimal" class="form-control" id="proteinas" name="proteinas" placeholder="Ex: 12,30" value="<?= getFieldValue('proteinas', $client_data_for_form, 2) ?>">
                                    </div>
                                     <div class="col-md-4">
                                        <label for="bone_mass_kg" class="form-label">Massa Óssea (Kg)</label>
                                        <input type="text" inputmode="decimal" class="form-control" id="bone_mass_kg" name="bone_mass_kg" placeholder="Ex: 2,80" value="<?= getFieldValue('bone_mass_kg', $client_data_for_form, 2) ?>">
                                    </div>
                                     <div class="col-md-4">
                                        <label for="water_percentage" class="form-label">% Água Corporal</label>
                                        <input type="text" inputmode="decimal" class="form-control" id="water_percentage" name="water_percentage" placeholder="Ex: 58,5" value="<?= getFieldValue('water_percentage', $client_data_for_form, 1) ?>">
                                    </div>
                                     <div class="col-md-4">
                                        <label for="morfotipo" class="form-label">Morfotipo</label>
                                        <select class="form-select" id="morfotipo" name="morfotipo">
                                            <option value="" <?= (getFieldValue('morfotipo', $client_data_for_form) == '') ? 'selected' : '' ?>>-- Selecione --</option>
                                            <option value="Ectomorfo" <?= (getFieldValue('morfotipo', $client_data_for_form) == 'Ectomorfo') ? 'selected' : '' ?>>Ectomorfo</option>
                                            <option value="Mesomorfo" <?= (getFieldValue('morfotipo', $client_data_for_form) == 'Mesomorfo') ? 'selected' : '' ?>>Mesomorfo</option>
                                            <option value="Endomorfo" <?= (getFieldValue('morfotipo', $client_data_for_form) == 'Endomorfo') ? 'selected' : '' ?>>Endomorfo</option>
                                        </select>
                                    </div>
                                    <!-- Campo Activity Level removido daqui, pois está na tabela clients -->
                                </div>
                            </div>
                        </div>

                        <!-- Card: Medidas (Perímetros) -->
                        <div class="card mb-4 border-success">
                            <div class="card-header bg-light border-success"><h5 class="mb-0 text-success"><i class="fas fa-ruler-combined me-2"></i>Medidas Corporais (Perímetros em cm)</h5></div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <!-- Campos preenchidos com $client_data_for_form -->
                                    <div class="col-md-3 col-6"><label for="ombros" class="form-label">Ombros</label><input type="text" inputmode="decimal" class="form-control" id="ombros" name="ombros" value="<?= getFieldValue('ombros', $client_data_for_form, 1) ?>"></div>
                                    <div class="col-md-3 col-6"><label for="peitoral_dorsal" class="form-label">Peitoral/Dorsal</label><input type="text" inputmode="decimal" class="form-control" id="peitoral_dorsal" name="peitoral_dorsal" value="<?= getFieldValue('peitoral_dorsal', $client_data_for_form, 1) ?>"></div>
                                    <div class="col-md-3 col-6"><label for="cintura" class="form-label">Cintura</label><input type="text" inputmode="decimal" class="form-control" id="cintura" name="cintura" value="<?= getFieldValue('cintura', $client_data_for_form, 1) ?>"></div>
                                    <div class="col-md-3 col-6"><label for="abdominal" class="form-label">Abdominal</label><input type="text" inputmode="decimal" class="form-control" id="abdominal" name="abdominal" value="<?= getFieldValue('abdominal', $client_data_for_form, 1) ?>"></div>
                                    <div class="col-md-3 col-6"><label for="quadril" class="form-label">Quadril</label><input type="text" inputmode="decimal" class="form-control" id="quadril" name="quadril" value="<?= getFieldValue('quadril', $client_data_for_form, 1) ?>"></div>
                                    <div class="col-md-3 col-6"><label for="braco_direito" class="form-label">Braço Direito</label><input type="text" inputmode="decimal" class="form-control" id="braco_direito" name="braco_direito" value="<?= getFieldValue('braco_direito', $client_data_for_form, 1) ?>"></div>
                                    <div class="col-md-3 col-6"><label for="braco_esquerdo" class="form-label">Braço Esquerdo</label><input type="text" inputmode="decimal" class="form-control" id="braco_esquerdo" name="braco_esquerdo" value="<?= getFieldValue('braco_esquerdo', $client_data_for_form, 1) ?>"></div>
                                    <div class="col-md-3 col-6"><label for="coxa_direita" class="form-label">Coxa Direita</label><input type="text" inputmode="decimal" class="form-control" id="coxa_direita" name="coxa_direita" value="<?= getFieldValue('coxa_direita', $client_data_for_form, 1) ?>"></div>
                                    <div class="col-md-3 col-6"><label for="coxa_esquerda" class="form-label">Coxa Esquerda</label><input type="text" inputmode="decimal" class="form-control" id="coxa_esquerda" name="coxa_esquerda" value="<?= getFieldValue('coxa_esquerda', $client_data_for_form, 1) ?>"></div>
                                    <div class="col-md-3 col-6"><label for="perna_direita" class="form-label">Perna Direita</label><input type="text" inputmode="decimal" class="form-control" id="perna_direita" name="perna_direita" value="<?= getFieldValue('perna_direita', $client_data_for_form, 1) ?>"></div>
                                    <div class="col-md-3 col-6"><label for="perna_esquerda" class="form-label">Perna Esquerda</label><input type="text" inputmode="decimal" class="form-control" id="perna_esquerda" name="perna_esquerda" value="<?= getFieldValue('perna_esquerda', $client_data_for_form, 1) ?>"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Card: Pregas Cutâneas (Plicometria) -->
                        <div class="card mb-4 border-info">
                            <div class="card-header bg-light border-info"><h5 class="mb-0 text-info"><i class="fas fa-compress-alt me-2"></i>Pregas Cutâneas (Plicometria em mm)</h5></div>
                            <div class="card-body">
                                 <div class="row g-3">
                                     <!-- Campos preenchidos com $client_data_for_form -->
                                    <div class="col-md-3 col-6"><label for="tricipital" class="form-label">Tricipital</label><input type="text" inputmode="decimal" class="form-control" id="tricipital" name="tricipital" value="<?= getFieldValue('tricipital', $client_data_for_form, 1) ?>"></div>
                                    <div class="col-md-3 col-6"><label for="subescapular" class="form-label">Subescapular</label><input type="text" inputmode="decimal" class="form-control" id="subescapular" name="subescapular" value="<?= getFieldValue('subescapular', $client_data_for_form, 1) ?>"></div>
                                    <div class="col-md-3 col-6"><label for="bicipital" class="form-label">Bicipital</label><input type="text" inputmode="decimal" class="form-control" id="bicipital" name="bicipital" value="<?= getFieldValue('bicipital', $client_data_for_form, 1) ?>"></div>
                                    <div class="col-md-3 col-6"><label for="peitoral" class="form-label">Peitoral</label><input type="text" inputmode="decimal" class="form-control" id="peitoral" name="peitoral" value="<?= getFieldValue('peitoral', $client_data_for_form, 1) ?>"></div>
                                    <div class="col-md-3 col-6"><label for="axilar_media" class="form-label">Axilar Média</label><input type="text" inputmode="decimal" class="form-control" id="axilar_media" name="axilar_media" value="<?= getFieldValue('axilar_media', $client_data_for_form, 1) ?>"></div>
                                    <div class="col-md-3 col-6"><label for="abdominal_plicometria" class="form-label">Abdominal</label><input type="text" inputmode="decimal" class="form-control" id="abdominal_plicometria" name="abdominal_plicometria" value="<?= getFieldValue('abdominal_plicometria', $client_data_for_form, 1) ?>"></div>
                                    <div class="col-md-3 col-6"><label for="supra_iliaca" class="form-label">Supra-ilíaca</label><input type="text" inputmode="decimal" class="form-control" id="supra_iliaca" name="supra_iliaca" value="<?= getFieldValue('supra_iliaca', $client_data_for_form, 1) ?>"></div>
                                    <div class="col-md-3 col-6"><label for="coxa" class="form-label">Coxa</label><input type="text" inputmode="decimal" class="form-control" id="coxa" name="coxa" value="<?= getFieldValue('coxa', $client_data_for_form, 1) ?>"></div>
                                    <div class="col-md-3 col-6"><label for="geminal" class="form-label">Geminal (Perna)</label><input type="text" inputmode="decimal" class="form-control" id="geminal" name="geminal" value="<?= getFieldValue('geminal', $client_data_for_form, 1) ?>"></div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-4 mb-3">
                            <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-plus-circle me-2"></i> Registar Nova Avaliação</button>
                            <!-- Link de voltar dinâmico -->
                            <a href="<?= htmlspecialchars($user_role === 'trainer' ? 'my_clients.php' : 'client_list_nutritionist.php') ?>" class="btn btn-secondary btn-lg"><i class="fas fa-arrow-left me-2"></i>Voltar</a>
                        </div>

                    </form>

                <?php elseif (empty($page_error)): // Se não houve erro fatal mas ID era inválido inicialmente ?>
                    <div class="alert alert-warning">ID do cliente inválido ou não fornecido.</div>
                     <div class="text-center mt-4 mb-3">
                         <a href="<?= htmlspecialchars($user_role === 'trainer' ? 'my_clients.php' : 'client_list_nutritionist.php') ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
                    </div>
                <?php endif; ?>

            </div><!-- /.card-body -->
        </div><!-- /.card -->
    </div><!-- /.container -->
</main> <!-- /.main-content -->

<?php
include 'footer.php';
?>
