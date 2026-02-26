<?php
/**
 * Script de migración: Agregar sistema de devoluciones
 * Ejecutar una sola vez: http://localhost/ferreteria1/agregar_devoluciones.php
 */

require_once 'config.php';

echo "<h2>🔧 Migrando base de datos - Sistema de Devoluciones</h2>";

try {
    // Verificar si las tablas ya existen
    $result = $conn->query("SHOW TABLES LIKE 'devoluciones'");
    if ($result->num_rows > 0) {
        echo "<p style='color: orange;'>⚠️ Las tablas de devoluciones ya existen. No es necesario ejecutar esta migración.</p>";
        echo "<p><a href='gestionar_devoluciones.php' style='background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir a Devoluciones</a></p>";
        exit;
    }
    
    echo "<p>Creando estructuras necesarias...</p>";
    
    // 1. Verificar y agregar campos a la tabla ventas si no existen
    $result = $conn->query("SHOW COLUMNS FROM ventas LIKE 'cliente_email'");
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE ventas ADD COLUMN cliente_email VARCHAR(100) NULL AFTER cliente_cedula";
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✅ Columna 'cliente_email' agregada a ventas</p>";
        }
    } else {
        echo "<p style='color: gray;'>ℹ️ Columna 'cliente_email' ya existe</p>";
    }
    
    $result = $conn->query("SHOW COLUMNS FROM ventas LIKE 'cliente_telefono'");
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE ventas ADD COLUMN cliente_telefono VARCHAR(20) NULL AFTER cliente_email";
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✅ Columna 'cliente_telefono' agregada a ventas</p>";
        }
    } else {
        echo "<p style='color: gray;'>ℹ️ Columna 'cliente_telefono' ya existe</p>";
    }
    
    // 2. Crear tabla de devoluciones
    $sql_devoluciones = "CREATE TABLE IF NOT EXISTS devoluciones (
        id INT PRIMARY KEY AUTO_INCREMENT,
        venta_id INT NOT NULL,
        numero_devolucion VARCHAR(50) UNIQUE NOT NULL,
        motivo TEXT NOT NULL,
        total_devuelto DECIMAL(10,2) NOT NULL,
        usuario_id INT,
        fecha_devolucion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
    )";
    
    if ($conn->query($sql_devoluciones)) {
        echo "<p style='color: green;'>✅ Tabla 'devoluciones' creada correctamente</p>";
    } else {
        throw new Exception("Error al crear tabla devoluciones: " . $conn->error);
    }
    
    // 3. Crear tabla de detalles de devolución
    $sql_detalles = "CREATE TABLE IF NOT EXISTS detalles_devolucion (
        id INT PRIMARY KEY AUTO_INCREMENT,
        devolucion_id INT NOT NULL,
        detalle_venta_id INT NOT NULL,
        producto_id INT NOT NULL,
        cantidad_devuelta INT NOT NULL,
        precio_unitario DECIMAL(10,2) NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (devolucion_id) REFERENCES devoluciones(id) ON DELETE CASCADE,
        FOREIGN KEY (detalle_venta_id) REFERENCES detalles_venta(id) ON DELETE CASCADE,
        FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql_detalles)) {
        echo "<p style='color: green;'>✅ Tabla 'detalles_devolucion' creada correctamente</p>";
    } else {
        throw new Exception("Error al crear tabla detalles_devolucion: " . $conn->error);
    }
    
    // 4. Crear índices para mejorar el rendimiento
    $indices = [
        "CREATE INDEX idx_devoluciones_venta ON devoluciones(venta_id)",
        "CREATE INDEX idx_devoluciones_fecha ON devoluciones(fecha_devolucion)",
        "CREATE INDEX idx_detalles_devolucion ON detalles_devolucion(devolucion_id)"
    ];
    
    foreach ($indices as $index_sql) {
        if ($conn->query($index_sql)) {
            echo "<p style='color: green;'>✅ Índice creado correctamente</p>";
        }
        // No lanzamos error si el índice ya existe
    }
    
    echo "<hr>";
    echo "<h3 style='color: green;'>🎉 Migración completada exitosamente</h3>";
    echo "<div style='background: #e8f5e9; padding: 20px; border-radius: 8px; margin-top: 20px;'>";
    echo "<h4>✅ Sistema de Devoluciones Instalado</h4>";
    echo "<p>Ahora puedes:</p>";
    echo "<ul>";
    echo "<li>✓ Procesar devoluciones de productos</li>";
    echo "<li>✓ El inventario se ajusta automáticamente</li>";
    echo "<li>✓ Se mantiene historial completo</li>";
    echo "<li>✓ Se registran todos los movimientos</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='margin-top: 20px;'>";
    echo "<p><strong>⚠️ Importante:</strong> Esta migración solo debe ejecutarse una vez. Puedes eliminar este archivo ahora.</p>";
    echo "<a href='gestionar_devoluciones.php' style='background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-right: 10px;'>📦 Ir a Devoluciones</a>";
    echo "<a href='dashboard.php' style='background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>📊 Ir al Dashboard</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #ffebee; padding: 20px; border-radius: 8px; margin-top: 20px;'>";
    echo "<p style='color: red;'>❌ Error en la migración:</p>";
    echo "<p style='color: red; font-weight: bold;'>" . $e->getMessage() . "</p>";
    echo "<p>Por favor, verifica:</p>";
    echo "<ul>";
    echo "<li>Que XAMPP y MySQL estén corriendo</li>";
    echo "<li>Que la base de datos 'fetteria_inventario' exista</li>";
    echo "<li>Que tengas permisos de administrador</li>";
    echo "</ul>";
    echo "</div>";
}

$conn->close();
?>
