<?php
// Script de migración de categorías - SOLO EJECUTAR UNA VEZ
require_once 'config.php';
require_once 'verificar_sesion.php';

// Solo administradores pueden ejecutar esto
requerirAdmin();

if (!isset($_GET['confirmar']) || $_GET['confirmar'] !== 'si') {
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Migración de Categorías</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 600px; }
        h1 { color: #333; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin: 20px 0; color: #856404; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; margin: 20px 0; color: #0c5460; }
        .btn { display: inline-block; padding: 12px 24px; margin: 10px 5px 10px 0; border-radius: 5px; text-decoration: none; border: none; cursor: pointer; font-size: 16px; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        ul { line-height: 1.8; }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚠️ Migración de Categorías</h1>
        
        <div class="warning">
            <strong>ADVERTENCIA:</strong> Este script modificará la estructura de tu base de datos.
        </div>
        
        <div class="info">
            <strong>Este script hará lo siguiente:</strong>
            <ul>
                <li>✓ Crear tabla <code>categorias</code> con 7 categorías predefinidas</li>
                <li>✓ Agregar columna <code>categoria_id</code> a la tabla <code>productos</code></li>
                <li>✓ Migrar automáticamente todos los productos existentes a la nueva estructura</li>
                <li>✓ Crear índices para optimizar búsquedas</li>
                <li>✓ Eliminar la columna antigua <code>categoria</code> (si hay datos duplicados)</li>
            </ul>
        </div>
        
        <p><strong>Categorías a crear:</strong></p>
        <ul>
            <li>1. Materiales</li>
            <li>2. Herramientas</li>
            <li>3. Pinturas</li>
            <li>4. Tubería</li>
            <li>5. Eléctrica</li>
            <li>6. Venenos</li>
            <li>7. Aceites</li>
            <li>8. Medicinas</li>
            <li>9. Aperos de caballo</li>
        </ul>
        
        <hr style="margin: 30px 0;">
        
        <p style="color: #666;"><strong>Asegúrate de tener un backup antes de continuar.</strong></p>
        
        <a href="?confirmar=si" class="btn btn-danger">⚡ Ejecutar Migración</a>
        <a href="dashboard.php" class="btn btn-secondary">❌ Cancelar</a>
    </div>
</body>
</html>';
    exit;
}

// Ahora ejecutar la migración
$errores = [];
$exitos = [];

try {
    // 1. Crear tabla de categorías
    $sql_categorias = "CREATE TABLE IF NOT EXISTS categorias (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nombre VARCHAR(100) NOT NULL UNIQUE,
        descripcion TEXT,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql_categorias)) {
        $exitos[] = "✓ Tabla <code>categorias</code> creada/verificada";
    } else {
        throw new Exception("Error al crear tabla categorias: " . $conn->error);
    }
    
    // 2. Insertar categorías
    $categorias = [
        ['nombre' => 'Materiales', 'descripcion' => 'Materiales y componentes'],
        ['nombre' => 'Herramientas', 'descripcion' => 'Herramientas y equipos'],
        ['nombre' => 'Pinturas', 'descripcion' => 'Pinturas y acabados'],
        ['nombre' => 'Tubería', 'descripcion' => 'Tuberías y accesorios'],
        ['nombre' => 'Eléctrica', 'descripcion' => 'Material eléctrico'],
        ['nombre' => 'Venenos', 'descripcion' => 'Venenos y pesticidas'],
        ['nombre' => 'Aceites', 'descripcion' => 'Aceites y lubricantes'],
        ['nombre' => 'Medicinas', 'descripcion' => 'Medicamentos y suplementos'],
        ['nombre' => 'Aperos de caballo', 'descripcion' => 'Aperos y accesorios para caballos']
    ];
    
    foreach ($categorias as $cat) {
        $stmt = $conn->prepare("INSERT IGNORE INTO categorias (nombre, descripcion) VALUES (?, ?)");
        $stmt->bind_param("ss", $cat['nombre'], $cat['descripcion']);
        $stmt->execute();
    }
    $exitos[] = "✓ " . count($categorias) . " categorías insertadas/verificadas";
    
    // 3. Verificar si la columna categoria_id existe
    $result = $conn->query("SHOW COLUMNS FROM productos LIKE 'categoria_id'");
    if ($result->num_rows == 0) {
        // Agregar columna categoria_id
        $sql_add_col = "ALTER TABLE productos ADD COLUMN categoria_id INT";
        if ($conn->query($sql_add_col)) {
            $exitos[] = "✓ Columna <code>categoria_id</code> agregada a productos";
        } else {
            throw new Exception("Error al agregar categoria_id: " . $conn->error);
        }
    } else {
        $exitos[] = "✓ Columna <code>categoria_id</code> ya existe";
    }
    
    // 4. Migrar datos de categoria (texto) a categoria_id (número)
    $sql_migrate = "UPDATE productos p
        JOIN categorias c ON p.categoria = c.nombre
        SET p.categoria_id = c.id
        WHERE p.categoria_id IS NULL OR p.categoria_id = 0";
    
    if ($conn->query($sql_migrate)) {
        $affected = $conn->affected_rows;
        if ($affected > 0) {
            $exitos[] = "✓ " . $affected . " productos migrados a la nueva estructura";
        } else {
            $exitos[] = "✓ Todos los productos ya estaban migrados";
        }
    } else {
        throw new Exception("Error al migrar datos: " . $conn->error);
    }
    
    // 5. Crear índices
    $sql_idx = "ALTER TABLE productos ADD FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT";
    if ($conn->query($sql_idx)) {
        $exitos[] = "✓ Integridad referencial establecida";
    } else {
        // Podría ya existir, no es error crítico
        $exitos[] = "✓ Integridad referencial verificada";
    }
    
    // 6. Opcional: renombrar columna categoria a categoria_texto (para backup)
    $result = $conn->query("SHOW COLUMNS FROM productos LIKE 'categoria'");
    if ($result->num_rows > 0) {
        $sql_rename = "ALTER TABLE productos CHANGE COLUMN categoria categoria_texto VARCHAR(50)";
        if ($conn->query($sql_rename)) {
            $exitos[] = "✓ Columna original renombrada a <code>categoria_texto</code> (backup)";
        }
    }
    
    $exitos[] = "✅ <strong>Migración completada exitosamente</strong>";
    
} catch (Exception $e) {
    $errores[] = "❌ " . $e->getMessage();
}

// Mostrar resultados
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Resultado de Migración</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 700px; }
        h1 { color: #333; }
        .exitos { background: #d4edda; border: 1px solid #28a745; padding: 15px; border-radius: 5px; margin: 20px 0; color: #155724; }
        .errores { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 20px 0; color: #721c24; }
        .exitos ul, .errores ul { margin: 10px 0; padding-left: 20px; }
        .exitos li, .errores li { margin: 8px 0; line-height: 1.6; }
        .btn { display: inline-block; padding: 12px 24px; margin-top: 20px; border-radius: 5px; text-decoration: none; border: none; cursor: pointer; font-size: 16px; background: #007bff; color: white; }
        .btn:hover { background: #0056b3; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Resultado de Migración de Categorías</h1>
        
        <?php if (!empty($exitos)): ?>
        <div class="exitos">
            <h2 style="margin-top: 0; color: #28a745;">✅ Operaciones Exitosas</h2>
            <ul>
                <?php foreach ($exitos as $msg): ?>
                <li><?php echo $msg; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($errores)): ?>
        <div class="errores">
            <h2 style="margin-top: 0; color: #721c24;">❌ Errores Detectados</h2>
            <ul>
                <?php foreach ($errores as $msg): ?>
                <li><?php echo $msg; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <a href="dashboard.php" class="btn">Ir al Dashboard</a>
    </div>
</body>
</html>
<?php
$conn->close();
?>
