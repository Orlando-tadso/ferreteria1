<?php
require_once 'verificar_sesion.php';
require_once 'config.php';
require_once 'Producto.php';

requerirAdmin();

$mensaje = '';
$tipo_mensaje = '';

if (isset($_GET['id'])) {
    $producto_obj = new Producto($conn);
    $producto = $producto_obj->obtenerPorId($_GET['id']);
    
    if (!$producto) {
        header('Location: productos.php');
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $nombre = $_POST['nombre'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $categoria = $_POST['categoria'] ?? '';
        $cantidad_minima = $_POST['cantidad_minima'] ?? 5;
        $precio_unitario = $_POST['precio_unitario'] ?? 0;
        $codigo_barras = $_POST['codigo_barras'] ?? '';
        
        if ($nombre && $categoria && $precio_unitario) {
            if ($producto_obj->actualizar($_GET['id'], $nombre, $descripcion, $categoria, $cantidad_minima, $precio_unitario, $codigo_barras)) {
                $mensaje = '✓ Artículo actualizado exitosamente';
                $tipo_mensaje = 'success';
                $producto = $producto_obj->obtenerPorId($_GET['id']);
            } else {
                $mensaje = '✗ Error al actualizar el artículo';
                $tipo_mensaje = 'error';
            }
        }
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
    <title>Editar Artículo - Ferretería</title>
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
                <a href="productos.php" class="nav-link active">📦 Productos</a>
                <?php if (esAdmin()): ?>
                    <a href="agregar_producto.php" class="nav-link">➕ Agregar Producto</a>
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
                <h1>✏️ Editar Artículo</h1>
            </header>

            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <section class="card">
                <form method="POST" class="form">
                    <div class="form-group">
                        <label for="nombre">Nombre del Artículo *</label>
                        <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($producto['nombre']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion" rows="4"><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="categoria">Categoría *</label>
                            <select id="categoria" name="categoria" required>
                                <option value="Herramientas" <?php echo $producto['categoria'] == 'Herramientas' ? 'selected' : ''; ?>>🔨 Herramientas</option>
                                <option value="Materiales" <?php echo $producto['categoria'] == 'Materiales' ? 'selected' : ''; ?>>🪛 Materiales</option>
                                <option value="Pinturas" <?php echo $producto['categoria'] == 'Pinturas' ? 'selected' : ''; ?>>🎨 Pinturas</option>
                                <option value="Tubería" <?php echo $producto['categoria'] == 'Tubería' ? 'selected' : ''; ?>>🚰 Tubería</option>
                                <option value="Eléctrica" <?php echo $producto['categoria'] == 'Eléctrica' ? 'selected' : ''; ?>>⚡ Eléctrica</option>
                                <option value="Venenos" <?php echo $producto['categoria'] == 'Venenos' ? 'selected' : ''; ?>>☠️ Venenos</option>
                                <option value="Aceites" <?php echo $producto['categoria'] == 'Aceites' ? 'selected' : ''; ?>>🛢️ Aceites</option>
                                <option value="Medicinas" <?php echo $producto['categoria'] == 'Medicinas' ? 'selected' : ''; ?>>💊 Medicinas</option>
                                <option value="Aperos de caballo" <?php echo $producto['categoria'] == 'Aperos de caballo' ? 'selected' : ''; ?>>🐴 Aperos de caballo</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="cantidad_actual">Cantidad Actual</label>
                            <input type="number" id="cantidad_actual" name="cantidad_actual" value="<?php echo $producto['cantidad']; ?>" disabled>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="precio_unitario">Precio Unitario ($) *</label>
                            <input type="number" id="precio_unitario" name="precio_unitario" value="<?php echo $producto['precio_unitario']; ?>" step="0.01" required>
                        </div>

                        <div class="form-group">
                            <label for="cantidad_minima">Cantidad Mínima</label>
                            <input type="number" id="cantidad_minima" name="cantidad_minima" value="<?php echo $producto['cantidad_minima']; ?>" min="1">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="codigo_barras">Código de Barras</label>
                        <input type="text" id="codigo_barras" name="codigo_barras" value="<?php echo $producto['codigo_barras'] ?? ''; ?>" placeholder="Ej: 1234567890123">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">💾 Guardar Cambios</button>
                        <a href="productos.php" class="btn btn-secondary">❌ Cancelar</a>
                    </div>
                </form>
            </section>

            <!-- Sección de Ajuste de Cantidad -->
            <section class="card">
                <h2>📊 Ajustar Cantidad de Stock</h2>
                <form method="POST" action="ajustar_cantidad.php" class="form">
                    <input type="hidden" name="producto_id" value="<?php echo $producto['id']; ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cantidad_ajuste">Cantidad</label>
                            <input type="number" id="cantidad_ajuste" name="cantidad" value="1" required>
                        </div>

                        <div class="form-group">
                            <label for="tipo">Tipo de Movimiento</label>
                            <select id="tipo" name="tipo" required>
                                <option value="entrada">➕ Entrada</option>
                                <option value="salida">➖ Salida</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="motivo">Motivo</label>
                            <input type="text" id="motivo" name="motivo" placeholder="Ej: Restock, Dañado...">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">🔄 Ajustar Cantidad</button>
                </form>
            </section>
        </main>
    </div>
</body>
</html>
