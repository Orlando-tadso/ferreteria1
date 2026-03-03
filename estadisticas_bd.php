<?php
/**
 * Estadísticas rápidas de la BD
 */
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$estadisticas = [];

// Contar registros en tablas principales
$tablas = ['ventas', 'movimientos', 'productos', 'detalles_venta', 'usuarios'];

foreach ($tablas as $tabla) {
    $result = $conn->query("SELECT COUNT(*) as total FROM $tabla");
    if ($result) {
        $row = $result->fetch_assoc();
        $estadisticas[$tabla] = intval($row['total']);
    } else {
        $estadisticas[$tabla] = 'Error: ' . $conn->error;
    }
}

// Información adicional de ventas
$query = "SELECT 
    COUNT(*) as total_ventas,
    SUM(total) as monto_total,
    MIN(fecha_venta) as primera_venta,
    MAX(fecha_venta) as ultima_venta
FROM ventas";

$result = $conn->query($query);
if ($result) {
    $estadisticas['ventas_detalle'] = $result->fetch_assoc();
}

// Información de movimientos
$query = "SELECT 
    tipo_movimiento,
    COUNT(*) as cantidad
FROM movimientos
GROUP BY tipo_movimiento";

$result = $conn->query($query);
if ($result) {
    $estadisticas['movimientos_por_tipo'] = [];
    while ($row = $result->fetch_assoc()) {
        $estadisticas['movimientos_por_tipo'][$row['tipo_movimiento']] = intval($row['cantidad']);
    }
}

echo json_encode($estadisticas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$conn->close();
?>
