<?php
// scripts/cleanup_access_delegations.php
// This script should be run by a cron job.
// Ensure the path to db.php is correct.
require_once __DIR__ . '/../db.php'; // Adjust path as necessary

try {
    // Update to use new table and column names
    $stmt = $pdo->prepare("UPDATE access_delegations SET is_active = FALSE WHERE is_active = TRUE AND expires_at <= NOW()");
    $stmt->execute();
    $num_deactivated = $stmt->rowCount();
    echo "Expired access delegations deactivated: " . $num_deactivated . "\n";
} catch (PDOException $e) {
    error_log("Error in cleanup_access_delegations script: " . $e->getMessage());
    echo "Error deactivating delegations: " . $e->getMessage() . "\n";
}
?>