/**
 * Utilidades para el Frontend
 */

/**
 * Mostrar notificación
 */
function mostrarNotificacion(mensaje, tipo = 'info') {
    const notif = document.createElement('div');
    notif.className = `notificacion notificacion-${tipo}`;
    notif.textContent = mensaje;
    
    document.body.appendChild(notif);
    
    setTimeout(() => {
        notif.remove();
    }, 4000);
}

/**
 * Formatear moneda
 */
function formatearMoneda(valor) {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0
    }).format(valor);
}

/**
 * Formatear fecha
 */
function formatearFecha(fecha) {
    return new Intl.DateTimeFormat('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(new Date(fecha));
}

/**
 * Validar email
 */
function validarEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

/**
 * Validar cédula
 */
function validarCedula(cedula) {
    return cedula && cedula.trim().length > 0;
}

/**
 * Obtener parámetro de URL
 */
function obtenerParametroURL(nombre) {
    const params = new URLSearchParams(window.location.search);
    return params.get(nombre);
}

/**
 * Redirigir si no está autenticado
 */
async function verificarAutenticacion() {
    try {
        const resultado = await api.obtenerUsuarioActual();
        if (!resultado.success) {
            window.location.href = '/ferreteria1/login.html';
        }
    } catch (error) {
        window.location.href = '/ferreteria1/login.html';
    }
}

/**
 * Cargar HTML desde archivo
 */
async function cargarHTML(ruta) {
    try {
        const respuesta = await fetch(ruta);
        return await respuesta.text();
    } catch (error) {
        console.error('Error cargando HTML:', error);
        return '';
    }
}
