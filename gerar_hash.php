<?php
require 'vendor/autoload.php';

use Laminas\Crypt\Password\Bcrypt;

$senha = '@novasenha2024';
$bcrypt = new Bcrypt();
$hash = $bcrypt->create($senha);

echo "Hash gerado: " . $hash . PHP_EOL;
