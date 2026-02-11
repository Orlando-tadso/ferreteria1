<?php
/**
 * Health Check - Diagnóstico rápido del sistema
 * Ejecutar: http://localhost/ferreteria1/health_check.php
 */

require_once 'config.php';

$diagnostico = [];

// 1. Estado de la conexión MySQL
$diagnostico['mysql'] = [
    'estado' => $conn->ping() ? '✅ Conectado' : '❌ Desconectado',
    'version' => $conn->server_info,
    'charset' => $conn->character_set_name()
];

// 2. Verificar tablas críticas
$tablas_esperadas = ['productos', 'ventas', 'detalles_venta', 'movimientos', 'usuarios'];
$tablas_existentes = [];
$result = $conn->query("SHOW TABLES FROM fetteria_inventario");
while ($row = $result->fetch_row()) {
    $tablas_existentes[] = $row[0];
}

$diagnostico['tablas'] = [
    'encontradas' => count($tablas_existentes),
    'esperadas' => count($tablas_esperadas),
    'faltantes' => array_diff($tablas_esperadas, $tablas_existentes),
    'estado' => count($tablas_existentes) === count($tablas_esperadas) ? '✅ Completo' : '⚠️ Incompleto'
];

// 3. Contar registros
$diagnostico['registros'] = [];
foreach ($tablas_esperadas as $tabla) {
    if (in_array($tabla, $tablas_existentes)) {
        $res = $conn->query("SELECT COUNT(*) as cnt FROM $tabla");
        $row = $res->fetch_assoc();
        $diagnostico['registros'][$tabla] = intval($row['cnt']);
    }
}

// 4. Verificar índices críticos
$indices_criticos = [
    'productos.idx_codigo_barras',
    'movimientos.idx_producto_id',
    'ventas.idx_fecha_venta'
];

$diagnostico['indices'] = [];
foreach ($indices_criticos as $idx) {
    list($tabla, $nombre) = explode('.', $idx);
    $res = $conn->query("SHOW INDEX FROM $tabla WHERE Key_name = '$nombre'");
    $diagnostico['indices'][$idx] = $res->num_rows > 0 ? '✅' : '❌';
}

// 5. Espacio en disco
$diagnostico['espacio'] = [];
$res = $conn->query("SELECT 
    SUM(data_length + index_length) as total_size,
    SUM(data_length) as data_size
    FROM information_schema.tables 
    WHERE table_schema = 'fetteria_inventario'");
$row = $res->fetch_assoc();
$diagnostico['espacio']['total_mb'] = round($row['total_size'] / 1024 / 1024, 2);
$diagnostico['espacio']['datos_mb'] = round($row['data_size'] / 1024 / 1024, 2);

// 6. Verificar archivo de logs
$log_file = __DIR__ . '/logs/error.log';
$diagnostico['logs'] = [
    'existe' => file_exists($log_file) ? '✅' : '❌',
    'tamaño_kb' => file_exists($log_file) ? round(filesize($log_file) / 1024, 2) : 0,
    'ruta' => $log_file
];

// 7. Últimos errores (últimas 5 líneas)
$dernos_errores = [];
if (file_exists($log_file)) {
    $lineas = file($log_file, FILE_SKIP_EMPTY_LINES | FILE_TEXT);
    $diagnóstico['ultimos_errores'] = array_slice($lineas, -5);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Health Check - Sistema Ferretería</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 30px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        h1 {
            color: white;
            margin-bottom: 30px;
            text-align: center;
            font-size: 2.5em;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .card h2 {
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
            margin-bottom: 15px;
            font-size: 1.3em;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .label {
            font-weight: 500;
            color: #555;
        }
        
        .value {
            color: #333;
            font-weight: 600;
        }
        
        .status-ok {
            color: #27ae60;
        }
        
        .status-error {
            color: #e74c3c;
        }
        
        .status-warning {
            color: #f39c12;
        }
        
        .tabla-registros {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .tabla-registros th {
            background: #f5f5f5;
            padding: 10px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #ddd;
        }
        
        .tabla-registros td {
            padding: 8px 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .footer {
            text-align: center;
            color: white;
            margin-top: 40px;
            font-size: 0.9em;
        }
        
        .refresh-btn {
            display: inline-block;
            padding: 10px 20px;
            background: white;
            color: #667eea;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
        }
        
        .refresh-btn:hover {
            background: #f5f5f5;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏥 Health Check - Sistema Ferretería</h1>
        
        <!-- MySQL Status -->
        <div class="card">
            <h2>🗄️ Base de Datos MySQL</h2>
            <div class="info-row">
                <span class="label">Estado</span>
                <span class="value <?php echo strpos($diagnostico['mysql']['estado'], '✅') ? 'status-ok' : 'status-error'; ?>">
                    <?php echo $diagnostico['mysql']['estado']; ?>
                </span>
            </div>
            <div class="info-row">
                <span class="label">Versión</span>
                <span class="value"><?php echo $diagnostico['mysql']['version']; ?></span>
            </div>
            <div class="info-row">
                <span class="label">Charset</span>
                <span class="value"><?php echo $diagnostico['mysql']['charset']; ?></span>
            </div>
        </div>
        
        <!-- Tablas -->
        <div class="card">
            <h2>📊 Tablas de Base de Datos</h2>
            <div class="info-row">
                <span class="label">Estado</span>
                <span class="value <?php echo strpos($diagnostico['tablas']['estado'], '✅') ? 'status-ok' : 'status-error'; ?>">
                    <?php echo $diagnostico['tablas']['estado']; ?>
                </span>
            </div>
            <div class="info-row">
                <span class="label">Tablas Encontradas</span>
                <span class="value"><?php echo $diagnostico['tablas']['encontradas']; ?> / <?php echo $diagnostico['tablas']['esperadas']; ?></span>
            </div>
            <?php if (!empty($diagnostico['tablas']['faltantes'])): ?>
                <div class="info-row">
                    <span class="label status-error">Tablas Faltantes</span>
                    <span class="value status-error"><?php echo implode(', ', $diagnostico['tablas']['faltantes']); ?></span>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Registros -->
        <div class="card">
            <h2>📈 Cantidad de Registros</h2>
            <table class="tabla-registros">
                <thead>
                    <tr>
                        <th>Tabla</th>
                        <th>Registros</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($diagnostico['registros'] as $tabla => $cant): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($tabla); ?></td>
                            <td><?php echo number_format($cant); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Índices -->
        <div class="card">
            <h2>⚡ Índices Críticos</h2>
            <?php foreach ($diagnostico['indices'] as $idx => $status): ?>
                <div class="info-row">
                    <span class="label"><?php echo htmlspecialchars($idx); ?></span>
                    <span class="value <?php echo strpos($status, '✅') ? 'status-ok' : 'status-error'; ?>">
                        <?php echo $status; ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Espacio en Disco -->
        <div class="card">
            <h2>💾 Uso de Espacio</h2>
            <div class="info-row">
                <span class="label">Total (Datos + Índices)</span>
                <span class="value"><?php echo $diagnostico['espacio']['total_mb']; ?> MB</span>
            </div>
            <div class="info-row">
                <span class="label">Solo Datos</span>
                <span class="value"><?php echo $diagnostico['espacio']['datos_mb']; ?> MB</span>
            </div>
        </div>
        
        <!-- Logs -->
        <div class="card">
            <h2>📝 Sistema de Logs</h2>
            <div class="info-row">
                <span class="label">Archivo de Logs</span>
                <span class="value <?php echo $diagnostico['logs']['existe'] === '✅' ? 'status-ok' : 'status-warning'; ?>">
                    <?php echo $diagnostico['logs']['existe']; ?>
                </span>
            </div>
            <div class="info-row">
                <span class="label">Tamaño</span>
                <span class="value"><?php echo $diagnostico['logs']['tamaño_kb']; ?> KB</span>
            </div>
            <?php if (!empty($diagnostico['ultimos_errores']) && count($diagnostico['ultimos_errores']) > 0): ?>
                <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 5px;">
                    <strong>⚠️ Últimos Errores:</strong>
                    <pre style="font-size: 0.85em; margin-top: 10px; overflow-x: auto;">
<?php 
// Mostrar solo las últimas 3 líneas
$ultimos = array_slice($diagnostico['ultimos_errores'], -3);
echo htmlspecialchars(implode('', $ultimos)); 
?>
                    </pre>
                </div>
            <?php else: ?>
                <div style="margin-top: 15px; padding: 10px; background: #d4edda; border-radius: 5px;">
                    <strong>✅ Sin errores recientes</strong>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p>Última verificación: <?php echo date('Y-m-d H:i:s'); ?></p>
            <a href="health_check.php" class="refresh-btn">🔄 Actualizar</a>
        </div>
    </div>
</body>
</html>
