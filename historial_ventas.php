<?php
require_once 'verificar_sesion.php';
require_once 'config.php';
require_once 'Venta.php';

$venta = new Venta($conn);
$ventas = $venta->obtenerHistorialVentas(100);

// Procesar ventas con sus detalles
$ventas_procesadas = [];
foreach ($ventas as $v) {
    // Filtrar solo ventas del sistema de ferretería (con total > 0)
    if ($v['total'] <= 0) {
        continue;
    }
    
    $detalles = $venta->obtenerDetallesVenta($v['id']);
    $v['detalles'] = $detalles;
    $v['total_venta'] = 0;
    foreach ($detalles as $detalle) {
        $v['total_venta'] += $detalle['subtotal'];
    }
    $ventas_procesadas[] = $v;
}

// Ordenar por fecha descendente
usort($ventas_procesadas, function($a, $b) {
    return strtotime($b['fecha_venta']) - strtotime($a['fecha_venta']);
});

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Ventas - Ferretería</title>
    <link rel="stylesheet" href="styles.css">
    <style>
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
                <a href="productos.php" class="nav-link">📦 Artículos</a>
                <?php if (esAdmin()): ?>
                    <a href="agregar_producto.php" class="nav-link">➕ Agregar Artículo</a>
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
                <h1>📊 Historial de Ventas</h1>
                <p>Registro de todas las transacciones realizadas</p>
                <div style="margin-top: 10px;">
                    <button onclick="location.reload()" class="btn btn-primary" style="cursor: pointer;">🔄 Actualizar</button>
                </div>
            </header>

            <section class="card">
                <h2>Historial de Ventas Agrupadas</h2>
                
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
                    <p class="no-data">No hay ventas registradas</p>
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
