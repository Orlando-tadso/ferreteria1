<?php
require_once 'verificar_sesion.php';
require_once 'config.php';
require_once 'Producto.php';

$producto_obj = new Producto($conn);
$todos_productos = $producto_obj->obtenerTodos();

// Funcionalidad de eliminar
if (isset($_GET['eliminar'])) {
    if (!esAdmin()) {
        header('Location: productos.php?error=No tienes permisos para eliminar productos');
        exit;
    }
    $id = $_GET['eliminar'];
    if ($producto_obj->eliminar($id)) {
        header('Location: productos.php?mensaje=Producto eliminado exitosamente');
        exit;
    }
}

// Filtrado y búsqueda
$filtro = $_GET['filtro'] ?? '';
$busqueda = strtolower($_GET['busqueda'] ?? '');

$productos_filtrados = array_filter($todos_productos, function($prod) use ($filtro, $busqueda) {
    $coincide_categoria = empty($filtro) || $prod['categoria'] == $filtro;
    $coincide_busqueda = empty($busqueda) || strpos(strtolower($prod['nombre']), $busqueda) !== false;
    return $coincide_categoria && $coincide_busqueda;
});

$categorias = array_unique(array_column($todos_productos, 'categoria'));

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - Ferretería</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <?php require_once 'menu.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <h1>📦 Gestión de Productos</h1>
                <p>Total de productos: <?php echo count($todos_productos); ?></p>
            </header>

            <?php if (isset($_GET['mensaje'])): ?>
                <div class="alert alert-success">
                    ✓ <?php echo htmlspecialchars($_GET['mensaje']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    ✗ <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Filtros y Búsqueda -->
            <section class="card">
                <form method="GET" class="filter-form">
                    <input type="text" name="busqueda" placeholder="🔍 Buscar producto..." value="<?php echo htmlspecialchars($busqueda); ?>">
                    
                    <select name="filtro">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo $cat; ?>" <?php echo $filtro == $cat ? 'selected' : ''; ?>>
                                <?php echo $cat; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button type="submit" class="btn btn-primary">🔍 Filtrar</button>
                    <a href="productos.php" class="btn btn-secondary">🔄 Limpiar</a>
                </form>
            </section>

            <!-- Tabla de Productos -->
            <section class="card">
                <table class="table table-responsive">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Cantidad</th>
                            <th>Precio Unitario</th>
                            <th>Valor Total</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos_filtrados as $prod): 
                            $valor_total = $prod['cantidad'] * $prod['precio_unitario'];
                            $estado = $prod['cantidad'] <= $prod['cantidad_minima'] ? 'Bajo Stock' : 'OK';
                            $clase_estado = $prod['cantidad'] <= $prod['cantidad_minima'] ? 'bajo-stock' : 'ok';
                        ?>
                            <tr class="<?php echo $clase_estado; ?>">
                                <td><strong><?php echo htmlspecialchars($prod['nombre']); ?></strong></td>
                                <td><?php echo $prod['categoria']; ?></td>
                                <td><?php echo $prod['cantidad']; ?></td>
                                <td>$<?php echo number_format($prod['precio_unitario'], 2); ?></td>
                                <td>$<?php echo number_format($valor_total, 2); ?></td>
                                <td>
                                    <span class="badge <?php echo $clase_estado; ?>">
                                        <?php echo $estado; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if (esAdmin()): ?>
                                            <a href="editar_producto.php?id=<?php echo $prod['id']; ?>" class="btn-action btn-edit">✏️ Editar</a>
                                            <a href="ver_historial.php?id=<?php echo $prod['id']; ?>" class="btn-action btn-info">📋 Historial</a>
                                            <a href="productos.php?eliminar=<?php echo $prod['id']; ?>" class="btn-action btn-delete" onclick="return confirm('¿Estás seguro?')">🗑️ Eliminar</a>
                                        <?php else: ?>
                                            <a href="ver_historial.php?id=<?php echo $prod['id']; ?>" class="btn-action btn-info">📋 Historial</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if (count($productos_filtrados) == 0): ?>
                    <p class="no-data">No se encontraron productos</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
    <script src="check-updates.js"></script>
</body>
</html>
