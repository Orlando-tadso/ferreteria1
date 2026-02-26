<?php
require_once 'verificar_sesion.php';
require_once 'config.php';
require_once 'Producto.php';

requerirAdmin();

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $producto = new Producto($conn);
    
    $nombre = $_POST['nombre'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $categoria = $_POST['categoria'] ?? '';
    $cantidad = $_POST['cantidad'] ?? 0;
    $cantidad_minima = $_POST['cantidad_minima'] ?? 5;
    $precio_unitario = $_POST['precio_unitario'] ?? 0;
    $codigo_barras = $_POST['codigo_barras'] ?? '';
    
    if ($nombre && $categoria && $precio_unitario) {
        if ($producto->crear($nombre, $descripcion, $categoria, $cantidad, $cantidad_minima, $precio_unitario, $codigo_barras)) {
            $mensaje = '✓ Producto agregado exitosamente';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = '✗ Error al agregar el producto';
            $tipo_mensaje = 'error';
        }
    } else {
        $mensaje = '✗ Por favor completa todos los campos requeridos';
        $tipo_mensaje = 'error';
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Producto - Ferretería</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <?php require_once 'menu.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <h1>➕ Agregar Nuevo Producto</h1>
            </header>

            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <section class="card">
                <form method="POST" class="form">
                    <div class="form-group">
                        <label for="nombre">Nombre del Producto *</label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion" rows="4"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="categoria">Categoría *</label>
                            <select id="categoria" name="categoria" required>
                                <option value="">Selecciona una categoría</option>
                                <option value="Herramientas">🔨 Herramientas</option>
                                <option value="Materiales">🪛 Materiales</option>
                                <option value="Pinturas">🎨 Pinturas</option>
                                <option value="Tubería">🚰 Tubería</option>
                                <option value="Eléctrica">⚡ Eléctrica</option>
                                <option value="Venenos">☠️ Venenos</option>
                                <option value="Aceites">🛢️ Aceites</option>
                                <option value="Medicinas">💊 Medicinas</option>
                                <option value="Aperos de caballo">🐴 Aperos de caballo</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="cantidad">Cantidad Inicial</label>
                            <input type="number" id="cantidad" name="cantidad" value="0" min="0">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="precio_unitario_display">Precio Unitario ($) *</label>
                            <input type="text" id="precio_unitario_display" inputmode="numeric" autocomplete="off" required>
                            <input type="hidden" id="precio_unitario" name="precio_unitario">
                        </div>

                        <div class="form-group">
                            <label for="cantidad_minima">Cantidad Mínima</label>
                            <input type="number" id="cantidad_minima" name="cantidad_minima" value="5" min="1">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="codigo_barras">Código de Barras</label>
                        <input type="text" id="codigo_barras" name="codigo_barras" placeholder="Ej: 1234567890123">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">💾 Guardar Producto</button>
                        <a href="productos.php" class="btn btn-secondary">❌ Cancelar</a>
                    </div>
                </form>
            </section>
        </main>
    </div>

<script>
    const precioDisplay = document.getElementById('precio_unitario_display');
    const precioHidden = document.getElementById('precio_unitario');
    const form = document.querySelector('form');

    function formatearPrecio(valor) {
        const soloDigitos = valor.replace(/[^\d]/g, '');
        if (!soloDigitos) {
            return { formatted: '', numeric: '' };
        }
        const numero = Number(soloDigitos);
        return {
            formatted: new Intl.NumberFormat('es-CO', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(numero),
            numeric: String(numero)
        };
    }

    precioDisplay.addEventListener('input', () => {
        const { formatted, numeric } = formatearPrecio(precioDisplay.value);
        precioDisplay.value = formatted;
        precioHidden.value = numeric;
    });

    form.addEventListener('submit', () => {
        const { numeric } = formatearPrecio(precioDisplay.value);
        precioHidden.value = numeric;
    });
</script>
</body>
</html>
