<?php
session_start();
include 'db.php';
include 'header.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Find the user with the given token
    $stmt = $pdo->prepare("SELECT id, first_login FROM users WHERE token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        // Token is valid
        // Update the token to NULL, but keep first_login as 1
        $update_stmt = $pdo->prepare("UPDATE users SET token = NULL WHERE id = ?");
        $update_stmt->execute([$user['id']]);

        // Redirect to login page
        echo "<script>alert('Conta confirmada com sucesso! Faça login para continuar.'); window.location.href = 'login.php';</script>";
        exit;
    } else {
        // Invalid token
        echo "<script>alert('Token inválido.'); window.location.href = 'login.php';</script>";
        exit;
    }
} else {
    // No token provided
    echo "<script>alert('Token não fornecido.'); window.location.href = 'login.php';</script>";
    exit;
}

include 'footer.php';
?>
