<?php
/**
 * Verificación de extensiones de PHP
 */
header('Content-Type: text/plain; charset=utf-8');

echo "=== Verificación de PHP ===\n\n";
echo "Versión de PHP: " . phpversion() . "\n\n";

$required_extensions = ['mysqli', 'pdo', 'pdo_mysql', 'json'];

echo "Extensiones Requeridas:\n";
foreach ($required_extensions as $ext) {
    $status = extension_loaded($ext) ? '✓' : '✗';
    echo "$status $ext\n";
}

echo "\nVariables de Entorno:\n";
echo "DATABASE_URL: " . (getenv('DATABASE_URL') ? 'Configurada' : 'No configurada') . "\n";
echo "MYSQL_URL: " . (getenv('MYSQL_URL') ? 'Configurada' : 'No configurada') . "\n";

echo "\nIntentando conexión a BD...\n";
try {
    require_once __DIR__ . '/config.php';
    echo "✓ Conexión exitosa\n";
    echo "Base de datos: " . DB_NAME . "\n";
    echo "Host: " . DB_HOST . "\n";
} catch (Exception $e) {
    echo "✗ Error en conexión: " . $e->getMessage() . "\n";
}
?>
