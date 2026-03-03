# 💻 GUÍA RÁPIDA: Desarrollo en la Nueva Estructura

## 🚀 Empezar Rápido

### Opción 1: Acceder al Frontend
```
http://localhost/ferreteria1/frontend/
```
- index.html → Dashboard
- punto_venta.html → POS
- productos.html → CRUD de Productos
- historial.html → Historial de Ventas

### Opción 2: Usar API Directamente
```
GET http://localhost/ferreteria1/api/productos.php
Headers: Authorization: Bearer {token}
```

## 📚 Ejemplos de Código

### Frontend: Agregar Producto

**HTML (frontend/productos.html):**
```html
<form id="form-crear-producto" onsubmit="crearProducto(event)">
    <input type="text" id="nombre" placeholder="Nombre" required />
    <input type="number" id="precio" placeholder="Precio" required />
    <button type="submit">Crear</button>
</form>
```

**JavaScript:**
```javascript
async function crearProducto(e) {
    e.preventDefault();
    
    const resultado = await api.crearProducto({
        nombre: document.getElementById('nombre').value,
        precio_unitario: parseFloat(document.getElementById('precio').value),
        categoria: 'Materiales',
        cantidad: 0
    });
    
    if (resultado.success) {
        mostrarNotificacion('Producto creado', 'success');
        cargarProductos(); // Recargar lista
    }
}
```

### Frontend: Listar Productos

```javascript
async function cargarProductos() {
    try {
        const resultado = await api.obtenerProductos();
        
        if (!resultado.success) {
            console.error('Error:', resultado.message);
            return;
        }
        
        // resultado.data contiene array de productos
        resultado.data.forEach(producto => {
            console.log(producto.nombre, producto.precio_unitario);
        });
    } catch (error) {
        console.error('Error:', error);
    }
}
```

### Frontend: Procesar Venta

```javascript
async function procesarVenta(datos) {
    const resultado = await api.registrarVenta({
        cliente_nombre: 'Juan',
        cliente_cedula: '123456789',
        productos: [
            { producto_id: 1, cantidad: 5 },
            { producto_id: 2, cantidad: 3 }
        ]
    });
    
    if (resultado.success) {
        console.log('Factura:', resultado.data.numero_factura);
        console.log('Total:', formatearMoneda(resultado.data.total));
    }
}
```

### Backend: Crear Nuevo Endpoint

**Archivo: backend/api/reportes.php**

```php
<?php
/**
 * API - REPORTES
 * GET /api/reportes.php?tipo=resumen
 */

require_once '../config.php';
require_once '../middleware.php';

validarMetodo(['GET', 'OPTIONS']);
$usuario = requerirAdmin(); // Solo admins

$tipo = $_GET['tipo'] ?? 'resumen';

if ($tipo === 'resumen') {
    try {
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_ventas,
                SUM(total) as total_dinero,
                AVG(total) as venta_promedio
            FROM ventas
            WHERE DATE(fecha_venta) = CURDATE()
        ");
        
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        responder(true, $resultado, 'Reporte obtenido');
    } catch (Exception $e) {
        logError("Error en reportes", $e->getMessage());
        responder(false, null, 'Error al obtener reporte', 500);
    }
} else {
    responder(false, null, 'Tipo de reporte no válido', 400);
}
?>
```

**Usar en Frontend:**

```javascript
// En frontend/js/api.js agregar
async obtenerReporte(tipo) {
    return this.get(`/backend/api/reportes.php?tipo=${tipo}`);
}

// Uso:
const reporte = await api.obtenerReporte('resumen');
console.log('Ventas hoy:', reporte.data.total_ventas);
console.log('Total dinero:', formatearMoneda(reporte.data.total_dinero));
```

## 🛠️ Utilidades Disponibles

### En Frontend (utils.js)

```javascript
// Formatear moneda
formatearMoneda(1500000)
// Output: $1.500.000

// Formatear fecha
formatearFecha('2026-03-02 14:30:00')
// Output: "2 de marzo de 2026 14:30"

// Mostrar notificación
mostrarNotificacion('Producto creado', 'success')
mostrarNotificacion('Error al guardar', 'error')

// Validar
validarEmail('user@example.com')
validarCedula('123456789')

// Otros
obtenerParametroURL('id')
verificarAutenticacion()
```

### En APIClient (api.js)

```javascript
const api = new APIClient();

// Métodos HTTP
api.get('/ruta')
api.post('/ruta', datos)
api.put('/ruta', datos)
api.delete('/ruta', datos)

// Métodos de Negocio
api.obtenerProductos()
api.crearProducto(datos)
api.obtenerVentas()
api.registrarVenta(datos)

// Token
api.guardarToken(token)
api.obtenerToken()
api.limpiarToken()
```

## 📐 Estructura de Respuesta de API

**Éxito:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "nombre": "Tornillo"
    },
    "message": "Producto obtenido"
}
```

**Error:**
```json
{
    "success": false,
    "data": null,
    "message": "Producto no encontrado"
}
```

## 🔍 Debugging

### Ver requests en console
```javascript
// Automáticamente aparecen en Networks tab del DevTools

// O log manual:
const resultado = await api.obtenerProductos();
console.log('Respuesta:', resultado);
```

### Error handling
```javascript
try {
    const resultado = await api.obtenerProductos();
    if (!resultado.success) {
        console.error('Error API:', resultado.message);
    }
} catch (error) {
    console.error('Error de red:', error);
}
```

### Verificar autenticación
```javascript
// Automático con requerirAutenticacion() en backend
// Frontend: verificarAutenticacion() redirige a login si es necesario

await verificarAutenticacion();
// Si falla, redirige a login.html
```

## 📦 Headers Automáticos

El cliente HTTP agrega automáticamente:
```javascript
{
    'Content-Type': 'application/json',
    'Authorization': 'Bearer {token}',  // Si existe token
    credentials: 'include'              // Para cookies de sesión
}
```

## ⚠️ Errores Comunes

### Error: 401 Unauthorized
**Causa:** Token no válido o expirado
**Solución:** Login nuevamente

```javascript
// Frontend detecta automáticamente:
if (respuesta.status === 401) {
    // Redirige a login
}
```

### Error: Método no permitido
**Causa:** Usando GET en un endpoint que requiere POST
**Ejemplo:** `api.modificar()` en lugar de `api.post()`

### Error: Token no encontrado
**Causa:** No está pasando token en header
**Solución:** `guardarToken()` después de login

## 🚦 Checklist para Nueva Funcionalidad

- [ ] ¿Creé el endpoint en backend/api/?
- [ ] ¿Está protegido con `requerirAuthentication()`?
- [ ] ¿Valida datos con `validarDatos()`?
- [ ] ¿Usa prepared statements?
- [ ] ¿Responde con `responder()`?
- [ ] ¿Agregué método en APIClient (api.js)?
- [ ] ¿Creé UI en un .html del frontend?
- [ ] ¿Manejo errores correctamente?
- [ ] ¿Muestro notificaciones al usuario?

## 🎓 Aprender Más

**Leer:**
- [ESTRUCTURA_NUEVA.md](./ESTRUCTURA_NUEVA.md) - Detalles de directorios
- [MIGRACION_FRONTEND_BACKEND.md](./MIGRACION_FRONTEND_BACKEND.md) - Cómo cambió
- [backend/config.php](./backend/config.php) - Config
- [backend/middleware.php](./backend/middleware.php) - Auth

**Inspeccionar:**
- Abrir DevTools (F12) → Network → Ver requests a API
- Console → Ver logs y errores
- Application → Storage → localStorage (token)

---

**¡Listo para desarrollar!** 🚀 El sistema es profesional, escalable y fácil de mantener.
