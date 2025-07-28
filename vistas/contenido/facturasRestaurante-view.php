<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Restaurante</title>
    <!-- Estilos -->
    <link rel="stylesheet" href="<?php echo SERVERURL; ?>vistas/plantilla/css/facturasRestaurante.css">
</head>
<body>
    <script>
        // Definir SERVERURL antes de cargar cualquier script
        var SERVERURL = '<?php echo SERVERURL; ?>';
    </script>

    <!-- Vista principal -->
    <div class="restaurante-container">
        <!-- Barra superior de control -->
        <div class="control-bar">
            <div class="control-user">
                <span id="cajero-actual"><i class="fas fa-user"></i> Cajero: <?php echo $_SESSION['nombre_usuario'] ?? 'Usuario'; ?></span>
            </div>
            <div class="control-buttons">
                <button id="btn-volver-dashboard" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Volver al Dashboard
                </button>
                <button id="btn-cerrar-sesion" class="btn btn-danger" data-token="<?php echo $lc->encryption($_SESSION['token_sd']); ?>">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </button>
            </div>
        </div>
        
        <!-- Contenido principal -->
        <div class="restaurante-content">
            <!-- Sidebar de Mesas -->
            <div class="mesas-sidebar">
                <div class="sidebar-header">
                    <h3>Mesas Disponibles</h3>
                    <button id="btn-nueva-mesa" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Mesa
                    </button>
                </div>
                <div class="mesas-list" id="mesas-container">
                    <!-- Las mesas se cargarán aquí dinámicamente -->
                </div>
            </div>

            <!-- Área principal de trabajo -->
            <div class="main-content">
                <!-- Header de la factura -->
                <div class="factura-header">
                    <div class="factura-info">
                        <h2 id="factura-title">Nueva Comanda</h2>
                        <div class="factura-meta">
                            <span id="mesa-seleccionada">Mesa: No seleccionada</span>
                            <span id="cliente-info">Cliente: Consumidor final</span>
                            <button id="btn-cambiar-cliente" class="btn btn-sm btn-primary">
                                <i class="fas fa-user-edit"></i> Cambiar
                            </button>
                        </div>
                    </div>
                    <div class="factura-actions">
                        <button id="btn-guardar" class="btn btn-success">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                        <button id="btn-imprimir" class="btn btn-info" disabled>
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                        <button id="btn-cerrar" class="btn btn-danger">
                            <i class="fas fa-times"></i> Cerrar
                        </button>
                    </div>
                </div>

                <!-- Contenido de la factura -->
                <div class="factura-body">
                    <!-- Panel de productos -->
                    <div class="productos-panel">
                        <div class="productos-header">
                            <h3>Productos</h3>
                            <div class="productos-search">
                                <input type="text" id="buscar-producto" placeholder="Buscar producto...">
                                <button id="btn-buscar"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                        <div class="categorias-tabs">
                            <!-- Las categorías se cargarán dinámicamente -->
                        </div>
                        <div class="productos-grid" id="productos-container">
                            <!-- Los productos se cargarán aquí dinámicamente -->
                        </div>
                    </div>

                    <!-- Panel de la comanda -->
                    <div class="comanda-panel">
                        <div class="comanda-header">
                            <h3>Comanda</h3>
                        </div>
                        <div class="comanda-items" id="comanda-items">
                            <!-- Los items de la comanda se agregarán aquí dinámicamente -->
                        </div>
                        <div class="comanda-totales">
                            <div class="totales-row">
                                <span>Subtotal:</span>
                                <span id="subtotal">L 0.00</span>
                            </div>
                            <div class="totales-row">
                                <span>Impuesto (15%):</span>
                                <span id="impuesto">L 0.00</span>
                            </div>
                            <div class="totales-row total">
                                <span>Total:</span>
                                <span id="total">L 0.00</span>
                            </div>
                        </div>
                        <div class="comanda-observaciones">
                            <label for="observaciones">Observaciones:</label>
                            <textarea id="observaciones" placeholder="Notas especiales..."></textarea>
                        </div>
                        <div class="comanda-pago">
                            <h4>Método de Pago</h4>
                            <div class="pago-options">
                                <label class="radio-container">Efectivo
                                    <input type="radio" name="metodo-pago" value="efectivo" checked>
                                    <span class="radio-checkmark"></span>
                                </label>
                                <label class="radio-container">Tarjeta
                                    <input type="radio" name="metodo-pago" value="tarjeta">
                                    <span class="radio-checkmark"></span>
                                </label>
                                <label class="radio-container">Transferencia
                                    <input type="radio" name="metodo-pago" value="transferencia">
                                    <span class="radio-checkmark"></span>
                                </label>
                            </div>
                        </div>
                        <div class="comanda-actions">
                            <button id="btn-limpiar" class="btn btn-warning">
                                <i class="fas fa-broom"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para nueva mesa -->
    <div id="modal-mesa" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Nueva Mesa</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="form-mesa">
                    <div class="form-group">
                        <label for="numero-mesa">Número de Mesa</label>
                        <input type="text" id="numero-mesa" required>
                    </div>
                    <div class="form-group">
                        <label for="capacidad-mesa">Capacidad</label>
                        <input type="number" id="capacidad-mesa" min="1" value="4" required>
                    </div>
                    <div class="form-group">
                        <label for="ubicacion-mesa">Ubicación</label>
                        <select id="ubicacion-mesa">
                            <option value="Interior">Interior</option>
                            <option value="Terraza">Terraza</option>
                            <option value="Barra">Barra</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar Mesa</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para seleccionar cliente -->
    <div id="modal-cliente" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Seleccionar Cliente</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div class="search-container">
                    <input type="text" id="buscar-cliente" placeholder="Buscar cliente...">
                    <button id="btn-buscar-cliente"><i class="fas fa-search"></i></button>
                </div>
                <div class="clientes-list" id="clientes-container">
                    <!-- Los clientes se cargarán aquí -->
                </div>
                <div class="modal-footer">
                    <button id="btn-nuevo-cliente" class="btn btn-success">
                        <i class="fas fa-plus"></i> Nuevo Cliente
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?php echo SERVERURL; ?>ajax/js/facturasRestaurante.js"></script>
</body>
</html>