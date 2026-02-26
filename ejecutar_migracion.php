<?php
require_once 'config.php';

echo "=== EJECUTANDO MIGRACIÓN DE DEVOLUCIONES ===\n\n";

// Leer el archivo SQL
$sql_file = file_get_contents('migrar_devoluciones.sql');

// Dividir por comandos (separados por punto y coma)
$statements = array_filter(
    array_map('trim', explode(';', $sql_file)),
    function($stmt) {
        return !empty($stmt) && 
               !preg_match('/^--/', $stmt) && 
               $stmt !== 'USE fetteria_inventario';
    }
);

$success_count = 0;
$error_count = 0;

foreach ($statements as $statement) {
    // Limpiar comentarios
    $statement = preg_replace('/--.*$/m', '', $statement);
    $statement = trim($statement);
    
    if (empty($statement)) continue;
    
    echo "Ejecutando: " . substr($statement, 0, 80) . "...\n";
    
    if ($conn->query($statement)) {
        echo "✓ Éxito\n\n";
        $success_count++;
    } else {
        // Si el error es "tabla ya existe", no es crítico
        if (strpos($conn->error, 'already exists') !== false || 
            strpos($conn->error, 'Duplicate column') !== false) {
            echo "⚠ Ya existe (omitiendo): " . $conn->error . "\n\n";
            $success_count++;
        } else {
            echo "✗ Error: " . $conn->error . "\n\n";
            $error_count++;
        }
    }
}

echo "\n=== RESUMEN ===\n";
echo "✓ Comandos exitosos: $success_count\n";
echo "✗ Comandos con error: $error_count\n";

// Verificar que las tablas existen
echo "\n=== VERIFICANDO TABLAS ===\n";
$tables = ['devoluciones', 'detalles_devolucion'];

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "✓ Tabla '$table' existe\n";
        
        // Mostrar estructura
        $desc = $conn->query("DESCRIBE $table");
        echo "  Columnas: ";
        $cols = [];
        while ($row = $desc->fetch_assoc()) {
            $cols[] = $row['Field'];
        }
        echo implode(', ', $cols) . "\n";
    } else {
        echo "✗ Tabla '$table' NO existe\n";
    }
}

// Verificar columnas en ventas
echo "\n=== VERIFICANDO COLUMNAS EN VENTAS ===\n";
$result = $conn->query("DESCRIBE ventas");
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}

if (in_array('cliente_email', $columns)) {
    echo "✓ Columna 'cliente_email' existe\n";
} else {
    echo "✗ Columna 'cliente_email' NO existe\n";
}

if (in_array('cliente_telefono', $columns)) {
    echo "✓ Columna 'cliente_telefono' existe\n";
} else {
    echo "✗ Columna 'cliente_telefono' NO existe\n";
}

echo "\n=== MIGRACIÓN COMPLETADA ===\n";

$conn->close();
?>
