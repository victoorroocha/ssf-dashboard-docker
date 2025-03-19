<?php
$host = getenv('DB_HOST');
$dbname = getenv('DB_NAME'); 
$user = getenv('DB_USER'); 
$password = getenv('DB_PASSWORD'); 

try {
    // Criação da conexão PDO com PostgreSQL
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
    echo "Conectado ao PostgreSQL com sucesso!";
} catch (PDOException $e) {
    die("Erro ao conectar: " . $e->getMessage()); 
}
?>
