<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redireciona para a página de login se não estiver logado
    exit;
}

// Verifica se o ID da aula foi fornecido
if (!isset($_GET['class_id'])) {
    header('Location: classes.php'); // Redireciona se o ID da aula não for fornecido
    exit;
}

$class_id = $_GET['class_id'];
$user_id = $_SESSION['user_id'];

// Inclui a conexão com o banco de dados
include 'db.php';

// Verifica se o usuário já está inscrito na aula
$stmt = $pdo->prepare("SELECT * FROM class_participants WHERE class_id = ? AND user_id = ?");
$stmt->execute([$class_id, $user_id]);
$already_enrolled = $stmt->fetch();

if ($already_enrolled) {
    // Se já estiver inscrito, exibe uma mensagem
    echo "<script>alert('Você já está inscrito nesta aula.'); window.location.href = 'classes.php';</script>";
    exit;
}

// Inscreve o usuário na aula
$stmt = $pdo->prepare("INSERT INTO class_participants (class_id, user_id) VALUES (?, ?)");
$stmt->execute([$class_id, $user_id]);

// Redireciona de volta para a página de aulas com uma mensagem de sucesso
echo "<script>alert('Inscrição realizada com sucesso!'); window.location.href = 'classes.php';</script>";
exit;
?>