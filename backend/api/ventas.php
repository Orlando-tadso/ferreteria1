<?php
/**
 * API - VENTAS
 * GET /api/ventas - Historial
 * GET /api/ventas?id=1 - Detalle de venta
 * POST /api/ventas - Registrar venta
 */

require_once '../config.php';
require_once '../middleware.php';
require_once '../classes/Venta.php';
require_once '../classes/Producto.php';

validarMetodo(['GET', 'POST', 'OPTIONS']);

$usuario = requerirAutenticacion();
$venta = new Venta($conn);
$producto = new Producto($conn);

$metodo = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

if ($metodo === 'GET') {
    if ($id) {
        $venta_data = $venta->obtenerVenta(intval($id));
        if ($venta_data) {
            $detalles = $venta->obtenerDetallesVenta(intval($id));
            $venta_data['detalles'] = $detalles;
            responder(true, $venta_data, 'Venta obtenida');
        } else {
            responder(false, null, 'Venta no encontrada', 404);
        }
    } else {
        $historial = $venta->obtenerHistorialVentas(100);
        responder(true, $historial, 'Historial de ventas');
    }
}

else if ($metodo === 'POST') {
    requerirAdmin(); // Solo admins pueden crear ventas
    
    $datos = obtenerJSON();
    validarDatos($datos, ['cliente_nombre', 'cliente_cedula', 'productos']);
    
    $productos_procesados = [];
    foreach ($datos['productos'] as $prod) {
        $p = $producto->obtenerProductoPorId($prod['producto_id']);
        
        if (!$p) {
            responder(false, null, 'Producto no encontrado: ' . $prod['producto_id'], 404);
        }
        
        if ($p['cantidad'] < $prod['cantidad']) {
            responder(false, null, 'Stock insuficiente para ' . $p['nombre'], 400);
        }
        
        $productos_procesados[] = [
            'producto_id' => $prod['producto_id'],
            'cantidad' => intval($prod['cantidad']),
            'precio_unitario' => floatval($p['precio_unitario']),
            'subtotal' => floatval($p['precio_unitario']) * intval($prod['cantidad'])
        ];
    }
    
    $resultado = $venta->registrarVenta(
        $datos['cliente_nombre'],
        $datos['cliente_cedula'],
        $productos_procesados,
        $usuario['usuario_id'],
        $datos['cliente_email'] ?? '',
        $datos['cliente_telefono'] ?? ''
    );
    
    if ($resultado['success']) {
        responder(true, $resultado, 'Venta registrada correctamente');
    } else {
        responder(false, null, $resultado['error'] ?? 'Error al registrar venta', 400);
    }
}

?>
