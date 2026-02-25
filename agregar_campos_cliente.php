<?php
/**
 * Script de migración: Agregar campos de email y teléfono a ventas
 * Ejecutar una sola vez: http://localhost/ferreteria1/agregar_campos_cliente.php
 */

require_once 'config.php';

echo "<h2>Migrando base de datos...</h2>";

try {
    // Verificar si las columnas ya existen
    $result = $conn->query("SHOW COLUMNS FROM ventas LIKE 'cliente_email'");
    if ($result->num_rows > 0) {
        echo "<p style='color: orange;'>⚠️ Las columnas ya existen. No es necesario ejecutar esta migración.</p>";
        exit;
    }
    
    // Agregar columna cliente_email
    $sql1 = "ALTER TABLE ventas ADD COLUMN cliente_email VARCHAR(150) NULL AFTER cliente_cedula";
    if ($conn->query($sql1)) {
        echo "<p style='color: green;'>✅ Columna 'cliente_email' agregada correctamente</p>";
    } else {
        throw new Exception("Error al agregar cliente_email: " . $conn->error);
    }
    
    // Agregar columna cliente_telefono
    $sql2 = "ALTER TABLE ventas ADD COLUMN cliente_telefono VARCHAR(20) NULL AFTER cliente_email";
    if ($conn->query($sql2)) {
        echo "<p style='color: green;'>✅ Columna 'cliente_telefono' agregada correctamente</p>";
    } else {
        throw new Exception("Error al agregar cliente_telefono: " . $conn->error);
    }
    
    echo "<h3 style='color: green;'>🎉 Migración completada exitosamente</h3>";
    echo "<p>Las ventas ahora pueden incluir correo electrónico y teléfono del cliente.</p>";
    echo "<p><strong>Importante:</strong> Esta migración solo debe ejecutarse una vez. Puedes eliminar este archivo ahora.</p>";
    echo "<p><a href='dashboard.php' style='background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir al Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

$conn->close();
?>
