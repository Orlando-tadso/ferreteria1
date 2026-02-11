<?php
/**
 * Sistema de Backup de Base de Datos
 * Genera un archivo SQL con todos los datos
 */

require_once 'verificar_sesion.php';
requerirAdmin(); // Solo administradores pueden hacer backups

require_once 'config.php';

// Crear directorio de backups si no existe
$backup_dir = __DIR__ . '/backups';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Verificar si se solicita descargar un backup
if (isset($_GET['download'])) {
    $archivo = basename($_GET['download']);
    $ruta_completa = $backup_dir . '/' . $archivo;
    
    if (file_exists($ruta_completa) && strpos($archivo, 'backup_') === 0) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $archivo . '"');
        header('Content-Length: ' . filesize($ruta_completa));
        readfile($ruta_completa);
        exit;
    }
}

// Verificar si se solicita eliminar un backup
if (isset($_GET['eliminar'])) {
    $archivo = basename($_GET['eliminar']);
    $ruta_completa = $backup_dir . '/' . $archivo;
    
    if (file_exists($ruta_completa) && strpos($archivo, 'backup_') === 0) {
        unlink($ruta_completa);
        header('Location: backup_database.php?mensaje=Backup eliminado');
        exit;
    }
}

$mensaje = '';
$error = '';

// Generar backup
if (isset($_POST['generar_backup'])) {
    try {
        $fecha = date('Y-m-d_H-i-s');
        $nombre_archivo = "backup_{$fecha}.sql";
        $ruta_archivo = $backup_dir . '/' . $nombre_archivo;
        
        // Obtener todas las tablas
        $tablas = [];
        $result = $conn->query("SHOW TABLES");
        while ($row = $result->fetch_array()) {
            $tablas[] = $row[0];
        }
        
        $backup_content = "-- Backup de Base de Datos\n";
        $backup_content .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
        $backup_content .= "-- Base de datos: " . DB_NAME . "\n\n";
        $backup_content .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        // Exportar cada tabla
        foreach ($tablas as $tabla) {
            // Estructura de la tabla
            $result = $conn->query("SHOW CREATE TABLE `$tabla`");
            $row = $result->fetch_array();
            
            $backup_content .= "\n-- Estructura de tabla: $tabla\n";
            $backup_content .= "DROP TABLE IF EXISTS `$tabla`;\n";
            $backup_content .= $row[1] . ";\n\n";
            
            // Datos de la tabla
            $result = $conn->query("SELECT * FROM `$tabla`");
            if ($result->num_rows > 0) {
                $backup_content .= "-- Datos de tabla: $tabla\n";
                
                while ($row = $result->fetch_assoc()) {
                    $valores = [];
                    foreach ($row as $valor) {
                        if ($valor === null) {
                            $valores[] = 'NULL';
                        } else {
                            $valores[] = "'" . $conn->real_escape_string($valor) . "'";
                        }
                    }
                    $backup_content .= "INSERT INTO `$tabla` VALUES (" . implode(', ', $valores) . ");\n";
                }
                $backup_content .= "\n";
            }
        }
        
        $backup_content .= "SET FOREIGN_KEY_CHECKS=1;\n";
        
        // Guardar archivo
        file_put_contents($ruta_archivo, $backup_content);
        
        $mensaje = "Backup generado exitosamente: $nombre_archivo";
    } catch (Exception $e) {
        $error = "Error al generar backup: " . $e->getMessage();
    }
}

// Listar backups existentes
$backups_existentes = [];
if (is_dir($backup_dir)) {
    $archivos = scandir($backup_dir);
    foreach ($archivos as $archivo) {
        if (strpos($archivo, 'backup_') === 0) {
            $ruta = $backup_dir . '/' . $archivo;
            $backups_existentes[] = [
                'nombre' => $archivo,
                'tamano' => filesize($ruta),
                'fecha' => date('Y-m-d H:i:s', filemtime($ruta))
            ];
        }
    }
    // Ordenar por fecha más reciente
    usort($backups_existentes, function($a, $b) {
        return strcmp($b['nombre'], $a['nombre']);
    });
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup de Base de Datos - Ferretería</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <h2>👨‍🔧 Ferretería</h2>
            </div>
            <nav class="nav-menu">
                <a href="dashboard.php" class="nav-link">📊 Dashboard</a>
                <a href="productos.php" class="nav-link">📦 Artículos</a>
                <a href="agregar_producto.php" class="nav-link">➕ Agregar Artículo</a>
                <a href="punto_venta.php" class="nav-link">🛒 Punto de Venta</a>
                <a href="movimientos.php" class="nav-link">📋 Movimientos</a>
                <a href="historial_ventas.php" class="nav-link">📊 Historial Ventas</a>
                <a href="bajo_stock.php" class="nav-link">⚠️ Bajo Stock</a>
                <hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">
                <a href="backup_database.php" class="nav-link active">💾 Backup de Datos</a>
                <a href="crear_usuario.php" class="nav-link">👤 Crear Usuario</a>
                <a href="logout.php" class="nav-link" style="color: #e74c3c;">🚪 Cerrar Sesión</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <h1>💾 Backup de Base de Datos</h1>
                <p>Genera y descarga copias de seguridad de todos tus datos</p>
            </header>

            <?php if ($mensaje): ?>
                <div class="alert alert-success">
                    ✓ <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    ✗ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Generar nuevo backup -->
            <section class="card">
                <h2>Generar Nuevo Backup</h2>
                <p>Crea una copia de seguridad completa de toda la base de datos (productos, ventas, usuarios, movimientos).</p>
                <form method="POST" style="margin-top: 20px;">
                    <button type="submit" name="generar_backup" class="btn btn-primary" onclick="return confirm('¿Generar backup ahora?')">
                        🔄 Generar Backup Ahora
                    </button>
                </form>
            </section>

            <!-- Lista de backups -->
            <section class="card">
                <h2>Backups Disponibles</h2>
                <?php if (empty($backups_existentes)): ?>
                    <p style="color: #666;">No hay backups disponibles. Genera uno usando el botón de arriba.</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Archivo</th>
                                <th>Fecha de Creación</th>
                                <th>Tamaño</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backups_existentes as $backup): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($backup['nombre']); ?></td>
                                    <td><?php echo $backup['fecha']; ?></td>
                                    <td><?php echo number_format($backup['tamano'] / 1024, 2); ?> KB</td>
                                    <td>
                                        <a href="backup_database.php?download=<?php echo urlencode($backup['nombre']); ?>" class="btn-small">
                                            ⬇️ Descargar
                                        </a>
                                        <a href="backup_database.php?eliminar=<?php echo urlencode($backup['nombre']); ?>" 
                                           class="btn-small btn-danger" 
                                           onclick="return confirm('¿Eliminar este backup?')">
                                            🗑️ Eliminar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>

            <!-- Información importante -->
            <section class="card" style="background-color: #fff3cd; border-left: 4px solid #ffc107;">
                <h3>⚠️ Información Importante</h3>
                <ul style="margin-left: 20px; line-height: 1.8;">
                    <li>Los backups se generan en formato SQL</li>
                    <li><strong>Descarga y guarda los backups en tu computadora o en la nube (Google Drive, Dropbox, etc.)</strong></li>
                    <li>Los backups en el servidor pueden perderse si hay problemas con Railway</li>
                    <li>Se recomienda hacer backups antes de realizar cambios importantes</li>
                    <li>Los backups incluyen: productos, ventas, usuarios, movimientos y toda la información</li>
                    <li><strong>Frecuencia recomendada:</strong> Diario si hay muchas ventas, semanal si hay poco movimiento</li>
                </ul>
            </section>

            <!-- Instrucciones de restauración -->
            <section class="card">
                <h3>🔧 ¿Cómo Restaurar un Backup?</h3>
                <ol style="margin-left: 20px; line-height: 1.8;">
                    <li>Descarga el archivo de backup (.sql)</li>
                    <li>Accede a Railway → MySQL → Database</li>
                    <li>Abre una conexión a la base de datos</li>
                    <li>Ejecuta el archivo SQL descargado</li>
                    <li>¡Todos los datos se restaurarán!</li>
                </ol>
                <p style="margin-top: 15px; color: #666;">
                    <strong>O también:</strong> Usa la página <a href="restaurar_backup.php">Restaurar Backup</a> para subir y restaurar automáticamente.
                </p>
            </section>
        </main>
    </div>
</body>
</html>
