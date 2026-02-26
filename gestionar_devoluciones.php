<?php
require_once 'verificar_sesion.php';
require_once 'config.php';
require_once 'Devolucion.php';

requerirAdmin();

$devolucion = new Devolucion($conn);
$usuario_id = $_SESSION['usuario_id'];

$mensaje = '';
$tipo_mensaje = '';
$venta_encontrada = null;
$detalles_venta = [];

// Manejar AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'buscar_venta') {
        $numero_factura = $_POST['numero_factura'] ?? '';
        
        if (empty($numero_factura)) {
            echo json_encode([
                'success' => false,
                'message' => 'Debe ingresar un número de factura'
            ]);
            exit;
        }
        
        $venta = $devolucion->buscarVentaPorFactura($numero_factura);
        
        if (!$venta) {
            echo json_encode([
                'success' => false,
                'message' => 'Venta no encontrada'
            ]);
            exit;
        }
        
        $detalles = $devolucion->obtenerDetallesVenta($venta['id']);
        
        if (empty($detalles)) {
            echo json_encode([
                'success' => false,
                'message' => 'No hay productos disponibles para devolver en esta venta'
            ]);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'venta' => $venta,
            'detalles' => $detalles
        ]);
        exit;
    }
    
    if ($_POST['action'] === 'procesar_devolucion') {
        $venta_id = $_POST['venta_id'] ?? 0;
        $motivo = $_POST['motivo'] ?? '';
        $productos_json = $_POST['productos'] ?? '[]';
        
        $productos = json_decode($productos_json, true);
        
        if (empty($venta_id) || empty($motivo) || empty($productos)) {
            echo json_encode([
                'success' => false,
                'message' => 'Faltan datos requeridos'
            ]);
            exit;
        }
        
        $resultado = $devolucion->registrarDevolucion($venta_id, $productos, $motivo, $usuario_id);
        echo json_encode($resultado);
        exit;
    }
}

// Obtener historial de devoluciones
$historial = $devolucion->obtenerHistorialDevoluciones(100);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Devoluciones - Sistema Ferretería</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .devolucion-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .buscar-venta {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .buscar-venta h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        
        .form-busqueda {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .form-busqueda input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
        }
        
        .btn-success {
            background-color: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background-color: #229954;
        }
        
        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #7f8c8d;
        }
        
        .venta-info {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: none;
        }
        
        .venta-info.show {
            display: block;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .info-item {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        
        .info-item label {
            font-weight: bold;
            color: #555;
            display: block;
            margin-bottom: 5px;
            font-size: 12px;
        }
        
        .info-item span {
            color: #2c3e50;
            font-size: 14px;
        }
        
        .productos-tabla {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .productos-tabla th,
        .productos-tabla td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .productos-tabla th {
            background-color: #3498db;
            color: white;
            font-weight: bold;
        }
        
        .productos-tabla tr:hover {
            background-color: #f5f5f5;
        }
        
        .cantidad-input {
            width: 80px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }
        
        .checkbox-devolver {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .motivo-devolucion {
            margin: 20px 0;
        }
        
        .motivo-devolucion textarea {
            width: 100%;
            min-height: 100px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
            resize: vertical;
        }
        
        .historial-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 30px;
        }
        
        .historial-container h3 {
            color: #2c3e50;
            margin-top: 0;
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .total-devolucion {
            font-size: 18px;
            font-weight: bold;
            color: #27ae60;
            text-align: right;
            margin-top: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        
        .acciones-devolucion {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-warning {
            background-color: #ffc107;
            color: #000;
        }
    </style>
</head>
<body>
    <div class="devolucion-container">
        <h1>📦 Gestionar Devoluciones</h1>
        <p style="color: #666;">Procesar devoluciones de productos. El inventario se ajustará automáticamente.</p>
        
        <div id="mensaje-container"></div>
        
        <!-- Buscar Venta -->
        <div class="buscar-venta">
            <h3>🔍 Buscar Venta</h3>
            <div class="form-busqueda">
                <input 
                    type="text" 
                    id="numero_factura" 
                    placeholder="Ingrese número de factura (ej: FAC-20260226123456-1234)"
                    autocomplete="off"
                >
                <button class="btn btn-primary" onclick="buscarVenta()">Buscar</button>
                <a href="dashboard.php" class="btn btn-secondary">← Volver</a>
            </div>
        </div>
        
        <!-- Información de la Venta -->
        <div class="venta-info" id="venta-info">
            <h3>📄 Información de la Venta</h3>
            <div class="info-grid" id="info-grid">
                <!-- Se llenará dinámicamente -->
            </div>
            
            <h4>Productos en la Venta</h4>
            <p style="color: #666; font-size: 14px;">Seleccione los productos a devolver e indique la cantidad</p>
            
            <table class="productos-tabla" id="productos-tabla">
                <thead>
                    <tr>
                        <th>Devolver</th>
                        <th>Producto</th>
                        <th>Cantidad Vendida</th>
                        <th>Ya Devuelto</th>
                        <th>Disponible</th>
                        <th>Cantidad a Devolver</th>
                        <th>Precio Unit.</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody id="productos-tbody">
                    <!-- Se llenará dinámicamente -->
                </tbody>
            </table>
            
            <div class="total-devolucion" id="total-devolucion">
                Total a Devolver: $0.00
            </div>
            
            <div class="motivo-devolucion">
                <label for="motivo" style="font-weight: bold; display: block; margin-bottom: 10px;">
                    Motivo de la Devolución *
                </label>
                <textarea 
                    id="motivo" 
                    placeholder="Describa el motivo de la devolución (ej: producto equivocado, defectuoso, etc.)"
                    required
                ></textarea>
            </div>
            
            <div class="acciones-devolucion">
                <button class="btn btn-secondary" onclick="cancelarDevolucion()">Cancelar</button>
                <button class="btn btn-success" onclick="procesarDevolucion()">✓ Procesar Devolución</button>
            </div>
        </div>
        
        <!-- Historial de Devoluciones -->
        <div class="historial-container">
            <h3>📋 Historial de Devoluciones</h3>
            <?php if (empty($historial)): ?>
                <p style="color: #666;">No hay devoluciones registradas</p>
            <?php else: ?>
                <table class="productos-tabla">
                    <thead>
                        <tr>
                            <th>Nº Devolución</th>
                            <th>Fecha</th>
                            <th>Nº Factura</th>
                            <th>Cliente</th>
                            <th>Motivo</th>
                            <th>Total Devuelto</th>
                            <th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historial as $dev): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($dev['numero_devolucion']); ?></strong></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($dev['fecha_devolucion'])); ?></td>
                                <td><?php echo htmlspecialchars($dev['numero_factura']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($dev['cliente_nombre']); ?><br>
                                    <small style="color: #666;"><?php echo htmlspecialchars($dev['cliente_cedula']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars(substr($dev['motivo'], 0, 50)) . (strlen($dev['motivo']) > 50 ? '...' : ''); ?></td>
                                <td style="color: #27ae60; font-weight: bold;">$<?php echo number_format($dev['total_devuelto'], 2); ?></td>
                                <td><?php echo htmlspecialchars($dev['usuario']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        let ventaActual = null;
        let detallesActuales = [];
        
        function buscarVenta() {
            const numero_factura = document.getElementById('numero_factura').value.trim();
            
            if (!numero_factura) {
                mostrarMensaje('Debe ingresar un número de factura', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'buscar_venta');
            formData.append('numero_factura', numero_factura);
            
            fetch('gestionar_devoluciones.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    ventaActual = data.venta;
                    detallesActuales = data.detalles;
                    mostrarInformacionVenta();
                } else {
                    mostrarMensaje(data.message || 'Error al buscar la venta', 'error');
                    ocultarInformacionVenta();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarMensaje('Error al procesar la solicitud', 'error');
            });
        }
        
        function mostrarInformacionVenta() {
            // Mostrar información general
            const infoGrid = document.getElementById('info-grid');
            const totalDevuelto = parseFloat(ventaActual.total_devuelto || 0);
            const numDevoluciones = parseInt(ventaActual.num_devoluciones || 0);
            
            infoGrid.innerHTML = `
                <div class="info-item">
                    <label>Número de Factura:</label>
                    <span>${ventaActual.numero_factura}</span>
                </div>
                <div class="info-item">
                    <label>Cliente:</label>
                    <span>${ventaActual.cliente_nombre}</span>
                </div>
                <div class="info-item">
                    <label>Cédula:</label>
                    <span>${ventaActual.cliente_cedula}</span>
                </div>
                <div class="info-item">
                    <label>Fecha Venta:</label>
                    <span>${formatearFecha(ventaActual.fecha_venta)}</span>
                </div>
                <div class="info-item">
                    <label>Total Venta:</label>
                    <span style="font-weight: bold; color: #2c3e50;">$${parseFloat(ventaActual.total).toFixed(2)}</span>
                </div>
                ${numDevoluciones > 0 ? `
                <div class="info-item">
                    <label>Devoluciones Previas:</label>
                    <span class="badge badge-warning">${numDevoluciones} - $${totalDevuelto.toFixed(2)}</span>
                </div>
                ` : ''}
            `;
            
            // Mostrar productos
            const tbody = document.getElementById('productos-tbody');
            tbody.innerHTML = '';
            
            detallesActuales.forEach((detalle, index) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td style="text-align: center;">
                        <input 
                            type="checkbox" 
                            class="checkbox-devolver" 
                            id="check-${index}"
                            onchange="toggleProducto(${index})"
                        >
                    </td>
                    <td>${detalle.producto_nombre}</td>
                    <td>${detalle.cantidad_vendida}</td>
                    <td>${detalle.cantidad_ya_devuelta}</td>
                    <td style="font-weight: bold;">${detalle.cantidad_disponible_devolver}</td>
                    <td>
                        <input 
                            type="number" 
                            class="cantidad-input" 
                            id="cantidad-${index}"
                            min="1"
                            max="${detalle.cantidad_disponible_devolver}"
                            value="1"
                            disabled
                            onchange="calcularTotal()"
                        >
                    </td>
                    <td>$${parseFloat(detalle.precio_unitario).toFixed(2)}</td>
                    <td id="subtotal-${index}">$0.00</td>
                `;
                tbody.appendChild(tr);
            });
            
            document.getElementById('venta-info').classList.add('show');
            document.getElementById('motivo').value = '';
            calcularTotal();
        }
        
        function ocultarInformacionVenta() {
            document.getElementById('venta-info').classList.remove('show');
            ventaActual = null;
            detallesActuales = [];
        }
        
        function toggleProducto(index) {
            const checkbox = document.getElementById(`check-${index}`);
            const input = document.getElementById(`cantidad-${index}`);
            input.disabled = !checkbox.checked;
            
            if (!checkbox.checked) {
                input.value = 1;
            }
            
            calcularTotal();
        }
        
        function calcularTotal() {
            let total = 0;
            
            detallesActuales.forEach((detalle, index) => {
                const checkbox = document.getElementById(`check-${index}`);
                const input = document.getElementById(`cantidad-${index}`);
                const subtotalElement = document.getElementById(`subtotal-${index}`);
                
                if (checkbox.checked) {
                    const cantidad = parseInt(input.value) || 0;
                    const subtotal = cantidad * parseFloat(detalle.precio_unitario);
                    subtotalElement.textContent = `$${subtotal.toFixed(2)}`;
                    total += subtotal;
                } else {
                    subtotalElement.textContent = '$0.00';
                }
            });
            
            document.getElementById('total-devolucion').textContent = 
                `Total a Devolver: $${total.toFixed(2)}`;
        }
        
        function procesarDevolucion() {
            const motivo = document.getElementById('motivo').value.trim();
            
            if (!motivo) {
                mostrarMensaje('Debe especificar el motivo de la devolución', 'error');
                return;
            }
            
            // Recolectar productos a devolver
            const productos = [];
            detallesActuales.forEach((detalle, index) => {
                const checkbox = document.getElementById(`check-${index}`);
                const input = document.getElementById(`cantidad-${index}`);
                
                if (checkbox.checked) {
                    const cantidad = parseInt(input.value) || 0;
                    if (cantidad > 0 && cantidad <= detalle.cantidad_disponible_devolver) {
                        productos.push({
                            detalle_venta_id: detalle.detalle_id,
                            cantidad_devolver: cantidad
                        });
                    }
                }
            });
            
            if (productos.length === 0) {
                mostrarMensaje('Debe seleccionar al menos un producto para devolver', 'error');
                return;
            }
            
            if (!confirm('¿Está seguro de procesar esta devolución? Esta acción ajustará el inventario y no se puede deshacer.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'procesar_devolucion');
            formData.append('venta_id', ventaActual.id);
            formData.append('motivo', motivo);
            formData.append('productos', JSON.stringify(productos));
            
            fetch('gestionar_devoluciones.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarMensaje(
                        `Devolución procesada exitosamente. Número: ${data.numero_devolucion}. Total: $${data.total_devuelto.toFixed(2)}`,
                        'success'
                    );
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    mostrarMensaje(data.error || 'Error al procesar la devolución', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarMensaje('Error al procesar la solicitud', 'error');
            });
        }
        
        function cancelarDevolucion() {
            if (confirm('¿Desea cancelar esta devolución?')) {
                ocultarInformacionVenta();
                document.getElementById('numero_factura').value = '';
            }
        }
        
        function mostrarMensaje(mensaje, tipo) {
            const container = document.getElementById('mensaje-container');
            const clase = tipo === 'success' ? 'alert-success' : 
                         tipo === 'error' ? 'alert-error' : 'alert-info';
            
            container.innerHTML = `<div class="alert ${clase}">${mensaje}</div>`;
            
            setTimeout(() => {
                container.innerHTML = '';
            }, 5000);
        }
        
        function formatearFecha(fecha) {
            const date = new Date(fecha);
            return date.toLocaleString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        // Permitir buscar con Enter
        document.getElementById('numero_factura').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                buscarVenta();
            }
        });
    </script>
</body>
</html>
