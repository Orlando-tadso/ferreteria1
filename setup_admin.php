<?php
require_once 'config.php';

// Eliminar usuario admin anterior si existe
$sql = "DELETE FROM usuarios WHERE nombre_usuario = 'admin'";
$conn->query($sql);

// Crear usuario admin nuevo
$nombre_usuario = "DAVID SANTANA";
$nombre_completo = "DAVID SANTANA B";
$email = "admin@ferreteria.com";
$contrasena = "DAVID2026";
$contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);

// Insertar usuario admin con rol 'admin'
$rol = 'admin';
$sql = "INSERT INTO usuarios (nombre_usuario, nombre_completo, contrasena, email, rol, activo) 
        VALUES (?, ?, ?, ?, ?, 1)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("❌ Error en prepare: " . $conn->error);
}

$stmt->bind_param("sssss", $nombre_usuario, $nombre_completo, $contrasena_hash, $email, $rol);

if ($stmt->execute()) {
    echo "<h2>✅ Usuario admin creado correctamente</h2>";
    echo "<p>👤 Usuario: <strong>admin</strong></p>";
    echo "<p>🔑 Contraseña: <strong>admin123</strong></p>";
    echo "<p><a href='login.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>Ir al Login →</a></p>";
} else {
    die("❌ Error al crear el usuario: " . $stmt->error);
}

$stmt->close();
?>
