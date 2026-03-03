<?php
/**
 * Clase Venta - Backend
 */

class Venta {
    private $conn;
    
    public function __construct($conexion) {
        $this->conn = $conexion;
    }
    
    public function obtenerPorCodigoBarras($codigo_barras) {
        try {
            $codigo_barras = str_replace(' ', '', trim($codigo_barras));
            $stmt = $this->conn->prepare("SELECT id, nombre, precio_unitario, cantidad FROM productos WHERE REPLACE(codigo_barras, ' ', '') = ?");
            $stmt->bind_param("s", $codigo_barras);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                return $result->fetch_assoc();
            }
            $stmt->close();
            return null;
        } catch (Exception $e) {
            logError("Error en obtenerPorCodigoBarras", $e->getMessage());
            return null;
        }
    }
    
    public function obtenerProductoPorId($id) {
        try {
            $id = intval($id);
            $stmt = $this->conn->prepare("SELECT id, nombre, precio_unitario, cantidad FROM productos WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $producto = $result->fetch_assoc();
                $stmt->close();
                return $producto;
            }
            $stmt->close();
            return null;
        } catch (Exception $e) {
            logError("Error en obtenerProductoPorId", $e->getMessage());
            return null;
        }
    }
    
    private function generarNumeroFactura() {
        $fecha = date('YmdHis');
        $random = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        return "FAC-" . $fecha . "-" . $random;
    }
    
    public function registrarVenta($cliente_nombre, $cliente_cedula, $productos, $usuario_id, $cliente_email = '', $cliente_telefono = '') {
        try {
            $this->conn->begin_transaction();
            
            $total = 0;
            foreach ($productos as $prod) {
                $total += $prod['subtotal'];
            }
            
            $numero_factura = $this->generarNumeroFactura();
            $total = floatval($total);
            $usuario_id = intval($usuario_id);
            
            $stmt = $this->conn->prepare("INSERT INTO ventas (numero_factura, cliente_nombre, cliente_cedula, cliente_email, cliente_telefono, total, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssdi", $numero_factura, $cliente_nombre, $cliente_cedula, $cliente_email, $cliente_telefono, $total, $usuario_id);
            $stmt->execute();
            
            $venta_id = $stmt->insert_id;
            $stmt->close();
            
            $stmt_detalle = $this->conn->prepare("INSERT INTO detalles_venta (venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmt_ajuste = $this->conn->prepare("UPDATE productos SET cantidad = cantidad - ? WHERE id = ?");
            $stmt_mov = $this->conn->prepare("INSERT INTO movimientos (producto_id, tipo_movimiento, cantidad, motivo) VALUES (?, ?, ?, ?)");
            
            foreach ($productos as $prod) {
                $producto_id = intval($prod['producto_id']);
                $cantidad = intval($prod['cantidad']);
                $precio_unitario = floatval($prod['precio_unitario']);
                $subtotal = floatval($prod['subtotal']);
                $tipo_mov = 'venta';
                $motivo = "Venta factura " . $numero_factura;
                
                $stmt_detalle->bind_param("iiidd", $venta_id, $producto_id, $cantidad, $precio_unitario, $subtotal);
                $stmt_detalle->execute();
                
                $stmt_ajuste->bind_param("ii", $cantidad, $producto_id);
                $stmt_ajuste->execute();
                
                $stmt_mov->bind_param("isis", $producto_id, $tipo_mov, $cantidad, $motivo);
                $stmt_mov->execute();
            }
            
            $stmt_detalle->close();
            $stmt_ajuste->close();
            $stmt_mov->close();
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'venta_id' => $venta_id,
                'numero_factura' => $numero_factura,
                'total' => $total
            ];
        } catch (Exception $e) {
            $this->conn->rollback();
            logError("Error en registrarVenta", $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function obtenerDetallesVenta($venta_id) {
        try {
            $venta_id = intval($venta_id);
            $stmt = $this->conn->prepare("SELECT dv.*, p.nombre FROM detalles_venta dv 
                    JOIN productos p ON dv.producto_id = p.id 
                    WHERE dv.venta_id = ?
                    ORDER BY dv.id");
            $stmt->bind_param("i", $venta_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $datos = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $datos;
        } catch (Exception $e) {
            logError("Error en obtenerDetallesVenta", $e->getMessage());
            return [];
        }
    }
    
    public function obtenerVenta($venta_id) {
        try {
            $venta_id = intval($venta_id);
            $stmt = $this->conn->prepare("SELECT * FROM ventas WHERE id = ?");
            $stmt->bind_param("i", $venta_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $venta = $result->fetch_assoc();
            $stmt->close();
            return $venta;
        } catch (Exception $e) {
            logError("Error en obtenerVenta", $e->getMessage());
            return null;
        }
    }
    
    public function obtenerHistorialVentas($limite = 50) {
        try {
            $limite = intval($limite);
            $stmt = $this->conn->prepare("SELECT * FROM ventas ORDER BY fecha_venta DESC LIMIT ?");
            $stmt->bind_param("i", $limite);
            $stmt->execute();
            $result = $stmt->get_result();
            $datos = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $datos;
        } catch (Exception $e) {
            logError("Error en obtenerHistorialVentas", $e->getMessage());
            return [];
        }
    }
    
    public function obtenerTotalDevuelto($venta_id) {
        try {
            $venta_id = intval($venta_id);
            $stmt = $this->conn->prepare("
                SELECT COALESCE(SUM(total_devuelto), 0) as total_devuelto 
                FROM devoluciones 
                WHERE venta_id = ?
            ");
            $stmt->bind_param("i", $venta_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            return floatval($row['total_devuelto']);
        } catch (Exception $e) {
            return 0;
        }
    }
}
?>
