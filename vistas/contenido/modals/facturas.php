<link rel="stylesheet" href="<?php echo SERVERURL; ?>vistas/plantilla/css/facturas_modales.css">
<?php
// ====== Soporte / WhatsApp ======
// Defaults por si no llegan definidas
$telefono_ws = isset($telefono_ws) && $telefono_ws !== '' ? $telefono_ws : '50489136844';
$mensaje_ws  = isset($mensaje_ws)  && $mensaje_ws  !== '' ? $mensaje_ws  : 'Hola ES MULTISERVICIOS, nos gustaría que nos puedan brindar asistencia técnica, muchas gracias.';

// Solo dígitos para la URL
$__tel_digits = preg_replace('/\D+/', '', (string)$telefono_ws);

// URL de WhatsApp (usa la tuya si ya la traes)
if (!isset($url_ws) || $url_ws === '') {
  $url_ws = 'https://api.whatsapp.com/send?phone=' . rawurlencode($__tel_digits)
          . '&text=' . rawurlencode($mensaje_ws);
}

// Formateo legible del número: +504 8913-6844 (toma últimas 8 como número local)
function __format_tel_legible($digits) {
  if (!$digits) return '';
  $local = substr($digits, -8);              // 8 últimas cifras
  $pref  = substr($digits, 0, -8);           // código país (lo que sobra)
  if (strlen($local) === 8) {
    $local = substr($local, 0, 4) . '-' . substr($local, 4);
  }
  return ($pref ? '+' . $pref . ' ' : '') . $local;
}
$telefono_ws_legible = __format_tel_legible($__tel_digits);
?>

<!-- INICIO MODAL AYUDA (PRO / DINÁMICO) -->
<div class="modal fade" id="modalAyuda" tabindex="-1" role="dialog" aria-labelledby="modalAyudaLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content help-modal help-center-modal">

      <div class="modal-header help-header">
        <div class="title-wrap">
          <span class="help-badge"><i class="fas fa-life-ring"></i></span>
          <div class="title-text">
            <h4 class="modal-title help-title" id="modalAyudaLabel">Centro de Ayuda de Facturación</h4>
            <small class="help-subtitle">Encuentre rápidamente atajos y operaciones frecuentes.</small>
          </div>
        </div>

        <div class="header-actions">
          <div class="btn-group btn-group-sm mr-2">
            <button type="button" class="btn btn-light" id="helpCopy" title="Copiar los resultados visibles">
              <i class="fas fa-copy"></i> Copiar
            </button>
            <button type="button" class="btn btn-light" id="helpPrint" title="Imprimir los resultados visibles">
              <i class="fas fa-print"></i> Imprimir
            </button>
          </div>
          <button type="button" class="close text-white ml-1" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      </div>

      <div class="modal-body help-center-body">
        <div class="help-center-toolbar">
          <div class="help-search-wrap">
            <i class="fas fa-search help-search-icon"></i>
            <input class="form-control" id="helpSearch" autocomplete="off"
                   placeholder="Buscar: guardar, producto, caja, cliente, F7..."
                   aria-label="Buscar en el Centro de Ayuda">
            <button type="button" id="helpClearSearch" class="help-clear-search" aria-label="Limpiar búsqueda" title="Limpiar búsqueda">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="help-result-status" aria-live="polite">
            <span id="helpResultCount">13 resultados</span>
          </div>
        </div>

        <div class="help-category-nav" id="helpCategoryNav" aria-label="Categorías de ayuda">
          <button type="button" class="help-category-btn active" data-help-category="all"><i class="fas fa-th-large"></i><span>Todo</span></button>
          <button type="button" class="help-category-btn" data-help-category="venta"><i class="fas fa-file-invoice-dollar"></i><span>Venta</span></button>
          <button type="button" class="help-category-btn" data-help-category="productos"><i class="fas fa-box-open"></i><span>Productos</span></button>
          <button type="button" class="help-category-btn" data-help-category="personas"><i class="fas fa-users"></i><span>Clientes y vendedores</span></button>
          <button type="button" class="help-category-btn" data-help-category="caja"><i class="fas fa-cash-register"></i><span>Caja</span></button>
          <button type="button" class="help-category-btn" data-help-category="general"><i class="fas fa-keyboard"></i><span>General</span></button>
        </div>

        <div class="help-quick-section">
          <div class="help-section-heading">
            <div>
              <strong><i class="fas fa-bolt"></i> Accesos rápidos</strong>
              <small>Pulse una tecla para localizar su explicación.</small>
            </div>
          </div>
          <div class="help-quick-grid" id="helpQuickGrid">
            <button type="button" class="help-quick-btn" data-help-jump="F2"><kbd>F2</kbd><span>Guardar</span></button>
            <button type="button" class="help-quick-btn" data-help-jump="F3"><kbd>F3</kbd><span>Productos</span></button>
            <button type="button" class="help-quick-btn" data-help-jump="F7"><kbd>F7</kbd><span>Registrar</span></button>
            <button type="button" class="help-quick-btn" data-help-jump="F8"><kbd>F8</kbd><span>Clientes</span></button>
            <button type="button" class="help-quick-btn" data-help-jump="F10"><kbd>F10</kbd><span>Aperturar caja</span></button>
            <button type="button" class="help-quick-btn" data-help-jump="F11"><kbd>F11</kbd><span>Cerrar caja</span></button>
          </div>
        </div>

        <div class="help-context-note">
          <i class="fas fa-info-circle"></i>
          <div>
            <strong>Importante</strong>
            <span>Las teclas de función trabajan cuando el foco está en <b>Código del Producto</b>. F1 abre esta ayuda en cualquier momento.</span>
          </div>
        </div>

        <div class="help-content-grid">
          <section class="help-shortcuts-panel" aria-labelledby="helpShortcutsTitle">
            <div class="help-section-heading help-list-heading">
              <div>
                <strong id="helpShortcutsTitle"><i class="fas fa-keyboard"></i> Atajos y operaciones</strong>
                <small id="helpCurrentFilter">Mostrando todas las opciones.</small>
              </div>
            </div>

            <div class="help-shortcuts-list" id="tableShortcuts" role="list">
              <article class="help-shortcut-item" data-help-cat="venta" data-help-key="F2" role="listitem"><div class="help-key"><kbd>F2</kbd></div><div class="help-item-copy"><strong>Guardar</strong><p>Guarda la factura como <u>borrador</u> para continuar después. No emite documento fiscal; podrá editarla o eliminarla sin afectar SAR.</p></div></article>
              <article class="help-shortcut-item" data-help-cat="productos" data-help-key="F3" role="listitem"><div class="help-key"><kbd>F3</kbd></div><div class="help-item-copy"><strong>Búsqueda de productos</strong><p>Abre el buscador y permite crear productos. Use “Actualizar” para refrescar después de un alta.</p></div></article>
              <article class="help-shortcut-item" data-help-cat="productos" data-help-key="F4" role="listitem"><div class="help-key"><kbd>F4</kbd></div><div class="help-item-copy"><strong>Descuentos</strong><p>Aplica descuentos a productos. Si está activa la seguridad de descuentos y precios, solicitará validación administrativa.</p></div></article>
              <article class="help-shortcut-item" data-help-cat="general" data-help-key="F5" role="listitem"><div class="help-key"><kbd>F5</kbd></div><div class="help-item-copy"><strong>Actualizar</strong><p>Recarga la página. <u>Precaución:</u> perderá información que todavía no haya guardado.</p></div></article>
              <article class="help-shortcut-item" data-help-cat="productos" data-help-key="F6" role="listitem"><div class="help-key"><kbd>F6</kbd></div><div class="help-item-copy"><strong>Modificar precio</strong><p>Ajusta el precio del producto. Si está activa la seguridad de descuentos y precios, solicitará validación administrativa.</p></div></article>
              <article class="help-shortcut-item" data-help-cat="venta" data-help-key="F7" role="listitem"><div class="help-key"><kbd>F7</kbd></div><div class="help-item-copy"><strong>Registrar / Cobrar</strong><p>Emite la factura fiscal. Es una acción definitiva; para corregir posteriormente se requiere anulación o Nota de Crédito.</p></div></article>
              <article class="help-shortcut-item" data-help-cat="personas" data-help-key="F8" role="listitem"><div class="help-key"><kbd>F8</kbd></div><div class="help-item-copy"><strong>Clientes</strong><p>Busca o crea clientes. Use “Actualizar” para refrescar la lista después de crear uno nuevo.</p></div></article>
              <article class="help-shortcut-item" data-help-cat="personas" data-help-key="F9" role="listitem"><div class="help-key"><kbd>F9</kbd></div><div class="help-item-copy"><strong>Vendedores</strong><p>Busca o crea colaboradores y vendedores para asignarlos a la venta.</p></div></article>
              <article class="help-shortcut-item" data-help-cat="caja" data-help-key="F10" role="listitem"><div class="help-key"><kbd>F10</kbd></div><div class="help-item-copy"><strong>Apertura de caja</strong><p>Habilita caja para ventas y permite registrar el fondo inicial del turno.</p></div></article>
              <article class="help-shortcut-item" data-help-cat="caja" data-help-key="F11" role="listitem"><div class="help-key"><kbd>F11</kbd></div><div class="help-item-copy"><strong>Cierre de caja</strong><p>Realiza el cierre del turno con conteo de ventas desde la primera hasta la última factura.</p></div></article>
              <article class="help-shortcut-item" data-help-cat="productos" data-help-key="+ −" role="listitem"><div class="help-key"><kbd>+</kbd><kbd>−</kbd></div><div class="help-item-copy"><strong>Cantidad</strong><p>Incrementa o disminuye la cantidad cuando el foco está en <em>Código del Producto</em>.</p></div></article>
              <article class="help-shortcut-item" data-help-cat="productos" data-help-key="*" role="listitem"><div class="help-key"><kbd>*</kbd></div><div class="help-item-copy"><strong>Comodín de cantidad</strong><p>Escriba <code>10*código</code> para agregar 10 unidades del producto de una sola vez.</p></div></article>
              <article class="help-shortcut-item" data-help-cat="general" data-help-key="F1" role="listitem"><div class="help-key"><kbd>F1</kbd></div><div class="help-item-copy"><strong>Ayuda</strong><p>Abre este Centro de Ayuda en cualquier momento.</p></div></article>
            </div>

            <div class="help-empty-state" id="helpEmptyState" hidden>
              <i class="fas fa-search"></i>
              <strong>No encontramos resultados</strong>
              <span>Pruebe otra palabra, tecla o categoría.</span>
              <button type="button" class="btn btn-sm btn-primary" id="helpResetFilters"><i class="fas fa-undo"></i> Ver todo</button>
            </div>
          </section>

          <aside class="help-side-panel">
            <div class="help-side-card">
              <div class="help-side-icon"><i class="fas fa-lightbulb"></i></div>
              <div><strong>Consejos rápidos</strong><ul><li>Evite <kbd>F5</kbd> si tiene cambios sin guardar.</li><li>Para cantidades rápidas use <code>n*código</code>.</li><li>Verifique existencia antes de emitir.</li></ul></div>
            </div>
            <div class="help-side-card">
              <div class="help-side-icon"><i class="fas fa-headset"></i></div>
              <div class="help-side-grow"><strong>¿Necesita más ayuda?</strong><p>Contacte al administrador o al soporte de ES MULTISERVICIOS.</p>
                <a href="<?php echo htmlspecialchars($url_ws, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="btn btn-success btn-sm btn-block"><i class="fab fa-whatsapp"></i> WhatsApp</a>
                <?php if ($telefono_ws_legible): ?><small class="help-phone"><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($telefono_ws_legible, ENT_QUOTES, 'UTF-8'); ?></small><?php endif; ?>
              </div>
            </div>
          </aside>
        </div>
      </div>

      <div class="modal-footer help-center-footer">
        <small class="text-muted mr-auto"><i class="fas fa-info-circle"></i> Use el buscador o las categorías para encontrar una opción rápidamente.</small>
        <button type="button" class="btn btn-primary" data-dismiss="modal"><i class="fas fa-check"></i> Entendido</button>
      </div>
    </div>
  </div>
</div>
<!-- FIN MODAL AYUDA (PRO / DINÁMICO) -->

<!--INICIO MODAL BUSQUEDA CONVERTIR COTIZACION EN FACTURAS-->
<div class="modal fade izzy-modal-consulta-facturacion fm-modal" id="modal_buscar_cotizaciones" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header fm-modal-header">
        <div>
          <h4 class="modal-title mb-0">Buscar Cotizaciones</h4>
          <small>Cotizaciones disponibles para cargar o imprimir.</small>
        </div>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body fm-modal-body">
        <form class="FormularioAjax fm-form" id="formulario_busqueda_cotizaciones">
          
<div class="fm-section-card fm-filter-card mb-3">
  <div class="fm-section-header">
    <div>
      <h6 class="mb-0"><i class="fas fa-filter mr-1"></i>Filtros de cotizaciones</h6>
      <small>Use los criterios necesarios y aplique la búsqueda.</small>
    </div>
    <button type="button" class="btn btn-outline-secondary btn-sm fm-toggle-filter" data-target="#cotizacionesFiltrosContenido" aria-expanded="true">
      <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
    </button>
  </div>
  <div id="cotizacionesFiltrosContenido" class="fm-section-body">
    
<div class="fm-filter-row">
  <div class="fm-filter-field">
    <label>Tipo Factura</label>
    <select id="tipo_cotizacion_reporte" name="tipo_cotizacion_reporte" class="form-control selectpicker" title="Tipo Factura" data-live-search="true"></select>
  </div>
  <div class="fm-filter-field"><label>Fecha Inicio</label><input type="date" required id="fechai" name="fechai" value="<?php 
    $fecha = date ("Y-m-d");
    $año = date("Y", strtotime($fecha));
    $mes = date("m", strtotime($fecha));
    $dia = date("d", mktime(0,0,0, $mes+1, 0, $año));
    $dia1 = date('d', mktime(0,0,0, $mes, 1, $año));
    $dia2 = date('d', mktime(0,0,0, $mes, $dia, $año));
    $fecha_inicial = date("Y-m-d", strtotime($año."-".$mes."-".$dia1));
    echo $fecha_inicial;
?>" class="form-control"></div>
  <div class="fm-filter-field"><label>Fecha Fin</label><input type="date" required id="fechaf" name="fechaf" value="<?php echo date ("Y-m-d");?>" class="form-control"></div>
  <div class="fm-filter-actions">
    <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i>Buscar</button>
    <button type="reset" id="btn-limpiar-filtros" class="btn btn-secondary"><i class="fas fa-broom mr-1"></i>Limpiar</button>
  </div>
</div>
  </div>
</div>
          <div class="fm-directory-card">
            
<div class="fm-list-toolbar">
  <div class="fm-toolbar-left"><button type="button" id="btnActualizarCotizacionesFm" class="btn table_actualizar btn-secondary ocultar btn-sm mr-2 mb-2"><i class="fas fa-sync-alt mr-1"></i>Actualizar</button></div>
  <div class="fm-toolbar-right">
    <label class="fm-page-size">Mostrar
      <select id="cotizacionesPageSize" class="form-control form-control-sm fm-page-size-select">
        <option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option>
      </select>
      registros
    </label>
    <div class="btn-group btn-group-sm fm-view-switch" role="group" aria-label="Tipo de vista">
      <button type="button" class="btn btn-outline-secondary fm-view-btn active" data-list="cotizaciones" data-view="detalle" title="Vista detalle"><i class="fas fa-list"></i></button>
      <button type="button" class="btn btn-outline-secondary fm-view-btn" data-list="cotizaciones" data-view="miniatura" title="Vista miniatura"><i class="fas fa-th-large"></i></button>
    </div>
    <div class="fm-search-wrap">
      <i class="fas fa-search"></i>
      <input type="search" id="cotizacionesSearch" class="form-control form-control-sm" placeholder="Buscar..." autocomplete="off">
      <button type="button" class="fm-search-clear" data-list="cotizaciones" title="Limpiar búsqueda"><i class="fas fa-times"></i></button>
    </div>
  </div>
</div>
<div id="cotizacionesListado" class="fm-listado" data-list="cotizaciones"></div>
<div class="fm-list-footer">
  <span id="cotizacionesInfo" class="fm-list-info">0 registros</span>
  <div id="cotizacionesPaginacion" class="fm-pagination"></div>
</div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i>Cerrar</button>
      </div>
    </div>
  </div>
</div>
<!--FIN MODAL BUSQUEDA CONVERTIR COTIZACION EN FACTURAS-->

<!--INICIO MODAL BUSQUEDA COBRAR CUENTAS POR COBRAR CLIENTES-->
<div class="modal fade izzy-modal-consulta-facturacion fm-modal" id="modal_buscar_cuentas_cobrar_clientes" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header fm-modal-header">
        <div>
          <h4 class="modal-title mb-0">Buscar Cuentas por Cobrar Clientes</h4>
          <small>Consulte saldos, abonos y documentos pendientes.</small>
        </div>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body fm-modal-body">
        <form class="FormularioAjax fm-form" id="formulario_busqueda_cuentas_cobrar_clientes">
          
<div class="fm-section-card fm-filter-card mb-3">
  <div class="fm-section-header">
    <div>
      <h6 class="mb-0"><i class="fas fa-filter mr-1"></i>Filtros de cuentas por cobrar</h6>
      <small>Use los criterios necesarios y aplique la búsqueda.</small>
    </div>
    <button type="button" class="btn btn-outline-secondary btn-sm fm-toggle-filter" data-target="#cxcFacturaFiltrosContenido" aria-expanded="true">
      <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
    </button>
  </div>
  <div id="cxcFacturaFiltrosContenido" class="fm-section-body">
    
<div class="fm-filter-row">
  <div class="fm-filter-field"><label>Estado</label><select id="cobrar_clientes_estado" name="cobrar_clientes_estado" class="form-control selectpicker" title="Estado" data-live-search="true"></select></div>
  <div class="fm-filter-field"><label>Clientes</label><select id="cobrar_clientes" name="cobrar_clientes" class="form-control selectpicker" title="Clientes" data-live-search="true"></select></div>
  <div class="fm-filter-field"><label>Fecha Inicio</label><input type="date" required id="fechai" name="fechai" value="<?php echo date ("Y-m-d");?>" class="form-control"></div>
  <div class="fm-filter-field"><label>Fecha Fin</label><input type="date" required id="fechaf" name="fechaf" value="<?php echo date ("Y-m-d");?>" class="form-control"></div>
  <div class="fm-filter-actions">
    <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i>Buscar</button>
    <button type="reset" id="btn-limpiar-filtros" class="btn btn-secondary"><i class="fas fa-broom mr-1"></i>Limpiar</button>
  </div>
</div>
  </div>
</div>
          <div class="fm-directory-card">
            
<div class="fm-list-toolbar">
  <div class="fm-toolbar-left"><button type="button" id="btnActualizarCxcFm" class="btn table_actualizar btn-secondary ocultar btn-sm mr-2 mb-2"><i class="fas fa-sync-alt mr-1"></i>Actualizar</button><button type="button" id="btnExcelCxcFm" class="btn table_reportes btn-success ocultar btn-sm mr-2 mb-2"><i class="fas fa-file-excel mr-1"></i>Excel</button><button type="button" id="btnPdfCxcFm" class="btn table_reportes btn-danger ocultar btn-sm mr-2 mb-2"><i class="fas fa-file-pdf mr-1"></i>PDF</button></div>
  <div class="fm-toolbar-right">
    <label class="fm-page-size">Mostrar
      <select id="cxcFacturaPageSize" class="form-control form-control-sm fm-page-size-select">
        <option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option>
      </select>
      registros
    </label>
    <div class="btn-group btn-group-sm fm-view-switch" role="group" aria-label="Tipo de vista">
      <button type="button" class="btn btn-outline-secondary fm-view-btn active" data-list="cxcFactura" data-view="detalle" title="Vista detalle"><i class="fas fa-list"></i></button>
      <button type="button" class="btn btn-outline-secondary fm-view-btn" data-list="cxcFactura" data-view="miniatura" title="Vista miniatura"><i class="fas fa-th-large"></i></button>
    </div>
    <div class="fm-search-wrap">
      <i class="fas fa-search"></i>
      <input type="search" id="cxcFacturaSearch" class="form-control form-control-sm" placeholder="Buscar..." autocomplete="off">
      <button type="button" class="fm-search-clear" data-list="cxcFactura" title="Limpiar búsqueda"><i class="fas fa-times"></i></button>
    </div>
  </div>
</div>
<div id="cxcFacturaListado" class="fm-listado" data-list="cxcFactura"></div>
<div class="fm-list-footer">
  <span id="cxcFacturaInfo" class="fm-list-info">0 registros</span>
  <div id="cxcFacturaPaginacion" class="fm-pagination"></div>
</div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i>Cerrar</button>
      </div>
    </div>
  </div>
</div>
<!--FIN MODAL BUSQUEDA COBRAR CUENTAS POR COBRAR CLIENTES-->

<!--INICIO MODAL BUSQUEDA FACTURAS BORRADOR-->
<div class="modal fade izzy-modal-consulta-facturacion fm-modal" id="modal_buscar_bill_draft" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header fm-modal-header">
        <div>
          <h4 class="modal-title mb-0">Buscar Facturas Pendientes</h4>
          <small>Continúe o elimine borradores sin afectar la secuencia fiscal.</small>
        </div>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body fm-modal-body">
        <form class="FormularioAjax fm-form" id="formulario_bill_draft">
          
<div class="fm-section-card fm-filter-card mb-3">
  <div class="fm-section-header">
    <div>
      <h6 class="mb-0"><i class="fas fa-filter mr-1"></i>Filtros de facturas pendientes</h6>
      <small>Use los criterios necesarios y aplique la búsqueda.</small>
    </div>
    <button type="button" class="btn btn-outline-secondary btn-sm fm-toggle-filter" data-target="#borradoresFacturaFiltrosContenido" aria-expanded="true">
      <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
    </button>
  </div>
  <div id="borradoresFacturaFiltrosContenido" class="fm-section-body">
    
<div class="fm-filter-row">
  <div class="fm-filter-field"><label>Fecha Inicio</label><input type="date" required id="fechai" name="fechai" value="<?php 
    $fecha = date ("Y-m-d");
    $año = date("Y", strtotime($fecha));
    $mes = date("m", strtotime($fecha));
    $dia = date("d", mktime(0,0,0, $mes+1, 0, $año));
    $dia1 = date('d', mktime(0,0,0, $mes, 1, $año));
    $dia2 = date('d', mktime(0,0,0, $mes, $dia, $año));
    $fecha_inicial = date("Y-m-d", strtotime($año."-".$mes."-".$dia1));
    echo $fecha_inicial;
?>" class="form-control"></div>
  <div class="fm-filter-field"><label>Fecha Fin</label><input type="date" required id="fechaf" name="fechaf" value="<?php echo date ("Y-m-d");?>" class="form-control"></div>
  <div class="fm-filter-actions">
    <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i>Buscar</button>
    <button type="reset" id="btn-limpiar-filtros" class="btn btn-secondary"><i class="fas fa-broom mr-1"></i>Limpiar</button>
  </div>
</div>
  </div>
</div>
          <div class="fm-directory-card">
            
<div class="fm-list-toolbar">
  <div class="fm-toolbar-left"><button type="button" id="btnActualizarBorradoresFm" class="btn table_actualizar btn-secondary btn-sm mr-2 mb-2"><i class="fas fa-sync-alt mr-1"></i>Actualizar</button></div>
  <div class="fm-toolbar-right">
    <label class="fm-page-size">Mostrar
      <select id="borradoresFacturaPageSize" class="form-control form-control-sm fm-page-size-select">
        <option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option>
      </select>
      registros
    </label>
    <div class="btn-group btn-group-sm fm-view-switch" role="group" aria-label="Tipo de vista">
      <button type="button" class="btn btn-outline-secondary fm-view-btn active" data-list="borradoresFactura" data-view="detalle" title="Vista detalle"><i class="fas fa-list"></i></button>
      <button type="button" class="btn btn-outline-secondary fm-view-btn" data-list="borradoresFactura" data-view="miniatura" title="Vista miniatura"><i class="fas fa-th-large"></i></button>
    </div>
    <div class="fm-search-wrap">
      <i class="fas fa-search"></i>
      <input type="search" id="borradoresFacturaSearch" class="form-control form-control-sm" placeholder="Buscar..." autocomplete="off">
      <button type="button" class="fm-search-clear" data-list="borradoresFactura" title="Limpiar búsqueda"><i class="fas fa-times"></i></button>
    </div>
  </div>
</div>
<div id="borradoresFacturaListado" class="fm-listado" data-list="borradoresFactura"></div>
<div class="fm-list-footer">
  <span id="borradoresFacturaInfo" class="fm-list-info">0 registros</span>
  <div id="borradoresFacturaPaginacion" class="fm-pagination"></div>
</div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i>Cerrar</button>
      </div>
    </div>
  </div>
</div>
<!--FIN MODAL BUSQUEDA FACTURAS BORRADOR-->

<!--INICIO MODAL BUSQUEDA CREDITO Y CONTADO-->
<div class="modal fade izzy-modal-consulta-facturacion fm-modal" id="modal_buscar_bill" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header fm-modal-header">
        <div>
          <h4 class="modal-title mb-0">Buscar Facturas</h4>
          <small>Facturas emitidas con acciones, reportes y Nota de Crédito.</small>
        </div>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body fm-modal-body">
        <form class="FormularioAjax fm-form" id="formulario_bill">
          
<div class="fm-section-card fm-filter-card mb-3">
  <div class="fm-section-header">
    <div>
      <h6 class="mb-0"><i class="fas fa-filter mr-1"></i>Filtros de facturas emitidas</h6>
      <small>Use los criterios necesarios y aplique la búsqueda.</small>
    </div>
    <button type="button" class="btn btn-outline-secondary btn-sm fm-toggle-filter" data-target="#facturasEmitidasFiltrosContenido" aria-expanded="true">
      <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
    </button>
  </div>
  <div id="facturasEmitidasFiltrosContenido" class="fm-section-body">
    
<div class="fm-filter-row">
  <div class="fm-filter-field"><label>Tipo Factura</label><select id="tipo_factura_reporte" name="tipo_factura_reporte" class="form-control selectpicker" title="Tipo de Factura" data-live-search="true"></select></div>
  <div class="fm-filter-field"><label>Facturador</label><select id="facturador" name="facturador" class="form-control selectpicker" title="Facturador" data-live-search="true"></select></div>
  <div class="fm-filter-field"><label>Vendedor</label><select id="vendedor" name="vendedor" class="form-control selectpicker" title="Vendedor" data-live-search="true"></select></div>
  <div class="fm-filter-field"><label>Fecha Inicio</label><input type="date" required id="fechai" name="fechai" value="<?php 
    $fecha = date ("Y-m-d");
    $año = date("Y", strtotime($fecha));
    $mes = date("m", strtotime($fecha));
    $dia = date("d", mktime(0,0,0, $mes+1, 0, $año));
    $dia1 = date('d', mktime(0,0,0, $mes, 1, $año));
    $dia2 = date('d', mktime(0,0,0, $mes, $dia, $año));
    $fecha_inicial = date("Y-m-d", strtotime($año."-".$mes."-".$dia1));
    echo $fecha_inicial;
?>" class="form-control"></div>
  <div class="fm-filter-field"><label>Fecha Fin</label><input type="date" required id="fechaf" name="fechaf" value="<?php echo date ("Y-m-d");?>" class="form-control"></div>
  <div class="fm-filter-actions">
    <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i>Buscar</button>
    <button type="reset" id="btn-limpiar-filtros" class="btn btn-secondary"><i class="fas fa-broom mr-1"></i>Limpiar</button>
  </div>
</div>
  </div>
</div>
          <div class="fm-directory-card">
            
<div class="fm-list-toolbar">
  <div class="fm-toolbar-left"><button type="button" id="btnActualizarFacturasFm" class="btn table_actualizar btn-secondary ocultar btn-sm mr-2 mb-2"><i class="fas fa-sync-alt mr-1"></i>Actualizar</button><button type="button" id="btnExcelFacturasFm" class="btn table_reportes btn-success ocultar btn-sm mr-2 mb-2"><i class="fas fa-file-excel mr-1"></i>Excel</button><button type="button" id="btnPdfFacturasFm" class="btn table_reportes btn-danger ocultar btn-sm mr-2 mb-2"><i class="fas fa-file-pdf mr-1"></i>PDF</button></div>
  <div class="fm-toolbar-right">
    <label class="fm-page-size">Mostrar
      <select id="facturasEmitidasPageSize" class="form-control form-control-sm fm-page-size-select">
        <option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option>
      </select>
      registros
    </label>
    <div class="btn-group btn-group-sm fm-view-switch" role="group" aria-label="Tipo de vista">
      <button type="button" class="btn btn-outline-secondary fm-view-btn active" data-list="facturasEmitidas" data-view="detalle" title="Vista detalle"><i class="fas fa-list"></i></button>
      <button type="button" class="btn btn-outline-secondary fm-view-btn" data-list="facturasEmitidas" data-view="miniatura" title="Vista miniatura"><i class="fas fa-th-large"></i></button>
    </div>
    <div class="fm-search-wrap">
      <i class="fas fa-search"></i>
      <input type="search" id="facturasEmitidasSearch" class="form-control form-control-sm" placeholder="Buscar..." autocomplete="off">
      <button type="button" class="fm-search-clear" data-list="facturasEmitidas" title="Limpiar búsqueda"><i class="fas fa-times"></i></button>
    </div>
  </div>
</div>
<div id="facturasEmitidasListado" class="fm-listado" data-list="facturasEmitidas"></div>
<div class="fm-list-footer">
  <span id="facturasEmitidasInfo" class="fm-list-info">0 registros</span>
  <div id="facturasEmitidasPaginacion" class="fm-pagination"></div>
</div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i>Cerrar</button>
      </div>
    </div>
  </div>
</div>
<!--FIN MODAL BUSQUEDA FACTURAS CREDITO Y CONTADO-->

<!-- =========================================================
     INICIO MODAL - CAJA DESDE FACTURACIÓN
     ========================================================= -->
<div class="modal fade izzy-modal-consulta-facturacion fm-modal" id="modalCajaFactura" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header fm-modal-header">
        <div>
          <h4 class="modal-title mb-0"><i class="fas fa-cash-register mr-1"></i>Caja desde Facturación</h4>
          <small>Consulta de caja, ventas, retiros, neto y operaciones disponibles.</small>
        </div>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body fm-modal-body">
        <form class="FormularioAjax fm-form" id="formCajaFactura">
          
<div class="fm-section-card fm-filter-card mb-3">
  <div class="fm-section-header">
    <div>
      <h6 class="mb-0"><i class="fas fa-filter mr-1"></i>Filtros de caja</h6>
      <small>Use los criterios necesarios y aplique la búsqueda.</small>
    </div>
    <button type="button" class="btn btn-outline-secondary btn-sm fm-toggle-filter" data-target="#cajaFacturaFiltrosContenido" aria-expanded="true">
      <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
    </button>
  </div>
  <div id="cajaFacturaFiltrosContenido" class="fm-section-body">
    
<div class="fm-filter-row">
  <div class="fm-filter-field"><label>Estado</label><select id="estado_caja_factura" name="estado_caja_factura" class="form-control"><option value="0">Todas</option><option value="1">Activas</option><option value="2">Cerradas</option></select></div>
  <div class="fm-filter-field"><label>Fecha Inicial</label><input type="date" class="form-control" id="fecha_caja_factura_i" name="fecha_caja_factura_i" value="<?php echo date('Y-m-d'); ?>"></div>
  <div class="fm-filter-field"><label>Fecha Final</label><input type="date" class="form-control" id="fecha_caja_factura_f" name="fecha_caja_factura_f" value="<?php echo date('Y-m-d'); ?>"></div>
  <div class="fm-filter-actions"><button type="submit" class="btn btn-primary"><i class="fas fa-filter mr-1"></i>Filtrar</button></div>
</div>
  </div>
</div>
          <div class="fm-directory-card">
            
<div class="fm-list-toolbar">
  <div class="fm-toolbar-left"><button type="button" id="btnActualizarCajaFactura" class="btn table_actualizar btn-secondary ocultar btn-sm mr-2 mb-2"><i class="fas fa-sync-alt mr-1"></i>Actualizar</button><button type="button" id="btnExcelCajaFacturaFm" class="btn table_reportes btn-success ocultar btn-sm mr-2 mb-2"><i class="fas fa-file-excel mr-1"></i>Excel</button><button type="button" id="btnPdfCajaFacturaFm" class="btn table_reportes btn-danger ocultar btn-sm mr-2 mb-2"><i class="fas fa-file-pdf mr-1"></i>PDF</button></div>
  <div class="fm-toolbar-right">
    <label class="fm-page-size">Mostrar
      <select id="cajaFacturaPageSize" class="form-control form-control-sm fm-page-size-select">
        <option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option>
      </select>
      registros
    </label>
    <div class="btn-group btn-group-sm fm-view-switch" role="group" aria-label="Tipo de vista">
      <button type="button" class="btn btn-outline-secondary fm-view-btn active" data-list="cajaFactura" data-view="detalle" title="Vista detalle"><i class="fas fa-list"></i></button>
      <button type="button" class="btn btn-outline-secondary fm-view-btn" data-list="cajaFactura" data-view="miniatura" title="Vista miniatura"><i class="fas fa-th-large"></i></button>
    </div>
    <div class="fm-search-wrap">
      <i class="fas fa-search"></i>
      <input type="search" id="cajaFacturaSearch" class="form-control form-control-sm" placeholder="Buscar..." autocomplete="off">
      <button type="button" class="fm-search-clear" data-list="cajaFactura" title="Limpiar búsqueda"><i class="fas fa-times"></i></button>
    </div>
  </div>
</div>
<div id="dataTableCajaFactura" class="fm-listado" data-list="cajaFactura"></div>
<div class="fm-list-footer">
  <span id="cajaFacturaInfo" class="fm-list-info">0 registros</span>
  <div id="cajaFacturaPaginacion" class="fm-pagination"></div>
</div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i>Cerrar</button>
      </div>
    </div>
  </div>
</div>
<!-- =========================================================
     FIN MODAL - CAJA DESDE FACTURACIÓN
     ========================================================= -->

<!-- =========================================================
     MODAL PREMIUM - FACTURA ANULADA / REGENERAR
     Solo se utiliza desde la vista principal de Facturación.
     ========================================================= -->
<div class="modal fade" id="modalRegenerarFactura" tabindex="-1" role="dialog"
     aria-labelledby="modalRegenerarFacturaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content izzy-regenerar-modal">
            <div class="modal-header izzy-regenerar-header">
                <div>
                    <div class="izzy-regenerar-eyebrow">
                        <i class="fas fa-shield-alt mr-1"></i> Documento fiscal
                    </div>
                    <h5 class="modal-title mb-0" id="modalRegenerarFacturaLabel">
                        Factura anulada correctamente
                    </h5>
                </div>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body izzy-regenerar-body">
                <div class="izzy-regenerar-status">
                    <div class="izzy-regenerar-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="izzy-regenerar-status-text">
                        <div class="izzy-regenerar-documento">
                            <span class="izzy-regenerar-label">Factura anulada</span>
                            <strong id="modalRegenerarFacturaNumero"></strong>
                        </div>
                        <div class="izzy-regenerar-cliente">
                            <span class="izzy-regenerar-label">Cliente</span>
                            <strong id="modalRegenerarFacturaCliente"></strong>
                        </div>
                    </div>
                </div>

                <div class="izzy-regenerar-copy">
                    <h6>¿Desea cargar una copia editable?</h6>
                    <p>
                        Puede utilizar los datos de la factura anulada como base para realizar las correcciones necesarias
                        y generar una nueva factura sin volver a ingresar toda la información.
                    </p>
                </div>

                <div class="izzy-regenerar-note">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>La factura original no será modificada.</strong>
                        <span>La nueva factura se registrará como un documento independiente y recibirá un nuevo número fiscal.</span>
                    </div>
                </div>
            </div>

            <div class="modal-footer izzy-regenerar-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-check mr-1"></i> Finalizar
                </button>
                <button type="button" class="btn btn-success" id="btnRegenerarFacturaConfirmar">
                    <i class="fas fa-file-import mr-1"></i> Cargar para corregir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- FIN MODAL PREMIUM - FACTURA ANULADA / REGENERAR -->