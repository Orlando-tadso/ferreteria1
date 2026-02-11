# Guía de Estabilidad de MySQL - Sistema Ferretería

## ✅ Mejoras Implementadas

### 1. **Reintentos de Conexión Automática**
- Si MySQL falla, el sistema reintenta 3 veces con backoff exponencial
- Esto previene errores ocasionales de conexión
- Configuración en: `config.php`

### 2. **Prepared Statements (Consultas Preparadas)**
Todos los archivos han sido actualizados para usar prepared statements:
- `Venta.php` - Todas las operaciones de venta
- `Producto.php` - Todas las operaciones de productos
- ✅ Más seguro contra SQL Injection
- ✅ Mejor performance con muchos datos
- ✅ Previene errores de caracteres especiales

### 3. **Índices Optimizados**
Se agregaron índices en tablas críticas:
- `productos.codigo_barras` - Para búsquedas rápidas por código
- `productos.categoria` - Para filtros por categoría
- `productos.cantidad` - Para búsquedas de bajo stock
- `movimientos.producto_id` - Para historial
- `movimientos.fecha_movimiento` - Para reportes por rango de fechas
- `ventas.fecha_venta` - Para reportes de ventas
- `ventas.usuario_id` - Para auditoría

### 4. **Manejo Robusto de Errores**
- Logging automático de errores en `/logs/error.log`
- Try-catch en todas las operaciones críticas
- Mensajes de error legibles para el usuario (sin datos técnicos)

### 5. **Transacciones ACID**
- Las ventas usan transacciones completas
- Si algo falla, se revierte TODO (rollback)
- Datos siempre consistentes

---

## 🛡️ Monitoreo y Mantenimiento

### Verificar el Log de Errores
```bash
# Windows PowerShell
Get-Content "c:\xampp\htdocs\ferreteria1\logs\error.log" -Tail 50
```

### Optimizar Base de Datos (Mensual)
```sql
-- Reparar tablas si hay corrupción
REPAIR TABLE productos, ventas, movimientos, detalles_venta;

-- Optimizar tablas para mejor performance
OPTIMIZE TABLE productos, ventas, movimientos, detalles_venta;

-- Ver estadísticas de tablas
SELECT table_name, table_rows, data_length, index_length
FROM information_schema.tables 
WHERE table_schema = 'fetteria_inventario'
ORDER BY table_rows DESC;
```

---

## ⚠️ Configuración de MySQL para Alta Carga

Si el sistema comienza a lentificarse con muchos datos, ajusta en `my.ini`:

```ini
[mysqld]
# Aumentar límite de conexiones simultáneas
max_connections = 100

# Mejor uso de memoria
innodb_buffer_pool_size = 512M
innodb_log_file_size = 256M

# Evitar bloqueos
table_open_cache = 2000
innodb_flush_log_at_trx_commit = 2

# Logs de queries lentas (para diagnosticar)
slow_query_log = 1
long_query_time = 2
slow_query_log_file = slow_queries.log
```

---

## 🔍 Pruebas Recomendadas Antes de Producción

### 1. **Test de Carga**
- Cargar 1000+ productos
- Simular 50+ ventas seguidas
- Verificar que no haya errores en `/logs/error.log`

### 2. **Test de Conexión Fallida**
- Detener MySQL manualmente
- Intentar registrar una venta
- Verificar que muestre mensaje amable y vuelva a conectar

### 3. **Test de Datos Válidos**
- Intentar registrar productos con nombres especiales (tildes, caracteres raros)
- Verificar que se guarden correctamente

### 4. **Test de Integridad**
```php
// Ejecutar en un archivo de prueba
<?php
require_once 'config.php';

// Verificar que todos los indices existan
$indices_esperados = [
    'productos' => ['idx_codigo_barras', 'idx_categoria', 'idx_cantidad'],
    'movimientos' => ['idx_producto_id', 'idx_fecha_movimiento'],
    'ventas' => ['idx_fecha_venta', 'idx_usuario_id']
];

foreach ($indices_esperados as $tabla => $indices) {
    $result = $conn->query("SHOW INDEX FROM $tabla");
    $indices_actuales = [];
    while ($row = $result->fetch_assoc()) {
        $indices_actuales[] = $row['Key_name'];
    }
    foreach ($indices as $idx) {
        if (!in_array($idx, $indices_actuales)) {
            echo "⚠️ FALTA ÍNDICE: $tabla.$idx\n";
        }
    }
}
echo "✅ Verificación completada\n";
?>
```

---

## 📊 Monitoreo en Tiempo Real

### Ver Conexiones Activas
```sql
SHOW PROCESSLIST;
```

### Ver Espacio en Disco
```sql
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS `Tamaño (MB)`
FROM information_schema.tables 
WHERE table_schema = 'fetteria_inventario'
ORDER BY (data_length + index_length) DESC;
```

---

## 🚨 Problemas Comunes y Soluciones

### "MySQL has gone away"
**Causa**: Conexión perdida durante operación larga
**Solución**: Ya implementada - reintentos automáticos en config.php

### Tablas corruptas
**Síntoma**: Errores random "Table is marked as crashed"
**Solución**: Ejecutar `REPAIR TABLE productos;`

### Lentitud con muchos datos
**Síntoma**: Las búsquedas tardan > 2 segundos
**Solución**: Verificar que los índices existan con `SHOW INDEX FROM tabla;`

### Error "Out of memory"
**Causa**: MySQL no tiene suficiente RAM
**Solución**: Aumentar `innodb_buffer_pool_size` en my.ini

---

## ✨ Recomendaciones Finales

1. **Backups Regulares**: Respaldar la BD diariamente
2. **Monitoreo**: Revisar `/logs/error.log` cada semana
3. **Límpieza**: Archivar ventas antiguas (> 1 año) en tabla separada
4. **Testing**: Antes de subir a producción, hacer test de carga
5. **Documentación**: Mantener lista de cambios en la BD

