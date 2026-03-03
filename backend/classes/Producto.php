<?php
/**
 * Clase Producto - Backend
 */

class Producto {
    private $conn;
    
    public function __construct($conexion) {
        $this->conn = $conexion;
    }
    
    public function obtenerTodos() {
        try {
            $stmt = $this->conn->prepare('SELECT p.*, c.nombre as categoria FROM productos p 
                                          LEFT JOIN categorias c ON p.categoria_id = c.id 
                                          ORDER BY p.nombre ASC');
            $stmt->execute();
            $result = $stmt->get_result();
            $datos = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $datos;
        } catch (Exception $e) {
            logError("Error en obtenerTodos", $e->getMessage());
            return [];
        }
    }
    
    public function obtenerPorId($id) {
        try {
            $id = intval($id);
            $stmt = $this->conn->prepare('SELECT p.*, c.nombre as categoria FROM productos p 
                                         LEFT JOIN categorias c ON p.categoria_id = c.id 
                                         WHERE p.id = ?');
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $producto = $result->fetch_assoc();
            $stmt->close();
            return $producto;
        } catch (Exception $e) {
            logError("Error en obtenerPorId", $e->getMessage());
            return null;
        }
    }
    
    private function obtenerCategoriaId($nombre_categoria) {
        $stmt = $this->conn->prepare("SELECT id FROM categorias WHERE nombre = ?");
        $stmt->bind_param("s", $nombre_categoria);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception("Categoría no encontrada: " . $nombre_categoria);
        }
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['id'];
    }
    
    public function crear($nombre, $descripcion, $categoria, $cantidad, $cantidad_minima, $precio_unitario, $codigo_barras = null) {
        try {
            $categoria_id = $this->obtenerCategoriaId($categoria);
            $stmt = $this->conn->prepare("INSERT INTO productos (nombre, descripcion, categoria_id, cantidad, cantidad_minima, precio_unitario, codigo_barras) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            $cantidad = intval($cantidad);
            $cantidad_minima = intval($cantidad_minima);
            $precio_unitario = floatval($precio_unitario);
            $codigo_barras = $codigo_barras ? trim($codigo_barras) : null;
            
            $stmt->bind_param("ssiiiids", $nombre, $descripcion, $categoria_id, $cantidad, $cantidad_minima, $precio_unitario, $codigo_barras);
            $stmt->execute();
            
            $id = $stmt->insert_id;
            $stmt->close();
            return $id;
        } catch (Exception $e) {
            logError("Error en crear", $e->getMessage());
            return false;
        }
    }
    
    public function actualizar($id, $nombre, $descripcion, $categoria, $cantidad_minima, $precio_unitario, $codigo_barras = null) {
        try {
            $categoria_id = $this->obtenerCategoriaId($categoria);
            $stmt = $this->conn->prepare("UPDATE productos SET 
                    nombre = ?,
                    descripcion = ?,
                    categoria_id = ?,
                    cantidad_minima = ?,
                    precio_unitario = ?,
                    codigo_barras = ?
                    WHERE id = ?");
            
            $id = intval($id);
            $cantidad_minima = intval($cantidad_minima);
            $precio_unitario = floatval($precio_unitario);
            $codigo_barras = $codigo_barras ? trim($codigo_barras) : null;
            
            $stmt->bind_param("ssiidsi", $nombre, $descripcion, $categoria_id, $cantidad_minima, $precio_unitario, $codigo_barras, $id);
            $stmt->execute();
            $stmt->close();
            return true;
        } catch (Exception $e) {
            logError("Error en actualizar", $e->getMessage());
            return false;
        }
    }
    
    public function eliminar($id) {
        try {
            $id = intval($id);
            $stmt = $this->conn->prepare("DELETE FROM productos WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            return true;
        } catch (Exception $e) {
            logError("Error en eliminar", $e->getMessage());
            return false;
        }
    }
    
    public function ajustarCantidad($id, $cantidad, $tipo, $motivo = '') {
        try {
            $id = intval($id);
            $cantidad = intval($cantidad);
            
            $stmt = $this->conn->prepare("UPDATE productos SET cantidad = cantidad + ? WHERE id = ?");
            $stmt->bind_param("ii", $cantidad, $id);
            $stmt->execute();
            $stmt->close();
            
            $stmt_mov = $this->conn->prepare("INSERT INTO movimientos (producto_id, tipo_movimiento, cantidad, motivo) VALUES (?, ?, ?, ?)");
            $stmt_mov->bind_param("isis", $id, $tipo, $cantidad, $motivo);
            $stmt_mov->execute();
            $stmt_mov->close();
            
            return true;
        } catch (Exception $e) {
            logError("Error en ajustarCantidad", $e->getMessage());
            return false;
        }
    }
    
    public function obtenerBajoStock() {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM productos WHERE cantidad <= cantidad_minima ORDER BY cantidad ASC");
            $stmt->execute();
            $result = $stmt->get_result();
            $datos = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $datos;
        } catch (Exception $e) {
            logError("Error en obtenerBajoStock", $e->getMessage());
            return [];
        }
    }
    
    public function obtenerHistorial($producto_id = null) {
        try {
            if ($producto_id) {
                $producto_id = intval($producto_id);
                $stmt = $this->conn->prepare("SELECT m.*, p.nombre FROM movimientos m 
                        JOIN productos p ON m.producto_id = p.id 
                        WHERE m.producto_id = ? 
                        ORDER BY m.fecha_movimiento DESC");
                $stmt->bind_param("i", $producto_id);
            } else {
                $stmt = $this->conn->prepare("SELECT m.*, p.nombre FROM movimientos m 
                        JOIN productos p ON m.producto_id = p.id 
                        ORDER BY m.fecha_movimiento DESC LIMIT 50");
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $datos = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $datos;
        } catch (Exception $e) {
            logError("Error en obtenerHistorial", $e->getMessage());
            return [];
        }
    }
    
    public function obtenerPorCodigoBarras($codigo_barras) {
        try {
            $codigo_barras = str_replace(' ', '', trim($codigo_barras));
            $stmt = $this->conn->prepare("SELECT * FROM productos WHERE REPLACE(codigo_barras, ' ', '') = ?");
            $stmt->bind_param("s", $codigo_barras);
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
            logError("Error en obtenerPorCodigoBarras", $e->getMessage());
            return null;
        }
    }
}
?>
