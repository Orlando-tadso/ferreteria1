<?php
/**
 * API - AUTENTICACIÓN
 * GET /api/auth/user - Obtener usuario actual
 * POST /api/auth/logout - Cerrar sesión
 */

require_once '../config.php';
require_once '../middleware.php';

validarMetodo(['GET', 'POST', 'OPTIONS']);

$ruta = $_GET['ruta'] ?? '';

if ($ruta === 'user' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $usuario = obtenerUsuarioActual();
    
    if (!$usuario) {
        responder(false, null, 'No autenticado', 401);
    }
    
    responder(true, [
        'usuario_id' => $usuario['usuario_id'],
        'usuario_rol' => $usuario['usuario_rol']
    ], 'Usuario autenticado');
}

else if ($ruta === 'logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    setcookie('auth_token', '', time() - 3600, '/');
    responder(true, null, 'Sesión cerrada correctamente');
}

else {
    responder(false, null, 'Ruta no encontrada', 404);
}

?>
