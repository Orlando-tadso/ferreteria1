<?php
require_once 'verificar_sesion.php';
require_once 'config.php';
require_once 'Producto.php';

if (isset($_GET['id'])) {
    $producto_obj = new Producto($conn);
    $producto = $producto_obj->obtenerPorId($_GET['id']);
    $historial = $producto_obj->obtenerHistorial($_GET['id']);
    
    if (!$producto) {
        header('Location: productos.php');
        exit;
    }
} else {
    header('Location: productos.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Movimientos - Ferretería</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <?php require_once 'menu.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <h1>📋 Historial de Movimientos</h1>
                <p>Producto: <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong></p>
                <a href="productos.php" class="btn btn-secondary">← Volver</a>
            </header>

            <!-- Información del Producto -->
            <section class="card">
                <h2>Información del Producto</h2>
                <div class="info-grid">
                    <div>
                        <strong>Nombre:</strong> <?php echo htmlspecialchars($producto['nombre']); ?>
                    </div>
                    <div>
                        <strong>Categoría:</strong> <?php echo $producto['categoria']; ?>
                    </div>
                    <div>
                        <strong>Cantidad Actual:</strong> <?php echo $producto['cantidad']; ?>
                    </div>
                    <div>
                        <strong>Precio Unitario:</strong> $<?php echo number_format($producto['precio_unitario'], 2); ?>
                    </div>
                    <div>
                        <strong>Valor Total:</strong> $<?php echo number_format($producto['cantidad'] * $producto['precio_unitario'], 2); ?>
                    </div>
                    <div>
                        <strong>Cantidad Mínima:</strong> <?php echo $producto['cantidad_minima']; ?>
                    </div>
                </div>
            </section>

            <!-- Historial de Movimientos -->
            <section class="card">
                <h2>Historial de Movimientos</h2>
                <?php if (count($historial) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Cantidad</th>
                                <th>Motivo</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historial as $mov): ?>
                                <tr class="<?php echo $mov['tipo_movimiento'] == 'entrada' ? 'entrada' : 'salida'; ?>">
                                    <td>
                                        <span class="badge <?php echo $mov['tipo_movimiento']; ?>">
                                            <?php echo $mov['tipo_movimiento'] == 'entrada' ? '➕ Entrada' : '➖ Salida'; ?>
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
                    <p class="no-data">No hay movimientos registrados para este producto</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
