# NUEVA ESTRUCTURA - Frontend/Backend Separado

## 📁 Estructura de Carpetas

```
ferreteria1/
├── backend/
│   ├── api/
│   │   ├── auth.php        (Autenticación)
│   │   ├── productos.php   (CRUD Productos)
│   │   ├── ventas.php      (Registro de Ventas)
│   │   └── .htaccess
│   ├── classes/
│   │   ├── Producto.php
│   │   ├── Venta.php
│   │   └── Devolucion.php (próximamente)
│   ├── config.php          (Configuración BD)
│   ├── middleware.php      (Autenticación JWT)
│   └── .htaccess
│
├── frontend/
│   ├── index.html          (Dashboard)
│   ├── punto_venta.html    (POS)
│   ├── productos.html      (Gestión productos)
│   ├── historial.html      (Ventas)
│   ├── login.html          (Login - próximamente)
│   ├── css/
│   │   └── styles.css      (Estilos globales)
│   ├── js/
│   │   ├── api.js          (Cliente HTTP)
│   │   ├── utils.js        (Funciones auxiliares)
│   │   └── [página].js     (Scripts específicos)
│   └── .htaccess
│
├── public/
│   └── index.php           (Entry point)
│
├── [archivos antiguos - deprecados]
├── .htaccess               (Enrutamiento raíz)
└── config.php              (Obsoleto - usar backend/config.php)
```

## 🎯 Ventajas de Esta Estructura

✅ **Separación clara** entre Frontend y Backend
✅ **Código organizado** y profesional
✅ **Escalable** - fácil agregar nuevas funcionalidades
✅ **Mantenible** - estructura clara de directorios
✅ **Seguro** - API endpoints protegidos con autenticación
✅ **Frontend agnóstico** - Se puede migrar a React/Vue sin tocar backend
✅ **API REST** - Estándar de la industria

## 🔌 Cómo Funciona

### Backend (API REST)

**Endpoints:**
- `POST /api/auth.php?ruta=login` - Login
- `GET /api/auth.php?ruta=user` - Obtener usuario actual
- `POST /api/auth.php?ruta=logout` - Logout

- `GET /api/productos.php` - Listar todos
- `GET /api/productos.php?id=1` - Obtener uno
- `POST /api/productos.php` - Crear
- `PUT /api/productos.php` - Actualizar
- `DELETE /api/productos.php` - Eliminar

- `GET /api/ventas.php` - Historial
- `GET /api/ventas.php?id=1` - Detalle de venta
- `POST /api/ventas.php` - Registrar venta

**Autenticación:** JWT Token (Bearer Token en header Authorization)

### Frontend (HTML/CSS/JS)

**Cliente HTTP:** `APIClient` en `frontend/js/api.js`

```javascript
// Uso en JavaScript
const api = new APIClient();

// Obtener datos
const productos = await api.obtenerProductos();

// Crear
const resultado = await api.crearProducto({
    nombre: 'Tornillo',
    categoria: 'Materiales',
    precio_unitario: 5000,
    cantidad: 100
});
```

**Autenticación:** Token guardado en localStorage, enviado automáticamente en headers

## ⚙️ Instalación y Uso

### 1. Las carpetas ya están creadas:
```
✓ backend/
✓ backend/api/
✓ backend/classes/
✓ frontend/
✓ frontend/js/
✓ frontend/css/
✓ public/
```

### 2. Inicializar Base de Datos:
```sql
-- Ejecutar schema en MySQL
mysql -u root < fetteria_inventario_schema.sql
```

### 3. Acceder a la aplicación:
- **Frontend:** http://localhost/ferreteria1/frontend/index.html
- **Dashboard:** http://localhost/ferreteria1/ (redirige a frontend)
- **API:** http://localhost/ferreteria1/api/productos.php

### 4. Login (actualmente hay dos formas):
- Antigua: `login.php` en la raíz (deprecated)
- Nueva: `frontend/login.html` (en desarrollo)

## 🔐 Seguridad

✅ CORS configurado
✅ JWT para autenticación
✅ Prepared statements (previene inyección SQL)
✅ Headers de seguridad
✅ Acceso restringido a admin
✅ Validación de datos en entrada

## 🚀 Próximos Pasos

1. **[ ]** Crear `frontend/login.html` con autenticación JWT
2. **[ ]** Crear endpoints para devoluciones
3. **[ ]** Agregar reportes/estadísticas
4. **[ ]** Migrar completamente a frontend separado
5. **[ ]** Eliminar archivos deprecated (antiguos .php)
6. **[ ]** Opcionalmente: Migrar a React/Vue

## 📝 Migrando del Sistema Anterior

**Archivos deprecados que pueden eliminarse después:**
- `punto_venta.php` → use `frontend/punto_venta.html`
- `dashboard.php` → use `frontend/index.html`
- `productos.php` → use `frontend/productos.html`
- `index.php` → use `frontend/index.html`
- Todos los `.php` que renderizan HTML

**Archivos que se deben mantener:**
- `login.php` (temporal, hasta migrar a JWT)
- `config.php` (references desde backend)
- `Producto.php` → deprecated (copied to `backend/classes/`)
- `Venta.php` → deprecated (copied to `backend/classes/`)

## 💻 Desarrollo Local

**Recomendaciones:**
1. Usar el cliente HTTP `APIClient` para todas las llamadas
2. Mantener lógica de negocio en backend (clases)
3. Frontend solo para presentación y UX
4. Usar `utils.js` para funciones comunes
5. Consumir API siempre a través de `api.js`

**Ejemplo de nueva funcionalidad:**

Backend (api/nuevaFuncionalidad.php):
```php
require_once '../config.php';
require_once '../middleware.php';

$usuario = requerirAdmin();
// Lógica...
responder(true, $datos, 'Mensaje');
```

Frontend (nuevo.html):
```javascript
const resultado = await api.post('/api/nuevaFuncionalidad.php', datos);
if (resultado.success) {
    mostrarNotificacion('Éxito!', 'success');
}
```

---
**Sistema actualizado:** Marzo 2, 2026
