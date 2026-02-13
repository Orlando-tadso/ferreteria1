<?php
// La sesión se inicia automáticamente en config.php
// Solo verificamos que exista

require_once 'seguridad.php';

// Establecer headers de seguridad
establecerHeadersSeguridad();

// Si no hay sesión activa, redirige a login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Validar que la sesión no haya sido secuestrada
validarSesion();

function obtenerRolUsuario() {
    $rol = $_SESSION['usuario_rol'] ?? 'inspector';
    if (!in_array($rol, ['admin', 'inspector', 'user'], true)) {
        $rol = 'inspector';
    }
    return $rol;
}

function esAdmin() {
    return obtenerRolUsuario() === 'admin';
}

function requerirAdmin() {
    if (!esAdmin()) {
        header("Location: dashboard.php");
        exit;
    }
}
?>
