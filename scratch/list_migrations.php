<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=3308;dbname=emute", "root", "");
    $stmt = $pdo->query("SELECT migration FROM migrations");
    if ($stmt) {
        while ($row = $stmt->fetch()) {
            echo $row[0] . "\n";
        }
    } else {
        echo "Table 'migrations' not found.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
