<?php
session_start();
include 'db.php'; // Inclui a conexão com o banco de dados

header('Content-Type: application/json'); // Define o tipo de resposta como JSON

// --- Constantes de Configuração ---
define('UPLOAD_DIR_BASE', 'uploads/chat_media/'); // Diretório base relativo ao script
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50 MB (ajuste conforme necessário)
define('ALLOWED_MIMES', [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
    'audio/mpeg', 'audio/ogg', 'audio/wav', 'audio/aac', 'audio/mp4', // mp4 pode ser audio
    'video/mp4', 'video/webm', 'video/ogg'
]);
// Mapeamento de MIME para subdiretório e tipo de mensagem
define('MIME_MAP', [
    'image' => ['dir' => 'images', 'type' => 'image'],
    'audio' => ['dir' => 'audio', 'type' => 'audio'],
    'video' => ['dir' => 'video', 'type' => 'video']
]);
// Caminho base da URL para acessar os arquivos (IMPORTANTE: Ajuste se necessário!)
// Se o seu projeto está em http://localhost/Projeto_PAP/, este caminho está correto.
// Se estiver em http://localhost/, remova /Projeto_PAP
define('BASE_FILE_URL', '/Projeto_PAP/');


// --- Verificações Iniciais ---
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Não autenticado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
    exit;
}

if (!isset($_POST['receiver_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Destinatário não especificado.']);
    exit;
}

// --- Obtenção e Validação dos Dados ---
$sender_id = $_SESSION['user_id'];
$receiver_id = filter_var($_POST['receiver_id'], FILTER_VALIDATE_INT);
$caption = trim($_POST['message'] ?? ''); // Mensagem de texto ou legenda do arquivo

if (!$receiver_id) {
    echo json_encode(['status' => 'error', 'message' => 'Destinatário inválido.']);
    exit;
}

if ($sender_id == $receiver_id) {
     echo json_encode(['status' => 'error', 'message' => 'Não pode enviar mensagens para si mesmo.']);
     exit;
}

// --- Processamento do Arquivo (se houver) ---
$message_type = 'text'; // Tipo padrão
$file_url = null;
$file_name_original = null;
$file_size = null;
$duration = null; // Duração (pode ser implementado depois com bibliotecas como getID3)

// Verifica se um arquivo foi enviado e se não houve erro no upload
if (isset($_FILES['chat_file']) && $_FILES['chat_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['chat_file'];
    $file_tmp_path = $file['tmp_name'];
    $file_name_original = basename($file['name']); // Nome original do arquivo
    $file_size = $file['size'];
    $file_mime_type = mime_content_type($file_tmp_path); // Obtém o tipo MIME real

    // 1. Validação do Tipo MIME
    if (!in_array($file_mime_type, ALLOWED_MIMES)) {
        echo json_encode(['status' => 'error', 'message' => 'Tipo de ficheiro não permitido (' . htmlspecialchars($file_mime_type) . ').']);
        exit;
    }

    // 2. Validação do Tamanho
    if ($file_size > MAX_FILE_SIZE) {
        echo json_encode(['status' => 'error', 'message' => 'Ficheiro demasiado grande (Máx: ' . (MAX_FILE_SIZE / 1024 / 1024) . ' MB).']);
        exit;
    }

    // 3. Determinar Subdiretório e Tipo de Mensagem
    $file_category = null;
    foreach (MIME_MAP as $category => $map) {
        if (strpos($file_mime_type, $category . '/') === 0) {
            $file_category = $category;
            break;
        }
    }

    if (!$file_category) {
        // Segurança extra, caso mime_content_type retorne algo inesperado mas permitido
        error_log("Tipo MIME permitido mas não mapeado: " . $file_mime_type);
        echo json_encode(['status' => 'error', 'message' => 'Erro ao categorizar o tipo de ficheiro.']);
        exit;
    }

    $sub_dir = MIME_MAP[$file_category]['dir'];
    $message_type = MIME_MAP[$file_category]['type'];

    // 4. Gerar Nome Único e Caminho de Destino
    $file_extension = strtolower(pathinfo($file_name_original, PATHINFO_EXTENSION));
    $unique_filename = uniqid('chat_' . $sender_id . '_', true) . '.' . $file_extension;
    $upload_path_relative = UPLOAD_DIR_BASE . $sub_dir . '/'; // Caminho relativo ao script
    $upload_path_full = $upload_path_relative . $unique_filename; // Caminho completo no sistema de ficheiros

    // Garante que o diretório de upload existe
    if (!is_dir($upload_path_relative)) {
        if (!mkdir($upload_path_relative, 0775, true)) { // Cria recursivamente com permissões mais seguras
             error_log("Falha ao criar diretório de upload: " . $upload_path_relative);
             echo json_encode(['status' => 'error', 'message' => 'Erro interno ao preparar o upload.']);
             exit;
        }
    }

    // 5. Mover o Arquivo Carregado
    if (!move_uploaded_file($file_tmp_path, $upload_path_full)) {
        error_log("Falha ao mover ficheiro carregado de {$file_tmp_path} para {$upload_path_full}");
        echo json_encode(['status' => 'error', 'message' => 'Erro ao guardar o ficheiro carregado.']);
        exit;
    }

    // 6. Definir a URL Acessível pela Web
    // Concatena o caminho base da URL com o caminho relativo do arquivo
    $file_url = BASE_FILE_URL . $upload_path_relative . $unique_filename;

} elseif (isset($_FILES['chat_file']) && $_FILES['chat_file']['error'] !== UPLOAD_ERR_NO_FILE) {
    // Se houve um erro de upload (diferente de nenhum arquivo enviado)
    $upload_error_code = $_FILES['chat_file']['error'];
    // Mapear códigos de erro para mensagens amigáveis (opcional)
    $error_message = 'Erro desconhecido durante o upload do ficheiro (Código: ' . $upload_error_code . ').';
    // Logar o erro real
    error_log("Erro de upload de ficheiro no chat: Código " . $upload_error_code);
    echo json_encode(['status' => 'error', 'message' => $error_message]);
    exit;
}

// --- Validação Final ---
// Não permite enviar mensagem completamente vazia (sem texto E sem ficheiro)
if ($message_type === 'text' && empty($caption)) {
    echo json_encode(['status' => 'error', 'message' => 'A mensagem não pode estar vazia.']);
    exit;
}

// --- Inserção no Banco de Dados ---
try {
    // Prepara a inserção com as novas colunas
    $stmt = $pdo->prepare("
        INSERT INTO messages
            (sender_id, receiver_id, message_type, message, file_url, file_name, file_size, duration)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    // Executa a inserção
    // Usa $caption para a coluna 'message'. Se for vazio e houver ficheiro, guarda NULL.
    // $duration é NULL por agora.
    $stmt->execute([
        $sender_id,
        $receiver_id,
        $message_type,
        ($caption ?: null), // Guarda NULL se $caption for vazio
        $file_url,
        $file_name_original,
        $file_size,
        $duration
    ]);

    // Verifica se a inserção foi bem-sucedida
    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Mensagem enviada com sucesso.']);
    } else {
        // Este caso é raro se não houver exceção, mas pode acontecer
        error_log("send_message.php: rowCount foi 0 após INSERT sem exceção.");
        echo json_encode(['status' => 'error', 'message' => 'Falha ao guardar a mensagem (sem erro específico).']);
    }
} catch (PDOException $e) {
    // Em caso de erro no banco de dados, registra o erro e retorna uma mensagem genérica
    error_log("Erro DB ao enviar mensagem (send_message.php): " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Erro interno ao processar a mensagem.']);
}
?>
