<?php
require_once 'verificar_sesion.php';
require_once 'config.php';
require_once 'Producto.php';
require_once 'Venta.php';

$producto_obj = new Producto($conn);
$venta_obj = new Venta($conn);

if (isset($_GET['id'])) {
    $producto = $producto_obj->obtenerPorId($_GET['id']);
} else {
    $producto = null;
}

// Obtener movimientos MANUALES (solo entradas y salidas, NO ventas)
$todos_movimientos = $producto_obj->obtenerHistorial();

// Filtrar solo entrada y salida manual (excluir ventas que se registraron en movimientos)
$historial = array_filter($todos_movimientos, function($mov) {
    // Mantener solo entrada y salida manual (no venta)
    return $mov['tipo_movimiento'] == 'entrada' || $mov['tipo_movimiento'] == 'salida';
});

// Obtener ventas agrupadas
$ventas = $venta_obj->obtenerHistorialVentas(100);
$movimientos_ventas = [];

foreach ($ventas as $v) {
    // Filtrar solo ventas del sistema de ferretería (con total > 0)
    if ($v['total'] <= 0) {
        continue;
    }
    
    $detalles = $venta_obj->obtenerDetallesVenta($v['id']);
    
    // Agrupar todos los productos de una venta en una sola línea
    $productos_nombres = [];
    $cantidad_total = 0;
    
    foreach ($detalles as $detalle) {
        $productos_nombres[] = $detalle['nombre'] . ' (x' . $detalle['cantidad'] . ')';
        $cantidad_total += $detalle['cantidad'];
    }
    
    $movimientos_ventas[] = [
        'nombre' => implode(', ', $productos_nombres),
        'tipo_movimiento' => 'venta',
        'cantidad' => $cantidad_total,
        'motivo' => 'Venta factura ' . $v['numero_factura'] . (!empty($v['cliente_nombre']) ? ' - Cliente: ' . $v['cliente_nombre'] : ''),
        'fecha_movimiento' => $v['fecha_venta']
    ];
}

// Combinar movimientos manuales y ventas agrupadas, ordenar por fecha descendente
$historial_combinado = array_merge($historial, $movimientos_ventas);
usort($historial_combinado, function($a, $b) {
    return strtotime($b['fecha_movimiento']) - strtotime($a['fecha_movimiento']);
});

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movimientos de Inventario - Ferretería</title>
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
                <a href="productos.php" class="nav-link">📦 Productos</a>
                <?php if (esAdmin()): ?>
                    <a href="agregar_producto.php" class="nav-link">➕ Agregar Producto</a>
                    <a href="punto_venta.php" class="nav-link">🛒 Punto de Venta</a>
                <?php endif; ?>
                <a href="movimientos.php" class="nav-link active">📋 Movimientos</a>
                <a href="historial_ventas.php" class="nav-link">📊 Historial Ventas</a>
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
                <h1>📋 Historial de Movimientos y Ventas</h1>
                <?php if ($producto): ?>
                    <p>Producto: <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong></p>
                    <a href="movimientos.php" class="btn btn-secondary">Ver todos</a>
                <?php endif; ?>
            </header>

            <!-- Sección combinada de Movimientos y Ventas -->
            <section class="card">
                <h2>📦 Todos los Movimientos (Inventario y Ventas)</h2>
                <?php if (count($historial_combinado) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Artículo</th>
                                <th>Tipo</th>
                                <th>Cantidad</th>
                                <th>Motivo / Detalles</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historial_combinado as $mov): ?>
                                <tr class="<?php echo $mov['tipo_movimiento'] == 'entrada' ? 'entrada' : ($mov['tipo_movimiento'] == 'venta' ? 'salida' : 'salida'); ?>">
                                    <td><?php echo htmlspecialchars($mov['nombre']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $mov['tipo_movimiento']; ?>">
                                            <?php 
                                            if ($mov['tipo_movimiento'] == 'entrada') {
                                                echo '➕ Entrada';
                                            } elseif ($mov['tipo_movimiento'] == 'venta') {
                                                echo '🛒 Venta';
                                            } else {
                                                echo '➖ Salida';
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo abs($mov['cantidad']); ?></td>
                                    <td><?php echo htmlspecialchars($mov['motivo'] ?: 'N/A'); ?></td>
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($mov['fecha_movimiento'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-data">No hay movimientos registrados</p>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <script>
        // Script para estilos dinámicos de ventas
    </script>
</body>
</html>
