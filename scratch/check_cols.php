<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=3308;dbname=emute", "root", "");
    $tables = ['tb_triagem', 'tb_agendamento'];
    foreach ($tables as $table) {
        echo "Table: $table\n";
        $stmt = $pdo->query("DESCRIBE $table");
        while ($row = $stmt->fetch()) {
            echo " - " . $row[0] . "\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
