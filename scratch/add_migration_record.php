<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=3308;dbname=emute", "root", "");
    $pdo->exec("INSERT INTO migrations (migration, batch) VALUES ('2026_05_03_154940_add_be_columns_to_triagem', 1)");
    echo "Migration record '2026_05_03_154940_add_be_columns_to_triagem' added to database.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
