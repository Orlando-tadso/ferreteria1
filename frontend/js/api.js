/**
 * API Client - Cliente HTTP para comunicación con backend
 * Uso: 
 *   const api = new APIClient();
 *   api.get('/api/productos').then(data => console.log(data));
 */

class APIClient {
    constructor(baseURL = null) {
        this.baseURL = baseURL ?? this.detectarBaseURL();
        this.token = this.obtenerToken();
    }

    detectarBaseURL() {
        const segmentos = window.location.pathname.split('/').filter(Boolean);
        if (segmentos.length > 0 && segmentos[0] === 'ferreteria1') {
            return '/ferreteria1';
        }
        return '';
    }
    
    /**
     * Obtener token del localStorage
     */
    obtenerToken() {
        return localStorage.getItem('auth_token') || null;
    }
    
    /**
     * Guardar token
     */
    guardarToken(token) {
        if (token) {
            localStorage.setItem('auth_token', token);
            this.token = token;
        }
    }
    
    /**
     * Limpiar token
     */
    limpiarToken() {
        localStorage.removeItem('auth_token');
        this.token = null;
    }
    
    /**
     * Obtener headers por defecto
     */
    obtenerHeaders(adicionales = {}) {
        const headers = {
            'Content-Type': 'application/json',
            ...adicionales
        };
        
        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }
        
        return headers;
    }
    
    /**
     * Hacer request
     */
    async request(ruta, opciones = {}) {
        const url = `${this.baseURL}${ruta}`;
        const metodo = opciones.metodo || 'GET';
        const body = opciones.body ? JSON.stringify(opciones.body) : undefined;
        const headers = this.obtenerHeaders(opciones.headers || {});
        
        try {
            const respuesta = await fetch(url, {
                method: metodo,
                headers,
                body,
                credentials: 'include'
            });
            
            const datos = await respuesta.json();
            
            // Si no está autenticado, limpiar token
            if (respuesta.status === 401) {
                this.limpiarToken();
                window.location.href = `${this.baseURL}/frontend/login.html`;
                return;
            }
            
            if (!respuesta.ok && respuesta.status !== 200) {
                throw new Error(datos.message || `Error ${respuesta.status}`);
            }
            
            return datos;
        } catch (error) {
            console.error('Error en request:', error);
            throw error;
        }
    }
    
    /**
     * GET
     */
    async get(ruta) {
        return this.request(ruta, { metodo: 'GET' });
    }
    
    /**
     * POST
     */
    async post(ruta, body) {
        return this.request(ruta, { metodo: 'POST', body });
    }
    
    /**
     * PUT
     */
    async put(ruta, body) {
        return this.request(ruta, { metodo: 'PUT', body });
    }
    
    /**
     * DELETE
     */
    async delete(ruta, body) {
        return this.request(ruta, { metodo: 'DELETE', body });
    }
    
    /**
     * ==== MÉTODOS DE NEGOCIO ====
     */
    
    /**
     * Obtener usuario actual
     */
    async obtenerUsuarioActual() {
        return this.get('/backend/api/auth.php?ruta=user');
    }
    
    /**
     * Cerrar sesión
     */
    async logout() {
        const resultado = await this.post('/backend/api/auth.php?ruta=logout');
        this.limpiarToken();
        return resultado;
    }
    
    /**
     * Obtener todos los productos
     */
    async obtenerProductos() {
        return this.get('/backend/api/productos.php');
    }
    
    /**
     * Obtener un producto
     */
    async obtenerProducto(id) {
        return this.get(`/backend/api/productos.php?id=${id}`);
    }
    
    /**
     * Crear producto
     */
    async crearProducto(datos) {
        return this.post('/backend/api/productos.php', datos);
    }
    
    /**
     * Actualizar producto
     */
    async actualizarProducto(datos) {
        return this.put('/backend/api/productos.php', datos);
    }
    
    /**
     * Eliminar producto
     */
    async eliminarProducto(id) {
        return this.delete('/backend/api/productos.php', { id });
    }
    
    /**
     * Obtener historial de ventas
     */
    async obtenerVentas() {
        return this.get('/backend/api/ventas.php');
    }
    
    /**
     * Obtener detalle de venta
     */
    async obtenerVenta(id) {
        return this.get(`/backend/api/ventas.php?id=${id}`);
    }
    
    /**
     * Registrar venta
     */
    async registrarVenta(datos) {
        return this.post('/backend/api/ventas.php', datos);
    }
}

// Crear instancia global
const api = new APIClient();
