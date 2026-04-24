<?php

$search_terms = ['paciente', 'medico', 'seguradora', 'agendamento', 'consulta', 'triagem', 'servico', 'artigo'];
$found_tables = [];

$handle = fopen('emute.sql', 'r');
if ($handle) {
    $current_table = null;
    while (($line = fgets($handle)) !== false) {
        if (strpos($line, 'CREATE TABLE IF NOT EXISTS') !== false) {
            $parts = explode('`', $line);
            if (count($parts) > 1) {
                $current_table = $parts[1];
                $match = false;
                foreach ($search_terms as $term) {
                    if (stripos($current_table, $term) !== false) {
                        $match = true;
                        break;
                    }
                }
                if ($match) {
                    $found_tables[$current_table] = [];
                } else {
                    $current_table = null;
                }
            }
        } elseif ($current_table && isset($found_tables[$current_table]) && strpos($line, '`') !== false) {
            $parts = explode('`', $line);
            if (count($parts) > 1) {
                $col_name = $parts[1];
                if (!empty(trim($col_name)) && strpos($col_name, ' ') === false) {
                    $found_tables[$current_table][] = $col_name;
                }
            }
        } elseif ($current_table && strpos($line, ');') !== false) {
            $current_table = null;
        }
    }
    fclose($handle);
}

foreach ($found_tables as $table => $columns) {
    echo "Table: $table\n";
    echo "Columns: " . implode(', ', $columns) . "\n";
    echo "--------------------\n";
}
