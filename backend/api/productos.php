<?php
/**
 * API - PRODUCTOS
 * GET /api/productos - Listar todos
 * GET /api/productos?id=1 - Obtener uno
 * POST /api/productos - Crear
 * PUT /api/productos - Actualizar
 * DELETE /api/productos - Eliminar
 */

require_once '../config.php';
require_once '../middleware.php';
require_once '../classes/Producto.php';

validarMetodo(['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']);

$usuario = requerirAutenticacion();
$producto = new Producto($conn);

$metodo = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

if ($metodo === 'GET') {
    if ($id) {
        $prod = $producto->obtenerPorId(intval($id));
        if ($prod) {
            responder(true, $prod, 'Producto obtenido');
        } else {
            responder(false, null, 'Producto no encontrado', 404);
        }
    } else {
        $todos = $producto->obtenerTodos();
        responder(true, $todos, 'Productos listados');
    }
}

else if ($metodo === 'POST') {
    requerirAdmin(); // Solo admins pueden crear
    
    $datos = obtenerJSON();
    validarDatos($datos, ['nombre', 'categoria', 'cantidad', 'precio_unitario']);
    
    $id = $producto->crear(
        $datos['nombre'],
        $datos['descripcion'] ?? '',
        $datos['categoria'],
        $datos['cantidad'] ?? 0,
        $datos['cantidad_minima'] ?? 5,
        $datos['precio_unitario'],
        $datos['codigo_barras'] ?? null
    );
    
    if ($id) {
        responder(true, ['id' => $id], 'Producto creado correctamente');
    } else {
        responder(false, null, 'Error al crear producto', 400);
    }
}

else if ($metodo === 'PUT') {
    requerirAdmin();
    
    $datos = obtenerJSON();
    validarDatos($datos, ['id', 'nombre', 'categoria', 'precio_unitario']);
    
    $actualizado = $producto->actualizar(
        $datos['id'],
        $datos['nombre'],
        $datos['descripcion'] ?? '',
        $datos['categoria'],
        $datos['cantidad_minima'] ?? 5,
        $datos['precio_unitario'],
        $datos['codigo_barras'] ?? null
    );
    
    if ($actualizado) {
        responder(true, null, 'Producto actualizado correctamente');
    } else {
        responder(false, null, 'Error al actualizar producto', 400);
    }
}

else if ($metodo === 'DELETE') {
    requerirAdmin();
    
    $datos = obtenerJSON();
    validarDatos($datos, ['id']);
    
    $eliminado = $producto->eliminar($datos['id']);
    
    if ($eliminado) {
        responder(true, null, 'Producto eliminado correctamente');
    } else {
        responder(false, null, 'Error al eliminar producto', 400);
    }
}

?>
