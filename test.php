<?php
// Testando se as variáveis de ambiente estão sendo passadas corretamente
echo "DB_HOST: " . getenv('DB_HOST') . "<br>";
echo "DB_USER: " . getenv('DB_USER') . "<br>";
echo "DB_PASSWORD: " . getenv('DB_PASSWORD') . "<br>";
echo "DB_NAME: " . getenv('DB_NAME') . "<br>";

// Tentando a conexão com o banco
try {
    $pdo = new PDO("pgsql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_NAME'), getenv('DB_USER'), getenv('DB_PASSWORD'));
    echo "Conexão com o banco de dados estabelecida com sucesso!";
} catch (PDOException $e) {
    echo "Erro na conexão com o banco: " . $e->getMessage();
}
?>
