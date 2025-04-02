<?php
// Configurações do banco de dados
$host = 'localhost';      
$dbname = 'Projeto_PAP'; 
$username = 'root';       
$password = '';          

try {
    // Cria a conexão PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // Configura o PDO para lançar exceções em caso de erros
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Configura o PDO para retornar resultados como arrays associativos por padrão
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Em caso de erro na conexão, exibe uma mensagem e interrompe a execução
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}
?>