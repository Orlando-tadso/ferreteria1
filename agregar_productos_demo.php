<?php
require_once 'verificar_sesion.php';
require_once 'config.php';
require_once 'Producto.php';

requerirAdmin();

// Evitar ejecución accidental
if (!isset($_GET['confirmar']) || $_GET['confirmar'] !== 'si') {
    echo '<h2>Agregar Productos de Ejemplo con Códigos de Barras</h2>';
    echo '<p>Este script agregará algunos productos de ejemplo al sistema.</p>';
    echo '<p><strong>ADVERTENCIA:</strong> Esto puede duplicar productos si ya existen.</p>';
    echo '<p><a href="?confirmar=si" style="padding: 10px 20px; background: #27ae60; color: white; text-decoration: none; border-radius: 5px;">✓ Confirmar y Agregar Productos</a></p>';
    exit;
}

$producto = new Producto($conn);

$productos_demo = [
    [
        'nombre' => 'Tornillo de Acero 5mm',
        'descripcion' => 'Tornillo de acero galvanizado de 5mm x 50mm',
        'categoria' => 'Materiales',
        'cantidad' => 500,
        'cantidad_minima' => 50,
        'precio_unitario' => 0.50,
        'codigo_barras' => '1234567890001'
    ],
    [
        'nombre' => 'Clavo Común 2"',
        'descripcion' => 'Clavo común de acero de 2 pulgadas',
        'categoria' => 'Materiales',
        'cantidad' => 1000,
        'cantidad_minima' => 100,
        'precio_unitario' => 0.25,
        'codigo_barras' => '1234567890002'
    ],
    [
        'nombre' => 'Martillo 500g',
        'descripcion' => 'Martillo de goma con mango de madera',
        'categoria' => 'Herramientas',
        'cantidad' => 25,
        'cantidad_minima' => 5,
        'precio_unitario' => 12.99,
        'codigo_barras' => '1234567890003'
    ],
    [
        'nombre' => 'Destornillador Philips',
        'descripcion' => 'Destornillador tipo Philips de acero tratado',
        'categoria' => 'Herramientas',
        'cantidad' => 40,
        'cantidad_minima' => 5,
        'precio_unitario' => 4.50,
        'codigo_barras' => '1234567890004'
    ],
    [
        'nombre' => 'Pintura Acrílica Blanca 1L',
        'descripcion' => 'Pintura acrílica blanca para interiores',
        'categoria' => 'Pinturas',
        'cantidad' => 30,
        'cantidad_minima' => 5,
        'precio_unitario' => 8.99,
        'codigo_barras' => '1234567890005'
    ],
    [
        'nombre' => 'Tubo PVC 3"',
        'descripcion' => 'Tubo PVC para tubería de agua de 3 pulgadas',
        'categoria' => 'Tubería',
        'cantidad' => 20,
        'cantidad_minima' => 2,
        'precio_unitario' => 15.99,
        'codigo_barras' => '1234567890006'
    ],
    [
        'nombre' => 'Bombilla LED 9W',
        'descripcion' => 'Bombilla LED de 9W equivalente a 60W',
        'categoria' => 'Eléctrica',
        'cantidad' => 60,
        'cantidad_minima' => 10,
        'precio_unitario' => 3.75,
        'codigo_barras' => '1234567890007'
    ],
    [
        'nombre' => 'Cable Eléctrico 2.5mm',
        'descripcion' => 'Cable eléctrico de cobre de 2.5mm por metro',
        'categoria' => 'Eléctrica',
        'cantidad' => 100,
        'cantidad_minima' => 10,
        'precio_unitario' => 1.25,
        'codigo_barras' => '1234567890008'
    ],
    [
        'nombre' => 'Llave Inglesa 10"',
        'descripcion' => 'Llave inglesa cromada de 10 pulgadas',
        'categoria' => 'Herramientas',
        'cantidad' => 15,
        'cantidad_minima' => 3,
        'precio_unitario' => 7.99,
        'codigo_barras' => '1234567890009'
    ],
    [
        'nombre' => 'Cemento Portland 50kg',
        'descripcion' => 'Cemento Portland tipo I bolsa de 50kg',
        'categoria' => 'Materiales',
        'cantidad' => 10,
        'cantidad_minima' => 2,
        'precio_unitario' => 9.99,
        'codigo_barras' => '1234567890010'
    ]
];

echo '<h2>Agregando Productos de Ejemplo...</h2>';
echo '<table border="1" cellpadding="10" style="border-collapse: collapse; margin-top: 20px;">';
echo '<tr><th>Producto</th><th>Código de Barras</th><th>Estado</th></tr>';

$contador = 0;
foreach ($productos_demo as $prod) {
    $resultado = $producto->crear(
        $prod['nombre'],
        $prod['descripcion'],
        $prod['categoria'],
        $prod['cantidad'],
        $prod['cantidad_minima'],
        $prod['precio_unitario'],
        $prod['codigo_barras']
    );
    
    $estado = $resultado ? '✓ Agregado' : '✗ Error';
    $color = $resultado ? '#27ae60' : '#e74c3c';
    
    echo '<tr style="background-color: ' . $color . '20;">';
    echo '<td>' . htmlspecialchars($prod['nombre']) . '</td>';
    echo '<td>' . htmlspecialchars($prod['codigo_barras']) . '</td>';
    echo '<td style="color: ' . $color . '; font-weight: bold;">' . $estado . '</td>';
    echo '</tr>';
    
    if ($resultado) $contador++;
}

echo '</table>';
echo '<h3 style="margin-top: 20px; color: #27ae60;">✓ Se agregaron ' . $contador . ' productos exitosamente!</h3>';
echo '<p><a href="punto_venta.php" style="padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px;">Ir al Punto de Venta →</a></p>';
?>
