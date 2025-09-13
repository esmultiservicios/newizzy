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
  <!-- Select2 CSS -->
  <link rel="stylesheet" href="<?php echo SERVERURL; ?>vistas/plantilla/css/select2.min.css">
</head>
<body class="vista-facturacion-restaurante">
  <script>var SERVERURL = '<?php echo SERVERURL; ?>';</script>

  <div class="restaurante-container">
    <!-- Barra superior de control -->
    <div class="control-bar">
      <div class="control-user">
        <span id="cajero-actual">
          <i class="fas fa-user"></i> <span id="cajero-nombre"></span>
        </span>
      </div>

      <!-- CENTRO: SOLO el counter -->
      <div class="control-center">
        <div id="factura-counter" class="control-counter counter-normal" title="">
          <i class="fas fa-file-invoice"></i>
          <span id="factura-disponibles" class="counter-value">Cargando…</span>
        </div>
      </div>

      <div class="control-buttons">
        <button id="btn-volver-dashboard" class="btn btn-light">
          <i class="fas fa-arrow-left"></i> Volver
        </button>
        <button id="btn-help" class="btn btn-info">
          <i class="fas fa-circle-question"></i> Ayuda
        </button>
        <button id="btn-cerrar-sesion" class="btn btn-danger"
                data-token="<?php echo $lc->encryption($_SESSION['token_sd']); ?>">
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
          <!-- Fila superior: Título a la izquierda / Acciones a la derecha -->
          <div class="fh-row fh-row-top">
            <div class="factura-info">
              <h2 id="factura-title"><i class="fas fa-receipt"></i> Nueva Comanda</h2>
            </div>

            <!-- Acciones de la factura (MISMA LÍNEA, DERECHA) -->
            <div class="factura-actions">
              <button id="btn-apertura-caja" class="btn btn-primary">
                <i class="fas fa-lock-open"></i> Aperturar Caja
              </button>

              <button id="btn-guardar" class="btn btn-success">
                <i class="fas fa-save"></i> Guardar
              </button>
              <button id="btn-imprimir" class="btn btn-info" disabled>
                <i class="fas fa-print"></i> Imprimir
              </button>
              <button id="btn-cerrar" class="btn btn-danger">
                <i class="fas fa-times"></i> Cerrar
              </button>

              <!-- GESTIONAR: SIEMPRE AQUÍ -->
              <div class="gestion-compact" id="gestion-fija" style="display:inline-block; position:relative;">
                <button id="btn-gestionar-acciones" class="btn btn-secondary">
                  <i class="fas fa-tools"></i> Gestionar
                </button>
                <div class="gest-menu" id="gestionar-menu">
                  <button type="button" data-target="#btn-nuevo-cliente-rapido">
                    <i class="fas fa-user-plus"></i> Crear cliente
                  </button>

                  <div class="dropdown-divider" style="margin:.35rem 0;border-top:1px solid #e5e7eb;"></div>

                  <button type="button" data-target="#btn-nueva-categoria">
                    <i class="fas fa-folder-plus"></i> + Categoría
                  </button>
                  <button type="button" data-target="#btn-nuevo-producto">
                    <i class="fas fa-plus-square"></i> Nuevo producto
                  </button>
                  <button type="button" data-target="#btn-gestionar-combos">
                    <i class="fas fa-layer-group"></i> Combos
                  </button>

                  <div class="dropdown-divider" style="margin:.35rem 0;border-top:1px solid #e5e7eb;"></div>

                  <!-- === NUEVOS ACCESOS DE PROMOS === -->
                  <button type="button" data-target="#btn-gestionar-promos">
                    <i class="fas fa-tags"></i> Promociones
                  </button>
                  <button type="button" data-target="#btn-nueva-promocion">
                    <i class="fas fa-tag"></i> Nueva promoción
                  </button>
                  <button type="button" data-target="#btn-asignar-promo-productos">
                    <i class="fas fa-cart-plus"></i> Asignar productos a promo
                  </button>
                  <button type="button" data-target="#btn-asignar-promo-categorias">
                    <i class="fas fa-sitemap"></i> Asignar categorías a promo
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Fila inferior: Servicio + (Mesa/Cliente/Cambiar) en la misma línea -->
          <div class="fh-row fh-row-bottom">
            <!-- selector de tipo de servicio -->
            <div class="factura-servicio" id="servicio-switch" title="Elige si es para llevar o en mesa">
              <div class="segmented-control" aria-label="Tipo de servicio">
                <input type="radio" name="servicioTipo" id="srv-llevar" value="llevar" checked>
                <label for="srv-llevar" title="Cobro sin mesa. Cocina imprime con 'PARA LLEVAR' si aplica">Para llevar</label>

                <input type="radio" name="servicioTipo" id="srv-mesa" value="mesa">
                <label for="srv-mesa" title="Requiere elegir una mesa. El pedido puede quedar abierto.">En mesa</label>
              </div>
            </div>

            <!-- Meta compacta a la par del servicio -->
            <div class="factura-meta">
              <span id="mesa-seleccionada"><i class="fas fa-table"></i> No seleccionada</span>

              <span id="cliente-info" class="cliente-info">
                <i class="fas fa-user"></i>
                <span class="cli-datos">
                  <span class="cli-nombre">Consumidor final</span>
                  <small class="cli-rtn-wrap is-hidden">
                    <i class="fas fa-id-card"></i>
                    <span class="cli-rtn"></span>
                  </small>
                </span>
              </span>

              <button id="btn-cambiar-cliente" class="btn btn-sm btn-primary">
                <i class="fa-solid fa-right-left"></i> Cambiar
              </button>

              <!-- Botones originales OCULTOS para que el menú Gestionar pueda dispararlos -->
              <button id="btn-nuevo-cliente-rapido" class="btn btn-sm btn-success" style="display:none;">
                <i class="fas fa-user-plus"></i> Crear cliente
              </button>

              <div class="gestion-productos-actions" style="display:none;">
                <button id="btn-nueva-categoria" class="btn btn-secondary btn-sm">
                  <i class="fas fa-folder-plus"></i> + Categoría
                </button>
                <button id="btn-nuevo-producto" class="btn btn-primary btn-sm">
                  <i class="fas fa-plus"></i> Nuevo producto
                </button>
                <button id="btn-gestionar-combos" class="btn btn-info btn-sm">
                  <i class="fas fa-layer-group"></i> Combos
                </button>
              </div>

              <!-- === TRIGGERS OCULTOS PARA PROMOS (los usa el menú Gestionar) === -->
              <button id="btn-gestionar-promos" class="btn btn-secondary btn-sm" style="display:none;">
                <i class="fas fa-tags"></i> Promociones
              </button>
              <button id="btn-nueva-promocion" class="btn btn-primary btn-sm" style="display:none;">
                <i class="fas fa-tag"></i> Nueva promoción
              </button>
              <button id="btn-asignar-promo-productos" class="btn btn-info btn-sm" style="display:none;">
                <i class="fas fa-cart-plus"></i> Asignar productos a promo
              </button>
              <button id="btn-asignar-promo-categorias" class="btn btn-info btn-sm" style="display:none;">
                <i class="fas fa-sitemap"></i> Asignar categorías a promo
              </button>

              <!-- Menú compacto original (ya no se usa) -->
              <div class="gestion-compact" style="display:none;">
                <button id="btn-gestion" class="btn btn-secondary btn-sm">
                  <i class="fas fa-tools"></i> Gestionar
                </button>
                <div class="gest-menu" id="gest-menu">
                  <button type="button" data-target="#btn-nueva-categoria">
                    <i class="fas fa-folder-plus"></i> + Categoría
                  </button>
                  <button type="button" data-target="#btn-nuevo-producto">
                    <i class="fas fa-plus"></i> Nuevo producto
                  </button>
                  <button type="button" data-target="#btn-gestionar-combos">
                    <i class="fas fa-layer-group"></i> Combos
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Botones móviles para alternar vistas -->
        <button id="btn-mostrar-productos" class="btn btn-primary btn-mostrar-productos" style="display:none;">
          <i class="fas fa-box-open"></i> Ver Productos
        </button>
        <button id="btn-mostrar-comanda" class="btn btn-info btn-mostrar-comanda" style="display:none;">
          <i class="fas fa-clipboard-list"></i> Ver Comanda
        </button>

        <!-- Cuerpo -->
        <div class="factura-body">
          <!-- Panel de productos -->
          <div class="productos-panel" id="panel-productos">
            <div class="productos-header">
              <h3><i class="fas fa-boxes"></i> Productos</h3>

              <!-- Buscador + Escáner (misma fila, pegados) -->
              <div class="productos-search">
                <!-- Buscar por nombre/desc -->
                <div class="search-group" id="sg-name">
                  <input type="text" id="buscar-producto" class="input-lg"
                        placeholder="Buscar producto por nombre o descripción…">
                  <button id="btn-buscar" class="btn btn-primary"><i class="fas fa-search"></i></button>
                  <small class="help-under">Escribe para filtrar. Pulsa <b>Enter</b> o la <b>lupa</b> para confirmar.</small>
                </div>

                <!-- Escanear código de barras -->
                <div class="search-group" id="sg-barcode">
                  <input type="text" id="scan-codigo" class="input-lg" autocomplete="off"
                        placeholder="Escanear código de barras…">
                  <small class="help-under">Coloca el foco y escanea (<b>Enter</b>).</small>
                </div>
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

            <div class="categorias-tabs" id="categorias-tabs"></div>
            <div class="productos-grid" id="productos-container"></div>
          </div>

          <!-- Panel de la comanda -->
          <div class="comanda-panel" id="panel-comanda">
            <div class="comanda-header">
              <h3><i class="fas fa-clipboard-list"></i> Comanda</h3>
              <button id="btn-limpiar" class="btn btn-warning btn-sm">
                <i class="fas fa-broom"></i> Limpiar
              </button>
            </div>
            <div class="comanda-items" id="comanda-items"></div>
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
              </div>
            </div>
          </div>
        </div> <!-- /factura-body -->
      </div> <!-- /main-content -->
    </div> <!-- /restaurante-content -->
  </div> <!-- /restaurante-container -->

  <!-- ============== MODALES ============== -->

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
            <label for="estado-mesa"><i class="fas a-traffic-light"></i> Estado</label>
            <select id="estado-mesa" class="select2">
              <option value="">(auto)</option>
              <option value="disponible">Disponible</option>
              <option value="ocupada">Ocupada</option>
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-danger" data-close="#modal-mesa" type="button">
          <i class="fas fa-times"></i> Cerrar
        </button>
        <button class="btn btn-success" type="submit" form="form-mesa">
          <i class="fas fa-save"></i> Guardar Mesa
        </button>
      </div>
    </div>
  </div>

  <!-- MODAL: SELECCIONAR CLIENTE -->
  <div id="modal-cliente" class="modal rs-modal" role="dialog" aria-modal="true" aria-labelledby="titulo-modal-selector-cliente">
    <div class="modal-content">
      <div class="modal-header">
        <h3 id="titulo-modal-selector-cliente"><i class="fas fa-user-friends"></i> Seleccionar cliente</h3>
        <span class="close" data-close="#modal-cliente">&times;</span>
      </div>
      <div class="modal-body">
        <div class="search-container">
          <input type="text" id="buscar-cliente" placeholder="Buscar por nombre o identificación">
          <button id="btn-buscar-cliente" type="button"><i class="fas fa-search"></i></button>
        </div>
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

  <!-- MODAL: NUEVO/EDITAR CLIENTE -->
  <div id="modal-nuevo-cliente" class="modal rs-modal" role="dialog" aria-modal="true" aria-labelledby="titulo-modal-cliente">
    <div class="modal-content">
      <div class="modal-header">
        <h3 id="titulo-modal-cliente"><i class="fas fa-user-edit"></i> Nuevo Cliente</h3>
        <span class="close" data-close="#modal-nuevo-cliente">&times;</span>
      </div>
      <div class="modal-body">
        <form id="form-nuevo-cliente" autocomplete="off">
          <input type="hidden" id="cli-id">
          <div class="form-group">
            <label for="cli-nombre"><i class="fas a-quote-left"></i> Nombre / Razón social *</label>
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
            <label for="cli-correo"><i class="fas a-envelope"></i> Correo</label>
            <input type="email" id="cli-correo" placeholder="cliente@correo.com">
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-danger" data-close="#modal-nuevo-cliente" type="button">
          <i class="fas fa-times"></i> Cerrar
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
        <div class="form-group">
          <label class="label-strong" for="cat-estacion">Estación (ruta de comanda)</label>
          <div class="segmented-control" id="cat-estacion">
            <input type="radio" name="catEstacion" id="cat-est-cocina" value="cocina" checked>
            <label for="cat-est-cocina">Cocina</label>
            <input type="radio" name="catEstacion" id="cat-est-barra" value="barra">
            <label for="cat-est-barra">Barra</label>
          </div>
          <small class="hint">
            Si eliges <b>Cocina</b>, los productos de esta categoría se imprimirán en cocina por defecto.
            Si eliges <b>Barra</b>, se imprimirán en barra.
          </small>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-danger" data-close="#modal-categoria" type="button">
          <i class="fas fa-times"></i> Cerrar
        </button>
        <button id="btn-guardar-categoria" class="btn btn-success" type="button">
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
        <div class="form-group">
          <label class="label-strong">¿A qué estación pertenece este producto?</label>
          <div class="segmented-control" id="prod-estacion">
            <input type="radio" name="prodEstacion" id="prod-est-cocina" value="cocina" checked>
            <label for="prod-est-cocina">Cocina</label>
            <input type="radio" name="prodEstacion" id="prod-est-barra" value="barra">
            <label for="prod-est-barra">Barra</label>
          </div>
          <small class="hint">Al elegir la estación, el selector de <b>categoría</b> te mostrará solo las categorías de esa estación.</small>
        </div>

        <div class="form-group" id="prod-estacion-info" style="display:none;">
          <div class="info-chip">
            Estación de la categoría seleccionada: <b id="prod-estacion-info-val">—</b>
          </div>
        </div>

        <div class="form-group">
          <label for="prod-categoria"><i class="fas fa-sitemap"></i> Categoría</label>
          <select id="prod-categoria" class="select2" data-placeholder="Selecciona una categoría" required></select>
        </div>

        <div class="form-group">
          <label for="prod-nombre"><i class="fas a-quote-left"></i> Nombre</label>
          <input type="text" id="prod-nombre" placeholder="Ej. Refresco Pepsi" required/>
        </div>

        <div class="form-group">
          <label for="prod-descripcion"><i class="fas fa-align-left"></i> Descripción (opcional)</label>
          <input type="text" id="prod-descripcion" placeholder="Descripción corta" />
        </div>

        <div class="form-group">
          <label for="prod-precio"><i class="fas fa-dollar-sign"></i> Precio de venta</label>
          <input type="number" id="prod-precio" step="0.01" min="0" value=""  placeholder="0.00" required />
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
              <button class="btn btn-sm btn-secondary" id="btnSeleccionarImagen" type="button">
                <i class="fas fa-image"></i> Seleccionar imagen
              </button>
              <input type="file" id="imagen_producto" name="imagen_producto" accept="image/*" class="file-upload-input">
              <span class="paste-text">o pega (Ctrl+V)</span>
            </p>
            <div class="file-preview" id="productoPreview"></div>
          </div>
          <div class="file-info" id="productoInfo">Ningún archivo seleccionado</div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-danger" data-close="#modal-producto" type="button">
          <i class="fas fa-times"></i> Cerrar
        </button>
        <button id="btn-guardar-producto" class="btn btn-success" type="button">
          <i class="fas fa-save"></i> Guardar
        </button>
      </div>
    </div>
  </div>

  <!-- =================== MODAL: LISTA DE COMBOS =================== -->
  <div id="modal-combos" class="modal rs-modal">
    <div class="modal-content" style="max-width:980px;">
      <div class="modal-header">
        <h3><i class="fas fa-layer-group"></i> Combos</h3>
        <span class="close" data-close="#modal-combos">&times;</span>
      </div>
      <div class="modal-body">
        <div class="inline-actions" style="margin-bottom:10px;">
          <button id="btn-nuevo-combo" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Nuevo combo</button>
          <span class="muted">Define un producto "combo" y sus componentes.</span>
        </div>
        <div id="combos-grid" class="combos-grid"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-danger" data-close="#modal-combos" type="button">
          <i class="fas fa-times"></i> Cerrar
        </button>
      </div>
    </div>
  </div>

  <!-- =================== MODAL: EDITOR DE COMBO =================== -->
  <div id="modal-combo-editor" class="modal rs-modal" role="dialog" aria-modal="true" aria-labelledby="titulo-modal-combo">
    <div class="modal-content" style="max-width:1080px;">
      <div class="modal-header">
        <h3 id="titulo-modal-combo"><i class="fas fa-layer-group"></i> Nuevo combo</h3>
        <span class="close" data-close="#modal-combo-editor">&times;</span>
      </div>

      <div class="modal-body">
        <input type="hidden" id="combo-id" value="">
        <input type="hidden" id="combo-producto-hidden" value="">

        <div id="combo-help-message" class="mb-2"></div>
        <div id="combo-producto-display" style="display:none"></div>

        <div id="combo-producto-container" class="mb-3">
          <label class="label-strong">Producto que representa el combo</label>
          <div style="display:flex; gap:.5rem; align-items:stretch;">
            <select id="combo-producto" class="form-control select2"
                    data-placeholder="Selecciona el producto combo" style="width:100%;">
              <option value=""></option>
            </select>
            <button type="button" class="btn btn-info"
                    onclick="calcularDisponibilidadComboUI(document.getElementById('combo-id').value, 1)">
              <i class="fas fa-boxes"></i> Disponibilidad
            </button>
          </div>
          <p id="combo-producto-help" class="help-text"></p>
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

        <hr>

        <h4>Reglas por categoría (opcional)</h4>
        <p class="help-text">Define límites de selección para categorías de componentes opcionales.</p>

        <div id="combo-reglas-container" class="table-responsive" style="margin-top:10px;">
          <table class="table table-sm">
            <thead>
              <tr>
                <th style="width:55%">Categoría</th>
                <th style="width:25%">Máx. selección</th>
                <th style="width:20%"></th>
              </tr>
            </thead>
            <tbody id="combo-reglas-rows"></tbody>
          </table>
          <button type="button" class="btn btn-secondary" id="btn-add-regla">
            <i class="fas fa-plus"></i> Agregar regla
          </button>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-close="#modal-combo-editor">
          <i class="fas fa-times"></i> Cerrar
        </button>
        <button type="button" id="btn-guardar-combo" class="btn btn-success">
          <i class="fas fa-save"></i> Guardar combo
        </button>
      </div>
    </div>
  </div>

  <!-- =================== MODAL: AYUDA =================== -->
  <div id="modal-help" class="modal rs-modal modal--help modal--xl" role="dialog" aria-modal="true" aria-labelledby="titulo-modal-help" style="display:none;">
    <div class="modal-content">
      <div class="modal-header">
        <h3 id="titulo-modal-help">
          <i class="fas fa-circle-question"></i> Ayuda & Atajos
        </h3>
        <span class="close" data-close="#modal-help" title="Cerrar">&times;</span>
      </div>

      <div class="modal-body help-body">
        <!-- Hero -->
        <div class="help-hero">
          <div class="help-hero-icon">
            <i class="fas fa-keyboard"></i>
          </div>
          <div class="help-hero-text">
            <h4>Atajos de teclado</h4>
            <p>Acelera tu flujo: todos los atajos usan <strong>Ctrl</strong> (Win/Linux) o <strong>Cmd ⌘</strong> (Mac). Algunos combinan con <strong>Alt</strong>.</p>
            <ul class="help-bullets">
              <li>Windows/Linux: <span class="kbd">Ctrl</span> • Mac: <span class="kbd">Cmd</span></li>
              <li>Para evitar conflictos con el navegador, usamos <span class="kbd">Alt</span> en varios atajos.</li>
            </ul>
          </div>
        </div>

        <!-- Grilla de tarjetas -->
        <div class="help-grid">
          <!-- Comanda -->
          <div class="help-card">
            <div class="help-card-title"><i class="fas fa-receipt"></i> Comanda</div>
            <ul class="help-keys">
              <li>
                <div>Guardar factura</div>
                <div class="keys"><span class="kbd">Ctrl/Cmd</span><span class="kbd">G</span></div>
              </li>
              <li>
                <div>Imprimir</div>
                <div class="keys"><span class="kbd">Ctrl/Cmd</span><span class="kbd">I</span></div>
              </li>
              <li>
                <div>Limpiar comanda</div>
                <div class="keys"><span class="kbd">Ctrl/Cmd</span><span class="kbd">Alt</span><span class="kbd">L</span></div>
              </li>
              <li>
                <div>Cerrar factura</div>
                <div class="keys"><span class="kbd">Ctrl/Cmd</span><span class="kbd">Alt</span><span class="kbd">X</span></div>
              </li>
              <li>
                <div>Ver Productos/Comanda</div>
                <div class="keys"><span class="kbd">Ctrl/Cmd</span><span class="kbd">Alt</span><span class="kbd">V</span></div>
              </li>
              <li>
                <div>Buscar producto</div>
                <div class="keys"><span class="kbd">Ctrl/Cmd</span><span class="kbd">Alt</span><span class="kbd">F</span></div>
              </li>
            </ul>
          </div>

          <!-- Gestión rápida -->
          <div class="help-card">
            <div class="help-card-title"><i class="fas fa-bolt"></i> Gestión rápida</div>
            <ul class="help-keys">
              <li>
                <div>Nueva mesa</div>
                <div class="keys"><span class="kbd">Ctrl/Cmd</span><span class="kbd">M</span></div>
              </li>
              <li>
                <div>Cambiar cliente</div>
                <div class="keys"><span class="kbd">Ctrl/Cmd</span><span class="kbd">Alt</span><span class="kbd">C</span></div>
              </li>
              <li>
                <div>Nuevo cliente</div>
                <div class="keys"><span class="kbd">Ctrl/Cmd</span><span class="kbd">Alt</span><span class="kbd">R</span></div>
              </li>
              <li>
                <div>Nuevo producto</div>
                <div class="keys"><span class="kbd">Ctrl/Cmd</span><span class="kbd">Alt</span><span class="kbd">P</span></div>
              </li>
              <li>
                <div>Nueva categoría</div>
                <div class="keys"><span class="kbd">Ctrl/Cmd</span><span class="kbd">Alt</span><span class="kbd">K</span></div>
              </li>
              <li>
                <div>Gestionar combos</div>
                <div class="keys"><span class="kbd">Ctrl/Cmd</span><span class="kbd">Alt</span><span class="kbd">B</span></div>
              </li>
            </ul>

            <div class="help-split"></div>
            <div class="help-subtitle"><i class="fas fa-lightbulb"></i> Consejos</div>
            <ul class="help-keys">
              <li>
                <div>Escanear / código rápido</div>
                <div class="keys"><span class="kbd">Enter</span></div>
              </li>
              <li>
                <div>En móvil, al agregar producto salta a Comanda</div>
                <div class="keys"><span class="kbd">Auto</span></div>
              </li>
            </ul>
          </div>
        </div>

        <!-- Conceptos -->
        <div class="help-concepts">
          <div class="concept">
            <span class="badge">Para llevar</span>
            No exige mesa y <strong>conserva el cliente</strong> que tengas seleccionado.
          </div>
          <div class="concept">
            <span class="badge">Mesa</span>
            Al seleccionar mesa, el modo cambia automáticamente a <strong>Mesa</strong>.
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-danger" data-close="#modal-help" type="button">
          <i class="fas fa-times"></i> Cerrar
        </button>
      </div>
    </div>
  </div>
  <!-- =================== MODALES NUEVOS: PROMOCIONES =================== -->

  <!-- LISTA DE PROMOCIONES -->
  <div id="modal-promociones-list" class="modal rs-modal">
    <div class="modal-content" style="max-width:980px;">
      <div class="modal-header">
        <h3><i class="fas fa-tags"></i> Promociones</h3>
        <span class="close" data-close="#modal-promociones-list">&times;</span>
      </div>
      <div class="modal-body">
        <div class="inline-actions" style="margin-bottom:10px;">
          <button id="btn-abrir-nueva-promocion" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Nueva promoción
          </button>
          <span class="muted">Administra promos, vigencia y reglas.</span>
        </div>
        <div class="table-responsive">
          <table class="table table-sm" id="promos-table">
            <thead>
              <tr>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Valor</th>
                <th>Vigencia</th>
                <th>Aplica a</th>
                <th>Prioridad</th>
                <th>Estado</th>
                <th style="width:120px">Acciones</th>
              </tr>
            </thead>
            <tbody id="promos-rows">
              <!-- Rellenar por JS -->
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-danger" data-close="#modal-promociones-list" type="button">
          <i class="fas fa-times"></i> Cerrar
        </button>
      </div>
    </div>
  </div>

  <!-- CREAR / EDITAR PROMOCIÓN -->
  <!-- CREAR / EDITAR PROMOCIÓN (UI simplificado: fecha=DÍAS, horario opcional) -->
  <div id="modal-promocion" class="modal rs-modal">
    <div class="modal-content" style="max-width:760px;">
      <div class="modal-header">
        <h3><i class="fas fa-tag"></i> <span id="titulo-modal-promocion">Nueva promoción</span></h3>
        <span class="close" data-close="#modal-promocion">&times;</span>
      </div>

      <div class="modal-body">
        <form id="form-promocion" autocomplete="off">
          <input type="hidden" id="promo-id">
          <input type="hidden" id="promo-empresa-id" value="<?php echo $_SESSION['empresa_id'] ?? 1; ?>">

          <div class="form-group">
            <label for="promo-nombre"><i class="fas fa-heading"></i> Nombre *</label>
            <input type="text" id="promo-nombre" required>
          </div>

          <div class="form-group">
            <label for="promo-descripcion"><i class="fas fa-align-left"></i> Descripción</label>
            <input type="text" id="promo-descripcion" placeholder="Opcional">
          </div>

          <div class="form-group" style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <div>
              <label for="promo-tipo"><i class="fas fa-balance-scale"></i> Tipo de descuento</label>
              <select id="promo-tipo" class="select2">
                <option value="PORC">PORC (%)</option>
                <option value="MONTO">MONTO (L)</option>
              </select>
            </div>
            <div>
              <label for="promo-valor"><i class="fas fa-percentage"></i> Valor *</label>
              <input type="number" id="promo-valor" step="0.01" min="0" value="0.00" required>
            </div>
          </div>

          <!-- Rango de fechas (días) -->
          <div class="form-group" style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <div>
              <label for="promo-fecha-inicio"><i class="fas fa-calendar-plus"></i> Fecha inicio *</label>
              <input type="date" id="promo-fecha-inicio" required>
            </div>
            <div>
              <label for="promo-fecha-fin"><i class="fas fa-calendar-check"></i> Fecha fin *</label>
              <input type="date" id="promo-fecha-fin" required>
            </div>
          </div>

          <!-- Toggle de horario diario -->
          <div class="form-group" style="display:flex; align-items:center; gap:12px; margin-top:2px;">
            <label class="radio-container" title="Restringir la promo a un horario por día dentro del rango">
              <input type="checkbox" id="promo-usa-horario"> Usar horario diario
            </label>
            <small class="hint">Si lo activas, las horas aplican <b>cada día</b> entre las fechas seleccionadas.</small>
          </div>

          <!-- Horario diario (opcional) -->
          <div class="form-group" style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <div>
              <label for="promo-hora-inicio"><i class="fas fa-clock"></i> Hora inicio</label>
              <input type="time" id="promo-hora-inicio" disabled>
            </div>
            <div>
              <label for="promo-hora-fin"><i class="fas fa-clock"></i> Hora fin</label>
              <input type="time" id="promo-hora-fin" disabled>
            </div>
          </div>

          <!-- Días de la semana -->
          <div class="form-group">
            <label class="label-strong"><i class="fas fa-calendar-day"></i> Días de la semana</label>
            <div style="display:flex; flex-wrap:wrap; gap:8px;">
              <label class="radio-container"><input type="checkbox" value="mon" class="promo-dia"> Lun</label>
              <label class="radio-container"><input type="checkbox" value="tue" class="promo-dia"> Mar</label>
              <label class="radio-container"><input type="checkbox" value="wed" class="promo-dia"> Mié</label>
              <label class="radio-container"><input type="checkbox" value="thu" class="promo-dia"> Jue</label>
              <label class="radio-container"><input type="checkbox" value="fri" class="promo-dia"> Vie</label>
              <label class="radio-container"><input type="checkbox" value="sat" class="promo-dia"> Sáb</label>
              <label class="radio-container"><input type="checkbox" value="sun" class="promo-dia"> Dom</label>
            </div>
            <small class="hint">Si no seleccionas ninguno, aplica a <b>todos</b> los días.</small>
          </div>

          <div class="form-group" style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <div>
              <label for="promo-aplica-a"><i class="fas fa-bullseye"></i> Aplica a</label>
              <select id="promo-aplica-a" class="select2">
                <option value="PRODUCTO">PRODUCTO(s)</option>
                <option value="CATEGORIA">CATEGORIA(s)</option>
                <option value="TODOS">TODOS</option>
              </select>
            </div>
            <div>
              <label for="promo-prioridad"><i class="fas fa-sort-amount-up"></i> Prioridad</label>
              <input type="number" id="promo-prioridad" value="0" step="1">
            </div>
          </div>

          <div class="form-group" style="display:flex; gap:14px; flex-wrap:wrap;">
            <label class="radio-container"><input type="checkbox" id="promo-acumula" /> Acumula con mayoreo</label>
            <label class="radio-container"><input type="checkbox" id="promo-estado" checked /> Activa</label>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button class="btn btn-danger" data-close="#modal-promocion" type="button">
          <i class="fas fa-times"></i> Cerrar
        </button>
        <button id="btn-guardar-promocion" class="btn btn-success" type="button">
          <i class="fas fa-save"></i> Guardar promoción
        </button>
      </div>
    </div>
  </div>

  <!-- ASIGNAR PRODUCTOS A PROMOCIÓN -->
  <div id="modal-promo-productos" class="modal rs-modal">
    <div class="modal-content" style="max-width:980px;">
      <div class="modal-header">
        <h3><i class="fas fa-cart-plus"></i> Asignar productos a promoción</h3>
        <span class="close" data-close="#modal-promo-productos">&times;</span>
      </div>
      <div class="modal-body">
        <form id="form-promo-productos">
          <div class="form-group">
            <label for="pp-promocion"><i class="fas fa-tags"></i> Promoción</label>
            <select id="pp-promocion" class="select2" data-placeholder="Selecciona la promoción">
              <option value=""></option>
            </select>
          </div>

          <div class="form-group">
            <label for="pp-productos"><i class="fas fa-boxes"></i> Productos (múltiple)</label>
            <select id="pp-productos" class="select2" multiple data-placeholder="Selecciona uno o más productos">
              <option value=""></option>
            </select>
            <small class="hint">Pulsa Guardar para vincular los productos seleccionados a la promo.</small>
          </div>
        </form>

        <div class="table-responsive" style="margin-top:10px;">
          <table class="table table-sm">
            <thead>
              <tr>
                <th>Producto</th>
                <th>Barcode</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="pp-listado">
              <!-- Rellenar por JS con productos ya asignados -->
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-danger" data-close="#modal-promo-productos" type="button">
          <i class="fas fa-times"></i> Cerrar
        </button>
        <button id="btn-guardar-promo-productos" class="btn btn-success" type="button">
          <i class="fas fa-save"></i> Guardar asignación
        </button>
      </div>
    </div>
  </div>

  <!-- ASIGNAR CATEGORÍAS A PROMOCIÓN -->
  <div id="modal-promo-categorias" class="modal rs-modal">
    <div class="modal-content" style="max-width:760px;">
      <div class="modal-header">
        <h3><i class="fas fa-sitemap"></i> Asignar categorías a promoción</h3>
        <span class="close" data-close="#modal-promo-categorias">&times;</span>
      </div>
      <div class="modal-body">
        <form id="form-promo-categorias">
          <div class="form-group">
            <label for="pc-promocion"><i class="fas fa-tags"></i> Promoción</label>
            <select id="pc-promocion" class="select2" data-placeholder="Selecciona la promoción">
              <option value=""></option>
            </select>
          </div>

          <div class="form-group">
            <label for="pc-categorias"><i class="fas fa-layer-group"></i> Categorías (múltiple)</label>
            <select id="pc-categorias" class="select2" multiple data-placeholder="Selecciona una o más categorías">
              <option value=""></option>
            </select>
            <small class="hint">Pulsa Guardar para vincular las categorías seleccionadas a la promo.</small>
          </div>
        </form>

        <div class="table-responsive" style="margin-top:10px;">
          <table class="table table-sm">
            <thead>
              <tr>
                <th>Categoría</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="pc-listado">
              <!-- Rellenar por JS con categorías ya asignadas -->
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-danger" data-close="#modal-promo-categorias" type="button">
          <i class="fas fa-times"></i> Cerrar
        </button>
        <button id="btn-guardar-promo-categorias" class="btn btn-success" type="button">
          <i class="fas fa-save"></i> Guardar asignación
        </button>
      </div>
    </div>
  </div>

  <!-- Scripts (orden correcto) -->
  <script src="<?php echo SERVERURL; ?>ajax/query/jquery-3.5.1.min.js"></script>
  <script src="<?php echo SERVERURL; ?>ajax/sweetalert/sweetalert.min.js"></script>
  <script src="<?php echo SERVERURL; ?>ajax/librerias/select2.min.js"></script>
  <script src="<?php echo SERVERURL; ?>ajax/js/facturasRestaurante.js"></script>
</body>
</html>