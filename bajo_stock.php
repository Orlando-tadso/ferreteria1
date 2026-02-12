<?php
require_once 'verificar_sesion.php';
require_once 'config.php';
require_once 'Producto.php';

$producto_obj = new Producto($conn);
$bajo_stock = $producto_obj->obtenerBajoStock();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artículos Bajo Stock - Ferretería</title>
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
                <?php if (esAdmin()): ?>
                    <a href="agregar_producto.php" class="nav-link">➕ Agregar Artículo</a>
                    <a href="punto_venta.php" class="nav-link">🛒 Punto de Venta</a>
                <?php endif; ?>
                <a href="movimientos.php" class="nav-link">📋 Movimientos</a>
                <a href="historial_ventas.php" class="nav-link">📊 Historial Ventas</a>
                <a href="bajo_stock.php" class="nav-link active">⚠️ Bajo Stock</a>
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
                <h1>⚠️ Artículos en Bajo Stock</h1>
                <p>Artículos que necesitan restock: <?php echo count($bajo_stock); ?></p>
            </header>

            <section class="card">
                <?php if (count($bajo_stock) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Artículo</th>
                                <th>Categoría</th>
                                <th>Cantidad Actual</th>
                                <th>Cantidad Mínima</th>
                                <th>Diferencia</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bajo_stock as $prod):
                                $diferencia = $prod['cantidad_minima'] - $prod['cantidad'];
                            ?>
                                <tr class="alert-row">
                                    <td><strong><?php echo htmlspecialchars($prod['nombre']); ?></strong></td>
                                    <td><?php echo $prod['categoria']; ?></td>
                                    <td><?php echo $prod['cantidad']; ?></td>
                                    <td><?php echo $prod['cantidad_minima']; ?></td>
                                    <td class="alert-value">-<?php echo $diferencia; ?></td>
                                    <td>
                                        <?php if (esAdmin()): ?>
                                            <a href="editar_producto.php?id=<?php echo $prod['id']; ?>" class="btn-small">✏️ Editar</a>
                                        <?php else: ?>
                                            <span class="badge">Solo lectura</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-success">
                        ✓ Todos los artículos tienen suficiente stock
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
    <script src="check-updates.js"></script>
</body>
</html>
