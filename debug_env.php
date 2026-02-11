<?php
// Archivo para debuggear variables de entorno en Railway
header('Content-Type: text/plain');

echo "=== Variables de entorno disponibles ===\n\n";

// Buscar variables relacionadas con MySQL
$mysql_vars = [];
foreach ($_ENV as $key => $value) {
    if (stripos($key, 'MYSQL') !== false || stripos($key, 'DATABASE') !== false) {
        $mysql_vars[$key] = $value;
    }
}

echo "Variables MySQL/Database encontradas:\n";
if (empty($mysql_vars)) {
    echo "NINGUNA VARIABLE MYSQL ENCONTRADA\n\n";
} else {
    foreach ($mysql_vars as $key => $value) {
        // Ocultar parcialmente contraseñas
        if (stripos($key, 'PASS') !== false) {
            $value = substr($value, 0, 3) . '***';
        }
        echo "$key = $value\n";
    }
}

echo "\n--- Probando getenv() ---\n";
$test_vars = [
    'MYSQL_MYSQLHOST',
    'MYSQLHOST', 
    'DATABASE_URL',
    'MYSQL_URL',
    'MYSQL_MYSQLUSER',
    'MYSQLUSER'
];

foreach ($test_vars as $var) {
    $val = getenv($var);
    if ($val !== false) {
        if (stripos($var, 'PASS') !== false) {
            $val = substr($val, 0, 3) . '***';
        }
        echo "$var = $val\n";
    } else {
        echo "$var = NO ENCONTRADA\n";
    }
}

echo "\n=== Todas las variables de entorno ===\n";
foreach ($_ENV as $key => $value) {
    if (stripos($key, 'PASS') !== false || stripos($key, 'SECRET') !== false || stripos($key, 'KEY') !== false) {
        $value = substr($value, 0, 3) . '***';
    }
    echo "$key = $value\n";
}
