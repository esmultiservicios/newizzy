<!-- INICIO MODAL AYUDA COTIZACIÓN (PRO) -->
<div class="modal fade" id="modalAyudaQuote" tabindex="-1" role="dialog" aria-labelledby="modalAyudaQuoteLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content help-modal">

      <!-- Header -->
      <div class="modal-header help-header">
        <div class="title-wrap">
          <span class="help-badge">
            <i class="fas fa-life-ring"></i>
          </span>

          <div class="title-text">
            <h4 class="modal-title help-title" id="modalAyudaQuoteLabel">Centro de Ayuda</h4>
            <small class="help-subtitle">Atajos y operaciones rápidas de cotización</small>
          </div>
        </div>

        <div class="header-actions">
          <div class="btn-group btn-group-sm mr-2">
            <button type="button" class="btn btn-light" id="helpCopyQuote">
              <i class="fas fa-copy"></i> Copiar
            </button>

            <button type="button" class="btn btn-light" id="helpPrintQuote">
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
              <u>Código del Producto</u> dentro del área de cotización.
            </div>
          </div>
        </div>

        <!-- Buscador visual -->
        <div class="form-group position-relative mb-4">
          <input class="form-control form-control-lg pl-5" id="helpSearchQuote" placeholder="Buscar atajo... (ej. F2, descuento, cliente)">
          <i class="fas fa-search help-search-icon"></i>
        </div>

        <div class="row">

          <!-- Atajos -->
          <div class="col-lg-8">
            <div class="table-responsive">
              <table class="table table-hover table-sm table-shortcuts" id="tableShortcutsQuote">
                <thead>
                  <tr>
                    <th style="width:13%">Tecla</th>
                    <th style="width:25%">Acción</th>
                    <th>Descripción</th>
                  </tr>
                </thead>

                <tbody>
                  <tr>
                    <td><kbd>F1</kbd></td>
                    <td>Ayuda</td>
                    <td>Abre esta ventana de ayuda para consultar los atajos disponibles en cotización.</td>
                  </tr>

                  <tr>
                    <td><kbd>F2</kbd></td>
                    <td>Búsqueda de productos</td>
                    <td>Abre el buscador de productos. También permite crear nuevos productos y actualizar la lista después de registrarlos.</td>
                  </tr>

                  <tr>
                    <td><kbd>F3</kbd></td>
                    <td>Descuentos</td>
                    <td>Permite aplicar descuentos a los productos agregados a la cotización.</td>
                  </tr>

                  <tr>
                    <td><kbd>F4</kbd></td>
                    <td>Modificar precio</td>
                    <td>Permite cambiar el precio del producto seleccionado cuando se requiera ajustar el valor de la cotización.</td>
                  </tr>

                  <tr>
                    <td><kbd>F6</kbd></td>
                    <td>Registrar cotización</td>
                    <td>Guarda o registra la cotización con los productos, cliente, vendedor y valores actuales.</td>
                  </tr>

                  <tr>
                    <td><kbd>F7</kbd></td>
                    <td>Clientes</td>
                    <td>Abre la búsqueda de clientes. También permite crear nuevos clientes y actualizar la lista.</td>
                  </tr>

                  <tr>
                    <td><kbd>F8</kbd></td>
                    <td>Colaboradores</td>
                    <td>Abre la búsqueda de vendedores o colaboradores para asignarlos a la cotización.</td>
                  </tr>

                  <tr>
                    <td><kbd>F9</kbd></td>
                    <td>Comentario</td>
                    <td>Permite agregar un comentario u observación a la cotización.</td>
                  </tr>

                  <tr>
                    <td><kbd>+</kbd></td>
                    <td>Aumentar cantidad</td>
                    <td>Incrementa la cantidad del producto cuando el foco está en el campo <em>Código del Producto</em>.</td>
                  </tr>

                  <tr>
                    <td><kbd>−</kbd></td>
                    <td>Disminuir cantidad</td>
                    <td>Disminuye la cantidad del producto cuando el foco está en el campo <em>Código del Producto</em>.</td>
                  </tr>

                  <tr>
                    <td><kbd>*</kbd></td>
                    <td>Comodín de cantidad</td>
                    <td>Permite agregar una cantidad directa escribiendo <code>10*código</code>. Ejemplo: <code>10*ABC123</code>.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Lateral -->
          <div class="col-lg-4">

            <div class="card shadow-sm mb-3">
              <div class="card-body py-3">
                <h6 class="mb-2">
                  <i class="fas fa-lightbulb mr-1"></i> Consejos rápidos
                </h6>

                <ul class="list-unstyled small mb-0">
                  <li class="mb-1">Seleccione primero el cliente antes de agregar productos.</li>
                  <li class="mb-1">Use <kbd>F2</kbd> para buscar productos rápidamente.</li>
                  <li class="mb-1">Para cantidades rápidas: <kbd>n*</kbd><code>código</code>. Ejemplo: <code>5*ABC123</code>.</li>
                  <li class="mb-1">Revise descuentos y precios antes de registrar la cotización.</li>
                </ul>
              </div>
            </div>

            <div class="card shadow-sm">
              <div class="card-body py-3">
                <h6 class="mb-2">
                  <i class="fas fa-headset mr-1"></i> Soporte
                </h6>

                <p class="small mb-3">
                  ¿Necesita ayuda con la cotización? Contacte al administrador del sistema.
                </p>

                <?php if (isset($url_ws) && !empty($url_ws)): ?>
                <a href="<?php echo htmlspecialchars($url_ws, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="btn btn-success btn-sm btn-block">
                  <i class="fab fa-whatsapp"></i> Chatear por WhatsApp
                </a>
                <?php endif; ?>

                <?php if (isset($telefono_ws_legible) && $telefono_ws_legible): ?>
                <div class="small text-muted mt-2">
                  <i class="fas fa-phone-alt mr-1"></i>
                  <?php echo htmlspecialchars($telefono_ws_legible, ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <?php endif; ?>
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <small class="text-muted mr-auto">
          Tip: enfoque el campo <em>Código del Producto</em> para usar las teclas de función.
        </small>

        <button type="button" class="btn btn-secondary" data-dismiss="modal">
          <i class="fas fa-times"></i> Cerrar
        </button>

        <button type="button" class="btn btn-primary" data-dismiss="modal">
          <i class="fas fa-check"></i> Entendido
        </button>
      </div>

    </div>
  </div>
</div>
<!-- FIN MODAL AYUDA COTIZACIÓN (PRO) -->