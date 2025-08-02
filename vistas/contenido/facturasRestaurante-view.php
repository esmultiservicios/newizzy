<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sistema de Restaurante</title>
    <!-- Estilos -->
    <link rel="stylesheet" href="<?php echo SERVERURL; ?>vistas/plantilla/css/facturasRestaurante.css">
</head>
<body>
    <script>
        // Definir SERVERURL antes de cargar cualquier script
        var SERVERURL = '<?php echo SERVERURL; ?>';
    </script>

    <!-- Vista principal optimizada para tablet -->
    <div class="restaurante-container">
        <!-- Barra superior de control -->
        <div class="control-bar">
            <div class="control-user">
                <span id="cajero-actual"><i class="fas fa-user"></i> Cajero: <?php echo $_SESSION['nombre_usuario'] ?? 'Usuario'; ?></span>
            </div>
            <div class="control-buttons">
                <button id="btn-volver-dashboard" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Volver
                </button>
                <button id="btn-cerrar-sesion" class="btn btn-danger" data-token="<?php echo $lc->encryption($_SESSION['token_sd']); ?>">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </button>
            </div>
        </div>
        
        <!-- Contenido principal -->
        <div class="restaurante-content">
            <!-- Sidebar de Mesas (oculto por defecto en móvil) -->
            <div class="mesas-sidebar">
                <div class="sidebar-header">
                    <h3><i class="fas fa-chair"></i> Mesas</h3>
                    <button id="btn-nueva-mesa" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Nueva
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
                        <h2 id="factura-title"><i class="fas fa-receipt"></i> Nueva Comanda</h2>
                        <div class="factura-meta">
                            <span id="mesa-seleccionada"><i class="fas fa-table"></i> No seleccionada</span>
                            <span id="cliente-info"><i class="fas fa-user"></i> Consumidor final</span>
                            <button id="btn-cambiar-cliente" class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i> Cambiar
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

                <!-- Agrega esto después del div con clase factura-header -->
                <button id="btn-mostrar-productos" class="btn btn-primary btn-mostrar-productos" style="display: none;">
                    <i class="fas fa-box-open"></i> Ver Productos
                </button>
                <button id="btn-mostrar-comanda" class="btn btn-info btn-mostrar-comanda" style="display: none;">
                    <i class="fas fa-clipboard-list"></i> Ver Comanda
                </button>

                <!-- Contenido de la factura -->
                <div class="factura-body">
                    <!-- Panel de productos -->
                    <div class="productos-panel">
                        <div class="productos-header">
                            <h3><i class="fas fa-boxes"></i> Productos</h3>
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

                    <!-- Panel de la comanda optimizado para tablet -->
                    <div class="comanda-panel">
                        <div class="comanda-header">
                            <h3><i class="fas fa-clipboard-list"></i> Comanda</h3>
                            <button id="btn-limpiar" class="btn btn-warning btn-sm">
                                <i class="fas fa-broom"></i> Limpiar
                            </button>
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
                            <label for="observaciones"><i class="fas fa-sticky-note"></i> Observaciones:</label>
                            <textarea id="observaciones" placeholder="Notas especiales..."></textarea>
                        </div>
                        <div class="comanda-pago">
                            <h4><i class="fas fa-money-bill-wave"></i> Método de Pago</h4>
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para nueva mesa -->
    <div id="modal-mesa" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Nueva Mesa</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="form-mesa">
                    <div class="form-group">
                        <label for="numero-mesa"><i class="fas fa-hashtag"></i> Número de Mesa</label>
                        <input type="text" id="numero-mesa" required>
                    </div>
                    <div class="form-group">
                        <label for="capacidad-mesa"><i class="fas fa-users"></i> Capacidad</label>
                        <input type="number" id="capacidad-mesa" min="1" value="4" required>
                    </div>
                    <div class="form-group">
                        <label for="ubicacion-mesa"><i class="fas fa-map-marker-alt"></i> Ubicación</label>
                        <select id="ubicacion-mesa">
                            <option value="Interior">Interior</option>
                            <option value="Terraza">Terraza</option>
                            <option value="Barra">Barra</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save"></i> Guardar Mesa
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para seleccionar cliente -->
    <div id="modal-cliente" class="modal">
        <div class="modal-content modal-centered">
            <div class="modal-header">
                <h3><i class="fas fa-user-tag"></i> Seleccionar Cliente</h3>
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