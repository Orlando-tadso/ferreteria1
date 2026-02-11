<?php
require_once 'verificar_sesion.php';
require_once 'config.php';
require_once 'Venta.php';

$venta = new Venta($conn);
$ventas = $venta->obtenerHistorialVentas(100);

// Obtener todos los detalles de ventas
$todos_detalles = [];
foreach ($ventas as $v) {
    $detalles = $venta->obtenerDetallesVenta($v['id']);
    foreach ($detalles as $detalle) {
        $detalle['numero_factura'] = $v['numero_factura'];
        $detalle['cliente_nombre'] = $v['cliente_nombre'];
        $detalle['cliente_cedula'] = $v['cliente_cedula'];
        $detalle['fecha_venta'] = $v['fecha_venta'];
        $todos_detalles[] = $detalle;
    }
}

// Ordenar por fecha descendente
usort($todos_detalles, function($a, $b) {
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
                <h2>Todos los Productos Vendidos</h2>
                
                <?php if (count($todos_detalles) > 0): ?>
                    <table class="table table-responsive">
                        <thead>
                            <tr>
                                <th>Factura</th>
                                <th>Cliente</th>
                                <th>Cédula</th>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio Unitario</th>
                                <th>Subtotal</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todos_detalles as $detalle): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($detalle['numero_factura']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($detalle['cliente_nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($detalle['cliente_cedula']); ?></td>
                                    <td><?php echo htmlspecialchars($detalle['nombre']); ?></td>
                                    <td><?php echo $detalle['cantidad']; ?></td>
                                    <td>$<?php echo number_format($detalle['precio_unitario'], 2); ?></td>
                                    <td><strong>$<?php echo number_format($detalle['subtotal'], 2); ?></strong></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($detalle['fecha_venta'])); ?></td>
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
        // Script simplificado ya que ahora no usamos detalles ocultos
    </script>
</body>
</html>
