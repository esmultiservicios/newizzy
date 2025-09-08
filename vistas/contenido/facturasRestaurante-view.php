<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sistema de Restaurante</title>
    <!-- FontAwesome (iconos) -->
    <link rel="stylesheet" href="<?php echo SERVERURL; ?>fontawesome/css/all.min.css">    
    <!-- Estilos principales -->
    <link rel="stylesheet" href="<?php echo SERVERURL; ?>vistas/plantilla/css/facturasRestaurante.css">
    <!-- Select2 CSS (para búsqueda en selects) -->
    <link rel="stylesheet" href="<?php echo SERVERURL; ?>vistas/plantilla/css/select2.min.css">
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
                    <div class="sidebar-actions">
                        <button id="btn-nueva-mesa" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Nueva
                        </button>
                    </div>
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
                                <i class="fas fa-user-switch"></i> Cambiar
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
                                <!-- BOTÓN PARA GESTIONAR COMBOS -->
                                <button id="btn-gestionar-combos" class="btn btn-info btn-sm">
                                    <i class="fas fa-layer-group"></i> Combos
                                </button>
                            </div>
                        </div>

                        <!-- Filtro por estación (Todas / Cocina / Barra) -->
                        <div class="estacion-filter" id="filtro-estacion">
                            <div class="segmented-control">
                                <input type="radio" name="filEst" id="fil-est-todas" value="todas" checked>
                                <label for="fil-est-todas">Todas</label>
                                <input type="radio" name="filEst" id="fil-est-cocina" value="cocina">
                                <label for="fil-est-cocina">Cocina</label>
                                <input type="radio" name="filEst" id="fil-est-barra" value="barra">
                                <label for="fil-est-barra">Barra</label>
                            </div>
                        </div>

                        <div class="categorias-tabs" id="categorias-tabs">
                            <!-- Las categorías se cargarán dinámicamente (cada chip tendrá botón editar) -->
                        </div>
                        <div class="productos-grid" id="productos-container">
                            <!-- Los productos se cargarán aquí dinámicamente; cada tarjeta tendrá botón editar -->
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

    <!-- Modal para nueva/editar mesa -->
    <div id="modal-mesa" class="modal rs-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> <span id="titulo-modal-mesa">Nueva Mesa</span></h3>
                <span class="close" data-close="#modal-mesa">&times;</span>
            </div>
            <div class="modal-body">
                <form id="form-mesa">
                    <input type="hidden" id="mesa-id" value="">
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
                        <select id="ubicacion-mesa" class="select2">
                            <option value="Interior">Interior</option>
                            <option value="Terraza">Terraza</option>
                            <option value="Barra">Barra</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="estado-mesa"><i class="fas fa-traffic-light"></i> Estado</label>
                        <select id="estado-mesa" class="select2">
                            <option value="">(auto)</option>
                            <option value="disponible">Disponible</option>
                            <option value="ocupada">Ocupada</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save"></i> Guardar Mesa
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ===== MODAL: SELECCIONAR CLIENTE ===== -->
    <div id="modal-cliente" class="modal rs-modal" role="dialog" aria-modal="true" aria-labelledby="titulo-modal-selector-cliente">
        <div class="modal-content">
            <div class="modal-header">
            <h3 id="titulo-modal-selector-cliente"><i class="fas fa-user-friends"></i> Seleccionar cliente</h3>
            <span class="close" data-close="#modal-cliente" title="Cerrar">&times;</span>
            </div>

            <div class="modal-body">
            <!-- Buscador -->
            <div class="search-container">
                <input type="text" id="buscar-cliente" placeholder="Buscar por nombre o identificación">
                <button id="btn-buscar-cliente" type="button" title="Buscar"><i class="fas fa-search"></i></button>
            </div>

            <!-- Lista -->
            <div id="clientes-container" class="clientes-list"></div>
            </div>

            <div class="modal-footer">
            <button class="btn btn-danger" data-close="#modal-cliente" type="button"><i class="fas fa-times"></i> Cerrar</button>

            <button id="btn-nuevo-cliente" class="btn btn-primary" type="button">
                <i class="fas fa-user-plus"></i> Nuevo cliente
            </button>

            <button id="btn-editar-cliente-seleccionado" class="btn btn-info" type="button" disabled>
                <i class="fas fa-user-edit"></i> Editar seleccionado
            </button>

            <button id="btn-seleccionar-cliente" class="btn btn-success" type="button" disabled>
                <i class="fas fa-check"></i> Seleccionar
            </button>
            </div>
        </div>
    </div>

    <!-- ===== MODAL: NUEVO/EDITAR CLIENTE ===== -->
    <div id="modal-nuevo-cliente" class="modal rs-modal" role="dialog" aria-modal="true" aria-labelledby="titulo-modal-cliente">
        <div class="modal-content">
            <div class="modal-header">
            <h3 id="titulo-modal-cliente"><i class="fas fa-user-edit"></i> Nuevo Cliente</h3>
            <span class="close" data-close="#modal-nuevo-cliente" title="Cerrar">&times;</span>
            </div>

            <div class="modal-body">
            <form id="form-nuevo-cliente" autocomplete="off">
                <input type="hidden" id="cli-id">

                <div class="form-group">
                <label for="cli-nombre"><i class="fas fa-signature"></i> Nombre / Razón social *</label>
                <input type="text" id="cli-nombre" required>
                </div>

                <div class="form-group">
                <label for="cli-rtn"><i class="fas fa-id-card"></i> Identificación / RTN</label>
                <input type="text" id="cli-rtn" placeholder="Opcional">
                </div>

                <div class="form-group">
                <label for="cli-localidad"><i class="fas fa-map-marker-alt"></i> Localidad</label>
                <input type="text" id="cli-localidad" placeholder="Barrio/Colonia">
                </div>

                <div class="form-group">
                <label for="cli-telefono"><i class="fas fa-phone"></i> Teléfono</label>
                <input type="text" id="cli-telefono" placeholder="+504 ...">
                </div>

                <div class="form-group">
                <label for="cli-correo"><i class="fas fa-envelope"></i> Correo</label>
                <input type="email" id="cli-correo" placeholder="cliente@correo.com">
                </div>
            </form>
            </div>

            <div class="modal-footer">
            <button class="btn btn-danger" data-close="#modal-nuevo-cliente" type="button">
                <i class="fas fa-times"></i> Cancelar
            </button>
            <button class="btn btn-success" type="submit" form="form-nuevo-cliente">
                <i class="fas fa-save"></i> Guardar
            </button>
            </div>
        </div>
    </div>

    <!-- Modal Nueva/Editar Categoría -->
    <div id="modal-categoria" class="modal rs-modal">
        <div class="modal-content" style="max-width:480px;">
            <div class="modal-header">
                <h3><i class="fas fa-folder-plus"></i> <span id="titulo-modal-categoria">Nueva Categoría</span></h3>
                <span class="close" data-close="#modal-categoria">&times;</span>
            </div>
            <div class="modal-body">
                <input type="hidden" id="cat-id" value="">
                <div class="form-group">
                    <label for="cat-nombre"><i class="fas fa-tag"></i> Nombre de la categoría</label>
                    <input type="text" id="cat-nombre" placeholder="Ej. Bebidas" />
                </div>
                <!-- === Estación para la CATEGORÍA (Cocina/Barra/Ninguna) === -->
                <div class="form-group">
                    <label class="label-strong" for="cat-estacion">Estación (ruta de comanda)</label>
                    <div class="segmented-control" id="cat-estacion">
                        <input type="radio" name="catEstacion" id="cat-est-ninguna" value="ninguna" checked>
                        <label for="cat-est-ninguna" title="No se envía a ninguna estación">Ninguna</label>

                        <input type="radio" name="catEstacion" id="cat-est-cocina" value="cocina">
                        <label for="cat-est-cocina" title="Todo producto de esta categoría va a Cocina">Cocina</label>

                        <input type="radio" name="catEstacion" id="cat-est-barra" value="barra">
                        <label for="cat-est-barra" title="Todo producto de esta categoría va a Barra">Barra</label>
                    </div>
                    <small class="hint">
                        Si eliges <b>Cocina</b>, los productos de esta categoría se imprimirán en cocina por defecto. 
                        Si eliges <b>Barra</b>, se imprimirán en barra. <b>Ninguna</b> no manda comanda.
                    </small>
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

    <!-- Modal Nuevo/Editar Producto -->
    <div id="modal-producto" class="modal rs-modal">
        <div class="modal-content" style="max-width:760px;">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> <span id="titulo-modal-producto">Nuevo Producto</span></h3>
                <span class="close" data-close="#modal-producto">&times;</span>
            </div>
            <div class="modal-body">
                <input type="hidden" id="prod-id" value="">

                <!-- === Ruteo / Estación para el PRODUCTO (filtra las categorías visibles) === -->
                <div class="form-group">
                    <label class="label-strong">¿A qué estación pertenece este producto?</label>
                    <div class="segmented-control" id="prod-estacion">
                        <input type="radio" name="prodEstacion" id="prod-est-cocina" value="cocina" checked>
                        <label for="prod-est-cocina">Cocina</label>

                        <input type="radio" name="prodEstacion" id="prod-est-barra" value="barra">
                        <label for="prod-est-barra">Barra</label>

                        <input type="radio" name="prodEstacion" id="prod-est-ninguna" value="ninguna">
                        <label for="prod-est-ninguna">Ninguna</label>
                    </div>
                    <small class="hint">
                        Al elegir la estación, el selector de <b>categoría</b> te mostrará solo las categorías de esa estación.
                    </small>
                </div>

                <!-- Mensaje informativo (se actualiza solo) -->
                <div class="form-group" id="prod-estacion-info" style="display:none;">
                    <div class="info-chip">
                        Estación de la categoría seleccionada: <b id="prod-estacion-info-val">—</b>
                    </div>
                </div>

                <div class="form-group">
                    <label for="prod-categoria"><i class="fas fa-sitemap"></i> Categoría</label>
                    <!-- Select2 habilitado -->
                    <select id="prod-categoria" class="select2" data-placeholder="Selecciona una categoría"></select>
                </div>

                <div class="form-group">
                    <label for="prod-nombre"><i class="fas fa-quote-left"></i> Nombre</label>
                    <input type="text" id="prod-nombre" placeholder="Ej. Refresco Pepsi" />
                </div>

                <div class="form-group">
                    <label for="prod-descripcion"><i class="fas fa-align-left"></i> Descripción (opcional)</label>
                    <input type="text" id="prod-descripcion" placeholder="Descripción corta" />
                </div>

                <div class="form-group">
                    <label for="prod-precio"><i class="fas fa-dollar-sign"></i> Precio de venta</label>
                    <input type="number" id="prod-precio" step="0.01" min="0" value="" placeholder="0.00"/>
                </div>

                <div class="form-group" style="display:flex; gap:14px; flex-wrap:wrap;">
                    <label class="radio-container"><input type="checkbox" id="prod-isv1"/> ISV 1</label>
                    <label class="radio-container"><input type="checkbox" id="prod-isv2"/> ISV 2</label>
                </div>

                <!-- Uploader de imagen -->
                <div class="file-upload-container">
                    <label><i class="fas fa-image mr-1"></i> Imagen del Producto</label>
                    <div class="file-upload-area image-upload-area" id="productoDropArea" tabindex="0" aria-label="Zona para arrastrar y soltar imagen">
                        <i class="fas fa-image fa-3x mb-2"></i>
                        <p class="file-upload-instructions">
                            <span class="drag-text">Arrastra la imagen aquí</span>
                            <button class="btn btn-sm btn-secondary" id="btnSeleccionarImagen" type="button">Seleccionar imagen</button>
                            <input type="file" id="imagen_producto" name="imagen_producto" accept="image/*" class="file-upload-input">
                            <span class="paste-text">o pega (Ctrl+V)</span>
                        </p>
                        <div class="file-preview" id="productoPreview"></div>
                    </div>
                    <div class="file-info" id="productoInfo">Ningún archivo seleccionado</div>
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

    <!-- =================== MODAL: LISTA DE COMBOS (Tarjetas) =================== -->
    <div id="modal-combos" class="modal rs-modal">
        <div class="modal-content" style="max-width:980px;">
            <div class="modal-header">
                <h3><i class="fas fa-layer-group"></i> Combos</h3>
                <span class="close" data-close="#modal-combos">&times;</span>
            </div>
            <div class="modal-body">
                <div class="inline-actions" style="margin-bottom:10px;">
                    <button id="btn-nuevo-combo" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Nuevo combo</button>
                    <span class="muted help-message">Define un producto “combo” y sus componentes.</span>
                </div>

                <!-- Grid de tarjetas -->
                <div id="combos-grid" class="combos-grid">
                  <!-- tarjetas dinámicas -->
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-close="#modal-combos"><i class="fas fa-times"></i> Cerrar</button>
            </div>
        </div>
    </div>
    
    <!-- =================== MODAL: EDITOR DE COMBO (Componentes en tarjetas) =================== -->
    <div id="modal-combo-editor" class="modal rs-modal" role="dialog" aria-modal="true" aria-labelledby="titulo-modal-combo">
        <div class="modal-content" style="max-width:1080px;">
            <div class="modal-header">
            <h3 id="titulo-modal-combo"><i class="fas fa-layer-group"></i> Nuevo combo</h3>
            <span class="close" data-close="#modal-combo-editor" title="Cerrar">&times;</span>
            </div>

            <div class="modal-body">
            <div id="combo-help-message" class="mb-2"></div>

            <!-- Display solo en edición -->
            <div id="combo-producto-display" style="display:none"></div>

            <!-- Selector en creación -->
            <div id="combo-producto-container">
                <div class="form-group">
                <label class="label-strong">Producto que representa el combo</label>
                <select id="combo-producto" class="select2" data-placeholder="Selecciona el producto combo">
                    <option value=""></option>
                </select>
                <p id="combo-producto-help" class="help-text"></p>
                </div>
            </div>

            <div class="form-group">
                <div id="combo-activo-container"></div>
            </div>

            <hr>

            <h4>Componentes</h4>
            <p class="help-text">Organiza por <strong>Grupo</strong> (ej. Bebida, Acompañante). Usa <strong>Max selección</strong> cuando corresponda.</p>

            <div id="combo-items-container"></div>

            <div class="mt-2">
                <button type="button" id="btn-add-combo-item" class="btn btn-secondary">
                <i class="fas fa-plus"></i> Agregar componente
                </button>
            </div>
            </div>

            <div class="modal-footer">
            <button type="button" class="btn btn-danger" data-close="#modal-combo-editor"><i class="fas fa-times"></i> Cancelar</button>
            <button type="button" id="btn-guardar-combo" class="btn btn-primary"><i class="fas fa-save"></i> Guardar combo</button>
            </div>
        </div>
    </div>

    <!-- Scripts (orden correcto) -->
    <script src="<?php echo SERVERURL; ?>ajax/query/jquery-3.5.1.min.js"></script>
    <script src="<?php echo SERVERURL; ?>ajax/sweetalert/sweetalert.min.js"></script>
    <!-- Select2 JS (requiere jQuery cargado antes) -->
    <script src="<?php echo SERVERURL; ?>ajax/librerias/select2.min.js"></script>
    <!-- Tu JS principal -->
    <script src="<?php echo SERVERURL; ?>ajax/js/facturasRestaurante.js"></script>
</body>
</html>
