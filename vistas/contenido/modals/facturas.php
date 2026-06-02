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

<!-- INICIO MODAL AYUDA (PRO) -->
<div class="modal fade" id="modalAyuda" tabindex="-1" role="dialog" aria-labelledby="modalAyudaLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content help-modal">

      <!-- Header -->
      <div class="modal-header help-header">
        <div class="title-wrap">
          <span class="help-badge"><i class="fas fa-life-ring"></i></span>
          <div class="title-text">
            <h4 class="modal-title help-title" id="modalAyudaLabel">Centro de Ayuda</h4>
            <small class="help-subtitle">Atajos y operaciones rápidas de facturación</small>
          </div>
        </div>

        <div class="header-actions">
          <div class="btn-group btn-group-sm mr-2">
            <button type="button" class="btn btn-light" id="helpCopy">
              <i class="fas fa-copy"></i> Copiar
            </button>
            <button type="button" class="btn btn-light" id="helpPrint">
              <i class="fas fa-print"></i> Imprimir
            </button>
          </div>
          <button type="button" class="close text-white ml-1" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      </div>

      <!-- Body -->
      <div class="modal-body pt-0">
        <!-- Aviso -->
        <div class="callout callout-info mb-3">
          <div class="d-flex">
            <i class="fas fa-info-circle mr-2 mt-1"></i>
            <div class="small">
              <strong>Importante:</strong> las teclas de función funcionan cuando el foco está en el campo
              <u>Código del Producto</u>.
            </div>
          </div>
        </div>

        <!-- Buscador -->
        <div class="form-group position-relative mb-4">
          <input class="form-control form-control-lg pl-5" id="helpSearch" placeholder="Buscar atajo... (ej. F2, cotización, cliente)">
          <i class="fas fa-search help-search-icon"></i>
        </div>

        <div class="row">
          <!-- Atajos -->
          <div class="col-lg-8">
            <div class="table-responsive">
              <table class="table table-hover table-sm table-shortcuts" id="tableShortcuts">
                <thead>
                  <tr>
                    <th style="width:13%">Tecla</th>
                    <th style="width:22%">Acción</th>
                    <th>Descripción</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td><kbd>F2</kbd></td><td>Guardar</td><td>Guarda la factura como <u>borrador</u> para continuar después. No emite documento fiscal; podrás editar/eliminar sin afectar SAR.</td></tr>
                  <tr><td><kbd>F3</kbd></td><td>Búsqueda de productos</td><td>Abre el buscador; permite crear productos. Usa “Actualizar” para refrescar tras un alta.</td></tr>
                  <tr><td><kbd>F4</kbd></td><td>Descuentos</td><td>Aplica descuentos a productos. Puede requerir autorización de supervisor/administrador.</td></tr>
                  <tr><td><kbd>F5</kbd></td><td>Actualizar</td><td>Recarga la página. <u>Precaución</u>: perderás lo no guardado.</td></tr>
                  <tr><td><kbd>F6</kbd></td><td>Modificar precio</td><td>Ajusta el precio cuando exista soporte (compra/cotización).</td></tr>
                  <tr><td><kbd>F7</kbd></td><td>Registrar / Cobrar</td><td>Emite la factura fiscal. Acción <u>definitiva</u>: no editable (solo anulación o nota de crédito).</td></tr>
                  <tr><td><kbd>F8</kbd></td><td>Clientes</td><td>Busca o crea clientes. Usa “Actualizar” para refrescar la lista.</td></tr>
                  <tr><td><kbd>F9</kbd></td><td>Vendedores</td><td>Busca o crea colaboradores/vendedores.</td></tr>
                  <tr><td><kbd>F10</kbd></td><td>Apertura de caja</td><td>Habilita caja para ventas y registro de fondo inicial.</td></tr>
                  <tr><td><kbd>F11</kbd></td><td>Cierre de caja</td><td>Cierre del día con conteo de ventas (desde la primera hasta la última factura).</td></tr>
                  <tr><td><kbd>+</kbd> / <kbd>−</kbd></td><td>Cantidad</td><td>Incrementa/disminuye la cantidad con el foco en <em>Código del Producto</em>.</td></tr>
                  <tr><td><kbd>*</kbd></td><td>Comodín de cantidad</td><td>Escribe <code>10*código</code> para agregar 10 unidades del producto.</td></tr>
                  <tr><td><kbd>F1</kbd></td><td>Ayuda</td><td>Abre esta ventana de ayuda en cualquier momento.</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Lateral -->
          <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
              <div class="card-body py-3">
                <h6 class="mb-2"><i class="fas fa-lightbulb mr-1"></i> Consejos rápidos</h6>
                <ul class="list-unstyled small mb-0">
                  <li class="mb-1">Evita <kbd>F5</kbd> si tienes cambios sin guardar.</li>
                  <li class="mb-1">Para cantidades rápidas: <kbd>n*</kbd><code>código</code> (ej. <code>10*ABC123</code>).</li>
                  <li class="mb-1">Verifica existencia antes de emitir.</li>
                </ul>
              </div>
            </div>

            <div class="card shadow-sm">
              <div class="card-body py-3">
                <h6 class="mb-2"><i class="fas fa-headset mr-1"></i> Soporte</h6>
                <p class="small mb-3">¿Necesitas más ayuda? Contacta al administrador del sistema.</p>

                <!-- Botón WhatsApp -->
                <a href="<?php echo htmlspecialchars($url_ws, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="btn btn-success btn-sm btn-block">
                  <i class="fab fa-whatsapp"></i> Chatear por WhatsApp
                </a>
                <?php if ($telefono_ws_legible): ?>
                <div class="small text-muted mt-2">
                  <i class="fas fa-phone-alt mr-1"></i><?php echo htmlspecialchars($telefono_ws_legible, ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <small class="text-muted mr-auto">Tip: enfoca <em>Código del Producto</em> para usar las teclas de función.</small>
        <button type="button" class="btn btn-primary" data-dismiss="modal"><i class="fas fa-check"></i> Entendido</button>
      </div>
    </div>
  </div>
</div>
<!-- FIN MODAL AYUDA (PRO) -->

<!-- Inicio Modal para editar precio -->
<div class="modal fade" id="editarPrecioModal" tabindex="-1" role="dialog" aria-labelledby="editarPrecioModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h4 class="modal-title" id="editarPrecioModalLabel">
          <i class="fas fa-dollar-sign"></i> Editar Precio
        </h4>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <form id="editar-precio-form">
          <input type="hidden" id="producto-precio-index" value="">

          <div class="form-group mb-3">
            <label for="nuevo-precio-producto" class="form-label">Nuevo precio de venta</label>
            <div class="input-group">
              <span class="input-group-text">L.</span>
              <input type="number" class="form-control" id="nuevo-precio-producto" min="0.01" step="0.01" placeholder="0.00">
            </div>
            <small class="text-muted">Este cambio aplica solo a la línea actual de la factura/proforma.</small>
          </div>

          <div class="form-group mb-0">
            <label class="form-label">Vista previa</label>

                <div id="precio-total-preview" class="precio-preview-box">
                    <div class="precio-preview-item">
                    <span>Subtotal</span>
                    <strong id="preview-subtotal">L. 0.00</strong>
                    </div>

                    <div class="precio-preview-item">
                    <span>ISV 15%</span>
                    <strong id="preview-isv15">L. 0.00</strong>
                    </div>

                    <div class="precio-preview-item">
                    <span>ISV 18%</span>
                    <strong id="preview-isv18">L. 0.00</strong>
                    </div>

                    <div class="precio-preview-item total">
                    <span>Total</span>
                    <strong id="preview-total">L. 0.00</strong>
                    </div>
                </div>
            </div>

        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">
          <i class="fas fa-times fa-lg mr-1"></i> Cancelar
        </button>
        <button type="button" class="btn btn-primary" id="guardar-precio">
          <i class="far fa-save fa-lg mr-1"></i> Guardar
        </button>
      </div>
    </div>
  </div>
</div>
<!-- Fin Modal para editar precio -->

<!--INICIO MODAL PARA FORMULARIO DESCUENTOS EN FACTURACION-->
<div class="modal fade" id="modalDescuentoFacturacion">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Descuento</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="form-horizontal" id="formDescuentoFacturacion" action="" method="POST" data-form=""
                    enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <input type="hidden" required="required" readonly id="descuento_productos_id"
                                name="descuento_productos_id" />
                            <input type="hidden" required="required" readonly id="row_index" name="row_index" />
                            <input type="hidden" required="required" readonly id="col_index" name="col_index" />
                            <div class="input-group mb-3">
                                <input type="text" required readonly id="pro_descuento_fact" name="pro_descuento_fact"
                                    class="form-control" />
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fa fa-plus-square fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-8 mb-3">
                            <label for="producto_descuento_fact">Producto <span class="priority">*<span /></label>
                            <input type="text" readonly required id="producto_descuento_fact"
                                name="producto_descuento_fact" placeholder="Producto" class="form-control"
                                maxlength="11"
                                oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" />
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="precio_descuento_fact">Precio <span class="priority">*<span /></label>
                            <input type="text" readonly required id="precio_descuento_fact" name="precio_descuento_fact"
                                placeholder="Precio" class="form-control" maxlength="30"
                                oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"
                                step="0.01" />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-4 mb-3">
                            <label for="porcentaje_descuento_fact">% Descuento <span class="priority">*<span /></label>
                            <input type="text" required id="porcentaje_descuento_fact" name="porcentaje_descuento_fact"
                                placeholder="Porcentaje de Descuento" class="form-control" maxlength="11"
                                oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" />
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="descuento_fact">Valor Descuento <span class="priority">*<span /></label>
                            <input type="text" required id="descuento_fact" name="descuento_fact"
                                placeholder="Descuento" class="form-control" maxlength="30"
                                oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"
                                step="0.01" />
                        </div>
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;"
                    id="reg_DescuentoFacturacion" form="formDescuentoFacturacion">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PARA FORMULARIO DESCUENTOS EN FACTURACION-->

<!--INICIO MODAL BUSQUEDA CONVERTIR COTIZACION EN FACTURAS-->
<div class="modal fade" id="modal_buscar_cotizaciones">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Buscar Cotizaciones</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formulario_busqueda_cotizaciones">

                    <div class="row align-items-end">
                        <!-- Tipo Factura -->
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="form-group">
                                <label class="small mb-1">Tipo Factura</label>
                                <select id="tipo_cotizacion_reporte" name="tipo_cotizacion_reporte"
                                    class="form-control selectpicker" title="Tipo Factura" data-live-search="true">
                                </select>
                            </div>
                        </div>

                        <!-- Fecha Inicio -->
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="form-group">
                                <label class="small mb-1">Fecha Inicio</label>
                                <input type="date" required id="fechai" name="fechai" value="<?php 
                                    $fecha = date ("Y-m-d");
                                    $año = date("Y", strtotime($fecha));
                                    $mes = date("m", strtotime($fecha));
                                    $dia = date("d", mktime(0,0,0, $mes+1, 0, $año));
                                    $dia1 = date('d', mktime(0,0,0, $mes, 1, $año));
                                    $dia2 = date('d', mktime(0,0,0, $mes, $dia, $año));
                                    $fecha_inicial = date("Y-m-d", strtotime($año."-".$mes."-".$dia1));
                                    echo $fecha_inicial;
                                ?>" class="form-control" title="Fecha Inicio">
                            </div>
                        </div>

                        <!-- Fecha Fin -->
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="form-group">
                                <label class="small mb-1">Fecha Fin</label>
                                <input type="date" required id="fechaf" name="fechaf"
                                    value="<?php echo date ("Y-m-d");?>" class="form-control" title="Fecha Fin">
                            </div>
                        </div>

                        <!-- Botón Buscar -->
                        <div class="col-md-3 col-sm-6 mb-3 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search fa-lg mr-1"></i> Buscar
                            </button>
                            <button type="reset" id="btn-limpiar-filtros" class="btn btn-secondary">
                                <i class="fas fa-broom fa-lg mr-1"></i> Limpiar
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-md-12">
                            <div class="overflow-auto">
                                <table id="DatatableBusquedaCotizaciones"
                                    class="table table-header-gradient table-striped table-condensed table-hover"
                                    style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Continuar</th>
                                            <th>Imprimir</th>
                                            <th>Fecha</th>
                                            <th>Tipo</th>
                                            <th>Proveedor</th>
                                            <th>Factura</th>
                                            <th>SubTotal</th>
                                            <th>ISV</th>
                                            <th>Descuento</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">

            </div>
        </div>
    </div>
</div>
<!--FIN MODAL BUSQUEDA CONVERTIR COTIZACION EN FACTURAS-->

<!--INICIO MODAL BUSQUEDA COBRAR CUENTAS POR COBRAR CLIENTES-->
<div class="modal fade" id="modal_buscar_cuentas_cobrar_clientes">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Buscar Cuentas por Cobrar Clientes</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formulario_busqueda_cuentas_cobrar_clientes">
                    <div class="container-fluid">
                        <!-- Fila de filtros -->
                        <div class="row align-items-end">
                            <div class="col-md-3 col-sm-6 mb-2">
                                <div class="form-group">
                                    <label class="small mb-1">Estado</label>
                                    <select id="cobrar_clientes_estado" name="cobrar_clientes_estado"
                                        class="form-control selectpicker" title="Estado" data-live-search="true">
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3 col-sm-6 mb-2">
                                <div class="form-group">
                                    <label class="small mb-1">Clientes</label>
                                    <select id="cobrar_clientes" name="cobrar_clientes"
                                        class="form-control selectpicker" title="Clientes" data-live-search="true">
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3 col-sm-6 mb-2">
                                <div class="form-group">
                                    <label class="small mb-1">Fecha Inicio</label>
                                    <input type="date" required id="fechai" name="fechai"
                                        value="<?php echo date ("Y-m-d");?>" class="form-control" title="Fecha Inicio">
                                </div>
                            </div>

                            <div class="col-md-3 col-sm-6 mb-2">
                                <div class="form-group">
                                    <label class="small mb-1">Fecha Fin</label>
                                    <input type="date" required id="fechaf" name="fechaf"
                                        value="<?php echo date ("Y-m-d");?>" class="form-control" title="Fecha Fin">
                                </div>
                            </div>
                        </div>

                        <!-- Fila de botones ajustada -->
                        <div class="row mb-3">
                            <div class="col-12 text-right">
                                <button type="submit" class="btn btn-primary mr-2">
                                    <i class="fas fa-search fa-lg mr-1"></i> Buscar
                                </button>
                                <button type="reset" id="btn-limpiar-filtros" class="btn btn-secondary">
                                    <i class="fas fa-broom fa-lg mr-1"></i> Limpiar
                                </button>
                            </div>
                        </div>

                        <!-- Tabla de resultados -->
                        <div class="row">
                            <div class="col-12">
                                <div class="table-responsive">
                                    <table id="DatatableBusquedaCuentasCobrarClientes"
                                        class="table table-header-gradient table-striped table-condensed table-hover"
                                        style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Cliente</th>
                                                <th>Estado</th>
                                                <th>Factura</th>
                                                <th>Crédito</th>
                                                <th>Abonos</th>
                                                <th>Saldo</th>
                                                <th>Abonar</th>
                                                <th>Abonos Realizados</th>
                                                <th>Factura</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger" data-dismiss="modal">
                    <i class="fas fa-times fa-lg mr-1"></i> Cancelar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL BUSQUEDA COBRAR CUENTAS POR COBRAR CLIENTES-->

<!--INICIO MODAL BUSQUEDA FACTURAS BORRADOR-->
<div class="modal fade" id="modal_buscar_bill_draft">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Buscar Facturas Pendientes</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formulario_bill_draft">
                    <div class="container-fluid">
                        <!-- Fila de filtros -->
                        <div class="row align-items-end">
                            <div class="col-md-5 col-sm-6 mb-2">
                                <div class="form-group">
                                    <label class="small mb-1">Fecha Inicio</label>
                                    <input type="date" required id="fechai" name="fechai" value="<?php 
                                        $fecha = date ("Y-m-d");
                                        $año = date("Y", strtotime($fecha));
                                        $mes = date("m", strtotime($fecha));
                                        $dia = date("d", mktime(0,0,0, $mes+1, 0, $año));
                                        $dia1 = date('d', mktime(0,0,0, $mes, 1, $año));
                                        $dia2 = date('d', mktime(0,0,0, $mes, $dia, $año));
                                        $fecha_inicial = date("Y-m-d", strtotime($año."-".$mes."-".$dia1));
                                        echo $fecha_inicial;
                                    ?>" class="form-control" title="Fecha Inicio">
                                </div>
                            </div>

                            <div class="col-md-5 col-sm-6 mb-2">
                                <div class="form-group">
                                    <label class="small mb-1">Fecha Fin</label>
                                    <input type="date" required id="fechaf" name="fechaf"
                                        value="<?php echo date ("Y-m-d");?>" class="form-control" title="Fecha Fin">
                                </div>
                            </div>
                        </div>

                        <!-- Fila de botones ajustada -->
                        <div class="row mb-3">
                            <div class="col-12 text-right">
                                <button type="submit" class="btn btn-primary mr-2">
                                    <i class="fas fa-search fa-lg mr-1"></i> Buscar
                                </button>
                                <button type="reset" id="btn-limpiar-filtros" class="btn btn-secondary">
                                    <i class="fas fa-broom fa-lg mr-1"></i> Limpiar
                                </button>
                            </div>
                        </div>

                        <!-- Tabla de resultados -->
                        <div class="row">
                            <div class="col-12">
                                <div class="table-responsive">
                                    <table id="DatatableBusquedaBillDraft"
                                        class="table table-header-gradient table-striped table-condensed table-hover"
                                        style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>Continuar</th>
                                                <th>Eliminar</th>
                                                <th>Fecha</th>
                                                <th>Tipo</th>
                                                <th>Empresa</th>
                                                <th>Factura</th>
                                                <th>SubTotal</th>
                                                <th>ISV</th>
                                                <th>Descuento</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger" data-dismiss="modal">
                    <i class="fas fa-times fa-lg mr-1"></i> Cancelar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL BUSQUEDA FACTURAS BORRADOR-->

<!--INICIO MODAL BUSQUEDA CREDITO Y CONTADO-->
<div class="modal fade" id="modal_buscar_bill">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Buscar Facturas</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formulario_bill">
                    <div class="container-fluid">
                        <!-- Primera fila de filtros -->
                        <div class="row align-items-end mb-3">
                            <div class="col-md-3 col-sm-6">
                                <div class="form-group">
                                    <label class="small mb-1">Tipo Factura</label>
                                    <select id="tipo_factura_reporte" name="tipo_factura_reporte"
                                        class="form-control selectpicker" title="Tipo de Factura"
                                        data-live-search="true">
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3 col-sm-6">
                                <div class="form-group">
                                    <label class="small mb-1">Facturador</label>
                                    <select id="facturador" name="facturador" class="form-control selectpicker"
                                        title="Facturador" data-live-search="true">
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3 col-sm-6">
                                <div class="form-group">
                                    <label class="small mb-1">Vendedor</label>
                                    <select id="vendedor" name="vendedor" class="form-control selectpicker"
                                        title="Vendedor" data-live-search="true">
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Segunda fila con fechas y botones -->
                        <div class="row align-items-end mb-3">
                            <div class="col-md-3 col-sm-6">
                                <div class="form-group">
                                    <label class="small mb-1">Fecha Inicio</label>
                                    <input type="date" required id="fechai" name="fechai" value="<?php 
                                        $fecha = date ("Y-m-d");
                                        $año = date("Y", strtotime($fecha));
                                        $mes = date("m", strtotime($fecha));
                                        $dia = date("d", mktime(0,0,0, $mes+1, 0, $año));
                                        $dia1 = date('d', mktime(0,0,0, $mes, 1, $año));
                                        $dia2 = date('d', mktime(0,0,0, $mes, $dia, $año));
                                        $fecha_inicial = date("Y-m-d", strtotime($año."-".$mes."-".$dia1));
                                        echo $fecha_inicial;
                                    ?>" class="form-control" title="Fecha Inicio">
                                </div>
                            </div>

                            <div class="col-md-3 col-sm-6">
                                <div class="form-group">
                                    <label class="small mb-1">Fecha Fin</label>
                                    <input type="date" required id="fechaf" name="fechaf"
                                        value="<?php echo date ("Y-m-d");?>" class="form-control" title="Fecha Fin">
                                </div>
                            </div>

                            <!-- Botones en la misma fila -->
                            <div class="col-md-6 col-sm-12 d-flex align-items-end justify-content-end">
                                <button type="submit" class="btn btn-primary mr-2">
                                    <i class="fas fa-search fa-lg mr-1"></i> Buscar
                                </button>
                                <button type="reset" id="btn-limpiar-filtros" class="btn btn-secondary">
                                    <i class="fas fa-broom fa-lg mr-1"></i> Limpiar
                                </button>
                            </div>
                        </div>

                        <!-- Contador de registros -->
                        <div class="row mb-2">
                            <div class="col-12 text-right">
                                <small class="text-muted">Mostrando <span id="contador-registros">5</span>
                                    registros</small>
                            </div>
                        </div>

                        <!-- Tabla de resultados -->
                        <div class="row">
                            <div class="col-12">
                                <div class="table-responsive">
                                    <table id="DatatableBusquedaBill"
                                        class="table table-header-gradient table-striped table-condensed table-hover"
                                        style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Tipo</th>
                                                <th>Cliente</th>
                                                <th>Factura</th>
                                                <th>SubTotal</th>
                                                <th>ISV</th>
                                                <th>Descuento</th>
                                                <th>Total</th>
                                                <th>Factura</th>
                                                <th>Comprobante</th>
                                                <th>Enviar</th>
                                                <th>Anular</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger" data-dismiss="modal">
                    <i class="fas fa-times fa-lg mr-1"></i> Cancelar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL BUSQUEDA FACTURAS CREDITO Y CONTADO-->

<!-- Modal: Programar Factura Recurrente -->
<div class="modal fade" id="recurringBillModal" tabindex="-1" role="dialog" aria-labelledby="recurringBillModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title" id="recurringBillModalLabel">Programar Factura Recurrente</h6>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <!-- Tipo de documento -->
        <div class="form-group">
          <label class="small mb-1 d-block">Tipo de documento</label>
          <div class="btn-group btn-group-sm" role="group" aria-label="Tipo de documento">
            <button type="button" class="btn btn-primary" id="btn-rec-tipo-normal" data-tipo="0">Normal</button>
            <button type="button" class="btn btn-outline-primary" id="btn-rec-tipo-proforma" data-tipo="1">Proforma</button>
          </div>
          <input type="hidden" id="rec_tipo_documento" value="0">
        </div>

        <!-- Tipo de factura (contado/crédito) -->
        <div class="form-group">
          <label class="small mb-1 d-block">Tipo de factura</label>
          <div class="btn-group btn-group-sm" role="group" aria-label="Tipo de factura">
            <button type="button" class="btn btn-primary" id="btn-rec-contado" data-tipo="1">Contado</button>
            <button type="button" class="btn btn-outline-primary" id="btn-rec-credito" data-tipo="2">Crédito</button>
          </div>
          <input type="hidden" id="rec_tipo_factura" value="1">
        </div>

        <!-- Fecha/hora de primera ejecución -->
        <div class="form-group">
          <label class="small mb-1">Fecha de generación</label>
          <input type="datetime-local" class="form-control" id="rec_start_at" required>
          <small class="text-muted">Cuándo quieres que se genere por primera vez.</small>
        </div>

        <!-- Periodicidad -->
        <div class="form-group">
          <label class="small mb-1">Periodicidad</label>
          <select id="rec_periodicidad" class="form-control">
            <option value="once">Una vez</option>
            <option value="daily">Diaria</option>
            <option value="weekly">Semanal</option>
            <option value="monthly" selected>Mensual</option>
          </select>
        </div>

        <!-- Vigencia (opcional) -->
        <div class="form-group">
          <label class="small mb-1">Hasta (opcional)</label>
          <input type="date" class="form-control" id="rec_until">
          <small class="text-muted">Si lo dejas vacío, se repite indefinidamente.</small>
        </div>

        <div class="alert alert-info d-flex align-items-center" id="rec_info">
          <i class="fas fa-info-circle mr-2"></i>
          Se creará una factura con los datos actuales de esta vista en cada fecha programada.
        </div>

        <!-- Spinner -->
        <div id="rec_spinner" class="text-center" style="display:none;">
          <i class="fas fa-spinner fa-spin fa-2x"></i>
          <div>Guardando recurrencia...</div>
        </div>
      </div>

      <div class="modal-footer py-2">
        <button type="button" class="btn btn-danger btn-sm" data-dismiss="modal">
          <i class="fas fa-times-circle mr-1"></i> Cancelar
        </button>
        <button type="button" class="btn btn-primary btn-sm" id="confirmRecurring">
          <i class="fas fa-calendar-check mr-1"></i> Guardar recurrencia
        </button>
      </div>
    </div>
  </div>
</div>

<!-- =========================================================
     INICIO MODAL - CAJA DESDE FACTURACIÓN
     ========================================================= -->
     <div class="modal fade" id="modalCajaFactura" tabindex="-1" role="dialog" aria-labelledby="modalCajaFacturaLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">

            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="modalCajaFacturaLabel">
                    <i class="fas fa-cash-register mr-1"></i>
                    Caja desde Facturación
                    <small class="d-block mt-1 text-light" style="opacity:.85;">
                        Consulta de caja, ventas, retiros y neto.
                    </small>
                </h5>

                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <form id="formCajaFactura" autocomplete="off">
                    <div class="row mb-3">

                        <div class="col-md-3 col-sm-6 mb-2">
                            <label class="small mb-1">Estado</label>
                            <select id="estado_caja_factura" name="estado_caja_factura" class="form-control">
                                <option value="0">Todas</option>
                                <option value="1">Activas</option>
                                <option value="2">Cerradas</option>
                            </select>
                        </div>

                        <div class="col-md-3 col-sm-6 mb-2">
                            <label class="small mb-1">Fecha Inicial</label>
                            <input type="date" class="form-control" id="fecha_caja_factura_i" name="fecha_caja_factura_i" value="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="col-md-3 col-sm-6 mb-2">
                            <label class="small mb-1">Fecha Final</label>
                            <input type="date" class="form-control" id="fecha_caja_factura_f" name="fecha_caja_factura_f" value="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="col-md-3 col-sm-6 mb-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary mr-2">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>

                            <button type="button" class="btn btn-secondary" id="btnActualizarCajaFactura">
                                <i class="fas fa-sync-alt"></i> Actualizar
                            </button>
                        </div>

                    </div>
                </form>

                <div class="table-responsive">
                    <table id="dataTableCajaFactura" class="table table-striped table-hover table-condensed" style="width:100%">
                    </table>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>

        </div>
    </div>
</div>
<!-- =========================================================
     FIN MODAL - CAJA DESDE FACTURACIÓN
     ========================================================= -->