<?php
// Asegurar que la sesión esté activa (config.php la inicia automáticamente)
if (session_status() == PHP_SESSION_NONE) {
    // Configurar tiempo de vida de sesión (8 horas)
    ini_set('session.gc_maxlifetime', 28800);
    session_start();
    
    // Regenerar ID de sesión la primera vez (por seguridad)
    if (!isset($_SESSION['inicializado'])) {
        session_regenerate_id(true);
        $_SESSION['inicializado'] = true;
    }
}

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
