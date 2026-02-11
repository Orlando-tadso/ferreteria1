<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'fetteria_inventario');

// Crear archivo de log si no existe
$log_dir = __DIR__ . '/logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

function logError($message, $context = '') {
    $log_file = __DIR__ . '/logs/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message";
    if ($context) {
        $log_message .= " | Contexto: $context";
    }
    $log_message .= "\n";
    error_log($log_message, 3, $log_file);
}

// Crear conexión con reintentos
$conn = null;
$max_reintentos = 3;
$retraso = 1; // segundos

for ($intento = 1; $intento <= $max_reintentos; $intento++) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    if (!$conn->connect_error) {
        // Configurar charset
        $conn->set_charset("utf8mb4");
        break;
    }
    
    $error_msg = "Intento $intento de conexión fallido: " . $conn->connect_error;
    logError($error_msg);
    
    if ($intento < $max_reintentos) {
        sleep($retraso);
        $retraso *= 2; // Backoff exponencial
    }
}

// Si después de reintentos sigue fallando
if ($conn->connect_error) {
    logError("Falló conexión a MySQL después de $max_reintentos intentos", $_SERVER['REQUEST_URI'] ?? 'CLI');
    http_response_code(503);
    die("<h1>Error de Sistema</h1><p>No se puede conectar a la base de datos. Por favor, intente más tarde.</p>");
}

// Crear base de datos si no existe
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
$conn->query($sql);

// Seleccionar la base de datos
$conn->select_db(DB_NAME);

// Crear tabla de productos
$sql = "CREATE TABLE IF NOT EXISTS productos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    categoria VARCHAR(50) NOT NULL,
    cantidad INT NOT NULL DEFAULT 0,
    cantidad_minima INT NOT NULL DEFAULT 5,
    precio_unitario DECIMAL(10,2) NOT NULL,
    codigo_barras VARCHAR(50) UNIQUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_codigo_barras (codigo_barras),
    INDEX idx_categoria (categoria),
    INDEX idx_cantidad (cantidad)
)";
$conn->query($sql);

// Agregar columna código_barras si no existe (para bases de datos existentes)
$check_column = $conn->query("SHOW COLUMNS FROM productos LIKE 'codigo_barras'");
if ($check_column && $check_column->num_rows == 0) {
    $sql = "ALTER TABLE productos ADD COLUMN codigo_barras VARCHAR(50) UNIQUE DEFAULT NULL";
    $conn->query($sql);
}

// Agregar índices si no existen (para bases de datos existentes)
$check_idx = $conn->query("SHOW INDEX FROM productos WHERE Key_name = 'idx_codigo_barras'");
if ($check_idx && $check_idx->num_rows == 0) {
    $conn->query("ALTER TABLE productos ADD INDEX idx_codigo_barras (codigo_barras)");
    $conn->query("ALTER TABLE productos ADD INDEX idx_categoria (categoria)");
    $conn->query("ALTER TABLE productos ADD INDEX idx_cantidad (cantidad)");
}

// Crear tabla de movimientos (historial)
$sql = "CREATE TABLE IF NOT EXISTS movimientos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    producto_id INT NOT NULL,
    tipo_movimiento VARCHAR(20) NOT NULL,
    cantidad INT NOT NULL,
    motivo VARCHAR(100),
    fecha_movimiento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    INDEX idx_producto_id (producto_id),
    INDEX idx_fecha_movimiento (fecha_movimiento),
    INDEX idx_tipo_movimiento (tipo_movimiento)
)";
$conn->query($sql);

// Agregar índices a movimientos si no existen
$check_idx = $conn->query("SHOW INDEX FROM movimientos WHERE Key_name = 'idx_producto_id'");
if ($check_idx && $check_idx->num_rows == 0) {
    $conn->query("ALTER TABLE movimientos ADD INDEX idx_producto_id (producto_id)");
    $conn->query("ALTER TABLE movimientos ADD INDEX idx_fecha_movimiento (fecha_movimiento)");
    $conn->query("ALTER TABLE movimientos ADD INDEX idx_tipo_movimiento (tipo_movimiento)");
}

// Crear tabla de usuarios
$sql = "CREATE TABLE IF NOT EXISTS usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre_usuario VARCHAR(100) NOT NULL UNIQUE,
    nombre_completo VARCHAR(150) NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    rol VARCHAR(20) DEFAULT 'inspector',
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql);

// Desactivar restricciones de clave foránea temporalmente
$conn->query("SET FOREIGN_KEY_CHECKS=0");

// Crear tabla de ventas solo si no existe
$sql = "CREATE TABLE IF NOT EXISTS ventas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    numero_factura VARCHAR(50) UNIQUE NOT NULL,
    cliente_nombre VARCHAR(150) NOT NULL,
    cliente_cedula VARCHAR(20) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    usuario_id INT,
    fecha_venta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_numero_factura (numero_factura),
    INDEX idx_fecha_venta (fecha_venta),
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_cliente_cedula (cliente_cedula)
)";
$conn->query($sql);

// Agregar índices a ventas si no existen
$check_idx = $conn->query("SHOW INDEX FROM ventas WHERE Key_name = 'idx_fecha_venta'");
if ($check_idx && $check_idx->num_rows == 0) {
    $conn->query("ALTER TABLE ventas ADD INDEX idx_numero_factura (numero_factura)");
    $conn->query("ALTER TABLE ventas ADD INDEX idx_fecha_venta (fecha_venta)");
    $conn->query("ALTER TABLE ventas ADD INDEX idx_usuario_id (usuario_id)");
    $conn->query("ALTER TABLE ventas ADD INDEX idx_cliente_cedula (cliente_cedula)");
}

// Agregar columna 'rol' a la tabla usuarios si no existe (para bases de datos existentes)
$check_column_rol = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'rol'");
if ($check_column_rol && $check_column_rol->num_rows == 0) {
    $sql = "ALTER TABLE usuarios ADD COLUMN rol VARCHAR(20) DEFAULT 'inspector'";
    $conn->query($sql);
}

// Asegurar que el usuario 'admin' tenga rol 'admin' (migración para instalaciones previas)
$conn->query("UPDATE usuarios SET rol = 'admin' WHERE (nombre_usuario = 'admin' OR email = 'admin@ferreteria.com' OR nombre_completo LIKE 'Administrador') AND (rol IS NULL OR rol = '')");

// Asegurar que usuarios no admin queden como inspectores
$conn->query("UPDATE usuarios SET rol = 'inspector' WHERE (rol IS NULL OR rol = '' OR rol = 'user') AND (nombre_usuario <> 'admin' AND email <> 'admin@ferreteria.com' AND nombre_completo NOT LIKE 'Administrador')");

// Crear tabla de detalles_venta solo si no existe
$sql = "CREATE TABLE IF NOT EXISTS detalles_venta (
    id INT PRIMARY KEY AUTO_INCREMENT,
    venta_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
)";
$conn->query($sql);

// Reactivar restricciones de clave foránea
$conn->query("SET FOREIGN_KEY_CHECKS=1");

?>
