<?php
// Em includes/auth_utils.php

// Se $GLOBALS['available_permissions'] não for definido em outro lugar, defina aqui.
if (!isset($GLOBALS['available_permissions'])) {
    $GLOBALS['available_permissions'] = [
        'manage_clients' => 'Gerir Clientes (Admin)',
        'manage_classes' => 'Gerir Aulas (Admin)',
        'manage_trainers' => 'Gerir Treinadores (Admin)',
        'manage_nutritionists' => 'Gerir Nutricionistas (Admin)',
        'manage_receptionists' => 'Gerir Recepcionistas (Admin)',
        'view_admin_dashboard' => 'Aceder Painel Admin (Estatísticas)',
        // Adicione outras permissões conforme necessário
        // 'access_financial_reports' => 'Aceder Relatórios Financeiros',
    ];
}


/**
 * Verifica se o usuário tem uma permissão específica, seja diretamente ou por delegação.
 *
 * @param PDO $pdo Objeto de conexão PDO.
 * @param int $user_id ID do usuário a ser verificado.
 * @param string $permission_key A chave da permissão (ex: 'manage_clients').
 * @return bool True se tiver a permissão, false caso contrário.
 */
function user_has_specific_permission(PDO $pdo, int $user_id, string $permission_key): bool {
    // 1. Verifica se o usuário é um administrador direto (tem todas as permissões)
    // Assume que $_SESSION['user_id'] e $_SESSION['role'] estão definidos
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
        return true;
    }

    // 2. Verifica delegações ativas para esta permissão específica
    try {
        $stmt = $pdo->prepare("
            SELECT ad.delegated_permissions
            FROM access_delegations ad
            JOIN users u_admin ON ad.granting_admin_id = u_admin.id
            WHERE ad.delegated_user_id = :user_id
              AND ad.is_active = TRUE
              AND ad.expires_at > NOW()
              AND u_admin.role = 'admin' -- Garante que quem delegou ainda é admin
        ");
        $stmt->execute(['user_id' => $user_id]);
        $delegations = $stmt->fetchAll();

        // --- DEBUGGING (Comentado) ---
        // echo "<li style='color: cyan; font-size: 0.8em; padding: 5px;'>DEBUG AUTH: Checking user $user_id for permission '$permission_key'. Found " . count($delegations) . " active delegations matching basic criteria.</li>";
        // --- DEBUGGING END ---

        foreach ($delegations as $delegation) {
            if (!empty($delegation['delegated_permissions'])) {
                $delegated_perms_array = json_decode($delegation['delegated_permissions'], true);
                // Verifica se json_decode foi bem sucedido e se a permissão está no array
                if (is_array($delegated_perms_array) && in_array($permission_key, $delegated_perms_array)) {
                    // --- DEBUGGING (Comentado) ---
                    // echo "<li style='color: lightgreen; font-size: 0.8em; padding: 5px;'>DEBUG AUTH: Permission '$permission_key' GRANTED via delegation.</li>";
                    return true; // Permissão específica encontrada na delegação
                } else {
                    // --- DEBUGGING (Comentado) ---
                    // echo "<li style='color: orange; font-size: 0.8em; padding: 5px;'>DEBUG AUTH: Delegation found, but permission check failed for '$permission_key'.</li>";
                    // echo "<li style='color: orange; font-size: 0.8em; padding: 5px;'>DEBUG AUTH: Raw permissions: " . htmlspecialchars($delegation['delegated_permissions']) . "</li>";
                    // echo "<li style='color: orange; font-size: 0.8em; padding: 5px;'>DEBUG AUTH: Decoded array (type " . gettype($delegated_perms_array) . "): " . htmlspecialchars(json_encode($delegated_perms_array)) . "</li>";
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Erro ao verificar permissão específica delegada: " . $e->getMessage());
        return false; // Em caso de erro, nega o acesso por segurança
    }

    return false;
}

/**
 * Obtém informações da delegação ativa para o usuário, se houver.
 * Pode ser ajustado para mostrar quais permissões estão delegadas.
 *
 * @param PDO $pdo Objeto de conexão PDO.
 * @param int $user_id ID do usuário delegado.
 * @return array|null Retorna os dados da delegação ou null se não houver.
 */
function obter_info_delegacao_ativa(PDO $pdo, int $user_id): ?array {

    try {
        $stmt = $pdo->prepare("
            SELECT ad.expires_at, ad.delegated_permissions, u_admin.name as admin_concedente_name
            FROM access_delegations ad
            JOIN users u_admin ON ad.granting_admin_id = u_admin.id
            WHERE ad.delegated_user_id = :user_id
              AND ad.is_active = TRUE
              AND ad.expires_at > NOW()
              AND u_admin.role = 'admin'
            ORDER BY ad.created_at DESC -- Pega a delegação mais recente se houver múltiplas (embora a lógica de criação desative antigas)
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $user_id]);
        $delegation_info = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($delegation_info && !empty($delegation_info['delegated_permissions'])) {
            $delegation_info['delegated_permissions_array'] = json_decode($delegation_info['delegated_permissions'], true);
        } else if ($delegation_info) {
            $delegation_info['delegated_permissions_array'] = [];
        }

        return $delegation_info ?: null;

    } catch (PDOException $e) {
        error_log("Erro ao obter info da delegação ativa: " . $e->getMessage());
        return null;
    }
}
?>
