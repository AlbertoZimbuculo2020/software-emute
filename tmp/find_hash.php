<?php
$password = '000000';
$target = 'd3b7bfd43497204f23d0629cb730d7d29143b6494b38e336ecd9a326c7374d0bafe10159260096eeae00c20a0737c0fa7817a2ffe7775d1fd8fb5a1e95d7e82d';

$salts = ['', 'emute', 'EMUTE', 'mute', 'hospital', 'clinic', 'admin', 'password', '123'];

foreach ($salts as $s) {
    $combos = [
        $password . $s,
        $s . $password,
        strtoupper($password . $s),
        strtolower($password . $s),
    ];
    
    foreach ($combos as $c) {
        if (hash('sha512', $c) === $target) die("FOUND PLAIN: $c\n");
        if (hash('sha512', mb_convert_encoding($c, 'UTF-16LE')) === $target) die("FOUND UTF-16LE: $c\n");
        if (hash('sha512', mb_convert_encoding($c, 'UTF-16BE')) === $target) die("FOUND UTF-16BE: $c\n");
        if (hash('sha512', md5($c)) === $target) die("FOUND SHA512(MD5): $c\n");
        if (hash('sha512', hash('sha512', $c)) === $target) die("FOUND DOUBLE: $c\n");
    }
}
echo "NOT FOUND\n";
