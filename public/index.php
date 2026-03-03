<?php
/**
 * PUBLIC/INDEX.PHP - Entry Point Principal
 * Redirige las solicitudes apropiadamente
 */

// Si es una solicitud a /api/* redirigir al backend
if (strpos($_SERVER['REQUEST_URI'], '/backend/') !== false) {
    // Dejar que Apache lo sirva directamente
    return false;
}

// Si es una solicitud a /frontend/* servir archivos estáticos
if (strpos($_SERVER['REQUEST_URI'], '/frontend/') !== false) {
    return false;
}

// Si es login.php, servir desde raíz
if (strpos($_SERVER['REQUEST_URI'], 'login.php') !== false) {
    require_once '../login.php';
    exit;
}

// Por defecto, redirigir a frontend
header('Location: /ferreteria1/frontend/index.html');
exit;
?>
