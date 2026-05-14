<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=3308;dbname=emute", "root", "");
    $stmt = $pdo->query("SELECT DESCRICAO, IMAGEM FROM tb_empresa LIMIT 1");
    $row = $stmt->fetch();
    if ($row) {
        echo "Name: " . $row['DESCRICAO'] . "\n";
        echo "Logo size: " . strlen($row['IMAGEM']) . " bytes\n";
    } else {
        echo "No record in tb_empresa.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
