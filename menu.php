<?php
// Archivo de menú compartido para toda la aplicación
// Se incluye en todos los archivos que tienen sidebar

// Obtener la página actual para marcar como activa
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar -->
<aside class="sidebar">
    <div class="logo">
        <h2>👨‍🔧 Ferretería</h2>
    </div>
    <nav class="nav-menu">
        <a href="dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">📊 Dashboard</a>
        <a href="productos.php" class="nav-link <?php echo $current_page === 'productos.php' ? 'active' : ''; ?>">📦 Productos</a>
        <?php if (esAdmin()): ?>
            <a href="agregar_producto.php" class="nav-link <?php echo $current_page === 'agregar_producto.php' ? 'active' : ''; ?>">➕ Agregar Producto</a>
            <a href="punto_venta.php" class="nav-link <?php echo $current_page === 'punto_venta.php' ? 'active' : ''; ?>">🛒 Punto de Venta</a>
        <?php endif; ?>
        <a href="movimientos.php" class="nav-link <?php echo $current_page === 'movimientos.php' ? 'active' : ''; ?>">📋 Movimientos</a>
        <a href="historial_ventas.php" class="nav-link <?php echo $current_page === 'historial_ventas.php' ? 'active' : ''; ?>">📊 Historial Ventas</a>
        <?php if (esAdmin()): ?>
            <a href="gestionar_devoluciones.php" class="nav-link <?php echo $current_page === 'gestionar_devoluciones.php' ? 'active' : ''; ?>">📦 Devoluciones</a>
        <?php endif; ?>
        <a href="bajo_stock.php" class="nav-link <?php echo $current_page === 'bajo_stock.php' ? 'active' : ''; ?>">⚠️ Bajo Stock</a>
        <hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">
        <?php if (esAdmin()): ?>
            <a href="crear_usuario.php" class="nav-link <?php echo $current_page === 'crear_usuario.php' ? 'active' : ''; ?>">👤 Crear Usuario</a>
        <?php endif; ?>
        <a href="logout.php" class="nav-link" style="color: #e74c3c;">🚪 Cerrar Sesión</a>
    </nav>
</aside>
