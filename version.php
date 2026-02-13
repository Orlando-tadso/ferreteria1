<?php
// Archivo de versión - usa el mtime de archivos clave
// Solo cambia cuando realmente hay cambios en el código
header('Content-Type: application/json');

// Archivos clave cuyo cambio indica una actualización
$archivos_clave = [
    __DIR__ . '/dashboard.php',
    __DIR__ . '/productos.php',
    __DIR__ . '/punto_venta.php',
    __DIR__ . '/movimientos.php',
    __DIR__ . '/historial_ventas.php',
    __DIR__ . '/agregar_producto.php',
    __DIR__ . '/editar_producto.php',
    __DIR__ . '/login.php',
    __DIR__ . '/setup_admin.php',
    __DIR__ . '/styles.css'
];

// Obtener el timestamp más reciente de los archivos
$timestamp_maximo = 0;
foreach ($archivos_clave as $archivo) {
    if (file_exists($archivo)) {
        $mtime = filemtime($archivo);
        if ($mtime > $timestamp_maximo) {
            $timestamp_maximo = $mtime;
        }
    }
}

echo json_encode(['timestamp' => $timestamp_maximo]);
?>
