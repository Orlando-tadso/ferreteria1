<?php
require_once 'verificar_sesion.php';
require_once 'config.php';

// Solo administradores pueden acceder a este recurso
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_usuario = trim($_POST['nombre_usuario'] ?? '');
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contrasena = trim($_POST['contrasena'] ?? '');
    $contrasena_confirm = trim($_POST['contrasena_confirm'] ?? '');
    
    // Validaciones
    if (empty($nombre_usuario) || empty($nombre_completo) || empty($contrasena)) {
        $mensaje = 'Por favor completa todos los campos requeridos';
        $tipo_mensaje = 'error';
    } elseif ($contrasena !== $contrasena_confirm) {
        $mensaje = 'Las contraseñas no coinciden';
        $tipo_mensaje = 'error';
    } elseif (strlen($contrasena) < 6) {
        $mensaje = 'La contraseña debe tener al menos 6 caracteres';
        $tipo_mensaje = 'error';
    } else {
        // Verificar si el usuario ya existe
        $sql = "SELECT id FROM usuarios WHERE nombre_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $nombre_usuario);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $mensaje = 'El usuario ya existe';
            $tipo_mensaje = 'error';
        } else {
            // Crear usuario
            $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);
            $rol = 'inspector';
            $sql = "INSERT INTO usuarios (nombre_usuario, nombre_completo, email, contrasena, rol) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $nombre_usuario, $nombre_completo, $email, $contrasena_hash, $rol);
            
            if ($stmt->execute()) {
                $mensaje = 'Usuario creado exitosamente';
                $tipo_mensaje = 'success';
            } else {
                $mensaje = 'Error al crear el usuario';
                $tipo_mensaje = 'error';
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Usuario - Ferretería</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <h2>👨‍🔧 Ferretería</h2>
            </div>
            <nav class="nav-menu">
                <a href="dashboard.php" class="nav-link">📊 Dashboard</a>
                <a href="productos.php" class="nav-link">📦 Productos</a>
                <?php if (esAdmin()): ?>
                    <a href="agregar_producto.php" class="nav-link">➕ Agregar Producto</a>
                    <a href="punto_venta.php" class="nav-link">🛒 Punto de Venta</a>
                <?php endif; ?>
                <a href="movimientos.php" class="nav-link">📋 Movimientos</a>
                <a href="historial_ventas.php" class="nav-link">📊 Historial Ventas</a>
                <a href="bajo_stock.php" class="nav-link">⚠️ Bajo Stock</a>
                <hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">
                <a href="crear_usuario.php" class="nav-link active">👤 Crear Usuario</a>
                <a href="logout.php" class="nav-link" style="color: #e74c3c;">🚪 Cerrar Sesión</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <h1>👤 Crear Nuevo Usuario</h1>
                <p>Agregar un nuevo usuario al sistema</p>
            </header>

            <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>

            <section class="card">
                <form method="POST" class="form">
                    <div class="form-group">
                        <label for="nombre_usuario">Nombre de Usuario *</label>
                        <input type="text" id="nombre_usuario" name="nombre_usuario" required>
                    </div>

                    <div class="form-group">
                        <label for="nombre_completo">Nombre Completo *</label>
                        <input type="text" id="nombre_completo" name="nombre_completo" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email">
                    </div>

                    <div class="form-group">
                        <label for="contrasena">Contraseña *</label>
                        <input type="password" id="contrasena" name="contrasena" required>
                    </div>

                    <div class="form-group">
                        <label for="contrasena_confirm">Confirmar Contraseña *</label>
                        <input type="password" id="contrasena_confirm" name="contrasena_confirm" required>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">✅ Crear Usuario</button>
                        <a href="dashboard.php" class="btn btn-secondary">❌ Cancelar</a>
                    </div>
                </form>
            </section>
        </main>
    </div>
</body>
            
            <div class="form-group">
                <label for="nombre_completo">Nombre Completo <span class="required">*</span></label>
                <input type="text" id="nombre_completo" name="nombre_completo" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email">
            </div>
            
            <div class="form-group">
                <label for="contrasena">Contraseña <span class="required">*</span></label>
                <input type="password" id="contrasena" name="contrasena" required>
            </div>
            
            <div class="form-group">
                <label for="contrasena_confirm">Confirmar Contraseña <span class="required">*</span></label>
                <input type="password" id="contrasena_confirm" name="contrasena_confirm" required>
            </div>
            
                </form>
            </section>
        </main>
    </div>
</body>
</html>
