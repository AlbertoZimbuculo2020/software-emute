<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=3308", "root", "");
    $stmt = $pdo->query("SHOW DATABASES");
    while ($row = $stmt->fetch()) {
        echo $row[0] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
