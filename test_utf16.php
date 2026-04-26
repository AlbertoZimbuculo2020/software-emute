<?php
$p = '000000';
echo "PLAIN: " . hash('sha512', $p) . "\n";
echo "UTF-16LE: " . hash('sha512', mb_convert_encoding($p, 'UTF-16LE')) . "\n";
