<?php
/**
 * Sistema de Restauración de Backup
 * Permite subir y restaurar un archivo SQL de backup
 */

require_once 'verificar_sesion.php';
requerirAdmin(); // Solo administradores

require_once 'config.php';

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo_backup'])) {
    try {
        $archivo = $_FILES['archivo_backup'];
        
        // Validar archivo
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error al subir el archivo');
        }
        
        if ($archivo['size'] > 50 * 1024 * 1024) { // Máximo 50MB
            throw new Exception('El archivo es demasiado grande (máximo 50MB)');
        }
        
        $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        if ($extension !== 'sql') {
            throw new Exception('Solo se permiten archivos .sql');
        }
        
        // Leer contenido del archivo
        $sql_content = file_get_contents($archivo['tmp_name']);
        
        if (empty($sql_content)) {
            throw new Exception('El archivo está vacío');
        }
        
        // Confirmar restauración
        if (!isset($_POST['confirmar'])) {
            $error = '⚠️ ¡ATENCIÓN! Esta acción eliminará TODOS los datos actuales y los reemplazará con el backup. Esta acción NO se puede deshacer. Marca la casilla de confirmación.';
        } else {
            // Desactivar foreign keys temporalmente
            $conn->query("SET FOREIGN_KEY_CHECKS=0");
            
            // Dividir en queries individuales
            $queries = array_filter(
                array_map('trim', 
                    explode(';', $sql_content)
                )
            );
            
            $ejecutadas = 0;
            $errores = [];
            
            foreach ($queries as $query) {
                if (!empty($query) && substr($query, 0, 2) !== '--') {
                    if ($conn->query($query)) {
                        $ejecutadas++;
                    } else {
                        $errores[] = $conn->error;
                    }
                }
            }
            
            // Reactivar foreign keys
            $conn->query("SET FOREIGN_KEY_CHECKS=1");
            
            if (empty($errores)) {
                $mensaje = "✓ Backup restaurado exitosamente. $ejecutadas consultas ejecutadas.";
            } else {
                $error = "Restauración completada con algunos errores. $ejecutadas consultas ejecutadas. Errores: " . implode(', ', array_slice($errores, 0, 3));
            }
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurar Backup - Ferretería</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .warning-box {
            background-color: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .danger-box {
            background-color: #f8d7da;
            border: 2px solid #dc3545;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
    </style>
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
                <a href="backup_database.php" class="nav-link">💾 Backup de Datos</a>
                <a href="restaurar_backup.php" class="nav-link active">🔄 Restaurar Backup</a>
                <a href="crear_usuario.php" class="nav-link">👤 Crear Usuario</a>
                <a href="logout.php" class="nav-link" style="color: #e74c3c;">🚪 Cerrar Sesión</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <h1>🔄 Restaurar Backup</h1>
                <p>Restaura una copia de seguridad de la base de datos</p>
            </header>

            <?php if ($mensaje): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($mensaje); ?>
                    <br><br>
                    <a href="dashboard.php" class="btn btn-primary">Ir al Dashboard</a>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Advertencia -->
            <div class="danger-box">
                <h2 style="color: #dc3545; margin-top: 0;">⚠️ ¡ADVERTENCIA IMPORTANTE!</h2>
                <ul style="line-height: 1.8;">
                    <li><strong>Esta acción ELIMINARÁ todos los datos actuales</strong></li>
                    <li>Se perderán todas las ventas, productos, usuarios y movimientos actuales</li>
                    <li>Esta acción <strong>NO SE PUEDE DESHACER</strong></li>
                    <li><strong>Asegúrate de tener un backup reciente antes de continuar</strong></li>
                </ul>
            </div>

            <!-- Formulario de restauración -->
            <section class="card">
                <h2>Subir Archivo de Backup</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Archivo de Backup (.sql)</label>
                        <input type="file" name="archivo_backup" accept=".sql" required>
                        <small>Tamaño máximo: 50 MB</small>
                    </div>

                    <div class="form-group" style="margin-top: 20px;">
                        <label style="display: flex; align-items: center; gap: 10px;">
                            <input type="checkbox" name="confirmar" value="1" required>
                            <span style="color: #dc3545; font-weight: bold;">
                                Confirmo que entiendo que esta acción eliminará TODOS los datos actuales y NO se puede deshacer
                            </span>
                        </label>
                    </div>

                    <div style="margin-top: 30px; display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-danger">
                            🔄 Restaurar Backup
                        </button>
                        <a href="backup_database.php" class="btn btn-secondary">
                            ← Volver a Backups
                        </a>
                    </div>
                </form>
            </section>

            <!-- Instrucciones -->
            <section class="card">
                <h3>📋 Instrucciones</h3>
                <ol style="margin-left: 20px; line-height: 1.8;">
                    <li><strong>Genera un backup de seguridad actual</strong> antes de restaurar (por si acaso)</li>
                    <li>Selecciona el archivo .sql que descargaste previamente</li>
                    <li>Marca la casilla de confirmación</li>
                    <li>Haz clic en "Restaurar Backup"</li>
                    <li>Espera a que el proceso termine (puede tardar varios segundos)</li>
                    <li>Verifica que los datos se hayan restaurado correctamente</li>
                </ol>
            </section>

            <!-- Cuándo usar esta función -->
            <div class="warning-box">
                <h3>🤔 ¿Cuándo usar la restauración?</h3>
                <ul style="line-height: 1.8;">
                    <li>Si perdiste datos por error</li>
                    <li>Si la base de datos se corrompió</li>
                    <li>Si necesitas volver a un estado anterior</li>
                    <li>Si migraste a un nuevo servidor</li>
                    <li>Para pruebas o desarrollo con datos reales</li>
                </ul>
            </div>
        </main>
    </div>
    <script src="check-updates.js"></script>
</body>
</html>
