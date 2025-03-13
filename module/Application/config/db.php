<?php
$host = 'localhost';
$dbname = 'ssf_dashboard'; // Nome do banco de dados
$user = 'postgres'; // Seu usuário do PostgreSQL
$password = 'admin'; // A senha do seu banco PostgreSQL

try {
    // Criação da conexão PDO com PostgreSQL
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Configura para lançar exceções em caso de erro
    echo "Conectado ao PostgreSQL com sucesso!";
} catch (PDOException $e) {
    die("Erro ao conectar: " . $e->getMessage()); // Em caso de erro, exibe a mensagem de erro
}
?>
