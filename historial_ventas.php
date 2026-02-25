<?php
require_once 'verificar_sesion.php';
require_once 'config.php';
require_once 'Venta.php';

$venta = new Venta($conn);

// Determinar semana a mostrar
$semana_actual = isset($_GET['semana']) ? $_GET['semana'] : date('Y-W');

// Calcular fechas de lunes y sábado de la semana seleccionada
$year = substr($semana_actual, 0, 4);
$week = substr($semana_actual, 5, 2);

// Calcular lunes de la semana
$fecha_lunes = new DateTime();
$fecha_lunes->setISODate($year, $week, 1); // 1 = lunes
$lunes_str = $fecha_lunes->format('Y-m-d');

// Calcular sábado de la semana (6 días después del lunes)
$fecha_sabado = clone $fecha_lunes;
$fecha_sabado->modify('+5 days'); // +5 días = sábado
$sabado_str = $fecha_sabado->format('Y-m-d');

// Obtener todas las ventas y filtrar por la semana
$ventas = $venta->obtenerHistorialVentas(500);

// Procesar ventas con sus detalles
$ventas_procesadas = [];
$total_semana = 0;

foreach ($ventas as $v) {
    // Filtrar solo ventas del sistema de ferretería (con total > 0)
    if ($v['total'] <= 0) {
        continue;
    }
    
    // Filtrar por rango de fechas (lunes a sábado)
    $fecha_venta = date('Y-m-d', strtotime($v['fecha_venta']));
    if ($fecha_venta < $lunes_str || $fecha_venta > $sabado_str) {
        continue;
    }
    
    $detalles = $venta->obtenerDetallesVenta($v['id']);
    $v['detalles'] = $detalles;
    $v['total_venta'] = 0;
    foreach ($detalles as $detalle) {
        $v['total_venta'] += $detalle['subtotal'];
    }
    $total_semana += $v['total_venta'];
    $ventas_procesadas[] = $v;
}

// Ordenar por fecha descendente
usort($ventas_procesadas, function($a, $b) {
    return strtotime($b['fecha_venta']) - strtotime($a['fecha_venta']);
});

// Generar opciones de semanas (últimas 12 semanas)
$opciones_semanas = [];
for ($i = 0; $i < 12; $i++) {
    $fecha_temp = new DateTime();
    $fecha_temp->modify("-$i weeks");
    $semana_key = $fecha_temp->format('Y-W');
    
    // Calcular lunes y sábado para el label
    $temp_year = $fecha_temp->format('Y');
    $temp_week = $fecha_temp->format('W');
    $temp_lunes = new DateTime();
    $temp_lunes->setISODate($temp_year, $temp_week, 1);
    $temp_sabado = clone $temp_lunes;
    $temp_sabado->modify('+5 days');
    
    $opciones_semanas[] = [
        'key' => $semana_key,
        'label' => $temp_lunes->format('d/m/Y') . ' - ' . $temp_sabado->format('d/m/Y')
    ];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Ventas - Ferretería</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .form-control {
            padding: 10px;
            border: 2px solid #3498db;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .detalles-venta {
            display: none;
            margin-top: 10px;
            padding: 10px;
            background-color: #f9f9f9;
            border-left: 3px solid #3498db;
        }
        
        .detalles-venta table {
            width: 100%;
            font-size: 13px;
        }
        
        .detalles-venta th,
        .detalles-venta td {
            padding: 5px;
            text-align: left;
        }
        
        .btn-detalles {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .btn-detalles:hover {
            background-color: #2980b9;
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
                <a href="productos.php" class="nav-link">📦 Productos</a>
                <?php if (esAdmin()): ?>
                    <a href="agregar_producto.php" class="nav-link">➕ Agregar Producto</a>
                    <a href="punto_venta.php" class="nav-link">🛒 Punto de Venta</a>
                <?php endif; ?>
                <a href="movimientos.php" class="nav-link">📋 Movimientos</a>
                <a href="historial_ventas.php" class="nav-link active">📊 Historial de Ventas</a>
                <a href="bajo_stock.php" class="nav-link">⚠️ Bajo Stock</a>
                <hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">
                <?php if (esAdmin()): ?>
                    <a href="crear_usuario.php" class="nav-link">👤 Crear Usuario</a>
                <?php endif; ?>
                <a href="logout.php" class="nav-link" style="color: #e74c3c;">🚪 Cerrar Sesión</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <h1>📊 Historial de Ventas Semanales</h1>
                <p>Ventas de Lunes a Sábado para cuentas semanales</p>
            </header>

            <!-- Selector de Semana -->
            <section class="card">
                <h2>🗓️ Seleccionar Semana</h2>
                <form method="GET" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <select name="semana" class="form-control" style="width: 300px;" onchange="this.form.submit()">
                        <?php foreach ($opciones_semanas as $opcion): ?>
                            <option value="<?php echo $opcion['key']; ?>" <?php echo $opcion['key'] === $semana_actual ? 'selected' : ''; ?>>
                                <?php echo $opcion['label']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">Ver Semana</button>
                </form>
                
                <div style="margin-top: 20px; padding: 15px; background-color: #e8f5e9; border-left: 4px solid #4caf50; border-radius: 5px;">
                    <h3 style="margin: 0 0 10px 0; color: #2e7d32;">💰 Total de la Semana</h3>
                    <p style="margin: 0; font-size: 24px; font-weight: bold; color: #1b5e20;">
                        $<?php echo number_format($total_semana, 2); ?> COP
                    </p>
                    <p style="margin: 5px 0 0 0; font-size: 14px; color: #666;">
                        Del <?php echo $fecha_lunes->format('d/m/Y'); ?> al <?php echo $fecha_sabado->format('d/m/Y'); ?>
                        (<?php echo count($ventas_procesadas); ?> ventas)
                    </p>
                </div>
            </section>

            <section class="card">
                <h2>Detalle de Ventas</h2>
                
                <?php if (count($ventas_procesadas) > 0): ?>
                    <table class="table table-responsive">
                        <thead>
                            <tr>
                                <th>Factura</th>
                                <th>Cliente</th>
                                <th>Cédula</th>
                                <th>Productos</th>
                                <th>Total</th>
                                <th>Fecha y Hora</th>
                                <th>Detalles</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ventas_procesadas as $idx => $venta): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($venta['numero_factura']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($venta['cliente_nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($venta['cliente_cedula']); ?></td>
                                    <td>
                                        <?php 
                                        $productos_list = [];
                                        foreach ($venta['detalles'] as $det) {
                                            $productos_list[] = $det['nombre'] . ' (x' . $det['cantidad'] . ')';
                                        }
                                        echo htmlspecialchars(implode(', ', $productos_list));
                                        ?>
                                    </td>
                                    <td><strong>$<?php echo number_format($venta['total_venta'], 2); ?></strong></td>
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($venta['fecha_venta'])); ?></td>
                                    <td>
                                        <button class="btn-detalles" onclick="toggleDetalles(<?php echo $idx; ?>)">Ver Detalles</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="7" style="padding: 0;">
                                        <div id="detalles-<?php echo $idx; ?>" class="detalles-venta">
                                            <h4>Detalles de la Venta:</h4>
                                            <table>
                                                <thead>
                                                    <tr>
                                                        <th>Producto</th>
                                                        <th>Cantidad</th>
                                                        <th>Precio Unitario</th>
                                                        <th>Subtotal</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($venta['detalles'] as $detalle): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($detalle['nombre']); ?></td>
                                                            <td><?php echo $detalle['cantidad']; ?></td>
                                                            <td>$<?php echo number_format($detalle['precio_unitario'], 2); ?></td>
                                                            <td>$<?php echo number_format($detalle['subtotal'], 2); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-data">No hay ventas registradas en esta semana (<?php echo $fecha_lunes->format('d/m/Y'); ?> - <?php echo $fecha_sabado->format('d/m/Y'); ?>)</p>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <script>
        function toggleDetalles(idx) {
            const detallesDiv = document.getElementById('detalles-' + idx);
            if (detallesDiv.style.display === 'none' || detallesDiv.style.display === '') {
                detallesDiv.style.display = 'block';
            } else {
                detallesDiv.style.display = 'none';
            }
        }
    </script>
</body>
</html>
