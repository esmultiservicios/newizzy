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
  <!-- Evita mostrar el módulo a medio inicializar mientras llegan mesas/productos/configuración -->
  <style id="rs-boot-critical">
    body.vista-facturacion-restaurante.rs-booting .restaurante-container{visibility:hidden;}
    #rs-boot-screen{display:none;position:fixed;inset:0;z-index:2147483000;align-items:center;justify-content:center;background:#f5f7fa;}
    body.vista-facturacion-restaurante.rs-booting #rs-boot-screen{display:flex;}
    #rs-boot-screen .rs-boot-card{display:flex;align-items:center;gap:12px;padding:14px 18px;border:1px solid #dbe4ee;border-radius:12px;background:#fff;box-shadow:0 10px 30px rgba(15,23,42,.10);font:600 14px/1.25 Arial,sans-serif;color:#334155;}
    #rs-boot-screen .rs-boot-spinner{width:22px;height:22px;border:3px solid #d9e8f5;border-top-color:#2997d6;border-radius:50%;animation:rsBootSpin .75s linear infinite;}
    @keyframes rsBootSpin{to{transform:rotate(360deg)}}
  </style>
</head>
<body class="vista-facturacion-restaurante rs-booting">
  <script>var SERVERURL = '<?php echo SERVERURL; ?>';</script>
  <div id="rs-boot-screen" role="status" aria-live="polite">
    <div class="rs-boot-card"><span class="rs-boot-spinner" aria-hidden="true"></span><span>Preparando punto de venta…</span></div>
  </div>

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
        <div class="mesas-toolbar" id="mesas-toolbar">
          <div class="mesas-search">
            <i class="fas fa-search" aria-hidden="true"></i>
            <input type="search" id="buscar-mesa-rapido" placeholder="Buscar mesa..." autocomplete="off" aria-label="Buscar mesa">
          </div>
          <span class="mesas-count" id="mesas-count" title="Mesas visibles">0</span>
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
                <i class="fas fa-cash-register"></i> Cobrar
              </button>
              <button id="btn-guardar-cuenta" class="btn btn-warning" type="button">
                <i class="fas fa-bookmark"></i> Guardar cuenta
              </button>
              <button id="btn-cuentas-abiertas" class="btn btn-light" type="button">
                <i class="fas fa-folder-open"></i> Cuentas abiertas
              </button>
              <button id="btn-factura-recurrente" class="btn btn-primary" type="button" title="Programar una factura recurrente">
                <i class="fas fa-calendar-alt"></i> Recurrente
              </button>
              <button id="btn-cobrar-mesa" class="btn btn-success" type="button" style="display:none;">
                <i class="fas fa-cash-register"></i> Cobrar mesa
              </button>
              <button id="btn-imprimir" class="btn btn-info" type="button" style="display:none;">
                <i class="fas fa-receipt"></i> <span id="texto-btn-ticket">Ticket comanda</span>
              </button>
              <button id="btn-cerrar" class="btn btn-danger" type="button" style="display:none;">
                <i class="fas fa-times"></i> Cancelar cuenta
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

                  <div class="dropdown-divider" style="margin:.35rem 0;border-top:1px solid #e5e7eb;"></div>
                  <button type="button" id="btn-configuracion-restaurante">
                    <i class="fas fa-sliders-h"></i> Configuración del módulo
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
                <input type="hidden" class="cli-id" id="clientes_id" name="clientes_id" value="0">
                <span class="cli-datos">
                  <span class="cli-nombre-wrap"><i class="fas fa-user"></i><span class="cli-nombre">Consumidor final</span></span>
                  <small class="cli-rtn-wrap is-hidden"><i class="fas fa-id-card"></i><span class="cli-rtn-label">RTN</span><span class="cli-rtn"></span></small>
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
          <i class="fas fa-clipboard-list"></i> <span id="texto-btn-mostrar-comanda">Ver Comanda</span>
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
                <label for="fil-est-cocina" id="label-fil-est-cocina">Cocina</label>
                <input type="radio" name="filEst" id="fil-est-barra" value="barra">
                <label for="fil-est-barra" id="label-fil-est-barra">Barra</label>
              </div>
            </div>

            <div class="categorias-tabs" id="categorias-tabs"></div>
            <div class="productos-grid" id="productos-container"></div>
          </div>

          <!-- Panel de la comanda -->
          <div class="comanda-panel" id="panel-comanda">
            <div class="comanda-header">
              <h3 id="titulo-panel-comanda"><i class="fas fa-clipboard-list"></i> Comanda</h3>
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
          </div>
        </div> <!-- /factura-body -->
      </div> <!-- /main-content -->
    </div> <!-- /restaurante-content -->
  </div> <!-- /restaurante-container -->

  <!-- ============== MODALES ============== -->

  <!-- El cobro utiliza el modal unificado oficial de Facturación. -->


  <!-- MODAL DE PAGO OFICIAL DE FACTURACIÓN -->
<style>
  .payment-required { color:#dc3545; font-weight:700; }
  .payment-help-text { font-size: 12px; color:#6c757d; margin-top:6px; display:block; }
  .payment-help-text i { margin-right:4px; }
</style>

<!-- MODAL PAGOS UNIFICADO -->
<div class="modal fade" id="modal_pagos_unificado" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog payment-modal modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="payment-content modal-content">

      <!-- Header -->
      <div class="payment-header">
        <h4 class="mb-2 d-flex align-items-center">
          <i class="far fa-credit-card mr-2"></i> Método de pago
        </h4>

        <div class="payment-steps" id="paymentSteps" style="--progress-width: 33%;">
          <div class="step active" data-step="1"><div class="step-icon">1</div><div class="step-label">Elegir método</div></div>
          <div class="step" data-step="2"><div class="step-icon">2</div><div class="step-label">Detalles</div></div>
          <div class="step" data-step="3"><div class="step-icon">3</div><div class="step-label">Confirmar</div></div>
        </div>

        <button type="button" class="payment-close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true"><i class="fas fa-times"></i></span>
        </button>
      </div>

      <!-- Body -->
      <div class="payment-body">

        <!-- Pills Cliente / Total -->
        <div class="payment-info-card" id="pills_info">
          <div class="customer-info">
            <i class="far fa-user"></i>
            <span class="label">Cliente: </span>
            <span class="value" id="customer-name-bill">—</span>
          </div>
          <div class="amount-info">
            <i class="far fa-credit-card"></i>
            <span class="label">Pagar: </span>
            <span class="amount" id="bill-pay">L. 0.00</span>
          </div>
          <input type="hidden" name="customer_bill_pay" id="customer_bill_pay">
        </div>

        <!-- Opciones -->
        <div class="payment-options-card" id="global_options_bar">
          <div class="option-item" id="opt_print_wrap">
            <label class="payment-switch mb-0">
              <span class="switch-label">Imprimir comprobante</span>
              <input type="checkbox" id="comprobante_print_switch" value="0">
              <span class="payment-slider round"></span>
            </label>
            <span class="question mb-0 ml-2" id="label_print_comprobant">No</span>
          </div>

          <!-- PAGO MÚLTIPLE: visible en paso 1, oculto en 2–3 (JS) -->
          <div class="option-item" id="opt_multi_wrap">
            <label class="payment-switch mb-0">
              <span class="switch-label">Pagos múltiples</span>
              <input type="checkbox" id="pagos_multiples_switch" value="0">
              <span class="payment-slider round"></span>
            </label>
            <span class="question mb-0 ml-2" id="label_pagos_multiples">Desactivado</span>
          </div>
        </div>

        <!-- PASO 1: métodos (centrados) -->
        <div class="section-methods" id="section_methods">
          <div class="payment-methods-container">
            <div class="payment-methods-grid" id="paymentMethodsGrid">

              <div class="method-card default-focus selected" data-method="cash" tabindex="0" role="button" aria-pressed="true">
                <i class="fas fa-money-bill-wave method-icon" style="color:#26a269;"></i>
                <div class="method-name">Efectivo</div>
                <div class="method-badge">Rápido</div>
                <i class="fas fa-info-circle info-icon" data-toggle="tooltip" title="Pagos en efectivo"></i>
              </div>

              <div class="method-card" data-method="card" tabindex="0" role="button" aria-pressed="false">
                <i class="far fa-credit-card method-icon" style="color:#2d7ef7;"></i>
                <div class="method-name">Tarjeta</div>
                <div class="method-badge">POS</div>
                <i class="fas fa-info-circle info-icon" data-toggle="tooltip" title="Débito/Crédito"></i>
              </div>

              <div class="method-card" data-method="transfer" tabindex="0" role="button" aria-pressed="false">
                <i class="fas fa-exchange-alt method-icon" style="color:#06b6d4;"></i>
                <div class="method-name">Transferencia</div>
                <div class="method-badge">Bancaria</div>
                <i class="fas fa-info-circle info-icon" data-toggle="tooltip" title="Transferencia bancaria"></i>
              </div>

              <div class="method-card" data-method="check" tabindex="0" role="button" aria-pressed="false">
                <i class="fas fa-money-check method-icon" style="color:#8b5cf6;"></i>
                <div class="method-name">Cheque</div>
                <div class="method-badge">Empresarial</div>
                <i class="fas fa-info-circle info-icon" data-toggle="tooltip" title="Pago con cheque"></i>
              </div>

              <?php if ($_SESSION['planes_id_sistema'] == 5 || $_SESSION['planes_id_sistema'] == 7): ?>
                <div class="method-card premium" data-method="points" tabindex="0" role="button" aria-pressed="false">
                    <i class="fas fa-coins method-icon"></i>
                    <div class="method-name">Puntos</div>
                    <div class="method-badge">Loyalty</div>
                    <i class="fas fa-info-circle info-icon" data-toggle="tooltip" title="Redimir puntos (solo con Efectivo)"></i>
                </div>
              <?php endif; ?>

            </div>
          </div>

          <!-- Publicidad -->
          <div class="ad-space mt-3 p-2 text-center">
            <small class="text-muted">
              <i class="fas fa-star text-warning"></i>
              Potenciado por <strong>Su Sistema Premium</strong> · <a href="#" class="ad-link">Conozca más</a>
              <i class="fas fa-star text-warning"></i>
            </small>
          </div>
        </div>

        <!-- PASO 2: Detalles -->
        <div id="section_details" style="display:none;">
          <div class="payment-details-container">

            <!-- EFECTIVO -->
            <section class="payment-details payment-step active" id="payment_cash" data-method="cash">
              <div class="detail-header"><div class="method-display">
                <i class="fas fa-money-bill-wave"></i><span>Efectivo</span></div>
              </div>

              <form class="FormularioAjax" id="formEfectivoBill"
                    action="<?php echo SERVERURL; ?>ajax/addPagoFacturasEfectivoAjax.php"
                    method="POST" data-form="save" autocomplete="off" enctype="multipart/form-data">

                <div class="payment-form-group">
                  <input type="date" name="fecha_efectivo" id="fecha_efectivo" class="payment-form-control"
                         value="<?php echo date('Y-m-d'); ?>" placeholder=" ">
                  <label for="fecha_efectivo">Fecha</label>
                </div>

                <input type="hidden" class="comprobante_print_value" name="comprobante_print" value="0">
                <input type="hidden" class="multiple_pago" name="multiple_pago" value="0">
                <input type="hidden" name="factura_id_efectivo" id="factura_id_efectivo">
                <input type="hidden" name="tipo_factura" id="tipo_factura" value="1">
                <input type="hidden" name="origen_pago" id="origen_pago" value="0">
                <input type="hidden" name="monto_efectivo" id="monto_efectivo" step="0.01" placeholder="0.00">

                <div class="payment-form-group">
                  <input type="text" inputmode="decimal" name="efectivo_bill" id="efectivo_bill"
                         class="payment-form-control" placeholder=" " required>
                  <label for="efectivo_bill">Efectivo <span class="payment-required">*</span></label>
                  <span class="currency-symbol">L.</span>
                </div>

                <div class="payment-form-group" id="grupo_cambio_efectivo">
                  <input type="text" readonly name="cambio_efectivo" id="cambio_efectivo"
                         class="payment-form-control" placeholder=" ">
                  <label for="cambio_efectivo">Cambio</label>
                  <span class="currency-symbol">L.</span>
                </div>

                <div class="payment-form-group">
                  <select id="usuario_efectivo" name="usuario_efectivo" class="selectpicker form-control"
                          data-size="5" data-live-search="true" title="Usuario que Recibe" data-width="100%"></select>
                  <small class="payment-help-text"><i class="fas fa-info-circle"></i>Opcional.</small>
                </div>

                <button type="submit" id="pago_efectivo" class="btn btn-info btn-block mt-2">
                  <i class="fas fa-check mr-1"></i> Efectuar Pago
                </button>
                <div class="RespuestaAjax"></div>
              </form>
            </section>

            <!-- TARJETA -->
            <section class="payment-details payment-step" id="payment_card" data-method="card">
              <div class="detail-header"><div class="method-display">
                <i class="far fa-credit-card"></i><span>Tarjeta</span></div>
              </div>

              <form class="FormularioAjax" id="formTarjetaBill" method="POST" data-form="save"
                    action="<?php echo SERVERURL; ?>ajax/addPagoFacturasTarjetaAjax.php"
                    autocomplete="off" enctype="multipart/form-data">

                <div class="payment-form-group">
                  <input type="date" name="fecha_tarjeta" id="fecha_tarjeta" class="payment-form-control"
                         value="<?php echo date('Y-m-d'); ?>" placeholder=" ">
                  <label for="fecha_tarjeta">Fecha</label>
                </div>

                <input type="hidden" name="factura_id_tarjeta" id="factura_id_tarjeta">
                <input type="hidden" name="origen_pago" id="origen_pago" value="0">
                <input type="hidden" class="comprobante_print_value" name="comprobante_print" value="0">
                <input type="hidden" class="multiple_pago" name="multiple_pago" value="0">
                <input type="number" style="display:none;" name="monto_efectivo" id="monto_efectivo_tarjeta" step="0.01">
                <input type="hidden" name="importe_tarjeta" id="importe_tarjeta" step="0.01">
                <input type="hidden" name="tipo_factura" id="tipo_factura" value="1">

                <div class="payment-form-group">
                  <input type="text" id="cr_bill" name="cr_bill" class="payment-form-control" placeholder=" ">
                  <label for="cr_bill">Número de Tarjeta</label>
                  <small class="payment-help-text"><i class="fas fa-info-circle"></i>Opcional, pero recomendado para control y auditoría.</small>
                </div>

                <div class="form-row">
                  <div class="col-md-6">
                    <div class="payment-form-group">
                      <input type="text" name="exp" id="exp" class="payment-form-control" placeholder=" ">
                      <label for="exp">Expiración (MM/YY)</label>
                      <small class="payment-help-text"><i class="fas fa-info-circle"></i>Opcional.</small>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="payment-form-group">
                      <input type="text" name="cvcpwd" id="cvcpwd" class="payment-form-control" placeholder=" ">
                      <label for="cvcpwd">Número Aprobación</label>
                      <small class="payment-help-text"><i class="fas fa-info-circle"></i>Opcional, pero recomendado para control y auditoría.</small>
                      <small class="form-text text-muted mt-1"><i class="fas fa-info-circle mr-1"></i>Opcional, pero recomendado para control y auditoría.</small>
                    </div>
                  </div>
                </div>

                <div class="payment-form-group">
                  <select id="usuario_tarjeta" name="usuario_tarjeta" class="selectpicker form-control"
                          data-size="5" data-live-search="true" title="Usuario que Recibe" data-width="100%"></select>
                </div>

                <button type="submit" id="pago_tarjeta" class="btn btn-info btn-block mt-2">
                  <i class="fas fa-check mr-1"></i> Efectuar Pago
                </button>
                <div class="RespuestaAjax"></div>
              </form>
            </section>

            <!-- TRANSFERENCIA -->
            <section class="payment-details payment-step" id="payment_transfer" data-method="transfer">
              <div class="detail-header"><div class="method-display">
                <i class="fas fa-exchange-alt"></i><span>Transferencia</span></div>
              </div>

              <form class="FormularioAjax" id="formTransferenciaBill" method="POST" data-form="save"
                    action="<?php echo SERVERURL; ?>ajax/addPagoFacturasTransferenciaAjax.php"
                    autocomplete="off" enctype="multipart/form-data">

                <div class="payment-form-group">
                  <input type="date" name="fecha_transferencia" id="fecha_transferencia" class="payment-form-control"
                         value="<?php echo date('Y-m-d'); ?>" placeholder=" ">
                  <label for="fecha_transferencia">Fecha</label>
                </div>

                <input type="hidden" name="factura_id_transferencia" id="factura_id_transferencia">
                <input type="hidden" name="origen_pago" id="origen_pago" value="0">
                <input type="hidden" class="multiple_pago" name="multiple_pago" value="0">
                <input type="hidden" class="comprobante_print_value" name="comprobante_print" value="0">
                <input type="hidden" name="monto_efectivo" id="monto_efectivo">
                <input type="hidden" name="tipo_factura" id="tipo_factura_transferencia" value="1" step="0.01">

                <div class="payment-form-group">
                  <label class="d-block mb-1">Banco <span class="payment-required">*</span></label>
                  <select id="bk_nm" name="bk_nm" required class="selectpicker form-control"
                          data-size="5" data-live-search="true" title="Banco" data-width="100%"></select>
                </div>

                <div class="payment-form-group">
                  <input type="text" name="importe_transferencia" id="importe_transferencia"
                         class="payment-form-control" placeholder=" " required>
                  <label for="importe_transferencia">Importe <span class="payment-required">*</span></label>
                  <span class="currency-symbol">L.</span>
                </div>

                <div class="payment-form-group">
                  <input type="text" name="ben_nm" id="ben_nm" class="payment-form-control" placeholder=" ">
                  <label for="ben_nm">Número de Autorización</label>
                  <small class="payment-help-text"><i class="fas fa-info-circle"></i>Opcional, pero recomendado para control y auditoría.</small>
                </div>

                <div class="payment-form-group">
                  <select id="usuario_transferencia" name="usuario_transferencia" class="selectpicker form-control"
                          data-size="5" data-live-search="true" title="Usuario que Recibe" data-width="100%"></select>
                  <small class="payment-help-text"><i class="fas fa-info-circle"></i>Opcional.</small>
                </div>

                <button type="submit" id="pago_transferencia" class="btn btn-info btn-block mt-2">
                  <i class="fas fa-check mr-1"></i> Efectuar Pago
                </button>
                <div class="RespuestaAjax"></div>
              </form>
            </section>

            <!-- CHEQUE -->
            <section class="payment-details payment-step" id="payment_check" data-method="check">
              <div class="detail-header"><div class="method-display">
                <i class="fas fa-money-check"></i><span>Cheque</span></div>
              </div>

              <form class="FormularioAjax" id="formChequeBill" method="POST" data-form="save"
                    action="<?php echo SERVERURL; ?>ajax/addPagoFacturasChequeAjax.php"
                    autocomplete="off" enctype="multipart/form-data">

                <div class="payment-form-group">
                  <input type="date" name="fecha_cheque" id="fecha_cheque" class="payment-form-control"
                         value="<?php echo date('Y-m-d'); ?>" placeholder=" ">
                  <label for="fecha_cheque">Fecha</label>
                </div>

                <input type="hidden" class="multiple_pago" name="multiple_pago" value="0">
                <input type="hidden" class="comprobante_print_value" name="comprobante_print" value="0">
                <input type="hidden" name="origen_pago" id="origen_pago" value="0">
                <input type="hidden" name="factura_id_cheque" id="factura_id_cheque">
                <input type="hidden" name="monto_efectivo" id="monto_efectivo">
                <input type="hidden" name="tipo_factura" id="tipo_factura_cheque" value="1" step="0.01">

                <div class="payment-form-group">
                  <label class="d-block mb-1">Banco <span class="payment-required">*</span></label>
                  <select id="bk_nm_chk" name="bk_nm_chk" required class="selectpicker form-control"
                          data-size="5" data-live-search="true" title="Banco" data-width="100%"></select>
                </div>

                <div class="payment-form-group">
                  <input type="text" name="importe_cheque" id="importe_cheque" class="payment-form-control" placeholder=" " required>
                  <label for="importe_cheque">Importe <span class="payment-required">*</span></label>
                  <span class="currency-symbol">L.</span>
                </div>

                <div class="payment-form-group">
                  <input type="text" name="check_num" id="check_num" class="payment-form-control" placeholder=" ">
                  <label for="check_num">Número de Cheque</label>
                  <small class="payment-help-text"><i class="fas fa-info-circle"></i>Opcional, pero recomendado para control y auditoría.</small>
                </div>

                <div class="payment-form-group">
                  <select id="usuario_cheque" name="usuario_cheque" class="selectpicker form-control"
                          data-size="5" data-live-search="true" title="Usuario que Recibe" data-width="100%"></select>
                  <small class="payment-help-text"><i class="fas fa-info-circle"></i>Opcional.</small>
                </div>

                <button type="submit" id="pago_cheque" class="btn btn-info btn-block mt-2">
                  <i class="fas fa-check mr-1"></i> Efectuar Pago
                </button>
                <div class="RespuestaAjax"></div>
              </form>
            </section>

            <!-- PUNTOS -->
            <section class="payment-details payment-step" id="payment_points" data-method="points">
              <div class="detail-header"><div class="method-display">
                <i class="fas fa-coins"></i><span>Puntos</span></div>
              </div>

              <form class="FormularioAjax" id="formPuntosBill" method="POST" data-form="save"
                    action="<?php echo SERVERURL; ?>ajax/addPagoFacturasPuntosAjax.php"
                    autocomplete="off" enctype="multipart/form-data">

                <div class="payment-form-group">
                  <input type="date" name="fecha_puntos" id="fecha_puntos" class="payment-form-control"
                         value="<?php echo date('Y-m-d'); ?>" placeholder=" ">
                  <label for="fecha_puntos">Fecha</label>
                </div>

                <input type="hidden" class="comprobante_print_value" name="comprobante_print" value="0">
                <input type="hidden" class="multiple_pago" name="multiple_pago" value="0">
                <input type="hidden" name="factura_id_puntos" id="factura_id_puntos">
                <input type="hidden" name="tipo_factura" id="tipo_factura_puntos" value="1">
                <input type="hidden" name="origen_pago" id="origen_pago" value="0">

                <div class="payment-form-group">
                  <input type="text" name="puntos_disponibles" id="puntos_disponibles"
                         class="payment-form-control" placeholder=" " readonly required data-min-points="1" data-min-points="1">
                  <label for="puntos_disponibles">Puntos disponibles <span class="payment-required">*</span></label>
                  <small class="payment-help-text"><i class="fas fa-info-circle"></i>Debe ser mayor a cero para poder pagar con puntos.</small>
                  <small class="form-text text-muted mt-1"><i class="fas fa-info-circle mr-1"></i>Debe ser mayor a cero para poder pagar con puntos.</small>
                </div>

                <div class="payment-form-group">
                  <input type="text" inputmode="decimal" name="puntos_usar" id="puntos_uso"
                         class="payment-form-control" placeholder=" " required required>
                  <label for="puntos_uso">Puntos a usar <span class="payment-required">*</span></label>
                  <small class="payment-help-text"><i class="fas fa-info-circle"></i>Obligatorio y mayor a cero cuando seleccione pago con puntos.</small>
                  <small class="form-text text-muted mt-1"><i class="fas fa-info-circle mr-1"></i>Obligatorio cuando seleccione pago con puntos.</small>
                </div>

                <div class="payment-form-group">
                  <input type="text" name="equivalente_lempiras" id="equivalente_puntos"
                         class="payment-form-control" placeholder=" " readonly>
                  <label for="equivalente_puntos">Equivalente en Lempiras</label>
                  <span class="currency-symbol">L.</span>
                </div>

                <input type="hidden" name="importe_puntos" id="importe_puntos" value="0">

                <div class="payment-form-group">
                  <select id="usuario_puntos" name="usuario_puntos" class="selectpicker form-control"
                          data-size="5" data-live-search="true" title="Usuario que Recibe" data-width="100%"></select>
                  <small class="payment-help-text"><i class="fas fa-info-circle"></i>Opcional.</small>
                </div>

                <button type="submit" id="pago_puntos" class="btn btn-info btn-block mt-2" disabled>
                  <i class="fas fa-check mr-1"></i> Efectuar Pago
                </button>
                <div class="RespuestaAjax"></div>
              </form>
            </section>

          </div>
        </div>

        <!-- PASO 3: Confirmar (premium) -->
        <div id="section_confirm" style="display:none;">
          <div class="confirm-card premium">
            <div class="confirm-header">
              <i class="fas fa-check-circle"></i>
              <span>Confirmar pago</span>
            </div>

            <div class="confirm-info-grid">
              <div class="confirm-info-pill">
                <i class="far fa-user"></i>
                <span class="label">Cliente: </span>
                <span class="value" id="confirm-customer-name">—</span>
              </div>
              <div class="confirm-info-pill amount">
                <i class="far fa-credit-card"></i>
                <span class="label">Total factura: </span>
                <span class="value" id="confirm-total-amount">L. 0.00</span>
              </div>
            </div>

            <div class="confirm-options-grid">
              <div class="confirm-option">
                <span class="option-label">Imprimir comprobante</span>
                <span class="option-value pill" id="confirm-print-option">No</span>
              </div>
              <div class="confirm-option">
                <span class="option-label">Pagos múltiples</span>
                <span class="option-value pill" id="confirm-multi-option">Desactivado</span>
              </div>
            </div>

            <div class="confirm-separator"></div>

            <div class="payment-methods-summary">
              <h6>Métodos de pago aplicados:</h6>
              <div id="confirm-methods-list"></div>
            </div>

            <div class="confirm-separator"></div>

            <div class="confirm-totals-grid">
              <div class="total-line">
                <span>Total a aplicar</span>
                <span class="total-amount" id="confirm-total-apply">L. 0.00</span>
              </div>
              <div class="total-line difference" id="difference-line">
                <span>Diferencia</span>
                <span class="total-amount" id="confirm-difference">L. 0.00</span>
              </div>
            </div>

            <button type="button" class="btn btn-success btn-block confirm-submit-btn" id="btnConfirmPay">
              <i class="fas fa-check-circle mr-2"></i> Registrar pago
            </button>
          </div>
        </div>

      </div>

      <!-- Footer con botones mejorados -->
      <div class="payment-actions">
        <button type="button" class="payment-btn payment-btn-prev btn-warning" id="btnPrev">
          <i class="fas fa-arrow-left"></i> Atrás
        </button>
        <button type="button" class="payment-btn payment-btn-next" id="btnNext">
          <i class="fas fa-arrow-right"></i> Continuar
        </button>
        <button type="button" class="payment-btn payment-btn-close btn-secondary" data-dismiss="modal">
          <i class="fas fa-times"></i> Cerrar
        </button>
      </div>

    </div>
  </div>
</div>
<!-- /MODAL PAGOS UNIFICADO -->
  <!-- /MODAL DE PAGO OFICIAL DE FACTURACIÓN -->

  <!-- Modal reserva de mesa -->
  <div id="modal-reserva-mesa" class="modal rs-modal" role="dialog" aria-modal="true" style="display:none;">
    <div class="modal-content modal-content--reserva">
      <div class="modal-header">
        <h3><i class="fas fa-calendar-check"></i> Reservar mesa</h3>
        <span class="close" data-close="#modal-reserva-mesa" title="Cerrar">&times;</span>
      </div>
      <div class="modal-body">
        <form id="form-reserva-mesa" novalidate onsubmit="return false;">
          <input type="hidden" id="reserva-mesa-id">
          <div class="reserva-grid">
            <div class="form-group reserva-cliente-field">
              <label for="reserva-cliente"><i class="fas fa-user"></i> Cliente</label>
              <select id="reserva-cliente" class="form-control select2" required><option value="">Seleccione un cliente…</option></select>
            </div>
            <div class="form-group">
              <label for="reserva-personas"><i class="fas fa-users"></i> Personas</label>
              <input type="number" id="reserva-personas" class="form-control" min="1" value="2" required>
            </div>
            <div class="form-group">
              <label for="reserva-fecha"><i class="fas fa-calendar"></i> Fecha</label>
              <input type="date" id="reserva-fecha" class="form-control" required>
            </div>
            <div class="form-group">
              <label for="reserva-hora"><i class="fas fa-clock"></i> Hora</label>
              <input type="time" id="reserva-hora" class="form-control" required>
            </div>
            <div class="form-group reserva-notas-field">
              <label for="reserva-notas"><i class="fas fa-sticky-note"></i> Nota</label>
              <textarea id="reserva-notas" class="form-control" maxlength="250" rows="3" placeholder="Ej. cumpleaños, silla para bebé, ubicación preferida…"></textarea>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-close="#modal-reserva-mesa"><i class="fas fa-times"></i> Cancelar</button>
        <button type="button" class="btn btn-success" id="btn-guardar-reserva-mesa"><i class="fas fa-calendar-check"></i> Guardar reserva</button>
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
        <form id="form-mesa" novalidate onsubmit="return false;">
          <input type="hidden" class="form-control" id="mesa-id" value="">
          <div class="form-group">
            <label for="numero-mesa"><i class="fas fa-hashtag"></i> Número de Mesa</label>
            <input type="text" class="form-control" id="numero-mesa" required>
          </div>
          <div class="form-group">
            <label for="capacidad-mesa"><i class="fas fa-users"></i> Capacidad</label>
            <input type="number" class="form-control" id="capacidad-mesa" min="1" value="4" required>
          </div>
          <div class="form-group">
            <label for="ubicacion-mesa"><i class="fas fa-map-marker-alt"></i> Ubicación</label>
            <select id="ubicacion-mesa" class="form-control select2" required>
              <option value="">Seleccione…</option>
              <option value="Interior">Interior</option>
              <option value="Terraza">Terraza</option>
              <option value="Barra">Barra</option>
            </select>
          </div>
          <div class="form-group">
            <label for="estado-mesa"><i class="fas fa-traffic-light"></i> Estado</label>
            <select id="estado-mesa" class="form-control select2">
              <option value="">(auto)</option>
              <option value="disponible">Disponible</option>
              <option value="mantenimiento">Mantenimiento</option>
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
        <form id="form-nuevo-cliente" autocomplete="off" novalidate onsubmit="return false;">
          <input type="hidden" id="cli-id">
          <div class="form-group">
            <label for="cli-nombre"><i class="fas fa-quote-left"></i> Nombre / Razón social</label>
            <input class="form-control" type="text" id="cli-nombre" required>
          </div>
          <div class="form-group">
            <label for="cli-rtn"><i class="fas fa-id-card"></i> Identificación / RTN</label>
            <input type="text" class="form-control" id="cli-rtn" placeholder="Opcional">
          </div>
          <div class="form-group">
            <label for="cli-localidad"><i class="fas fa-map-marker-alt"></i> Localidad</label>
            <input type="text" class="form-control" id="cli-localidad" placeholder="Barrio/Colonia">
          </div>
          <div class="form-group">
            <label for="cli-telefono"><i class="fas fa-phone"></i> Teléfono</label>
            <input type="text" class="form-control" id="cli-telefono" placeholder="+504 ...">
          </div>
          <div class="form-group">
            <label for="cli-correo"><i class="fas fa-envelope"></i> Correo</label>
            <input type="email" class="form-control" id="cli-correo" placeholder="cliente@correo.com">
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
        <!-- FORM CATEGORÍA -->
        <form id="form-categoria" novalidate onsubmit="return false;">
          <input type="hidden" id="cat-id" value="">
          <div class="form-group">
            <label for="cat-nombre"><i class="fas fa-tag"></i> Nombre de la categoría</label>
            <input type="text" class="form-control" id="cat-nombre" placeholder="Ej. Bebidas" required />
          </div>
          <div class="form-group" id="cat-estacion-wrap">
            <label class="label-strong" for="cat-estacion">Grupo / estación</label>
            <div class="segmented-control" id="cat-estacion">
              <input type="radio" name="catEstacion" id="cat-est-cocina" value="cocina" checked>
              <label for="cat-est-cocina" id="label-cat-est-cocina">Cocina</label>
              <input type="radio" name="catEstacion" id="cat-est-barra" value="barra">
              <label for="cat-est-barra" id="label-cat-est-barra">Barra</label>
            </div>
            <small class="hint">
              Este grupo organiza los productos. Cuando las comandas están activas, también define la ruta de preparación sugerida.
            </small>
          </div>
        </form>
        <!-- /FORM CATEGORÍA -->
      </div>
      <div class="modal-footer">
        <button class="btn btn-danger" data-close="#modal-categoria" type="button">
          <i class="fas fa-times"></i> Cerrar
        </button>
        <!-- Sigue como button; valida desde JS con validateForm('form-categoria') -->
        <button id="btn-guardar-categoria" class="btn btn-success" type="button">
          <i class="fas fa-save"></i> Guardar
        </button>
      </div>
    </div>
  </div>

<!-- Modal Nuevo/Editar Producto -->
<div id="modal-producto" class="modal rs-modal modal--xl">
  <div class="modal-content">
    <div class="modal-header">
      <h3><i class="fas fa-plus-circle"></i> <span id="titulo-modal-producto">Nuevo Producto</span></h3>
      <span class="close" data-close="#modal-producto">&times;</span>
    </div>
    <div class="modal-body">
      <!-- FORM PRODUCTO -->
      <form id="form-producto" novalidate onsubmit="return false;">
  <input type="hidden" id="prod-id" value="">

  <div class="container-fluid px-0">
    <!-- fila 1: Estación (izq) + Hint + Chip (der) -->
<div class="form-row" id="prod-estacion-wrap">
  <!-- Izquierda: radios -->
  <div class="form-group col-lg-4 col-md-6">
    <label class="label-strong d-block">¿A qué estación pertenece este producto?</label>
    <div class="segmented-control" id="prod-estacion">
      <input type="radio" name="prodEstacion" id="prod-est-cocina" value="cocina" checked>
      <label for="prod-est-cocina" class="mr-2" id="label-prod-est-cocina">Cocina</label>
      <input type="radio" name="prodEstacion" id="prod-est-barra" value="barra">
      <label for="prod-est-barra" id="label-prod-est-barra">Barra</label>
    </div>
  </div>

  <!-- Derecha: hint en una sola línea + chip (opcional) -->
  <div class="form-group col-lg-8 col-md-6">
    <small class="hint d-block text-muted text-nowrap mb-2">
      El <b>grupo del producto</b> organiza el catálogo. Cuando las comandas están activas, también define su ruta de preparación.
    </small>

    <div id="prod-estacion-info" style="display:none;">
      <div class="info-chip">
        Estación sugerida por la categoría: <b id="prod-estacion-info-val">—</b>
      </div>
    </div>
  </div>
</div>


    <!-- fila 2 -->
    <div class="form-row">
      <div class="form-group col-md-6">
        <label for="prod-categoria"><i class="fas fa-sitemap"></i> Categoría</label>
        <select id="prod-categoria" class="form-control w-100 select2"
                data-placeholder="Selecciona una categoría"></select>
      </div>

      <div class="form-group col-md-6">
        <label for="prod-nombre"><i class="fas fa-quote-left"></i> Nombre</label>
        <input type="text" class="form-control" id="prod-nombre" placeholder="Ej. Refresco Pepsi">
      </div>
    </div>

    <!-- fila 3 -->
    <div class="form-row">
      <div class="form-group col-12">
        <label for="prod-descripcion"><i class="fas fa-align-left"></i> Descripción (opcional)</label>
        <input type="text" class="form-control" id="prod-descripcion" placeholder="Descripción corta">
      </div>
    </div>

    <!-- fila 4 -->
    <div class="form-row">
      <div class="form-group col-lg-4 col-md-6">
        <label for="prod-precio"><i class="fas fa-dollar-sign"></i> Precio de venta</label>
        <input type="number" class="form-control" id="prod-precio" step="0.01" min="0" placeholder="0.00">
      </div>

      <div class="form-group col-lg-4 col-md-6">
        <label class="label-strong d-block"><i class="fas fa-receipt"></i> Impuestos</label>
        <div class="d-flex align-items-center flex-wrap">
          <label class="radio-container mr-3 mb-0">
            <input type="checkbox" id="prod-isv1"> ISV 15%
          </label>
          <label class="radio-container mb-0">
            <input type="checkbox" id="prod-isv2"> ISV 15%
          </label>
        </div>
      </div>
    </div>

    <!-- fila 5 -->
    <div class="form-row">
      <div class="form-group col-12">
        <label><i class="fas fa-image mr-1"></i> Imagen del Producto</label>
        <div class="file-upload-area image-upload-area" id="productoDropArea" tabindex="0"
             aria-label="Zona para arrastrar y soltar imagen">
          <i class="fas fa-image fa-3x mb-2"></i>
          <p class="file-upload-instructions mb-2">
            <span class="drag-text mr-2">Arrastra la imagen aquí</span>
            <button class="btn btn-sm btn-secondary" id="btnSeleccionarImagen" type="button">
              <i class="fas fa-image"></i> Seleccionar imagen
            </button>
            <input type="file" id="imagen_producto" name="imagen_producto" accept="image/*"
                   class="file-upload-input d-none">
            <span class="paste-text ml-2">o pega (Ctrl+V)</span>
          </p>
          <div class="file-preview" id="productoPreview"></div>
        </div>
        <div class="file-info" id="productoInfo">Ningún archivo seleccionado</div>
      </div>
    </div>
  </div>
</form>

      <!-- /FORM PRODUCTO -->
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
    <div class="modal-content modal-content--combo-editor">
      <div class="modal-header">
        <h3 id="titulo-modal-combo"><i class="fas fa-layer-group"></i> Nuevo combo</h3>
        <span class="close" data-close="#modal-combo-editor">&times;</span>
      </div>

      <div class="modal-body">
        <!-- FORM COMBO (para validar si luego lo necesitas) -->
        <form id="form-combo-editor" novalidate onsubmit="return false;">
          <input type="hidden" id="combo-id" value="">
          <input type="hidden" id="combo-producto-hidden" value="">

          <div id="combo-help-message" class="mb-2"></div>
          <div id="combo-producto-display" style="display:none"></div>

          <div id="combo-producto-container" class="combo-master-card">
            <label class="label-strong">Producto que representa el combo</label>
            <div class="combo-master-row">
              <select id="combo-producto" class="form-control select2"
                      data-placeholder="Selecciona el producto combo" style="width:100%;" required>
                <option value=""></option>
              </select>
              <button type="button" class="btn btn-info"
                      onclick="calcularDisponibilidadComboUI(document.getElementById('combo-id').value, 1)">
                <i class="fas fa-boxes"></i> Disponibilidad
              </button>
            </div>
            <p id="combo-producto-help" class="help-text"></p>
          </div>

          <div id="combo-precio-wrap" class="combo-price-card"></div>

          <div class="form-group">
            <div id="combo-activo-container"></div>
          </div>

          <hr>

          <h4>Componentes del combo</h4>
          <p class="help-text">Agrega los productos o insumos que forman el combo. Define cuánto consume cada uno y si es obligatorio u opcional.</p>
          <div id="combo-items-container"></div>
          <div class="mt-2">
            <button type="button" id="btn-add-combo-item" class="btn btn-secondary">
              <i class="fas fa-plus"></i> Agregar componente
            </button>
          </div>

          <hr>

          <h4>Opciones de elección (opcional)</h4>
          <p class="help-text">Si el cliente puede elegir, limita cuántos productos puede seleccionar de cada categoría.</p>

          <div id="combo-reglas-container" class="combo-rules-panel">
            <div id="combo-reglas-rows" class="combo-rules-list"></div>
            <button type="button" class="btn btn-secondary" id="btn-add-regla">
              <i class="fas fa-plus"></i> Agregar regla
            </button>
          </div>
        </form>
        <!-- /FORM COMBO -->
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


  <!-- =================== MODAL: CUENTAS ABIERTAS =================== -->
  <div id="modal-cuentas-abiertas" class="modal rs-modal modal--xl" role="dialog" aria-modal="true" style="display:none;">
    <div class="modal-content rs-open-accounts-modal">
      <div class="modal-header">
        <h3><i class="fas fa-folder-open"></i> Cuentas abiertas</h3>
        <span class="close" data-close="#modal-cuentas-abiertas" title="Cerrar">&times;</span>
      </div>
      <div class="modal-body">
        <div class="rs-open-accounts-toolbar">
          <div>
            <strong>Continúa una cuenta sin duplicar la factura</strong>
            <small>Mesas y pedidos para llevar guardados como borrador.</small>
          </div>
          <div class="rs-open-accounts-search">
            <i class="fas fa-search"></i>
            <input type="text" id="buscar-cuenta-abierta" placeholder="Buscar cliente, mesa o cuenta…">
          </div>
        </div>
        <div id="cuentas-abiertas-listado" class="rs-open-accounts-grid">
          <div class="rs-empty-state"><i class="fas fa-spinner fa-spin"></i><span>Cargando cuentas…</span></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-close="#modal-cuentas-abiertas"><i class="fas fa-times"></i> Cerrar</button>
      </div>
    </div>
  </div>

  <!-- =================== MODAL: TICKET DE COMANDA =================== -->
  <div id="modal-ticket-comanda" class="modal rs-modal" role="dialog" aria-modal="true" style="display:none;">
    <div class="modal-content rs-ticket-modal">
      <div class="modal-header">
        <h3><i class="fas fa-receipt"></i> <span id="titulo-ticket-operacion">Ticket de comanda</span></h3>
        <span class="close" data-close="#modal-ticket-comanda" title="Cerrar">&times;</span>
      </div>
      <div class="modal-body">
        <div id="ticket-comanda-preview" class="rs-ticket-paper"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-close="#modal-ticket-comanda"><i class="fas fa-times"></i> Cerrar</button>
        <button type="button" id="btn-imprimir-ticket-comanda" class="btn btn-info"><i class="fas fa-print"></i> Imprimir ticket</button>
      </div>
    </div>
  </div>

  <!-- =================== MODAL: FOTO DE PRODUCTO =================== -->
  <div id="modal-foto-producto" class="modal rs-modal" role="dialog" aria-modal="true" style="display:none;">
    <div class="modal-content rs-photo-modal">
      <div class="modal-header">
        <h3><i class="fas fa-image"></i> <span id="foto-producto-titulo">Producto</span></h3>
        <span class="close" data-close="#modal-foto-producto" title="Cerrar">&times;</span>
      </div>
      <div class="modal-body rs-photo-body">
        <img id="foto-producto-grande" src="" alt="Vista ampliada del producto">
      </div>
    </div>
  </div>

  <!-- =================== MODAL: CONFIGURACIÓN DEL MÓDULO =================== -->
  <div id="modal-configuracion-restaurante" class="modal rs-modal" role="dialog" aria-modal="true" style="display:none;">
    <div class="modal-content rs-config-modal">
      <div class="modal-header">
        <h3><i class="fas fa-sliders-h"></i> Configuración del módulo</h3>
        <span class="close" data-close="#modal-configuracion-restaurante" title="Cerrar">&times;</span>
      </div>
      <div class="modal-body">
        <div class="rs-config-intro">
          <i class="fas fa-store"></i>
          <div><strong>Modo de operación</strong><small>Define si este punto de venta utiliza mesas o trabaja como venta directa.</small></div>
        </div>
        <div class="rs-config-options">
          <label class="rs-config-card" for="config-usar-mesas">
            <input type="checkbox" id="config-usar-mesas">
            <span class="rs-config-card-icon"><i class="fas fa-chair"></i></span>
            <span><strong>Usar mesas</strong><small>Muestra mesas y selector Para llevar / En mesa.</small></span>
          </label>
          <label class="rs-config-card" for="config-usar-comandas">
            <input type="checkbox" id="config-usar-comandas" checked>
            <span class="rs-config-card-icon"><i class="fas fa-fire"></i></span>
            <span><strong>Usar comandas</strong><small>Envía productos de Cocina/Barra a sus pantallas de preparación.</small></span>
          </label>
        </div>

        <div class="rs-config-stations" id="config-grupos-operacion">
          <div class="rs-config-section-title">
            <i class="fas fa-tags"></i>
            <div><strong>Nombres de agrupación</strong><small>Personaliza los dos filtros de productos sin cambiar la lógica interna del sistema.</small></div>
          </div>
          <div class="rs-config-station-grid">
            <div class="form-group">
              <label for="config-etiqueta-cocina">Nombre del grupo 1</label>
              <input type="text" id="config-etiqueta-cocina" class="form-control" maxlength="30" placeholder="Ej. Cocina, Productos, Bodega">
            </div>
            <div class="form-group">
              <label for="config-etiqueta-barra">Nombre del grupo 2</label>
              <input type="text" id="config-etiqueta-barra" class="form-control" maxlength="30" placeholder="Ej. Barra, Servicios, Mostrador">
            </div>
          </div>
          <small class="rs-config-note"><i class="fas fa-info-circle"></i> Internamente se conservan los valores actuales para no romper productos, filtros ni comandas existentes.</small>
        </div>

        <div class="rs-config-stations" id="config-salida-comanda">
          <div class="rs-config-section-title">
            <i class="fas fa-print"></i>
            <div>
              <strong>Salida de la comanda</strong>
              <small>Define qué ocurre con la orden de preparación cuando el negocio utiliza comandas.</small>
            </div>
          </div>

          <div class="rs-config-flow-grid" aria-label="Flujo de salida de la comanda">
            <section class="rs-config-flow-stage" data-config-stage="destino">
              <div class="rs-config-flow-heading">
                <span class="rs-config-flow-number">1</span>
                <div>
                  <strong>Destino de la orden</strong>
                  <small>¿Dónde debe llegar la comanda?</small>
                </div>
              </div>
              <input type="hidden" id="config-destino-comanda" value="pantalla">
              <div class="rs-config-choice-list" data-choice-target="config-destino-comanda">
                <button type="button" class="rs-config-choice" data-value="pantalla">
                  <span class="rs-config-choice-icon"><i class="fas fa-display"></i></span>
                  <span class="rs-config-choice-copy"><b>Solo pantalla</b><small>Cocina/Barra recibe la orden en pantalla.</small></span>
                  <span class="rs-config-choice-check"><i class="fas fa-check"></i></span>
                </button>
                <button type="button" class="rs-config-choice" data-value="ticket">
                  <span class="rs-config-choice-icon"><i class="fas fa-print"></i></span>
                  <span class="rs-config-choice-copy"><b>Solo ticket</b><small>Imprime la orden en el equipo de caja.</small></span>
                  <span class="rs-config-choice-check"><i class="fas fa-check"></i></span>
                </button>
                <button type="button" class="rs-config-choice" data-value="ambos">
                  <span class="rs-config-choice-icon"><i class="fas fa-layer-group"></i></span>
                  <span class="rs-config-choice-copy"><b>Pantalla + ticket</b><small>Usa ambas salidas al mismo tiempo.</small></span>
                  <span class="rs-config-choice-check"><i class="fas fa-check"></i></span>
                </button>
              </div>
            </section>

            <section class="rs-config-flow-stage" data-config-stage="momento">
              <div class="rs-config-flow-heading">
                <span class="rs-config-flow-number">2</span>
                <div>
                  <strong>Momento del ticket</strong>
                  <small>¿Cuándo debe generarse?</small>
                </div>
              </div>
              <input type="hidden" id="config-momento-ticket" value="enviar">
              <div class="rs-config-choice-list" data-choice-target="config-momento-ticket">
                <button type="button" class="rs-config-choice" data-value="enviar">
                  <span class="rs-config-choice-icon"><i class="fas fa-paper-plane"></i></span>
                  <span class="rs-config-choice-copy"><b>Al enviar</b><small>Ideal para mesas y preparación inmediata.</small></span>
                  <span class="rs-config-choice-check"><i class="fas fa-check"></i></span>
                </button>
                <button type="button" class="rs-config-choice" data-value="cobrar">
                  <span class="rs-config-choice-icon"><i class="fas fa-cash-register"></i></span>
                  <span class="rs-config-choice-copy"><b>Al cobrar</b><small>Útil para venta rápida o para llevar.</small></span>
                  <span class="rs-config-choice-check"><i class="fas fa-check"></i></span>
                </button>
              </div>
            </section>

            <section class="rs-config-flow-stage" data-config-stage="flujo">
              <div class="rs-config-flow-heading">
                <span class="rs-config-flow-number">3</span>
                <div>
                  <strong>Flujo de preparación</strong>
                  <small>¿Cómo trabaja Cocina?</small>
                </div>
              </div>
              <input type="hidden" id="config-flujo-cocina" value="pasos">
              <div class="rs-config-choice-list" data-choice-target="config-flujo-cocina">
                <button type="button" class="rs-config-choice" data-value="pasos">
                  <span class="rs-config-choice-icon"><i class="fas fa-list-check"></i></span>
                  <span class="rs-config-choice-copy"><b>Paso a paso</b><small>Pendiente → En preparación → Finalizar.</small></span>
                  <span class="rs-config-choice-check"><i class="fas fa-check"></i></span>
                </button>
                <button type="button" class="rs-config-choice" data-value="directo">
                  <span class="rs-config-choice-icon"><i class="fas fa-circle-check"></i></span>
                  <span class="rs-config-choice-copy"><b>Finalizar directo</b><small>Permite cerrar la orden sin paso intermedio.</small></span>
                  <span class="rs-config-choice-check"><i class="fas fa-check"></i></span>
                </button>
              </div>
            </section>
          </div>

          <div class="rs-config-print-help">
            <i class="fas fa-info-circle"></i>
            <span>La factura fiscal continúa usando la impresión normal de Facturas. Esta opción controla únicamente el ticket interno de la orden.</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-close="#modal-configuracion-restaurante"><i class="fas fa-times"></i> Cerrar</button>
        <button type="button" class="btn btn-success" id="btn-guardar-configuracion-restaurante"><i class="fas fa-save"></i> Guardar configuración</button>
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
            <div class="help-card-title" id="help-titulo-operacion"><i class="fas fa-receipt"></i> Comanda</div>
            <ul class="help-keys">
              <li>
                <div id="help-accion-principal">Cobrar / enviar a cocina</div>
                <div class="keys"><span class="kbd">Ctrl/Cmd</span><span class="kbd">G</span></div>
              </li>
              <li>
                <div id="help-ticket-label">Ticket de orden</div>
                <div class="keys"><span class="kbd">Ctrl/Cmd</span><span class="kbd">I</span></div>
              </li>
              <li>
                <div id="help-limpiar-label">Limpiar comanda</div>
                <div class="keys"><span class="kbd">Ctrl/Cmd</span><span class="kbd">Alt</span><span class="kbd">L</span></div>
              </li>
              <li>
                <div>Cancelar cuenta abierta</div>
                <div class="keys"><span class="kbd">Ctrl/Cmd</span><span class="kbd">Alt</span><span class="kbd">X</span></div>
              </li>
              <li>
                <div>Guardar cuenta abierta</div>
                <div class="keys"><span class="kbd">Ctrl/Cmd</span><span class="kbd">Alt</span><span class="kbd">S</span></div>
              </li>
              <li>
                <div>Abrir cuentas guardadas</div>
                <div class="keys"><span class="kbd">Ctrl/Cmd</span><span class="kbd">Alt</span><span class="kbd">A</span></div>
              </li>
              <li>
                <div id="help-ver-panel-label">Ver Productos/Comanda</div>
                <div class="keys"><span class="kbd">Ctrl/Cmd</span><span class="kbd">Alt</span><span class="kbd">V</span></div>
              </li>
              <li>
                <div>Buscar producto</div>
                <div class="keys"><span class="kbd">Ctrl/Cmd</span><span class="kbd">Alt</span><span class="kbd">F</span></div>
              </li>
            </ul>
          </div>

          <div class="help-card" id="help-flujo-operacion">
            <div class="help-card-title"><i class="fas fa-route"></i> Flujo recomendado</div>
            <ul class="help-bullets">
              <li><b>Para llevar:</b> puede cobrar de inmediato o usar <b>Guardar cuenta</b> para continuarla más tarde.</li>
              <li><b>En mesa:</b> seleccione la mesa, envíe a preparación y vuelva a abrirla desde la tarjeta de mesa o desde <b>Cuentas abiertas</b>.</li>
              <li>Los productos de <b>Cocina</b> y <b>Barra</b> se separan por su estación; una bebida de Barra no aparece en Cocina.</li>
              <li>La factura fiscal se muestra después de completar el pago. <b>Ticket de orden</b> es un comprobante interno para cocina/cliente.</li>
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
        <div id="promos-rows" class="responsive-record-list promos-list" aria-live="polite">
          <div class="rs-list-state"><i class="fas fa-tags"></i><span>Cargando promociones…</span></div>
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
  <div id="modal-promocion" class="modal rs-modal">
    <div class="modal-content modal-content--promo">
      <div class="modal-header">
        <h3><i class="fas fa-tag"></i> <span id="titulo-modal-promocion">Nueva promoción</span></h3>
        <span class="close" data-close="#modal-promocion" title="Cerrar">&times;</span>
      </div>
      <div class="modal-body">
        <form id="form-promocion" class="promo-form" autocomplete="off" novalidate onsubmit="return false;">
          <input type="hidden" id="promo-id">
          <input type="hidden" id="promo-empresa-id" value="<?php echo $_SESSION['empresa_id'] ?? 1; ?>">

          <div class="promo-section promo-section--identity">
            <div class="promo-section-title"><i class="fas fa-pen"></i><span>Información de la promoción</span></div>
            <div class="promo-grid promo-grid--2">
              <div class="form-group">
                <label for="promo-nombre"><i class="fas fa-heading"></i> Nombre</label>
                <input type="text" class="form-control" id="promo-nombre" placeholder="Ej. Happy Hour" required>
              </div>
              <div class="form-group">
                <label for="promo-descripcion"><i class="fas fa-align-left"></i> Descripción</label>
                <input type="text" class="form-control" id="promo-descripcion" placeholder="Breve descripción (opcional)">
              </div>
            </div>
          </div>

          <div class="promo-section">
            <div class="promo-section-title"><i class="fas fa-percent"></i><span>Descuento y aplicación</span></div>
            <div class="promo-grid promo-grid--4">
              <div class="form-group">
                <label for="promo-tipo">Tipo</label>
                <select id="promo-tipo" class="form-control select2" required>
                  <option value="PORC">Porcentaje (%)</option>
                  <option value="MONTO">Monto fijo (L)</option>
                </select>
              </div>
              <div class="form-group">
                <label for="promo-valor">Valor</label>
                <input type="number" class="form-control" id="promo-valor" step="0.01" min="0" value="0.00" required>
              </div>
              <div class="form-group">
                <label for="promo-aplica-a">Aplica a</label>
                <select id="promo-aplica-a" class="form-control select2" required>
                  <option value="PRODUCTO">Productos</option>
                  <option value="CATEGORIA">Categorías</option>
                  <option value="TODOS">Todos</option>
                </select>
              </div>
              <div class="form-group">
                <label for="promo-prioridad">Prioridad</label>
                <input type="number" class="form-control" id="promo-prioridad" value="0" step="1">
              </div>
            </div>
          </div>

          <div class="promo-section">
            <div class="promo-section-title"><i class="fas fa-calendar-alt"></i><span>Vigencia</span></div>
            <div class="promo-grid promo-grid--2">
              <div class="form-group">
                <label for="promo-fecha-inicio">Fecha inicio</label>
                <input type="date" class="form-control" id="promo-fecha-inicio" required>
              </div>
              <div class="form-group">
                <label for="promo-fecha-fin">Fecha fin</label>
                <input type="date" class="form-control" id="promo-fecha-fin" required>
              </div>
            </div>
            <div class="promo-schedule-row">
              <label class="promo-switch-card" for="promo-usa-horario">
                <input type="checkbox" id="promo-usa-horario">
                <span class="promo-switch-icon"><i class="fas fa-clock"></i></span>
                <span><strong>Usar horario diario</strong><small>Actívalo si la promoción solo aplica durante ciertas horas.</small></span>
              </label>
              <div class="promo-grid promo-grid--2 promo-hours">
                <div class="form-group"><label for="promo-hora-inicio">Hora inicio</label><input type="time" class="form-control" id="promo-hora-inicio" disabled></div>
                <div class="form-group"><label for="promo-hora-fin">Hora fin</label><input type="time" class="form-control" id="promo-hora-fin" disabled></div>
              </div>
            </div>
          </div>

          <div class="promo-section">
            <div class="promo-section-title"><i class="fas fa-calendar-day"></i><span>Días de la semana</span></div>
            <div class="promo-days">
              <label><input type="checkbox" value="mon" class="promo-dia"><span>Lun</span></label>
              <label><input type="checkbox" value="tue" class="promo-dia"><span>Mar</span></label>
              <label><input type="checkbox" value="wed" class="promo-dia"><span>Mié</span></label>
              <label><input type="checkbox" value="thu" class="promo-dia"><span>Jue</span></label>
              <label><input type="checkbox" value="fri" class="promo-dia"><span>Vie</span></label>
              <label><input type="checkbox" value="sat" class="promo-dia"><span>Sáb</span></label>
              <label><input type="checkbox" value="sun" class="promo-dia"><span>Dom</span></label>
            </div>
            <small class="hint">Si no seleccionas días, la promoción aplica todos los días.</small>
          </div>

          <div class="promo-options-row">
            <label class="promo-option-card"><input type="checkbox" id="promo-acumula"><span><i class="fas fa-layer-group"></i><strong>Acumula con mayoreo</strong></span></label>
            <label class="promo-option-card"><input type="checkbox" id="promo-estado" checked><span><i class="fas fa-toggle-on"></i><strong>Promoción activa</strong></span></label>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-danger" data-close="#modal-promocion" type="button"><i class="fas fa-times"></i> Cerrar</button>
        <button id="btn-guardar-promocion" class="btn btn-success" type="button"><i class="fas fa-save"></i> Guardar promoción</button>
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
        <form id="form-promo-productos" novalidate onsubmit="return false;">
          <div class="form-group">
            <label for="pp-promocion"><i class="fas fa-tags"></i> Promoción</label>
            <select id="pp-promocion" class="select2" data-placeholder="Selecciona la promoción" required>
              <option value=""></option>
            </select>
          </div>

          <div class="form-group promo-picker-field">
            <label><i class="fas fa-boxes"></i> Selecciona los productos</label>
            <select id="pp-productos" class="select2 promo-hidden-select" multiple required aria-hidden="true"></select>
            <div class="promo-picker-toolbar">
              <div class="promo-picker-search"><i class="fas fa-search"></i><input type="search" id="pp-buscar-producto" placeholder="Buscar producto por nombre o código…" autocomplete="off"></div>
              <span id="pp-seleccion-count" class="promo-picker-count">0 seleccionados</span>
            </div>
            <div id="pp-productos-grid" class="promo-picker-grid promo-product-grid" aria-live="polite"></div>
            <small class="hint">Haz clic en una tarjeta para seleccionar o quitar un producto. Puedes seleccionar varios.</small>
          </div>
        </form>

        <div id="pp-listado" class="responsive-record-list assignment-list" style="margin-top:10px;" aria-live="polite">
          <div class="rs-list-state"><i class="fas fa-box-open"></i><span>Seleccione una promoción</span></div>
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
        <form id="form-promo-categorias" novalidate onsubmit="return false;">
          <div class="form-group">
            <label for="pc-promocion"><i class="fas fa-tags"></i> Promoción</label>
            <select id="pc-promocion" class="select2" data-placeholder="Selecciona la promoción" required>
              <option value=""></option>
            </select>
          </div>

          <div class="form-group promo-picker-field">
            <label><i class="fas fa-layer-group"></i> Selecciona las categorías</label>
            <select id="pc-categorias" class="select2 promo-hidden-select" multiple required aria-hidden="true"></select>
            <div class="promo-picker-toolbar">
              <div class="promo-picker-search"><i class="fas fa-search"></i><input type="search" id="pc-buscar-categoria" placeholder="Buscar categoría…" autocomplete="off"></div>
              <span id="pc-seleccion-count" class="promo-picker-count">0 seleccionadas</span>
            </div>
            <div id="pc-categorias-grid" class="promo-picker-grid promo-category-grid" aria-live="polite"></div>
            <small class="hint">Haz clic en una categoría para seleccionarla o quitarla.</small>
          </div>
        </form>

        <div id="pc-listado" class="responsive-record-list assignment-list" style="margin-top:10px;" aria-live="polite">
          <div class="rs-list-state"><i class="fas fa-layer-group"></i><span>Seleccione una promoción</span></div>
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


  <!-- VISOR DE FACTURA: reutiliza el comportamiento de vista previa del sistema -->
  <div id="modal-factura-restaurante" class="rs-report-modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="rs-report-dialog">
      <div class="rs-report-header">
        <div><i class="fas fa-file-invoice"></i><strong>Factura generada</strong><span id="rs-report-subtitle"></span></div>
        <button type="button" id="btn-cerrar-factura-preview" class="rs-report-close" aria-label="Cerrar"><i class="fas fa-times"></i></button>
      </div>
      <div class="rs-report-body">
        <div id="rs-report-loading" class="rs-report-loading"><i class="fas fa-spinner fa-spin"></i><span>Cargando factura…</span></div>
        <iframe id="iframe-factura-restaurante" title="Factura generada" src="about:blank"></iframe>
      </div>
    </div>
  </div>

  <!-- Scripts (orden correcto) -->
  <script src="<?php echo SERVERURL; ?>ajax/query/jquery-3.5.1.min.js"></script>
  <script src="<?php echo SERVERURL; ?>ajax/sweetalert/sweetalert.min.js"></script>
  <script src="<?php echo SERVERURL; ?>ajax/librerias/select2.min.js"></script>
  <script>
    window.REST_COLABORADOR_ID = <?php echo json_encode((int)($_SESSION['colaborador_id_sd'] ?? 0)); ?>;
    window.REST_FECHA_SERVIDOR = <?php echo json_encode(date('Y-m-d')); ?>;
    window.REST_DB = <?php echo json_encode($GLOBALS['db'] ?? (defined('DB') ? DB : '')); ?>;
    window.REST_SISTEMA_PRUEBA = <?php echo json_encode((int)($GLOBALS['SISTEMA_PRUEBA'] ?? 0)); ?>;
    window.REST_REPORT_SERVER = <?php echo json_encode(defined('SERVERURLWINDOWS') ? SERVERURLWINDOWS : ''); ?>;
  </script>
  <script src="<?php echo SERVERURL; ?>ajax/js/facturasRestaurante.js"></script>
</body>
</html>
