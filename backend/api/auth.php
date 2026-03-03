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
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_SESSION['usuario_id'])) {
        responder(true, [
            'usuario_id' => $_SESSION['usuario_id'],
            'usuario_rol' => $_SESSION['usuario_rol'] ?? 'inspector',
            'usuario_nombre' => $_SESSION['usuario_nombre'] ?? null
        ], 'Usuario autenticado por sesión');
    }

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
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION = [];
    session_destroy();
    setcookie('auth_token', '', time() - 3600, '/');
    setcookie(session_name(), '', time() - 3600, '/');
    responder(true, null, 'Sesión cerrada correctamente');
}

else {
    responder(false, null, 'Ruta no encontrada', 404);
}

?>
