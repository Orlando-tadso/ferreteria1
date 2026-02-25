# 📋 INSTRUCCIONES: Ejecutar Migración de Base de Datos

## ⚠️ IMPORTANTE - LEER ANTES DE CONTINUAR

Se agregaron campos nuevos a la base de datos para almacenar **correo electrónico y teléfono** de los clientes en las ventas.

Esto es necesario para la futura integración con **facturación electrónica DIAN**.

---

## 🔧 Pasos para actualizar la base de datos en Railway:

### **Opción 1: Usar el script web (RECOMENDADO)**

1. Abre tu navegador
2. Ve a: `https://tu-dominio.railway.app/agregar_campos_cliente.php`
3. La migración se ejecutará automáticamente
4. Verás un mensaje de confirmación ✅
5. **¡Listo!** Ya puedes usar el sistema actualizado

### **Opción 2: Ejecutar SQL manualmente**

Si prefieres hacerlo mediante SQL directo en Railway:

1. Ve al panel de Railway
2. Abre tu servicio MySQL
3. Ve a la pestaña **"Query"**
4. Ejecuta este SQL:

```sql
ALTER TABLE ventas ADD COLUMN cliente_email VARCHAR(150) NULL AFTER cliente_cedula;
ALTER TABLE ventas ADD COLUMN cliente_telefono VARCHAR(20) NULL AFTER cliente_email;
```

---

## ✅ ¿Qué se agregó?

### **En el Punto de Venta:**
- Campo para **Email del cliente** (opcional)
- Campo para **Teléfono/Celular** (opcional)

### **En el Historial de Ventas:**
- Muestra email y teléfono en los detalles de cada venta

### **En los Tickets:**
- Email y teléfono aparecen en las facturas impresas

---

## 🎯 ¿Para qué sirve?

1. **Preparación para facturación electrónica DIAN**
   - El correo es OBLIGATORIO para enviar facturas electrónicas
   - El teléfono es recomendado

2. **Mejor gestión de clientes**
   - Contacto directo con clientes
   - Base de datos para marketing

3. **Cumplimiento normativo**
   - Estar listo cuando la DIAN lo requiera

---

## ❓ Preguntas Frecuentes

**¿Es obligatorio llenar estos campos?**
- **Nombre y cédula:** SÍ (obligatorios)
- **Email y teléfono:** NO (opcionales por ahora)

**¿Los datos antiguos se pierden?**
- NO. Las ventas anteriores siguen intactas
- Solo las nuevas ventas tendrán estos campos

**¿Puedo ejecutar la migración dos veces?**
- SÍ. El script detecta si ya se ejecutó y no la repite

---

## 📞 Soporte

Si tienes problemas con la migración, contacta al desarrollador.

---

**Fecha de actualización:** 24 de febrero de 2026
