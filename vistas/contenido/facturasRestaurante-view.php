<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sistema de Restaurante</title>
    <!-- Estilos -->
    <link rel="stylesheet" href="<?php echo SERVERURL; ?>vistas/plantilla/css/facturasRestaurante.css">
    <!-- (opcional) FontAwesome si usas los íconos -->
    <link rel="stylesheet" href="<?php echo SERVERURL; ?>fontawesome/css/all.min.css">
</head>
<body class="vista-facturacion-restaurante">
    <script>
        var SERVERURL = '<?php echo SERVERURL; ?>';
    </script>

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
            <!-- Sidebar de Mesas -->
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
                            <!-- Botón adicional siempre visible para crear cliente rápido -->
                            <button id="btn-nuevo-cliente-rapido" class="btn btn-sm btn-success">
                                <i class="fas fa-user-plus"></i> Crear cliente
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

                <!-- Botones móviles para alternar vistas -->
                <button id="btn-mostrar-productos" class="btn btn-primary btn-mostrar-productos" style="display: none;">
                    <i class="fas fa-box-open"></i> Ver Productos
                </button>
                <button id="btn-mostrar-comanda" class="btn btn-info btn-mostrar-comanda" style="display: none;">
                    <i class="fas fa-clipboard-list"></i> Ver Comanda
                </button>

                <!-- Cuerpo -->
                <div class="factura-body">
                    <!-- Panel de productos -->
                    <div class="productos-panel" id="panel-productos">
                        <div class="productos-header">
                            <h3><i class="fas fa-boxes"></i> Productos</h3>
                            <div class="productos-search">
                                <input type="text" id="buscar-producto" placeholder="Buscar producto...">
                                <button id="btn-buscar" class="btn btn-light btn-sm"><i class="fas fa-search"></i></button>
                            </div>
                            <div class="productos-actions">
                                <button id="btn-nueva-categoria" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-folder-plus"></i> + Categoría
                                </button>
                                <button id="btn-nuevo-producto" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Nuevo producto
                                </button>
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
                    <div class="comanda-panel" id="panel-comanda">
                        <div class="comanda-header">
                            <h3><i class="fas fa-clipboard-list"></i> Comanda</h3>
                            <button id="btn-limpiar" class="btn btn-warning btn-sm">
                                <i class="fas fa-broom"></i> Limpiar
                            </button>
                        </div>
                        <div class="comanda-items" id="comanda-items">
                            <!-- Items de comanda -->
                        </div>
                        <div class="comanda-totales">
                            <div class="totales-row">
                                <span>Subtotal:</span>
                                <span id="subtotal">L 0.00</span>
                            </div>
                            <div class="totales-row">
                                <span id="impuesto1-label">Impuesto (ISV 1):</span>
                                <span id="impuesto1">L 0.00</span>
                            </div>
                            <div class="totales-row">
                                <span id="impuesto2-label">Impuesto (ISV 2):</span>
                                <span id="impuesto2">L 0.00</span>
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
                                <label class="radio-container"><i class="fas fa-money-bill"></i> Efectivo
                                    <input type="radio" name="metodo-pago" value="" checked>
                                    <span class="radio-checkmark"></span>
                                </label>
                                <label class="radio-container"><i class="fas fa-credit-card"></i> Tarjeta
                                    <input type="radio" name="metodo-pago" value="tarjeta">
                                    <span class="radio-checkmark"></span>
                                </label>
                                <label class="radio-container"><i class="fas fa-university"></i> Transferencia
                                    <input type="radio" name="metodo-pago" value="transferencia">
                                    <span class="radio-checkmark"></span>
                                </label>
                                <!-- Nota: valor vacío = guardar como borrador (pedido tomado por mesero) -->
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
                <span class="close" data-close="#modal-mesa">&times;</span>
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
                <span class="close" data-close="#modal-cliente">&times;</span>
            </div>
            <div class="modal-body">
                <div class="search-container">
                    <input type="text" id="buscar-cliente" placeholder="Buscar cliente...">
                    <button id="btn-buscar-cliente" class="btn btn-light btn-sm"><i class="fas fa-search"></i></button>
                </div>
                <div class="clientes-list" id="clientes-container">
                    <!-- Los clientes se cargarán aquí -->
                </div>
                <div class="modal-footer">
                    <button id="btn-nuevo-cliente" class="btn btn-success">
                        <i class="fas fa-user-plus"></i> Nuevo Cliente
                    </button>
                    <button class="btn btn-danger" data-close="#modal-cliente">
                        <i class="fas fa-times"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Registro Rápido de Cliente -->
    <div id="modal-nuevo-cliente" class="modal">
        <div class="modal-content" style="max-width:560px;">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> Nuevo Cliente</h3>
                <span class="close" data-close="#modal-nuevo-cliente">&times;</span>
            </div>
            <div class="modal-body">
                <form id="form-nuevo-cliente">
                    <div class="form-group">
                        <label><i class="fas fa-signature"></i> Nombre / Razón social *</label>
                        <input type="text" id="cli-nombre" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-id-card"></i> RTN / Identidad</label>
                        <input type="text" id="cli-rtn" maxlength="14">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Localidad / Dirección</label>
                        <input type="text" id="cli-localidad" maxlength="150">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Teléfono</label>
                        <input type="text" id="cli-telefono" maxlength="8">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Correo</label>
                        <input type="email" id="cli-correo" maxlength="70">
                    </div>

                    <div class="modal-footer" style="margin-top:10px;">
                        <button class="btn btn-danger" type="button" data-close="#modal-nuevo-cliente">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button class="btn btn-success" type="submit">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Nueva Categoría -->
    <div id="modal-categoria" class="modal">
        <div class="modal-content" style="max-width:480px;">
            <div class="modal-header">
                <h3><i class="fas fa-folder-plus"></i> Nueva Categoría</h3>
                <span class="close" data-close="#modal-categoria">&times;</span>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="cat-nombre"><i class="fas fa-tag"></i> Nombre de la categoría</label>
                    <input type="text" id="cat-nombre" placeholder="Ej. Bebidas" />
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger" data-close="#modal-categoria">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button id="btn-guardar-categoria" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Nuevo Producto -->
    <div id="modal-producto" class="modal">
        <div class="modal-content" style="max-width:760px;">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Nuevo Producto</h3>
                <span class="close" data-close="#modal-producto">&times;</span>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="prod-nombre"><i class="fas fa-quote-left"></i> Nombre</label>
                    <input type="text" id="prod-nombre" placeholder="Ej. Refresco Pepsi" />
                </div>
                <div class="form-group">
                    <label for="prod-descripcion"><i class="fas fa-align-left"></i> Descripción (opcional)</label>
                    <input type="text" id="prod-descripcion" placeholder="Descripción corta" />
                </div>
                <div class="form-group">
                    <label for="prod-categoria"><i class="fas fa-sitemap"></i> Categoría</label>
                    <select id="prod-categoria"></select>
                </div>
                <div class="form-group">
                    <label for="prod-precio"><i class="fas fa-dollar-sign"></i> Precio de venta</label>
                    <input type="number" id="prod-precio" step="0.01" min="0" value="0.00" />
                </div>
                <div class="form-group" style="display:flex; gap:14px; flex-wrap:wrap;">
                    <label class="radio-container"><input type="checkbox" id="prod-isv1"/> ISV 1</label>
                    <label class="radio-container"><input type="checkbox" id="prod-isv2"/> ISV 2</label>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger" data-close="#modal-producto">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button id="btn-guardar-producto" class="btn btn-success">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?php echo SERVERURL; ?>ajax/query/jquery-3.5.1.min.js"></script>
    <script src="<?php echo SERVERURL; ?>ajax/sweetalert/sweetalert.min.js"></script>
    <script src="<?php echo SERVERURL; ?>ajax/js/facturasRestaurante.js"></script>
</body>
</html>
