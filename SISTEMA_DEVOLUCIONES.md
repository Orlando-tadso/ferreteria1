# 📦 SISTEMA DE DEVOLUCIONES - GUÍA COMPLETA

## 🎯 ¿Qué Soluciona?

Este sistema resuelve el problema cuando un cliente devuelve un producto porque:
- No era el producto que pidió
- El producto estaba defectuoso
- Se entregó la cantidad incorrecta
- Cualquier otro motivo de devolución

El sistema automáticamente:
✅ **Devuelve el producto al inventario**
✅ **Ajusta las cantidades en stock**
✅ **Registra el motivo de la devolución**
✅ **Mantiene historial completo**
✅ **Registra movimientos en el sistema**

---

## 🚀 INSTALACIÓN

### Paso 1: Ejecutar Migración de Base de Datos

Debes ejecutar el archivo SQL para agregar las nuevas tablas:

```sql
-- Opción A: Desde línea de comandos
mysql -u root -p fetteria_inventario < migrar_devoluciones.sql

-- Opción B: Desde phpMyAdmin
-- 1. Abre phpMyAdmin
-- 2. Selecciona la base de datos "fetteria_inventario"
-- 3. Ve a la pestaña "SQL"
-- 4. Copia y pega el contenido de migrar_devoluciones.sql
-- 5. Click en "Continuar"
```

### Paso 2: Verificar Tablas Creadas

Ejecuta esta consulta para verificar que todo está correcto:

```sql
SHOW TABLES LIKE '%devolucion%';
```

Deberías ver:
- `devoluciones`
- `detalles_devolucion`

---

## 📖 CÓMO USAR EL SISTEMA

### Procesar una Devolución

1. **Accede al Sistema**
   - Ve al Dashboard
   - Click en "📦 Devoluciones" (solo administradores)

2. **Buscar la Venta**
   - Ingresa el número de factura (ej: FAC-20260226123456-1234)
   - Click en "Buscar"

3. **Seleccionar Productos**
   - Marca los productos que el cliente está devolviendo
   - Ingresa la cantidad a devolver (no puede ser mayor a lo disponible)
   - El sistema muestra:
     - Cantidad vendida originalmente
     - Cantidad ya devuelta (si hay devoluciones previas)
     - Cantidad disponible para devolver

4. **Especificar Motivo**
   - Escribe el motivo de la devolución
   - Ejemplos:
     - "Cliente compró producto equivocado"
     - "Producto defectuoso"
     - "Error en la entrega"

5. **Procesar**
   - Click en "✓ Procesar Devolución"
   - Confirma la acción
   - El sistema genera un número de devolución (DEV-XXXXXXXXXX-XXXX)

---

## 🔍 ESTRUCTURA DE DATOS

### Tabla: devoluciones
```sql
- id: Identificador único
- venta_id: Referencia a la venta original
- numero_devolucion: DEV-20260226123456-1234
- motivo: Razón de la devolución
- total_devuelto: Monto total devuelto
- usuario_id: Usuario que procesó la devolución
- fecha_devolucion: Fecha y hora
```

### Tabla: detalles_devolucion
```sql
- id: Identificador único
- devolucion_id: Referencia a la devolución
- detalle_venta_id: Referencia al detalle de venta original
- producto_id: Producto devuelto
- cantidad_devuelta: Cantidad del producto
- precio_unitario: Precio al momento de la venta
- subtotal: Subtotal de esta línea
```

---

## ⚙️ QUÉ HACE EL SISTEMA AUTOMÁTICAMENTE

Cuando procesas una devolución, el sistema:

1. **Valida que la venta exista**
2. **Verifica que haya productos disponibles para devolver**
3. **Inicia una transacción de base de datos**
4. **Registra la devolución principal**
5. **Registra cada producto devuelto**
6. **DEVUELVE EL PRODUCTO AL INVENTARIO** (cantidad + cantidad_devuelta)
7. **Registra el movimiento** (tipo: 'devolucion')
8. **Confirma todos los cambios**
9. Si hay error, **revierte TODO** (rollback)

---

## 📊 REPORTES Y CONSULTAS

### Ver Total de Devoluciones por Período
```sql
SELECT 
    DATE(fecha_devolucion) as fecha,
    COUNT(*) as num_devoluciones,
    SUM(total_devuelto) as total
FROM devoluciones
WHERE fecha_devolucion >= '2026-01-01'
GROUP BY DATE(fecha_devolucion)
ORDER BY fecha DESC;
```

### Ver Productos Más Devueltos
```sql
SELECT 
    p.nombre,
    SUM(dd.cantidad_devuelta) as total_devuelto,
    COUNT(DISTINCT dd.devolucion_id) as num_devoluciones
FROM detalles_devolucion dd
JOIN productos p ON dd.producto_id = p.id
GROUP BY p.id, p.nombre
ORDER BY total_devuelto DESC
LIMIT 10;
```

### Ver Devoluciones de un Cliente
```sql
SELECT 
    d.numero_devolucion,
    d.fecha_devolucion,
    v.numero_factura,
    d.motivo,
    d.total_devuelto
FROM devoluciones d
JOIN ventas v ON d.venta_id = v.id
WHERE v.cliente_cedula = '1234567890'
ORDER BY d.fecha_devolucion DESC;
```

---

## 🛡️ SEGURIDAD Y VALIDACIONES

El sistema tiene múltiples capas de seguridad:

✅ **Solo administradores** pueden procesar devoluciones
✅ **Prepared statements** en todas las consultas (previene SQL injection)
✅ **Transacciones** garantizan integridad de datos
✅ **Validaciones de cantidad** (no se puede devolver más de lo vendido)
✅ **Validaciones de existencia** (venta debe existir)
✅ **Log de errores** en caso de problemas
✅ **Rollback automático** si algo falla

---

## 📝 EJEMPLO DE USO

### Escenario:
Cliente compra 5 martillos pero solo necesitaba 3. Quiere devolver 2.

### Proceso:
1. Buscar factura: `FAC-20260226120000-1234`
2. Sistema muestra:
   - Martillo N°8: Cantidad vendida: 5, Disponible devolver: 5
3. Seleccionar producto y poner cantidad: 2
4. Motivo: "Cliente compró cantidad incorrecta, solo necesitaba 3"
5. Procesar devolución
6. Sistema genera: `DEV-20260226120500-5678`

### Resultado:
- ✅ Inventario de martillos aumenta: +2 unidades
- ✅ Movimiento registrado: "Devolución DEV-... (Fact: FAC-...)"
- ✅ Quedan 3 martillos disponibles para devolver si es necesario
- ✅ Historial registrado para auditoría

---

## ❓ PREGUNTAS FRECUENTES

### ¿Puedo devolver parcialmente un producto?
**Sí.** Por ejemplo, si vendiste 10 unidades, puedes devolver 3, luego 2, etc.

### ¿Qué pasa con el dinero?
El sistema **registra** la devolución pero **no procesa reembolsos automáticos**. 
Debes hacer el reembolso manualmente y usar este registro para tu contabilidad.

### ¿Se puede cancelar una devolución?
**No.** Las devoluciones son definitivas. Si fue un error, deberías hacer una nueva 
venta con los productos devueltos incorrectamente.

### ¿Afecta estadísticas de ventas?
El total de la venta **NO se modifica**. Las devoluciones se registran por separado.
Para reportes, debes restar las devoluciones del total de ventas.

### ¿Se puede devolver después de mucho tiempo?
**Sí**, no hay límite de tiempo en el sistema. Sin embargo, deberías establecer 
políticas de devolución en tu negocio.

---

## 🔧 ARCHIVOS CREADOS

```
migrar_devoluciones.sql        → Script SQL de migración
Devolucion.php                 → Clase con lógica de devoluciones
gestionar_devoluciones.php     → Interfaz de usuario
SISTEMA_DEVOLUCIONES.md        → Esta guía
```

---

## 🆘 SOPORTE

Si encuentras algún error:
1. Revisa los logs en `logs/` 
2. Verifica que ejecutaste el script SQL correctamente
3. Verifica permisos de usuario (debe ser admin)
4. Revisa la consola del navegador (F12) para errores JavaScript

---

## ✅ CHECKLIST DE IMPLEMENTACIÓN

- [ ] Ejecutar `migrar_devoluciones.sql`
- [ ] Verificar tablas creadas
- [ ] Probar acceso a gestionar_devoluciones.php
- [ ] Hacer una venta de prueba
- [ ] Procesar una devolución de prueba
- [ ] Verificar que el inventario se ajustó correctamente
- [ ] Revisar historial de movimientos
- [ ] Revisar historial de devoluciones

---

## 📅 Fecha de Implementación: Febrero 2026

**Versión:** 1.0  
**Estado:** Producción Ready ✅
