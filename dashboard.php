<?php
require_once 'verificar_sesion.php';
require_once 'config.php';
require_once 'Producto.php';

$producto = new Producto($conn);
$todos_productos = $producto->obtenerTodos();
$bajo_stock = $producto->obtenerBajoStock();
$historial = $producto->obtenerHistorial();

// Calcular estadísticas
$total_productos = count($todos_productos);
$cantidad_total = 0;
$valor_total = 0;

foreach ($todos_productos as $prod) {
    $cantidad_total += $prod['cantidad'];
    $valor_total += $prod['cantidad'] * $prod['precio_unitario'];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Inventario - Ferretería</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <h2>👨‍🔧 Ferretería</h2>
            </div>
            <nav class="nav-menu">
                <a href="dashboard.php" class="nav-link active">📊 Dashboard</a>
                <a href="productos.php" class="nav-link">📦 Artículos</a>
                <?php if (esAdmin()): ?>
                    <a href="agregar_producto.php" class="nav-link">➕ Agregar Artículo</a>
                    <a href="punto_venta.php" class="nav-link">🛒 Punto de Venta</a>
                <?php endif; ?>
                <a href="movimientos.php" class="nav-link">📋 Movimientos</a>
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
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h1>DASHBOARD / SISTEMA DE INVENTARIOS</h1>
                        <p>Última actualización: <?php echo date('d/m/Y H:i:s'); ?></p>
                    </div>
                    <div style="text-align: right; color: #666; font-size: 14px;">
                        <p>👤 <?php echo htmlspecialchars($_SESSION['usuario_completo'] ?? 'Usuario'); ?></p>
                    </div>
                </div>
            </header>

            <!-- Estadísticas -->
            <section class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-info">
                        <p class="stat-label">Total de Artículos</p>
                        <p class="stat-value"><?php echo $total_productos; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-info">
                        <p class="stat-label">Cantidad Total</p>
                        <p class="stat-value"><?php echo $cantidad_total; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <p class="stat-label">Valor del Inventario</p>
                        <p class="stat-value">$<?php echo number_format($valor_total, 2); ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-info">
                        <p class="stat-label">Artículos Bajo Stock</p>
                        <p class="stat-value"><?php echo count($bajo_stock); ?></p>
                    </div>
                </div>
            </section>

            <!-- Contenido Principal -->
            <div class="content-grid">
                <!-- Productos Bajo Stock -->
                <section class="card">
                    <h2>⚠️ Artículos en Bajo Stock</h2>
                    <?php if (count($bajo_stock) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Artículo</th>
                                    <th>Cantidad</th>
                                    <th>Mínimo</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bajo_stock as $item): ?>
                                    <tr class="alert-row">
                                        <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                                        <td><?php echo $item['cantidad']; ?></td>
                                        <td><?php echo $item['cantidad_minima']; ?></td>
                                        <td>
                                            <?php if (esAdmin()): ?>
                                                <a href="editar_producto.php?id=<?php echo $item['id']; ?>" class="btn-small">Editar</a>
                                            <?php else: ?>
                                                <span class="badge">Solo lectura</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="no-data">✓ Todos los artículos tienen stock suficiente</p>
                    <?php endif; ?>
                </section>

                <!-- Últimos Movimientos -->
                <section class="card">
                    <h2>📋 Últimos Movimientos de Inventario</h2>
                    <?php if (count($historial) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Artículo</th>
                                    <th>Tipo</th>
                                    <th>Cantidad</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($historial, 0, 10) as $mov): ?>
                                    <tr class="<?php echo $mov['tipo_movimiento'] == 'entrada' ? 'entrada' : 'salida'; ?>">
                                        <td><?php echo htmlspecialchars($mov['nombre']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $mov['tipo_movimiento']; ?>">
                                                <?php echo ucfirst($mov['tipo_movimiento']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $mov['cantidad']; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($mov['fecha_movimiento'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="no-data">No hay movimientos registrados</p>
                    <?php endif; ?>
                </section>
            </div>

            <!-- Categorías Chart -->
            <section class="card full-width">
                <h2>📊 Análisis de Inventario por Categoría</h2>
                <div style="max-width: 500px; margin: 0 auto;">
                    <canvas id="categoriasChart"></canvas>
                </div>
            </section>
        </main>
    </div>

    <script>
        // Gráfico de categorías
        const categoriasData = {
            <?php
            $categorias = [];
            $cantidades = [];
            foreach ($todos_productos as $prod) {
                if (!isset($categorias[$prod['categoria']])) {
                    $categorias[$prod['categoria']] = 0;
                }
                $categorias[$prod['categoria']] += $prod['cantidad'];
            }
            
            foreach ($categorias as $cat => $cant) {
                echo "'" . htmlspecialchars($cat) . "': $cant,\n";
            }
            ?>
        };

        const ctx = document.getElementById('categoriasChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(categoriasData),
                datasets: [{
                    data: Object.values(categoriasData),
                    backgroundColor: [
                        '#FF6B6B',
                        '#4ECDC4',
                        '#45B7D1',
                        '#FFA07A',
                        '#98D8C8',
                        '#F7DC6F',
                        '#BB8FCE',
                        '#85C1E2'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
