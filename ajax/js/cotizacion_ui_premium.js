/* =========================================================
   IZZY | COTIZACIÓN PREMIUM v6.42
   - Detalle fijo como Facturas
   - Select2 de vigencia preservado
   - Primera fila siempre permanece al quitar
   - Centro de Ayuda equivalente al de Facturas
   ========================================================= */
(function(window, document, $){
  'use strict';
  if(!$) return;

  function activa(){
    return $('#quoteForm').length > 0;
  }




  function filaTieneProducto($tr){
    var code = String($tr.find('input[id^="bar-code-id_"]').val() || '').trim();
    var productId = String($tr.find('input[id^="productosQuote_id_"]').val() || '').trim();

    /* El texto/descripcion puede ser inicializado por otros scripts aun
       sin producto seleccionado. Solo codigo o ID real ocultan el mensaje. */
    return code !== '' || productId !== '';
  }

  function hayProductos(){
    var found = false;
    $('#QuoteItem tbody tr').each(function(){
      if(filaTieneProducto($(this))){
        found = true;
        return false;
      }
    });
    return found;
  }

  function actualizarEmpty(){
    var $shell = $('.quote-detail-shell');
    if(!$shell.length) return;
    $shell.toggleClass('has-products', hayProductos());
  }

  function prepararDetalle(){
    var $tableWrap = $('#QuoteItem').closest('.form-group.row.table-responsive-xl');
    if(!$tableWrap.length) return;

    if(!$tableWrap.parent().hasClass('quote-detail-shell')){
      $tableWrap.wrap('<div class="quote-detail-shell"></div>');
    }

    var $shell = $tableWrap.parent();

    if(!$shell.find('.quote-detail-empty').length){
      $shell.append(
        '<div class="quote-detail-empty" aria-hidden="true">' +
          '<i class="fas fa-file-invoice-dollar"></i>' +
          '<strong>Detalle de la cotización</strong>' +
          '<span>Agregue productos o servicios por código o búsqueda. El encabezado permanece fijo y las acciones quedan siempre visibles debajo.</span>' +
        '</div>'
      );
    }

    actualizarEmpty();

    $(document)
      .off('input.izzyQuoteEmpty change.izzyQuoteEmpty blur.izzyQuoteEmpty', '#QuoteItem input')
      .on('input.izzyQuoteEmpty change.izzyQuoteEmpty blur.izzyQuoteEmpty', '#QuoteItem input', actualizarEmpty);

    if(window.MutationObserver && !$shell.data('izzy-detail-observer')){
      var tbody = document.querySelector('#QuoteItem tbody');
      if(tbody){
        var obs = new MutationObserver(function(){
          window.requestAnimationFrame(actualizarEmpty);
        });
        obs.observe(tbody, {
          childList:true,
          subtree:true,
          attributes:true,
          attributeFilter:['value']
        });
        $shell.data('izzy-detail-observer', true);
      }
    }

    /* Algunos procesos del sistema cambian .val() sin emitir eventos.
       Este refresco solo controla visibilidad; no toca cálculos. */
    window.setInterval(function(){
      if(activa()) actualizarEmpty();
    }, 250);
  }

  function iniciarSelect2Vigencia(){
    var $select = $('#vigencia_quote');
    if(!$select.length || typeof $.fn.select2 !== 'function') return;

    if($select.hasClass('select2-hidden-accessible')){
      $select.trigger('change.select2');
      return;
    }

    var cfg = {
      width:'150px',
      minimumResultsForSearch:0,
      allowClear:false
    };

    if(typeof window.izzySoloSelect2 === 'function'){
      window.izzySoloSelect2($select, cfg);
    }else{
      $select.select2(cfg);
    }
  }

  function vigilarVigencia(){
    var select = document.getElementById('vigencia_quote');
    if(!select) return;

    iniciarSelect2Vigencia();

    if(window.MutationObserver && !$(select).data('izzy-vigencia-observer')){
      var obs = new MutationObserver(function(){
        window.setTimeout(function(){
          iniciarSelect2Vigencia();
          $('#vigencia_quote').trigger('change.select2');
        }, 30);
      });
      obs.observe(select, {childList:true});
      $(select).data('izzy-vigencia-observer', true);
    }

    window.setTimeout(iniciarSelect2Vigencia, 300);
    window.setTimeout(iniciarSelect2Vigencia, 800);
  }

  function limpiarPrimeraFila(){
    var $row = $('#QuoteItem tbody tr').first();
    if(!$row.length) return;

    $row.find('input').each(function(){
      var $input = $(this);
      var type = String($input.attr('type') || '').toLowerCase();

      if(type === 'checkbox'){
        $input.prop('checked', false);
      }else{
        $input.val('').attr('value','');
      }
    });

    $row.find('[id^="productNameQuote_text_"]').text('Descripción del Producto');
    $row.find('[id^="icon-search-bar_"]').show();
  }

  function quitarSeguro(){
    var $rows = $('#QuoteItem tbody tr');
    var $checked = $rows.find('.itemRowQuote:checked');

    if(!$checked.length){
      if(typeof window.showNotify === 'function'){
        window.showNotify('error','Error','Debe seleccionar una fila antes de intentar eliminarla');
      }
      return;
    }

    var $first = $rows.first();

    $checked.each(function(){
      var $row = $(this).closest('tr');
      if($row.is($first)){
        limpiarPrimeraFila();
      }else{
        $row.remove();
      }
    });

    if(!$('#QuoteItem tbody tr').length){
      $('#QuoteItem tbody').append($first);
      limpiarPrimeraFila();
    }

    $('#checkAllQuote').prop('checked',false).attr('checked',false);
    $('.itemRowQuote').prop('checked',false).attr('checked',false);

    if(typeof window.calculateTotalQuote === 'function'){
      window.calculateTotalQuote();
    }

    actualizarEmpty();
  }

  function instalarQuitarSeguro(){
    if(window.__izzyQuoteRemoveSafeInstalled) return;
    window.__izzyQuoteRemoveSafeInstalled = true;

    document.addEventListener('click', function(ev){
      var btn = ev.target && ev.target.closest ? ev.target.closest('#removeRowsQuote') : null;
      if(!btn) return;

      ev.preventDefault();
      ev.stopPropagation();
      if(typeof ev.stopImmediatePropagation === 'function'){
        ev.stopImmediatePropagation();
      }
      quitarSeguro();
    }, true);
  }

  var helpItems = [
    {key:'F1', cat:'general', title:'Ayuda', text:'Abre este Centro de Ayuda de Cotización en cualquier momento.'},
    {key:'F2', cat:'productos', title:'Búsqueda de productos', text:'Abre el buscador de productos y permite actualizar la lista después de registrar nuevos productos.'},
    {key:'F3', cat:'productos', title:'Descuentos', text:'Permite aplicar descuentos a los productos agregados a la cotización.'},
    {key:'F4', cat:'productos', title:'Modificar precio', text:'Permite cambiar el precio del producto seleccionado cuando sea necesario.'},
    {key:'F6', cat:'cotizacion', title:'Registrar cotización', text:'Guarda o registra la cotización con los productos, cliente, vendedor y valores actuales.'},
    {key:'F7', cat:'personas', title:'Clientes', text:'Abre la búsqueda de clientes y permite actualizar la lista.'},
    {key:'F8', cat:'personas', title:'Colaboradores', text:'Abre la búsqueda de vendedores o colaboradores para asignarlos a la cotización.'},
    {key:'F9', cat:'cotizacion', title:'Comentario', text:'Permite agregar un comentario u observación a la cotización.'},
    {key:'+ −', cat:'productos', title:'Cantidad', text:'Incrementa o disminuye la cantidad cuando el foco está en Código del Producto.'},
    {key:'*', cat:'productos', title:'Comodín de cantidad', text:'Escriba 10*código para agregar varias unidades del producto de una sola vez.'}
  ];

  function esc(text){
    return $('<div>').text(String(text || '')).html();
  }

  function renderHelp(filter, category){
    var $list = $('#quoteHelpList');
    if(!$list.length) return;

    filter = String(filter || '').toLowerCase().trim();
    category = category || 'all';

    var rows = helpItems.filter(function(item){
      var catOK = category === 'all' || item.cat === category;
      var txt = (item.key + ' ' + item.title + ' ' + item.text).toLowerCase();
      return catOK && (!filter || txt.indexOf(filter) !== -1);
    });

    $list.empty();

    rows.forEach(function(item){
      var keys = item.key.split(' ').map(function(k){
        return '<kbd>' + esc(k) + '</kbd>';
      }).join('');

      $list.append(
        '<article class="help-shortcut-item">' +
          '<div class="help-key">' + keys + '</div>' +
          '<div class="help-item-copy">' +
            '<strong>' + esc(item.title) + '</strong>' +
            '<p>' + esc(item.text) + '</p>' +
          '</div>' +
        '</article>'
      );
    });

    $('#quoteHelpResultCount').text(rows.length + (rows.length === 1 ? ' resultado' : ' resultados'));
    $('#quoteHelpEmpty').prop('hidden', rows.length !== 0);
  }

  function prepararAyuda(){
    var $modal = $('#modalAyudaQuote');
    if(!$modal.length || $modal.data('izzy-help-ready')) return;

    $modal.data('izzy-help-ready', true);
    $modal.find('.modal-content').addClass('help-center-modal');
    $modal.find('.modal-body').addClass('help-center-body');
    $modal.find('.modal-footer').addClass('help-center-footer');

    $('#modalAyudaQuoteLabel')
      .text('Centro de Ayuda de Cotización')
      .attr('title','Centro de Ayuda de Cotización');

    $modal.find('.help-subtitle').text('Encuentre rápidamente atajos y operaciones frecuentes.');

    /* Mismo soporte por defecto que usa Facturas. */
    var ws = 'https://api.whatsapp.com/send?phone=50489136844&text=' +
      encodeURIComponent('Hola ES MULTISERVICIOS, nos gustaría que nos puedan brindar asistencia técnica, muchas gracias.');

    var body =
      '<div class="help-center-toolbar">' +
        '<div class="help-search-wrap">' +
          '<i class="fas fa-search help-search-icon"></i>' +
          '<input class="form-control" id="helpSearchQuote" autocomplete="off" placeholder="Buscar: producto, descuento, cliente, F7..." aria-label="Buscar en el Centro de Ayuda">' +
          '<button type="button" id="helpClearSearchQuote" class="help-clear-search" aria-label="Limpiar búsqueda"><i class="fas fa-times"></i></button>' +
        '</div>' +
        '<div class="help-result-status"><span id="quoteHelpResultCount">10 resultados</span></div>' +
      '</div>' +

      '<div class="help-category-nav" id="quoteHelpCategoryNav">' +
        '<button type="button" class="help-category-btn active" data-help-category="all"><i class="fas fa-th-large"></i><span>Todo</span></button>' +
        '<button type="button" class="help-category-btn" data-help-category="cotizacion"><i class="fas fa-file-invoice-dollar"></i><span>Cotización</span></button>' +
        '<button type="button" class="help-category-btn" data-help-category="productos"><i class="fas fa-box-open"></i><span>Productos</span></button>' +
        '<button type="button" class="help-category-btn" data-help-category="personas"><i class="fas fa-users"></i><span>Clientes y vendedores</span></button>' +
        '<button type="button" class="help-category-btn" data-help-category="general"><i class="fas fa-keyboard"></i><span>General</span></button>' +
      '</div>' +

      '<div class="help-quick-section">' +
        '<div class="help-section-heading"><div><strong><i class="fas fa-bolt"></i> Accesos rápidos</strong><small>Pulse una tecla para localizar su explicación.</small></div></div>' +
        '<div class="help-quick-grid">' +
          '<button type="button" class="help-quick-btn" data-help-jump="F2"><kbd>F2</kbd><span>Productos</span></button>' +
          '<button type="button" class="help-quick-btn" data-help-jump="F3"><kbd>F3</kbd><span>Descuentos</span></button>' +
          '<button type="button" class="help-quick-btn" data-help-jump="F6"><kbd>F6</kbd><span>Registrar</span></button>' +
          '<button type="button" class="help-quick-btn" data-help-jump="F7"><kbd>F7</kbd><span>Clientes</span></button>' +
          '<button type="button" class="help-quick-btn" data-help-jump="F8"><kbd>F8</kbd><span>Vendedores</span></button>' +
        '</div>' +
      '</div>' +

      '<div class="help-context-note">' +
        '<i class="fas fa-info-circle"></i>' +
        '<div><strong>Importante</strong><span>Las teclas de función trabajan cuando el foco está en <b>Código del Producto</b>. F1 abre esta ayuda en cualquier momento.</span></div>' +
      '</div>' +

      '<div class="help-content-grid">' +
        '<section class="help-shortcuts-panel">' +
          '<div class="help-section-heading help-list-heading"><div><strong><i class="fas fa-keyboard"></i> Atajos y operaciones</strong><small>Mostrando todas las opciones.</small></div></div>' +
          '<div class="help-shortcuts-list" id="quoteHelpList"></div>' +
          '<div class="help-empty-state" id="quoteHelpEmpty" hidden><i class="fas fa-search"></i><strong>No encontramos resultados</strong><span>Pruebe otra palabra, tecla o categoría.</span></div>' +
        '</section>' +

        '<aside class="help-side-panel">' +
          '<div class="help-side-card">' +
            '<div class="help-side-icon"><i class="fas fa-lightbulb"></i></div>' +
            '<div><strong>Consejos rápidos</strong><ul><li>Seleccione primero el cliente.</li><li>Use <kbd>F2</kbd> para buscar productos.</li><li>Revise descuentos y precios antes de registrar.</li></ul></div>' +
          '</div>' +

          '<div class="help-side-card">' +
            '<div class="help-side-icon"><i class="fas fa-headset"></i></div>' +
            '<div class="help-side-grow"><strong>¿Necesita más ayuda?</strong><p>Contacte al administrador o al soporte de ES MULTISERVICIOS.</p>' +
              '<a href="' + ws + '" target="_blank" rel="noopener" class="btn btn-success btn-sm btn-block quote-help-whatsapp"><i class="fab fa-whatsapp"></i> WhatsApp</a>' +
              '<small class="help-phone"><i class="fas fa-phone-alt"></i> +504 8913-6844</small>' +
            '</div>' +
          '</div>' +
        '</aside>' +
      '</div>';

    $modal.find('.modal-body').html(body);
    $modal.find('.modal-footer').html(
      '<small class="text-muted mr-auto"><i class="fas fa-info-circle"></i> Use el buscador o las categorías para encontrar una opción rápidamente.</small>' +
      '<button type="button" class="btn btn-primary" data-dismiss="modal"><i class="fas fa-check"></i> Entendido</button>'
    );

    var cat = 'all';
    renderHelp('', cat);

    $(document)
      .off('input.izzyQuoteHelp','#helpSearchQuote')
      .on('input.izzyQuoteHelp','#helpSearchQuote',function(){
        renderHelp($(this).val(),cat);
      })
      .off('click.izzyQuoteCat','#quoteHelpCategoryNav .help-category-btn')
      .on('click.izzyQuoteCat','#quoteHelpCategoryNav .help-category-btn',function(){
        cat = $(this).data('help-category') || 'all';
        $('#quoteHelpCategoryNav .help-category-btn').removeClass('active');
        $(this).addClass('active');
        renderHelp($('#helpSearchQuote').val(),cat);
      })
      .off('click.izzyQuoteClear','#helpClearSearchQuote')
      .on('click.izzyQuoteClear','#helpClearSearchQuote',function(){
        $('#helpSearchQuote').val('').trigger('input').focus();
      })
      .off('click.izzyQuoteQuick','#modalAyudaQuote .help-quick-btn')
      .on('click.izzyQuoteQuick','#modalAyudaQuote .help-quick-btn',function(){
        cat = 'all';
        $('#quoteHelpCategoryNav .help-category-btn').removeClass('active').first().addClass('active');
        var key = String($(this).data('help-jump') || '');
        $('#helpSearchQuote').val(key);
        renderHelp(key,cat);
      });

    $('#helpCopyQuote').off('click.izzyHelp').on('click.izzyHelp',function(){
      var txt = $('#quoteHelpList .help-shortcut-item').map(function(){
        return $(this).text().replace(/\s+/g,' ').trim();
      }).get().join('\n');

      if(navigator.clipboard && navigator.clipboard.writeText){
        navigator.clipboard.writeText(txt);
      }
    });

    $('#helpPrintQuote').off('click.izzyHelp').on('click.izzyHelp',function(){
      window.print();
    });
  }



  /* =========================================================
     VENDEDORES EN COTIZACIÓN
     ---------------------------------------------------------
     El modal compartido de colaboradores puede mostrar empleados
     generales. En Cotización se cambia únicamente su fuente AJAX
     al endpoint que ya filtra puesto = "Vendedores".
     ========================================================= */
  function filtrarModalSoloVendedores(){
    var $modal = $('#modal_buscar_colaboradores_facturacion');
    var $table = $('#DatatableColaboradoresBusquedaFactura');

    if(!$modal.length || !$table.length || !$.fn.DataTable){
      return;
    }

    $modal
      .off('shown.bs.modal.izzyQuoteVendedores')
      .on('shown.bs.modal.izzyQuoteVendedores', function(){
        window.setTimeout(function(){
          if(!$.fn.DataTable.isDataTable($table)){
            return;
          }

          var table = $table.DataTable();
          var ajax = table.ajax && table.ajax.url ? table.ajax.url() : '';

          if(String(ajax).indexOf('llenarDataTableColaboradoresFacturas.php') === -1){
            var filteredUrl = String(ajax || '').replace(
              /core\/llenarDataTableColaboradores\.php(?:\?.*)?$/i,
              'core/llenarDataTableColaboradoresFacturas.php'
            );

            if(filteredUrl === String(ajax || '') || !filteredUrl){
              var currentPath = window.location.pathname || '/';
              var quotePos = currentPath.indexOf('/cotizacion/');
              var root = quotePos >= 0 ? currentPath.substring(0, quotePos + 1) : '/';
              filteredUrl = window.location.origin + root + 'core/llenarDataTableColaboradoresFacturas.php';
            }

            table.ajax.url(filteredUrl).load(null, false);
          }
        }, 40);
      });
  }



    /* =========================================================
       IZZY v6.66 | SEPARADORES DE MILES EN DETALLE
       El valor de cada input type=number permanece SIN COMAS.
       Se muestra una capa visual no interactiva al perder foco.
       ========================================================= */
    function iniciarSeparadoresMiles(tabla, campos) {
        var selector = campos.map(function (campo) {
            return '#' + tabla + ' input[id^="' + campo + '_"]';
        }).join(', ');
        var $tabla = $('#' + tabla);
        if (!$tabla.length) return;

        function convertir(valor) {
            var texto = String(valor == null ? '' : valor).trim();
            if (!texto) return '';
            var numero = Number(texto.replace(/,/g, ''));
            if (!Number.isFinite(numero)) return '';
            var decimales = texto.indexOf('.') >= 0
                ? Math.min(texto.split('.')[1].length, 4) : 2;
            return numero.toLocaleString('en-US', {
                minimumFractionDigits: decimales,
                maximumFractionDigits: decimales
            });
        }

        function asegurarPresentador(input) {
            var $input = $(input);
            var $marco = $input.parent('.izzy-miles-marco');
            if (!$marco.length) {
                $input.wrap('<span class="izzy-miles-marco"></span>');
                $marco = $input.parent();
                $marco.css({
                    position: 'relative', display: 'flex', alignItems: 'stretch',
                    flex: '1 1 0', width: '100%', minWidth: '0', maxWidth: '100%'
                });
                $input.css({width: '100%', minWidth: '0', flex: '1 1 auto'});
                $('<span class="izzy-miles-texto" aria-hidden="true"></span>')
                    .css({
                        position: 'absolute', left: '0', right: '0', top: '0', bottom: '0',
                        display: 'none', alignItems: 'center', justifyContent: 'center',
                        color: '#26384a', fontSize: '12px', fontWeight: '700',
                        whiteSpace: 'nowrap', pointerEvents: 'none', zIndex: 2,
                        overflow: 'hidden'
                    }).appendTo($marco);
            }
            return $marco;
        }

        function actualizar(input) {
            if (!input || !input.isConnected) return;
            var $input = $(input);
            var $marco = asegurarPresentador(input);
            var $texto = $marco.children('.izzy-miles-texto');
            var enEdicion = document.activeElement === input && !input.readOnly;
            var nuevo = convertir($input.val());
            if (enEdicion || !nuevo) {
                $input.css({'color': '', '-webkit-text-fill-color': ''});
                $texto.hide();
                return;
            }
            if ($texto.text() !== nuevo) $texto.text(nuevo);
            $texto.css('display', 'flex');
            $input.css({'color': 'transparent', '-webkit-text-fill-color': 'transparent'});
        }

        function refrescar() {
            $tabla.find(selector.replace(new RegExp('#' + tabla + ' ', 'g'), '')).each(function () {
                actualizar(this);
            });
        }

        $(document)
            .off('focusin.izzyMiles' + tabla, selector)
            .on('focusin.izzyMiles' + tabla, selector, function () {
                if (!this.readOnly) {
                    $(this).css({'color': '', '-webkit-text-fill-color': ''});
                    $(this).parent('.izzy-miles-marco').children('.izzy-miles-texto').hide();
                }
            })
            .off('focusout.izzyMiles' + tabla, selector)
            .on('focusout.izzyMiles' + tabla, selector, function () {
                actualizar(this);
            });

        refrescar();
        window.setInterval(refrescar, 500);
    }


    /* =========================================================
       IZZY v6.67 | Separadores en footer (solo visuales)
       Los textarea readonly conservan su valor original.
       ========================================================= */
    function iniciarMilesFooter(ids) {
        function formato(valor) {
            var limpio = String(valor == null ? '' : valor).trim()
                .replace(/\s/g, '').replace(/,/g, '');
            if (!limpio) return '';
            var numero = Number(limpio);
            if (!Number.isFinite(numero)) return '';
            var decimales = limpio.indexOf('.') >= 0
                ? Math.min(limpio.split('.')[1].length, 4) : 2;
            return numero.toLocaleString('en-US', {
                minimumFractionDigits: decimales,
                maximumFractionDigits: decimales
            });
        }

        function actualizar() {
            ids.forEach(function (id) {
                var input = document.getElementById(id);
                if (!input || !input.readOnly) return;

                var $input = $(input);
                var $grupo = $input.closest('.footer1 .input-group');
                if (!$grupo.length) return;

                $grupo.css('position', 'relative');
                var $visual = $grupo.children('.izzy-footer-miles[data-for="' + id + '"]');
                if (!$visual.length) {
                    $visual = $('<span class="izzy-footer-miles" aria-hidden="true"></span>')
                        .attr('data-for', id)
                        .css({
                            position: 'absolute', top: 0, bottom: 0, right: 0,
                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                            textAlign: 'center', whiteSpace: 'nowrap', overflow: 'hidden',
                            pointerEvents: 'none', zIndex: 3,
                            color: '#40566a', fontSize: '13px', fontWeight: '800'
                        })
                        .appendTo($grupo);
                }

                var numero = formato($input.val());
                if (!numero) {
                    $visual.hide();
                    input.style.removeProperty('color');
                    input.style.removeProperty('-webkit-text-fill-color');
                    return;
                }

                var inicio = $grupo.find('.input-group-prepend').outerWidth() || 40;
                $visual.css('left', inicio + 'px');
                if ($visual.text() !== numero) $visual.text(numero);
                $visual.show();
                input.style.setProperty('color', 'transparent', 'important');
                input.style.setProperty('-webkit-text-fill-color', 'transparent', 'important');
            });
        }

        actualizar();
        window.setInterval(actualizar, 350);
    }

  function iniciarLayoutCotizacionInmediato(){
    if(!activa()) return;
    prepararDetalle();
    iniciarSelect2Vigencia();
  }

  iniciarLayoutCotizacionInmediato();

  $(function(){
    if(!activa()) return;
      prepararDetalle();
    vigilarVigencia();
    iniciarSeparadoresMiles("QuoteItem", ["priceQuote", "discountQuote", "totalQuote"]);
    iniciarMilesFooter([
      'subTotalQuoteFooter', 'taxDescuentoFooter',
      'taxAmountQuoteFooter', 'taxAmountQuoteFooter18',
      'totalAftertaxQuoteFooter'
    ]);
    instalarQuitarSeguro();
    filtrarModalSoloVendedores();
    prepararAyuda();

    window.setTimeout(function(){
      actualizarEmpty();
      iniciarSelect2Vigencia();
      prepararAyuda();
    }, 350);
  });
})(window, document, window.jQuery);
