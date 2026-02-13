<?php
require_once 'config.php';

// Datos nuevos del administrador
$nombre_usuario = "DAVID SANTANA";
$nombre_completo = "DAVID SANTANA B";
$email = "admin@ferreteria.com";
$contrasena = "DAVID2026";
$contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);
$rol = 'admin';

try {
    // Primero, eliminar cualquier usuario admin existente
    $sql_delete = "DELETE FROM usuarios WHERE rol = 'admin'";
    if ($conn->query($sql_delete) === false) {
        throw new Exception("Error al eliminar usuario anterior: " . $conn->error);
    }
    
    // Insertar nuevo usuario admin
    $sql = "INSERT INTO usuarios (nombre_usuario, nombre_completo, contrasena, email, rol, activo) 
            VALUES (?, ?, ?, ?, ?, 1)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Error en prepare: " . $conn->error);
    }
    
    $stmt->bind_param("sssss", $nombre_usuario, $nombre_completo, $contrasena_hash, $email, $rol);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al crear el usuario: " . $stmt->error);
    }
    
    $stmt->close();
    
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Actualizar Admin - Ferretería</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                margin: 0;
            }
            
            .container {
                background: white;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.2);
                text-align: center;
                max-width: 500px;
            }
            
            h2 {
                color: #28a745;
                margin-bottom: 20px;
            }
            
            .info-box {
                background: #e3f2fd;
                border-left: 4px solid #2196f3;
                padding: 15px;
                margin: 15px 0;
                text-align: left;
                border-radius: 4px;
            }
            
            .info-box p {
                margin: 10px 0;
                font-size: 14px;
            }
            
            .info-box strong {
                color: #1976d2;
            }
            
            a {
                display: inline-block;
                padding: 12px 30px;
                background: #667eea;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                margin-top: 20px;
                transition: background 0.3s;
            }
            
            a:hover {
                background: #5568d3;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>✅ Administrador actualizado correctamente</h2>
            
            <div class="info-box">
                <p><strong>👤 Nombre Completo:</strong> DAVID SANTANA B</p>
                <p><strong>👤 Usuario:</strong> DAVID SANTANA</p>
                <p><strong>🔑 Contraseña:</strong> DAVID2026</p>
                <p><strong>📧 Email:</strong> admin@ferreteria.com</p>
                <p><strong>🔐 Rol:</strong> Administrador</p>
            </div>
            
            <p style="margin-top: 20px; color: #666; font-size: 14px;">
                Ya puedes cerrar la sesión actual e iniciar con las nuevas credenciales
            </p>
            
            <a href="logout.php">Cerrar sesión →</a>
            <a href="login.php" style="margin-left: 10px;">Ir al Login →</a>
        </div>
    </body>
    </html>
    <?php
    
} catch (Exception $e) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error - Ferretería</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                margin: 0;
            }
            
            .container {
                background: white;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.2);
                text-align: center;
                max-width: 500px;
            }
            
            h2 {
                color: #dc3545;
                margin-bottom: 20px;
            }
            
            .error-box {
                background: #f8d7da;
                border-left: 4px solid #dc3545;
                padding: 15px;
                margin: 15px 0;
                text-align: left;
                border-radius: 4px;
                color: #721c24;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>❌ Error al actualizar administrador</h2>
            <div class="error-box">
                <p><?php echo htmlspecialchars($e->getMessage()); ?></p>
            </div>
        </div>
    </body>
    </html>
    <?php
}

$conn->close();
?>
