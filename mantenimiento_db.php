<?php
/**
 * Script de Mantenimiento de Base de Datos
 * Ejecutar periódicamente (semanal o mensual)
 * 
 * Uso: Acceder en navegador:
 * http://localhost/ferreteria1/mantenimiento_db.php
 */

require_once 'verificar_sesion.php';
require_once 'config.php';

// Verificar que sea admin
if ($_SESSION['rol'] !== 'admin') {
    die("Acceso denegado. Solo administradores pueden ejecutar mantenimiento.");
}

if (!isset($_GET['accion'])) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Mantenimiento de Base de Datos</title>
        <link rel="stylesheet" href="styles.css">
        <style>
            .mantenimiento-container {
                max-width: 800px;
                margin: 40px auto;
                padding: 30px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .boton-mantenimiento {
                display: block;
                width: 100%;
                padding: 15px;
                margin: 10px 0;
                background: #3498db;
                color: white;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-size: 16px;
                text-decoration: none;
                text-align: center;
            }
            .boton-mantenimiento:hover {
                background: #2980b9;
            }
            .boton-peligro {
                background: #e74c3c;
            }
            .boton-peligro:hover {
                background: #c0392b;
            }
            .boton-exito {
                background: #27ae60;
            }
            .boton-exito:hover {
                background: #229954;
            }
            .resultado {
                margin-top: 30px;
                padding: 20px;
                background: #f0f0f0;
                border-radius: 5px;
                border-left: 4px solid #3498db;
            }
            .resultado.exito {
                border-left-color: #27ae60;
                background: #e8f8f5;
            }
            .resultado.error {
                border-left-color: #e74c3c;
                background: #fadbd8;
            }
            table {
                width: 100%;
                margin-top: 15px;
                border-collapse: collapse;
            }
            table th, table td {
                padding: 10px;
                text-align: left;
                border-bottom: 1px solid #ddd;
            }
            table th {
                background: #f5f5f5;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <div class="mantenimiento-container">
            <h1>🔧 Mantenimiento de Base de Datos</h1>
            <p>Selecciona una tarea de mantenimiento:</p>
            
            <a href="?accion=estadisticas" class="boton-mantenimiento">📊 Ver Estadísticas</a>
            <a href="?accion=reparar" class="boton-mantenimiento boton-exito">🔨 Reparar Tablas</a>
            <a href="?accion=optimizar" class="boton-mantenimiento boton-exito">⚡ Optimizar Tablas</a>
            <a href="?accion=verificar_indices" class="boton-mantenimiento">✅ Verificar Índices</a>
            <a href="?accion=limpieza_logs" class="boton-mantenimiento boton-peligro">🗑️ Limpiar Logs Antiguos</a>
            
            <div class="resultado">
                <h3>ℹ️ Información</h3>
                <ul>
                    <li><strong>Ver Estadísticas:</strong> Muestra el tamaño de todas las tablas</li>
                    <li><strong>Reparar Tablas:</strong> Repara tablas dañadas (ejecutar si hay errores)</li>
                    <li><strong>Optimizar Tablas:</strong> Mejora la performance eliminando espacio desperdiciado</li>
                    <li><strong>Verificar Índices:</strong> Comprueba que los índices críticos existan</li>
                    <li><strong>Limpiar Logs:</strong> Elimina logs de errores más antiguos que 30 días</li>
                </ul>
            </div>
        </div>
    </body>
    </html>
    <?php
} else {
    $accion = $_GET['accion'];
    $resultado = [];
    $es_error = false;
    
    switch ($accion) {
        case 'estadisticas':
            $sql = "SELECT 
                        table_name,
                        table_rows,
                        ROUND(((data_length + index_length) / 1024 / 1024), 2) AS tamanio_mb,
                        ROUND((data_length / 1024 / 1024), 2) AS datos_mb,
                        ROUND((index_length / 1024 / 1024), 2) AS indices_mb
                    FROM information_schema.tables 
                    WHERE table_schema = 'fetteria_inventario'
                    ORDER BY (data_length + index_length) DESC";
            
            $resultado['titulo'] = '📊 Estadísticas de Base de Datos';
            $resultado['tabla'] = $conn->query($sql);
            break;
            
        case 'reparar':
            $tablas = ['productos', 'ventas', 'movimientos', 'detalles_venta', 'usuarios'];
            $resultado['titulo'] = '🔨 Reparación de Tablas';
            $resultado['mensaje'] = [];
            
            foreach ($tablas as $tabla) {
                if ($conn->query("REPAIR TABLE $tabla")) {
                    $resultado['mensaje'][] = "✅ Tabla '$tabla' reparada correctamente";
                } else {
                    $resultado['mensaje'][] = "❌ Error reparando '$tabla': " . $conn->error;
                    $es_error = true;
                }
            }
            break;
            
        case 'optimizar':
            $tablas = ['productos', 'ventas', 'movimientos', 'detalles_venta', 'usuarios'];
            $resultado['titulo'] = '⚡ Optimización de Tablas';
            $resultado['mensaje'] = [];
            
            foreach ($tablas as $tabla) {
                if ($conn->query("OPTIMIZE TABLE $tabla")) {
                    $resultado['mensaje'][] = "✅ Tabla '$tabla' optimizada correctamente";
                } else {
                    $resultado['mensaje'][] = "❌ Error optimizando '$tabla': " . $conn->error;
                    $es_error = true;
                }
            }
            break;
            
        case 'verificar_indices':
            $indices_esperados = [
                'productos' => ['idx_codigo_barras', 'idx_categoria', 'idx_cantidad'],
                'movimientos' => ['idx_producto_id', 'idx_fecha_movimiento', 'idx_tipo_movimiento'],
                'ventas' => ['idx_numero_factura', 'idx_fecha_venta', 'idx_usuario_id', 'idx_cliente_cedula']
            ];
            
            $resultado['titulo'] = '✅ Verificación de Índices';
            $resultado['mensaje'] = [];
            $faltantes = [];
            
            foreach ($indices_esperados as $tabla => $indices) {
                $db_indices = [];
                $res = $conn->query("SHOW INDEX FROM $tabla");
                while ($row = $res->fetch_assoc()) {
                    $db_indices[] = $row['Key_name'];
                }
                
                foreach ($indices as $idx) {
                    if (!in_array($idx, $db_indices)) {
                        $faltantes[] = "$tabla.$idx";
                    }
                }
            }
            
            if (empty($faltantes)) {
                $resultado['mensaje'][] = "✅ Todos los índices críticos están presentes";
            } else {
                $resultado['mensaje'][] = "⚠️ Índices faltantes: " . implode(', ', $faltantes);
                $es_error = true;
            }
            break;
            
        case 'limpieza_logs':
            $log_dir = __DIR__ . '/logs';
            $resultado['titulo'] = '🗑️ Limpieza de Logs';
            $resultado['mensaje'] = [];
            
            if (is_dir($log_dir)) {
                $fecha_limite = time() - (30 * 24 * 60 * 60); // 30 días atrás
                $files = glob("$log_dir/*");
                $eliminados = 0;
                
                foreach ($files as $file) {
                    if (is_file($file) && filemtime($file) < $fecha_limite) {
                        if (unlink($file)) {
                            $eliminados++;
                        }
                    }
                }
                
                $resultado['mensaje'][] = "✅ Se eliminaron $eliminados archivos de log antiguos";
            } else {
                $resultado['mensaje'][] = "ℹ️ No hay logs para limpiar";
            }
            break;
            
        default:
            $resultado['titulo'] = 'Error';
            $resultado['mensaje'][] = "Acción no reconocida";
            $es_error = true;
    }
    
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Resultado de Mantenimiento</title>
        <link rel="stylesheet" href="styles.css">
        <style>
            .resultado-container {
                max-width: 900px;
                margin: 40px auto;
                padding: 30px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .resultado-box {
                padding: 20px;
                border-radius: 5px;
                margin-top: 20px;
                border-left: 4px solid #3498db;
                background: #e3f2fd;
            }
            .resultado-box.exito {
                border-left-color: #27ae60;
                background: #e8f8f5;
            }
            .resultado-box.error {
                border-left-color: #e74c3c;
                background: #fadbd8;
            }
            .tabla-resultado {
                width: 100%;
                margin-top: 20px;
                border-collapse: collapse;
            }
            .tabla-resultado th {
                background: #f5f5f5;
                padding: 10px;
                text-align: left;
                border-bottom: 2px solid #ddd;
            }
            .tabla-resultado td {
                padding: 10px;
                border-bottom: 1px solid #ddd;
            }
            .boton-volver {
                display: inline-block;
                padding: 10px 20px;
                background: #3498db;
                color: white;
                border-radius: 5px;
                text-decoration: none;
                margin-top: 20px;
            }
            .boton-volver:hover {
                background: #2980b9;
            }
        </style>
    </head>
    <body>
        <div class="resultado-container">
            <h1><?php echo $resultado['titulo']; ?></h1>
            
            <?php if (isset($resultado['mensaje'])): ?>
                <div class="resultado-box <?php echo $es_error ? 'error' : 'exito'; ?>">
                    <?php foreach ($resultado['mensaje'] as $msg): ?>
                        <p><?php echo htmlspecialchars($msg); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($resultado['tabla']) && $resultado['tabla']->num_rows > 0): ?>
                <table class="tabla-resultado">
                    <thead>
                        <tr>
                            <th>Tabla</th>
                            <th>Filas</th>
                            <th>Tamaño Total</th>
                            <th>Datos</th>
                            <th>Índices</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $resultado['tabla']->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo $row['table_name']; ?></strong></td>
                                <td><?php echo number_format($row['table_rows']); ?></td>
                                <td><?php echo $row['tamanio_mb']; ?> MB</td>
                                <td><?php echo $row['datos_mb']; ?> MB</td>
                                <td><?php echo $row['indices_mb']; ?> MB</td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <a href="mantenimiento_db.php" class="boton-volver">← Volver al menú</a>
        </div>
    </body>
    </html>
    <?php
}
?>
