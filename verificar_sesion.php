<?php
session_start();

// Si no hay sesión activa, redirige a login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

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
