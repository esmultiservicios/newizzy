<script>
// ===================== INICIO APP SIN DOCUMENT.READY =====================
(function () {
  function inicializarProductosApp() {
    initImageUpload();
    actualizarLabelsISVProductos();

    Promise.all([
      getEstadoProducto(),
      getCategoriasProducto(),
      getEmpresaProductos()
    ])
    .then(function() {
      listar_productos();
    })
    .catch(function(err) {
      console.error('[INIT] Error inicializando:', err);

      if (typeof showNotify === 'function') {
        showNotify('error', 'Error', 'No se pudo inicializar la página de productos');
      }
    });

    $('#form_main_productos #search').off('click.productos');
    $('#form_main_productos #search').on("click.productos", function(e) {
      e.preventDefault();
      listar_productos();
    });

    $('#form_main_productos').off('reset.productos');
    $('#form_main_productos').on('reset.productos', function() {
      var form = this;

      setTimeout(function() {
        $(form).find('.selectpicker').val('').selectpicker('refresh');
        $('#form_main_productos #buscar_productos_general').val('');
        listar_productos();
      }, 100);
    });

    $('#form_main_productos #buscar_productos_general').off('input.productos');
    $('#form_main_productos #buscar_productos_general').on('input.productos', function() {
      productosUIState.search = $(this).val() || '';
      productosUIState.page = 1;
      productosAplicarFiltrosUI();
    });

    $('#form_main_productos #categoria_producto_filtro, #form_main_productos #isv_producto_filtro')
      .off('changed.bs.select.productos change.productos')
      .on('changed.bs.select.productos change.productos', function() {
        productosUIState.page = 1;
        productosAplicarFiltrosUI();
      });

    $('#form_main_productos #buscar_productos').off('click.productos');
    $('#form_main_productos #buscar_productos').on('click.productos', function(e) {
      e.preventDefault();
      listar_productos();
    });

    $('#formProductos #tipo_producto').off('change.productosCategoria');
    $('#formProductos #tipo_producto').on('change.productosCategoria', evaluarCategoria);

    $("#formProductos #precio_venta, #formProductos #precio_compra").off("keyup.productosGanancia");
    $("#formProductos #precio_venta, #formProductos #precio_compra").on("keyup.productosGanancia", function() {
      var pc = parseFloat($("#formProductos #precio_compra").val()) || 0;
      var pv = parseFloat($("#formProductos #precio_venta").val()) || 0;

      $("#formProductos #porcentaje_venta").val((pv > pc) ? (pv - pc).toFixed(2) : "0");
    });

    $('#modalEditarBarcode').off('hidden.bs.modal.productosBarcode');
    $('#modalEditarBarcode').on('hidden.bs.modal.productosBarcode', function() {
      if ($('#formEditarBarcode')[0]) {
        $('#formEditarBarcode')[0].reset();
      }
    });

    $('#formProductos #label_producto_activo').html("Activo");

    $('#formProductos .switch').off('change.productosSwitch');
    $('#formProductos .switch').on('change.productosSwitch', function() {
      $('#formProductos #label_' + this.name).html($(this).is(':checked') ? "Sí" : "No");
    });

    initISVSwitches();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarProductosApp);
  } else {
    inicializarProductosApp();
  }
})();
// ===================== FIN INICIO APP =====================


/* =========================
   ISV: lógica de exclusión y habilitado
   ========================= */
function initISVSwitches() {
  const $form = $('#formProductos');
  const $isvFactura = $form.find('#producto_isv_factura');
  const $isv1 = $form.find('#producto_isv1');
  const $isv2 = $form.find('#producto_isv2');

  if (!$form.length || !$isvFactura.length || !$isv1.length || !$isv2.length) return;

  function asegurarCampoOculto(nombre) {
    let $campo = $form.find('input[name="' + nombre + '"]');
    if (!$campo.length) {
      $campo = $('<input>', { type: 'hidden', name: nombre }).appendTo($form);
    }
    return $campo;
  }

  function textoLabel(selector, fallback) {
    const texto = $.trim($(selector).first().text() || '');
    return texto || fallback;
  }

  function sincronizarTextosISV() {
    asegurarCampoOculto('producto_isv1_texto').val(
      textoLabel('#formProductos label[for="producto_isv1"], #formProductos #label_producto_isv1', 'ISV 1')
    );
    asegurarCampoOculto('producto_isv2_texto').val(
      textoLabel('#formProductos label[for="producto_isv2"], #formProductos #label_producto_isv2', 'ISV 2')
    );
  }

  function normalizarSeleccion(preferido) {
    const enabled = $isvFactura.is(':checked');

    if (!enabled) {
      $isv1.prop('checked', false).prop('disabled', true);
      $isv2.prop('checked', false).prop('disabled', true);
      sincronizarTextosISV();
      return;
    }

    $isv1.prop('disabled', false);
    $isv2.prop('disabled', false);

    // Si por HTML, reset o datos antiguos vienen ambos activos, conservar solo uno.
    if ($isv1.is(':checked') && $isv2.is(':checked')) {
      if (preferido === 2) $isv1.prop('checked', false);
      else $isv2.prop('checked', false);
    }

    // Con "Calcular ISV en Factura" activo SIEMPRE debe existir un tipo.
    // Por defecto se selecciona el ISV 1 (normalmente 15%).
    if (!$isv1.is(':checked') && !$isv2.is(':checked')) {
      $isv1.prop('checked', true);
    }

    sincronizarTextosISV();
  }

  $isv1.off('.productosISV').on('change.productosISV', function() {
    if (this.checked) {
      $isv2.prop('checked', false);
      normalizarSeleccion(1);
    } else {
      normalizarSeleccion(2);
    }
  });

  $isv2.off('.productosISV').on('change.productosISV', function() {
    if (this.checked) {
      $isv1.prop('checked', false);
      normalizarSeleccion(2);
    } else {
      normalizarSeleccion(1);
    }
  });

  $isvFactura.off('.productosISV').on('change.productosISV', function() {
    normalizarSeleccion(1);
  });

  $form.off('reset.productosISV').on('reset.productosISV', function() {
    setTimeout(function() {
      normalizarSeleccion(1);
    }, 0);
  });

  // Última protección antes de que el manejador AJAX general construya el FormData.
  $form.off('submit.productosISV').on('submit.productosISV', function() {
    normalizarSeleccion($isv2.is(':checked') ? 2 : 1);
    sincronizarTextosISV();
  });

  // Si la imagen proviene de pegar/arrastrar y el navegador perdió la asociación
  // con el input, se vuelve a anexar al FormData sin cambiar el flujo AJAX general.
  const formEl = $form.get(0);
  if (formEl && !formEl.dataset.productosFormDataHook) {
    formEl.dataset.productosFormDataHook = '1';
    formEl.addEventListener('formdata', function(e) {
      const file = window.__productoImagenFile;
      const input = document.getElementById('imagen_producto');
      const yaIncluida = input && input.files && input.files.length > 0;
      if (file && !yaIncluida && e.formData) {
        e.formData.set('imagen_producto', file, file.name || 'producto.jpg');
      }
      sincronizarTextosISV();
    });
  }

  normalizarSeleccion($isv2.is(':checked') ? 2 : 1);
}



/* =========================================================
   HELPERS PRODUCTOS
   ========================================================= */

function productosToNumber(valor) {
  if (valor === null || valor === undefined) {
    return 0;
  }

  if (typeof valor === 'number') {
    return valor;
  }

  return parseFloat(String(valor).replace(/[^\d.-]/g, '')) || 0;
}

function productosFormatoDinero(valor) {
  valor = productosToNumber(valor);

  return 'L ' + valor.toLocaleString('es-HN', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
}

function productosValor(valor, textoDefault) {
  if (valor === null || valor === undefined || String(valor).trim() === '') {
    return textoDefault !== undefined ? textoDefault : 'No registrado';
  }

  return String(valor).trim();
}

function productosEscape(valor) {
  return productosValor(valor, '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function productosBadgeDinero(valor, tipo) {
  var clase = 'productos-money-badge';

  if (tipo === 'compra') {
    clase += ' productos-money-compra';
  }

  if (tipo === 'venta') {
    clase += ' productos-money-venta';
  }

  if (tipo === 'ganancia') {
    clase += productosToNumber(valor) > 0 ? ' productos-money-ganancia' : ' productos-money-neutral';
  }

  if (tipo === 'mayoreo') {
    clase += productosToNumber(valor) > 0 ? ' productos-money-mayoreo' : ' productos-money-neutral';
  }

  return '<span class="' + clase + '">' + productosFormatoDinero(valor) + '</span>';
}

function productosEstadoBadge(estado) {
  if (parseInt(estado, 10) === 1) {
    return '' +
      '<span class="productos-status-badge productos-status-active">' +
        '<i class="fas fa-check-circle"></i> Activo' +
      '</span>';
  }

  return '' +
    '<span class="productos-status-badge productos-status-inactive">' +
      '<i class="fas fa-times-circle"></i> Inactivo' +
    '</span>';
}

function productosIsvBadge(row) {
  var aplica = String(row.isv_venta || '').toLowerCase() === 'si' || parseInt(row.isv_venta || 0, 10) === 1;
  var isvTipo = '';

  if (parseInt(row.isv1 || 0, 10) === 1) {
    isvTipo = getPorcentajeTextoISVProducto(1);
  } else if (parseInt(row.isv2 || 0, 10) === 1) {
    isvTipo = getPorcentajeTextoISVProducto(2);
  }

  if (aplica) {
    return '' +
      '<span class="productos-isv-badge productos-isv-si" title="Este producto calcula impuesto al vender">' +
        '<i class="fas fa-check-circle"></i> Sí ' + productosEscape(isvTipo) +
      '</span>';
  }

  return '' +
    '<span class="productos-isv-badge productos-isv-no" title="Este producto no calcula impuesto al vender">' +
      '<i class="fas fa-times-circle"></i> No' +
    '</span>';
}

function productosFiltrarRows(rows) {
  rows = rows || [];

  var texto = $('#form_main_productos #buscar_productos_general').val();
  var categoriaFiltro = $('#form_main_productos #categoria_producto_filtro').val();
  var isvFiltro = $('#form_main_productos #isv_producto_filtro').val();

  texto = texto === null || texto === undefined ? '' : String(texto).trim().toLowerCase();
  categoriaFiltro = categoriaFiltro === null || categoriaFiltro === undefined ? '' : String(categoriaFiltro).trim().toLowerCase();
  isvFiltro = isvFiltro === null || isvFiltro === undefined ? '' : String(isvFiltro).trim().toLowerCase();

  return rows.filter(function(item) {
    var categoria = item.categoria === null || item.categoria === undefined ? '' : String(item.categoria).trim().toLowerCase();
    var tipoProductoId = item.tipo_producto_id === null || item.tipo_producto_id === undefined ? '' : String(item.tipo_producto_id).trim().toLowerCase();

    var aplicaIsv = String(item.isv_venta || '').toLowerCase() === 'si' || parseInt(item.isv_venta || 0, 10) === 1;

    var textoBase = [
      item.nombre,
      item.descripcion,
      item.barCode,
      item.medida,
      item.categoria,
      item.precio_compra,
      item.precio_venta,
      item.porcentaje_venta,
      item.estado == 1 ? 'activo' : 'inactivo',
      aplicaIsv ? 'con isv si impuesto' : 'sin isv no impuesto'
    ].join(' ').toLowerCase();

    if (texto !== '' && textoBase.indexOf(texto) === -1) {
      return false;
    }

    if (categoriaFiltro !== '' && categoriaFiltro !== '0') {
      if (categoria.indexOf(categoriaFiltro) === -1 && tipoProductoId !== categoriaFiltro) {
        return false;
      }
    }

    if (isvFiltro !== '') {
      if (isvFiltro === 'si' && !aplicaIsv) {
        return false;
      }

      if (isvFiltro === 'no' && aplicaIsv) {
        return false;
      }
    }

    return true;
  });
}

function productosActualizarResumen(rows) {
  rows = rows || [];

  var totalActivos = 0;
  var totalIsv = 0;
  var totalVenta = 0;

  rows.forEach(function(item) {
    if (parseInt(item.estado, 10) === 1) {
      totalActivos++;
    }

    if (String(item.isv_venta || '').toLowerCase() === 'si' || parseInt(item.isv_venta || 0, 10) === 1) {
      totalIsv++;
    }

    totalVenta += productosToNumber(item.precio_venta);
  });

  $('#productos_total_registros').text(rows.length);
  $('#productos_total_activos').text(totalActivos);
  $('#productos_total_isv').text(totalIsv);
  $('#productos_total_venta').text(productosFormatoDinero(totalVenta));
}


/* =========================================================
   LABELS DINÁMICOS ISV - PRODUCTOS
   ========================================================= */

var cacheISVProductos = {};

function normalizarNumeroISVProductos(valor) {
  valor = String(valor || '0')
    .replace(/L/g, '')
    .replace(/\s/g, '')
    .replace(/[^\d.,-]/g, '');

  if (valor === '') {
    return 0;
  }

  if (valor.includes(',') && valor.includes('.')) {
    valor = valor.replace(/,/g, '');
  } else if (valor.includes(',') && !valor.includes('.')) {
    valor = valor.replace(/,/g, '.');
  }

  var numero = parseFloat(valor);

  return isNaN(numero) ? 0 : numero;
}

function formatearPorcentajeLabelISVProductos(valor) {
  valor = normalizarNumeroISVProductos(valor);

  if (valor <= 0) {
    return '';
  }

  if (Number.isInteger(valor)) {
    return valor.toString();
  }

  return valor.toFixed(2).replace(/\.?0+$/, '');
}

function fetchISVProductoSync(isv_id) {
  isv_id = parseInt(isv_id, 10);

  if (!isv_id || isv_id <= 0) {
    return 0;
  }

  if (cacheISVProductos[isv_id] !== undefined) {
    return cacheISVProductos[isv_id];
  }

  var porcentaje = 0;

  $.ajax({
    type: 'POST',
    url: '<?php echo SERVERURL;?>core/getISV.php',
    data: {
      isv_id: isv_id
    },
    dataType: 'json',
    async: false,
    success: function(response) {
      if (response && response.success === true && response.valor !== undefined) {
        porcentaje = normalizarNumeroISVProductos(response.valor);
      } else if (response && response.valor !== undefined) {
        porcentaje = normalizarNumeroISVProductos(response.valor);
      } else if (response && response.porcentaje !== undefined) {
        porcentaje = normalizarNumeroISVProductos(response.porcentaje);
      } else if (response && response.isv !== undefined) {
        porcentaje = normalizarNumeroISVProductos(response.isv);
      } else if ($.isArray(response) && response.length > 0) {
        porcentaje = normalizarNumeroISVProductos(response[0]);
      } else if (typeof response === 'number' || typeof response === 'string') {
        porcentaje = normalizarNumeroISVProductos(response);
      }
    },
    error: function(xhr) {
      console.log(xhr.responseText);
      porcentaje = 0;
    }
  });

  cacheISVProductos[isv_id] = porcentaje;

  return porcentaje;
}

function getPorcentajeTextoISVProducto(isv_id) {
  var porcentaje = fetchISVProductoSync(isv_id);
  var texto = formatearPorcentajeLabelISVProductos(porcentaje);

  return texto !== '' ? '(' + texto + '%)' : '';
}

function getTextoISVProducto(isv_id) {
  var porcentaje = fetchISVProductoSync(isv_id);
  var texto = formatearPorcentajeLabelISVProductos(porcentaje);

  return texto !== '' ? 'ISV ' + texto + '%' : 'ISV';
}

function actualizarLabelsISVProductos() {
  var isv1 = formatearPorcentajeLabelISVProductos(fetchISVProductoSync(1));
  var isv2 = formatearPorcentajeLabelISVProductos(fetchISVProductoSync(2));

  if (isv1 !== '') {
    $('#formProductos label[for="producto_isv1"], #formProductos #label_producto_isv1').html('ISV ' + isv1 + '%');
  }

  if (isv2 !== '') {
    $('#formProductos label[for="producto_isv2"], #formProductos #label_producto_isv2').html('ISV ' + isv2 + '%');
  }

  // Mantener sincronizado el texto real que el controlador usará en sus mensajes.
  const $form = $('#formProductos');
  if ($form.length) {
    let $t1 = $form.find('input[name="producto_isv1_texto"]');
    let $t2 = $form.find('input[name="producto_isv2_texto"]');
    if (!$t1.length) $t1 = $('<input>', {type:'hidden', name:'producto_isv1_texto'}).appendTo($form);
    if (!$t2.length) $t2 = $('<input>', {type:'hidden', name:'producto_isv2_texto'}).appendTo($form);
    $t1.val($.trim($('#formProductos label[for="producto_isv1"], #formProductos #label_producto_isv1').first().text()) || 'ISV 1');
    $t2.val($.trim($('#formProductos label[for="producto_isv2"], #formProductos #label_producto_isv2').first().text()) || 'ISV 2');
  }
}

function recargarLabelsISVProductos() {
  cacheISVProductos = {};
  actualizarLabelsISVProductos();
}


/* ===============================
   EDITAR CÓDIGO DE BARRA PRODUCTO
   =============================== */
$(document).off('click.productosBarcodeEdit', '#grupo_editar_bacode .editar_barcode');
$(document).on('click.productosBarcodeEdit', '#grupo_editar_bacode .editar_barcode', function(e) {
  e.preventDefault();

  const productoId = $('#formProductos input[name="productos_id"]').val();
  const producto = $('#formProductos input[name="producto"]').val();
  const barcode = $('#formProductos input[name="bar_code_product"]').val();

  if (!productoId || productoId === '0') {
    if (typeof showNotify === 'function') {
      showNotify(
        "warning",
        "Advertencia",
        "Primero debe seleccionar o editar un producto existente para cambiar el código de barra."
      );
    } else {
      console.warn("Primero debe seleccionar o editar un producto existente para cambiar el código de barra.");
    }

    return;
  }

  $('#formEditarBarcode input[name="productos_id"]').val(productoId);
  $('#formEditarBarcode input[name="pro_barcode"]').val('Editar Código de Barra');
  $('#formEditarBarcode input[name="producto"]').val(producto);
  $('#formEditarBarcode input[name="barcode"]').val(barcode);

  $('#modalEditarBarcode').modal({
    show: true,
    keyboard: false,
    backdrop: 'static'
  });

  setTimeout(function() {
    $('#formEditarBarcode input[name="barcode"]').focus().select();
  }, 500);
});

function generarBarcodeFechaHora() {
  const fecha = new Date();

  const year = fecha.getFullYear();
  const month = String(fecha.getMonth() + 1).padStart(2, '0');
  const day = String(fecha.getDate()).padStart(2, '0');
  const hour = String(fecha.getHours()).padStart(2, '0');
  const minute = String(fecha.getMinutes()).padStart(2, '0');
  const second = String(fecha.getSeconds()).padStart(2, '0');

  return `${year}${month}${day}${hour}${minute}${second}`;
}

$(document).off('click.productosGenerarBarcode', '#btnGenerarBarcode');
$(document).on('click.productosGenerarBarcode', '#btnGenerarBarcode', function(e) {
  e.preventDefault();

  const barcodeGenerado = generarBarcodeFechaHora();

  $('#formEditarBarcode input[name="barcode"]').val(barcodeGenerado).focus().select();

  if (typeof showNotify === 'function') {
    showNotify('success', 'Código generado', 'Se generó el código de barra automáticamente.');
  }
});

$(document).off('submit.productosEditarBarcode', '#formEditarBarcode');
$(document).on('submit.productosEditarBarcode', '#formEditarBarcode', function(e) {
  e.preventDefault();

  const productoId = $('#formEditarBarcode input[name="productos_id"]').val();
  const barcode = $('#formEditarBarcode input[name="barcode"]').val().trim();

  if (!productoId || productoId === '0') {
    showNotify("warning", "Advertencia", "No se recibió el ID del producto.");
    return;
  }

  if (barcode === '') {
    showNotify("warning", "Advertencia", "Ingrese el código de barra.");
    $('#formEditarBarcode input[name="barcode"]').focus();
    return;
  }

  if (barcode === '0') {
    showNotify("warning", "Advertencia", "El código de barra no puede ser cero.");
    $('#formEditarBarcode input[name="barcode"]').focus().select();
    return;
  }

  if (barcode.length > 100) {
    showNotify("warning", "Advertencia", "El código de barra no puede superar los 100 caracteres.");
    $('#formEditarBarcode input[name="barcode"]').focus().select();
    return;
  }

  const $btn = $('#editar_barcode');
  const textoOriginal = $btn.html();

  $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...');

  $.ajax({
    url: '<?php echo SERVERURL; ?>core/productos/editarBarcodeAjax.php',
    type: 'POST',
    dataType: 'json',
    data: {
      productos_id: productoId,
      barcode: barcode
    },
    success: function(response) {
      if (response && (response.success === true || response.status === true)) {
        showNotify("success", "Éxito", response.message || "Código de barra actualizado correctamente");

        $('#formProductos input[name="bar_code_product"]').val(response.barcode || barcode);

        $('#modalEditarBarcode').modal('hide');

        if (typeof listar_productos === 'function') {
          listar_productos();
        }
      } else {
        showNotify("error", "Error", response.message || "No se pudo actualizar el código de barra");
      }
    },
    error: function(xhr) {
      let mensaje = "Error al actualizar el código de barra";

      if (xhr.responseJSON && xhr.responseJSON.message) {
        mensaje = xhr.responseJSON.message;
      } else if (xhr.responseText) {
        try {
          const json = JSON.parse(xhr.responseText);

          if (json.message) {
            mensaje = json.message;
          }
        } catch (e) {
          console.error(xhr.responseText);
        }
      }

      showNotify("error", "Error", mensaje);
    },
    complete: function() {
      $btn.prop('disabled', false).html(textoOriginal);
    }
  });
});


function setISVFromData(datos, rowData) {
  const $isvFactura = $('#formProductos #producto_isv_factura');
  const $isv1 = $('#formProductos #producto_isv1');
  const $isv2 = $('#formProductos #producto_isv2');
  const $rest = $('#formProductos #producto_restaurante');

  let vIsv1 = rowData?.isv1;
  let vIsv2 = rowData?.isv2;
  let vRes  = rowData?.restaurante;

  if (vIsv1 == null && Array.isArray(datos)) vIsv1 = datos[25];
  if (vIsv2 == null && Array.isArray(datos)) vIsv2 = datos[26];
  if (vRes  == null && Array.isArray(datos)) vRes  = datos[24];

  vIsv1 = Number(vIsv1) === 1 ? 1 : 0;
  vIsv2 = Number(vIsv2) === 1 ? 1 : 0;
  vRes  = Number(vRes)  === 1 ? 1 : 0;

  $rest.prop('checked', vRes === 1);

  const on1 = vIsv1 === 1;
  const on2 = (vIsv2 === 1) && !on1;

  $isv1.prop('checked', on1);
  $isv2.prop('checked', on2);

  initISVSwitches();
}


/* =========================
   Uploader de imagen (Producto)
   ========================= */
function initImageUpload() {
  const dropArea = document.getElementById('productoDropArea');
  const fileInput = document.getElementById('imagen_producto');
  const preview = document.getElementById('productoPreview');
  const fileInfo = document.getElementById('productoInfo');
  const btnSelect = document.getElementById('btnSelectProductImage');
  const selectLink = dropArea ? dropArea.querySelector('.select-file-text') : null;

  if (!dropArea || !fileInput || fileInput.dataset.initialized) return;

  fileInput.dataset.initialized = 'true';

  window.__productoImagenFile = null;

  let isProcessing = false;

  ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function(ev) {
    dropArea.addEventListener(ev, preventDefaults, false);
  });

  ['dragenter', 'dragover'].forEach(function(ev) {
    dropArea.addEventListener(ev, function() {
      dropArea.classList.add('drag-over');
    }, false);
  });

  ['dragleave', 'drop'].forEach(function(ev) {
    dropArea.addEventListener(ev, function() {
      dropArea.classList.remove('drag-over');
    }, false);
  });

  dropArea.addEventListener('drop', function(e) {
    const files = e.dataTransfer && e.dataTransfer.files ? e.dataTransfer.files : [];

    if (files.length) {
      handleFiles(files, true);
    }
  });

  const openChooser = function(e) {
    e.preventDefault();
    e.stopPropagation();

    fileInput.value = '';
    fileInput.click();
  };

  if (btnSelect) {
    btnSelect.addEventListener('click', openChooser);
    btnSelect.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') {
        openChooser(e);
      }
    });
  }

  if (selectLink) {
    selectLink.addEventListener('click', openChooser);
    selectLink.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') {
        openChooser(e);
      }
    });
  }

  fileInput.addEventListener('change', function(e) {
    if (isProcessing) return;

    isProcessing = true;

    const files = e.target.files;

    if (files && files.length > 0) {
      handleFiles(files, false);
    }

    isProcessing = false;
  });

  document.addEventListener('paste', function(e) {
    const items = (e.clipboardData || (e.originalEvent && e.originalEvent.clipboardData))
      ? (e.clipboardData || e.originalEvent.clipboardData).items
      : [];

    let file = null;

    for (let i = 0; i < items.length; i++) {
      if (
        items[i].kind === 'file' &&
        items[i].type &&
        items[i].type.startsWith('image/')
      ) {
        file = items[i].getAsFile();
        break;
      }
    }

    if (file) {
      e.preventDefault();

      const dt = new DataTransfer();
      dt.items.add(file);

      handleFiles(dt.files, true);
    }
  });

  function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
  }

  function handleFiles(fileList, asignarAlInput) {
    if (!fileList || !fileList.length) return;

    const file = fileList[0];

    if (!file.type || !file.type.startsWith('image/')) {
      if (window.swal) {
        swal({
          title: 'Error',
          text: 'Selecciona una imagen válida (JPG, PNG, GIF)',
          icon: 'error'
        });
      } else {
        console.error('Selecciona una imagen válida (JPG, PNG, GIF)');
      }

      resetImage();
      return;
    }

    if (file.size > 2 * 1024 * 1024) {
      if (typeof showNotify === 'function') {
        showNotify('error', 'Error', 'La imagen no debe exceder 2MB');
      } else {
        console.error('La imagen no debe exceder 2MB');
      }

      resetImage();
      return;
    }

    window.__productoImagenFile = file;

    if (asignarAlInput && fileInput) {
      const dt = new DataTransfer();
      dt.items.add(file);
      fileInput.files = dt.files;
    }

    const reader = new FileReader();

    reader.onload = function(ev) {
      preview.innerHTML = '';

      const img = document.createElement('img');
      img.src = ev.target.result;
      img.alt = file.name;
      preview.appendChild(img);

      const removeBtn = document.createElement('button');
      removeBtn.className = 'btn-remove-image';
      removeBtn.type = 'button';
      removeBtn.title = 'Eliminar imagen';
      removeBtn.innerHTML = '<i class="fas fa-trash-alt"></i>';

      removeBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        resetImage();
      });

      preview.appendChild(removeBtn);

      preview.style.display = 'block';

      if (fileInfo) {
        fileInfo.textContent = file.name + ' (' + formatFileSize(file.size) + ')';
      }

      if (window.jQuery && $("#formProductos #productos_id").val()) {
        $("#formProductos #preview").attr("src", ev.target.result);
      }
    };

    reader.readAsDataURL(file);
  }

  function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';

    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return (bytes / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i];
  }

  function resetImage() {
    fileInput.value = '';
    window.__productoImagenFile = null;

    preview.innerHTML = '';
    preview.style.display = 'none';

    if (fileInfo) {
      fileInfo.textContent = 'Ningún archivo seleccionado';
    }

    if (window.jQuery && $("#formProductos #productos_id").val()) {
      $("#formProductos #preview").attr(
        "src",
        "<?php echo SERVERURL;?>vistas/plantilla/img/products/image_preview.png"
      );
    }
  }

  window.resetProductoImagen = resetImage;
}


/* =========================================================
   PRODUCTOS | UI IZZY 6.0 - DIV / KPI / EXPORT PREMIUM
   ========================================================= */

var PRODUCTOS_MOBILE_QUERY = '(max-width: 767.98px)';
var PRODUCTOS_STORAGE_VISTA = 'izzy.productos.tipo_vista';
var PRODUCTOS_STORAGE_FILTROS = 'izzy.productos.filtros.visible';
var PRODUCTOS_STORAGE_KPIS = 'izzy.productos.kpis.visible';

var productosUIState = {
  rows: [],
  filtered: [],
  page: 1,
  pageSize: 10,
  pageSizeDetalle: 10,
  pageSizeMiniatura: 6,
  view: 'detalle',
  preferredView: 'detalle',
  search: '',
  loading: false
};

function productosEsMovil() {
  return window.matchMedia
    ? window.matchMedia(PRODUCTOS_MOBILE_QUERY).matches
    : $(window).width() <= 767;
}

function productosParseRows(json) {
  if (Array.isArray(json)) return json;
  if (json && Array.isArray(json.data)) return json.data;
  if (json && Array.isArray(json.aaData)) return json.aaData;
  return [];
}

function productosConfigurarPanel(btnSelector, bodySelector, storageKey, defaultVisible) {
  var $btn = $(btnSelector);
  var $body = $(bodySelector);
  var visible = defaultVisible;

  try {
    var saved = localStorage.getItem(storageKey);
    if (saved !== null) visible = saved === '1';
  } catch (e) {}

  function sync() {
    $body.toggle(visible);
    $btn.attr('aria-expanded', visible ? 'true' : 'false');
    $btn.find('span').text(visible ? 'Ocultar' : 'Mostrar');
    $btn.find('i')
      .toggleClass('fa-chevron-up', visible)
      .toggleClass('fa-chevron-down', !visible);
  }

  sync();

  $btn.off('click.productosPanel').on('click.productosPanel', function() {
    visible = !visible;
    $body.stop(true, true)[visible ? 'slideDown' : 'slideUp'](170);
    sync();

    try {
      localStorage.setItem(storageKey, visible ? '1' : '0');
    } catch (e) {}
  });
}

function productosInicializarVista() {
  var saved = 'detalle';

  try {
    saved = localStorage.getItem(PRODUCTOS_STORAGE_VISTA) || 'detalle';
  } catch (e) {}

  productosUIState.preferredView = saved === 'miniatura' ? 'miniatura' : 'detalle';
  productosUIState.view = productosEsMovil() ? 'miniatura' : productosUIState.preferredView;

  productosActualizarBotonesVista();
  productosSincronizarPageSize();
}

function productosActualizarDisponibilidadVista() {
  var movil = productosEsMovil();

  $('.productos-view-btn[data-view="detalle"]')
    .prop('disabled', movil)
    .toggleClass('d-none', movil)
    .attr('aria-hidden', movil ? 'true' : 'false');
}

function productosActualizarBotonesVista() {
  productosActualizarDisponibilidadVista();

  $('.productos-view-btn')
    .removeClass('active')
    .attr('aria-pressed', 'false');

  $('.productos-view-btn[data-view="' + productosUIState.view + '"]')
    .addClass('active')
    .attr('aria-pressed', 'true');
}

function productosCambiarVista(vista) {
  productosUIState.view = productosEsMovil()
    ? 'miniatura'
    : (vista === 'miniatura' ? 'miniatura' : 'detalle');

  if (!productosEsMovil()) {
    productosUIState.preferredView = productosUIState.view;

    try {
      localStorage.setItem(PRODUCTOS_STORAGE_VISTA, productosUIState.preferredView);
    } catch (e) {}
  }

  productosUIState.page = 1;
  productosActualizarBotonesVista();
  productosSincronizarPageSize();
  productosRender();
}

function productosSincronizarPageSize() {
  var mini = productosUIState.view === 'miniatura';
  var opciones = mini ? [6, 12, 18, 30] : [10, 25, 50, 100];
  var preferido = mini ? productosUIState.pageSizeMiniatura : productosUIState.pageSizeDetalle;

  if (opciones.indexOf(preferido) === -1) preferido = opciones[0];

  var $select = $('#productosPageSize');
  $select.empty();

  opciones.forEach(function(n) {
    $select.append($('<option></option>').val(n).text(n));
  });

  productosUIState.pageSize = preferido;
  $select.val(String(preferido));
}

function productosAplicarFiltrosUI() {
  productosUIState.search = $('#buscar_productos_general').val() || '';
  productosUIState.filtered = productosFiltrarRows(productosUIState.rows || []);
  productosActualizarResumen(productosUIState.filtered);
  productosRender();
}

function listar_productos() {
  var estado = $('#form_main_productos #estado_producto').val();
  estado = (estado === null || estado === undefined || estado === '') ? 1 : estado;

  productosUIState.loading = true;
  productosRenderEstado('loading');

  $.ajax({
    method: 'POST',
    url: '<?php echo SERVERURL;?>core/llenarDataTableProductos.php',
    dataType: 'json',
    data: { estado: estado },
    timeout: 30000
  }).done(function(response) {
    productosUIState.rows = productosParseRows(response);
    productosUIState.loading = false;
    productosUIState.page = 1;
    productosAplicarFiltrosUI();

    if (typeof getPermisosTipoUsuarioAccesosTable === 'function' &&
        typeof getPrivilegioTipoUsuario === 'function') {
      getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
    }
  }).fail(function(xhr, textStatus, error) {
    productosUIState.rows = [];
    productosUIState.filtered = [];
    productosUIState.loading = false;
    productosActualizarResumen([]);
    productosRenderEstado('error');

    console.error('[Productos AJAX]', textStatus, error, xhr.status, xhr.responseText);

    if (typeof showNotify === 'function') {
      showNotify('error', 'Error', 'No se pudieron cargar los productos.');
    }
  });
}

function productosRenderEstado(tipo) {
  var config = {
    loading: {
      icon: 'fas fa-spinner fa-spin',
      title: 'Cargando productos',
      text: 'Espere mientras consultamos el catálogo.'
    },
    empty: {
      icon: 'fas fa-box-open',
      title: 'Sin registros',
      text: 'No se encontraron productos con los criterios actuales.'
    },
    error: {
      icon: 'fas fa-exclamation-circle',
      title: 'No fue posible cargar los datos',
      text: 'Revise la conexión e intente nuevamente.'
    }
  }[tipo];

  $('#productosListado').html(
    '<div class="productos-state">' +
      '<i class="' + config.icon + '"></i>' +
      '<strong>' + productosEscape(config.title) + '</strong>' +
      '<span>' + productosEscape(config.text) + '</span>' +
    '</div>'
  );

  $('#productosInfo').text('0 registros');
  $('#productosPaginacion').empty();
}

function productosImagenUrl(row) {
  var fallback = '<?php echo SERVERURL;?>vistas/plantilla/img/products/image_preview.png';
  var archivo = row.image || row.imagen || row.file || '';

  if (!archivo) return fallback;
  if (/^https?:\/\//i.test(archivo)) return archivo;

  return '<?php echo SERVERURL;?>vistas/plantilla/img/products/' + archivo;
}

function productosAccionesHtml(row) {
  return '' +
    '<div class="dropdown productos-actions acciones-dropdown">' +
      '<button type="button" class="btn btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
        '<i class="fas fa-cog"></i><span>Acciones</span>' +
      '</button>' +
      '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +
        '<button type="button" class="dropdown-item accion-item productos-ver-action" data-id="' + productosEscape(row.productos_id) + '">' +
          '<span class="accion-icon"><i class="fas fa-eye"></i></span><span class="accion-label">Ver detalles</span>' +
        '</button>' +
        '<button type="button" class="dropdown-item accion-item accion-editar table_editar productos-editar-action" data-id="' + productosEscape(row.productos_id) + '">' +
          '<span class="accion-icon accion-icon-editar"><i class="fas fa-edit"></i></span><span class="accion-label">Editar</span>' +
        '</button>' +
        '<button type="button" class="dropdown-item accion-item accion-eliminar table_eliminar productos-eliminar-action" data-id="' + productosEscape(row.productos_id) + '">' +
          '<span class="accion-icon accion-icon-eliminar"><i class="fas fa-trash-alt"></i></span><span class="accion-label">Eliminar</span>' +
        '</button>' +
      '</div>' +
    '</div>';
}

function productosRowPorId(id) {
  return (productosUIState.rows || []).find(function(row) {
    return String(row.productos_id) === String(id);
  }) || null;
}

function productosRenderDetalle(rows) {
  var html = '' +
    '<div class="productos-detail-header">' +
      '<div>Producto</div>' +
      '<div>Precios</div>' +
      '<div>Impuestos</div>' +
      '<div>Reglas / Control</div>' +
      '<div>Estado</div>' +
      '<div>Acciones</div>' +
    '</div>';

  rows.forEach(function(row) {
    var imageUrl = productosImagenUrl(row);
    var fallback = '<?php echo SERVERURL;?>vistas/plantilla/img/products/image_preview.png';

    html += '' +
      '<article class="productos-detail-row">' +
        '<div class="productos-cell productos-product-cell">' +
          '<span class="productos-cell-label">Producto</span>' +
          '<div class="productos-product-box">' +
            '<a href="#" class="iv-trigger productos-product-image" data-iv-src="' + productosEscape(imageUrl) + '" data-iv-fallback="' + productosEscape(fallback) + '" data-iv-title="' + productosEscape(row.nombre) + '">' +
              '<img src="' + productosEscape(imageUrl) + '" alt="' + productosEscape(row.nombre) + '" loading="lazy" onerror="this.onerror=null;this.src=\'' + fallback + '\';">' +
              '<span class="productos-image-overlay" aria-hidden="true"><span class="productos-image-overlay-icon"><i class="fas fa-search-plus"></i></span><span class="productos-image-overlay-text">Ver imagen</span></span>' +
            '</a>' +
            '<div class="productos-product-info">' +
              '<strong>' + productosEscape(productosValor(row.nombre, 'Sin nombre')) + '</strong>' +
              '<small>' + productosEscape(productosValor(row.descripcion, 'Sin descripción registrada')) + '</small>' +
              '<div class="productos-meta-line">' +
                '<span><i class="fas fa-barcode"></i>' + productosEscape(productosValor(row.barCode, 'Sin código')) + '</span>' +
                '<span><i class="fas fa-layer-group"></i>' + productosEscape(productosValor(row.categoria, 'Sin categoría')) + '</span>' +
                '<span><i class="fas fa-ruler-combined"></i>' + productosEscape(productosValor(row.medida, 'Sin medida')) + '</span>' +
              '</div>' +
            '</div>' +
          '</div>' +
        '</div>' +

        '<div class="productos-cell">' +
          '<span class="productos-cell-label">Precios</span>' +
          '<div class="productos-stack">' +
            '<span><b>Compra:</b> ' + productosBadgeDinero(row.precio_compra, 'compra') + '</span>' +
            '<span><b>Venta:</b> ' + productosBadgeDinero(row.precio_venta, 'venta') + '</span>' +
            '<span><b>Ganancia:</b> ' + productosBadgeDinero(row.porcentaje_venta, 'ganancia') + '</span>' +
            '<span><b>Mayoreo:</b> ' + productosBadgeDinero(row.precio_mayoreo, 'mayoreo') + '</span>' +
          '</div>' +
        '</div>' +

        '<div class="productos-cell">' +
          '<span class="productos-cell-label">Impuestos</span>' +
          '<div class="productos-stack">' +
            '<span><b>ISV venta:</b> ' + productosIsvBadge(row) + '</span>' +
            '<span><b>ISV compra:</b> ' + ((String(row.isv_compra || '').toLowerCase() === 'si' || Number(row.isv_compra) === 1) ? 'Sí' : 'No') + '</span>' +
            '<span><b>Restaurante:</b> ' + (Number(row.restaurante) === 1 ? 'Sí' : 'No') + '</span>' +
          '</div>' +
        '</div>' +

        '<div class="productos-cell">' +
          '<span class="productos-cell-label">Reglas / Control</span>' +
          '<div class="productos-stack">' +
            '<span><b>Mínimo:</b> ' + productosToNumber(row.cantidad_minima) + '</span>' +
            '<span><b>Máximo:</b> ' + productosToNumber(row.cantidad_maxima) + '</span>' +
            '<span><b>Cant. mayoreo:</b> ' + productosToNumber(row.cantidad_mayoreo) + '</span>' +
            '<span><b>Almacén:</b> ' + productosEscape(productosValor(row.almacen)) + '</span>' +
          '</div>' +
        '</div>' +

        '<div class="productos-cell productos-center-cell">' +
          '<span class="productos-cell-label">Estado</span>' +
          productosEstadoBadge(row.estado) +
        '</div>' +

        '<div class="productos-cell productos-actions-cell">' +
          '<span class="productos-cell-label">Acciones</span>' +
          productosAccionesHtml(row) +
        '</div>' +
      '</article>';
  });

  return html;
}

function productosRenderMiniatura(rows) {
  var html = '<div class="productos-mini-grid">';

  rows.forEach(function(row) {
    var imageUrl = productosImagenUrl(row);
    var fallback = '<?php echo SERVERURL;?>vistas/plantilla/img/products/image_preview.png';

    html += '' +
      '<article class="productos-mini-card">' +
        '<div class="productos-mini-topline"></div>' +
        '<div class="productos-mini-header">' +
          '<a href="#" class="iv-trigger productos-mini-image" data-iv-src="' + productosEscape(imageUrl) + '" data-iv-fallback="' + productosEscape(fallback) + '" data-iv-title="' + productosEscape(row.nombre) + '">' +
            '<img src="' + productosEscape(imageUrl) + '" alt="' + productosEscape(row.nombre) + '" loading="lazy" onerror="this.onerror=null;this.src=\'' + fallback + '\';">' +
            '<span class="productos-image-overlay" aria-hidden="true"><span class="productos-image-overlay-icon"><i class="fas fa-search-plus"></i></span><span class="productos-image-overlay-text">Ver imagen</span></span>' +
          '</a>' +
          '<div class="productos-mini-identity">' +
            '<div class="productos-mini-title-row">' +
              '<h4>' + productosEscape(productosValor(row.nombre, 'Sin nombre')) + '</h4>' +
              productosEstadoBadge(row.estado) +
            '</div>' +
            '<div class="productos-mini-code"><i class="fas fa-barcode"></i> ' + productosEscape(productosValor(row.barCode, 'Sin código')) + '</div>' +
          '</div>' +
        '</div>' +

        '<div class="productos-mini-body">' +
          '<div class="productos-mini-field"><span>Categoría</span><strong>' + productosEscape(productosValor(row.categoria, 'Sin categoría')) + '</strong></div>' +
          '<div class="productos-mini-field"><span>Medida</span><strong>' + productosEscape(productosValor(row.medida, 'Sin medida')) + '</strong></div>' +
          '<div class="productos-mini-field"><span>Compra</span><strong>' + productosFormatoDinero(row.precio_compra) + '</strong></div>' +
          '<div class="productos-mini-field"><span>Venta</span><strong>' + productosFormatoDinero(row.precio_venta) + '</strong></div>' +
          '<div class="productos-mini-field"><span>Ganancia</span><strong>' + productosFormatoDinero(row.porcentaje_venta) + '</strong></div>' +
          '<div class="productos-mini-field"><span>Mayoreo</span><strong>' + productosFormatoDinero(row.precio_mayoreo) + '</strong></div>' +
          '<div class="productos-mini-field"><span>ISV venta</span><strong>' + productosIsvBadge(row) + '</strong></div>' +
          '<div class="productos-mini-field"><span>Almacén</span><strong>' + productosEscape(productosValor(row.almacen)) + '</strong></div>' +
          '<div class="productos-mini-field productos-mini-field-full"><span>Descripción</span><strong>' + productosEscape(productosValor(row.descripcion, 'Sin descripción registrada')) + '</strong></div>' +
        '</div>' +

        '<div class="productos-mini-footer">' + productosAccionesHtml(row) + '</div>' +
      '</article>';
  });

  return html + '</div>';
}

function productosRender() {
  if (productosUIState.loading) {
    productosRenderEstado('loading');
    return;
  }

  var rows = productosUIState.filtered || [];

  if (!rows.length) {
    productosRenderEstado('empty');
    return;
  }

  var totalPages = Math.max(1, Math.ceil(rows.length / productosUIState.pageSize));
  if (productosUIState.page > totalPages) productosUIState.page = totalPages;
  if (productosUIState.page < 1) productosUIState.page = 1;

  var start = (productosUIState.page - 1) * productosUIState.pageSize;
  var pageRows = rows.slice(start, start + productosUIState.pageSize);

  $('#productosListado')
    .removeClass('vista-detalle vista-miniatura')
    .addClass('vista-' + productosUIState.view)
    .html(
      productosUIState.view === 'miniatura'
        ? productosRenderMiniatura(pageRows)
        : productosRenderDetalle(pageRows)
    );

  var end = Math.min(start + pageRows.length, rows.length);
  $('#productosInfo').text(
    'Mostrando ' + (start + 1) + ' a ' + end + ' de ' + rows.length + ' registros'
  );

  productosRenderPaginacion(totalPages);

  if (typeof getPermisosTipoUsuarioAccesosTable === 'function' &&
      typeof getPrivilegioTipoUsuario === 'function') {
    getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
  }

  if (typeof cerrarDropdownAcciones === 'function') {
    cerrarDropdownAcciones();
  }
}

function productosRenderPaginacion(totalPages) {
  var current = productosUIState.page;
  var html = '';

  function btn(label, page, disabled, active, icon) {
    return '<button type="button" class="productos-page-btn' + (active ? ' active' : '') + '"' +
      ' data-page="' + page + '"' + (disabled ? ' disabled' : '') + '>' +
      (icon ? '<i class="' + icon + ' mr-1"></i>' : '') + label +
    '</button>';
  }

  html += btn('Inicio', 1, current === 1, false, 'fas fa-angle-double-left');
  html += btn('Anterior', current - 1, current === 1, false, 'fas fa-angle-left');

  var from = Math.max(1, current - 2);
  var to = Math.min(totalPages, from + 4);
  from = Math.max(1, to - 4);

  for (var p = from; p <= to; p++) {
    html += btn(String(p), p, false, p === current, '');
  }

  html += btn('Siguiente', current + 1, current === totalPages, false, 'fas fa-angle-right');
  html += btn('Final', totalPages, current === totalPages, false, 'fas fa-angle-double-right');

  $('#productosPaginacion').html(html);
}

function productosVerDetalle(data) {
  if (!data) {
    showNotify('error', 'Error', 'No se encontró la información del producto.');
    return;
  }

  var isvTipo = 'No aplica';

  if (parseInt(data.isv1 || 0, 10) === 1) {
    isvTipo = getTextoISVProducto(1);
  } else if (parseInt(data.isv2 || 0, 10) === 1) {
    isvTipo = getTextoISVProducto(2);
  }

  var imageUrl = productosImagenUrl(data);
  var activo = parseInt(data.estado, 10) === 1;

  var html = '' +
    '<div class="productos-swal-detail">' +
      '<div class="productos-swal-hero">' +
        '<a href="#" class="iv-trigger productos-swal-image" ' +
           'data-iv-src="' + productosEscape(imageUrl) + '" ' +
           'data-iv-title="' + productosEscape(productosValor(data.nombre, 'Producto')) + '">' +
          '<img src="' + productosEscape(imageUrl) + '" alt="' + productosEscape(productosValor(data.nombre, 'Producto')) + '">' +
          '<span class="productos-image-overlay" aria-hidden="true">' +
            '<span class="productos-image-overlay-icon"><i class="fas fa-search-plus"></i></span>' +
            '<span class="productos-image-overlay-text">Ver imagen</span>' +
          '</span>' +
        '</a>' +
        '<div class="productos-swal-hero-copy">' +
          '<div class="productos-swal-title-row">' +
            '<div>' +
              '<span class="productos-swal-kicker">Producto</span>' +
              '<h3>' + productosEscape(productosValor(data.nombre, 'Sin nombre')) + '</h3>' +
            '</div>' +
            '<span class="productos-status-badge ' + (activo ? 'productos-status-active' : 'productos-status-inactive') + '">' +
              '<i class="fas ' + (activo ? 'fa-check-circle' : 'fa-times-circle') + '"></i> ' + (activo ? 'Activo' : 'Inactivo') +
            '</span>' +
          '</div>' +
          '<p>' + productosEscape(productosValor(data.descripcion, 'Sin descripción registrada')) + '</p>' +
          '<div class="productos-swal-meta">' +
            '<span><i class="fas fa-barcode"></i>' + productosEscape(productosValor(data.barCode, 'Sin código')) + '</span>' +
            '<span><i class="fas fa-layer-group"></i>' + productosEscape(productosValor(data.categoria, 'Sin categoría')) + '</span>' +
            '<span><i class="fas fa-ruler-combined"></i>' + productosEscape(productosValor(data.medida, 'Sin medida')) + '</span>' +
          '</div>' +
        '</div>' +
      '</div>' +

      '<div class="productos-swal-section">' +
        '<div class="productos-swal-section-title"><i class="fas fa-tags"></i><span>Precios</span></div>' +
        '<div class="productos-swal-grid productos-swal-grid-4">' +
          '<div class="productos-swal-field"><span>Precio compra</span><strong>' + productosFormatoDinero(data.precio_compra) + '</strong></div>' +
          '<div class="productos-swal-field"><span>Precio venta</span><strong>' + productosFormatoDinero(data.precio_venta) + '</strong></div>' +
          '<div class="productos-swal-field"><span>Ganancia</span><strong>' + productosFormatoDinero(data.porcentaje_venta) + '</strong></div>' +
          '<div class="productos-swal-field"><span>Precio mayoreo</span><strong>' + productosFormatoDinero(data.precio_mayoreo) + '</strong></div>' +
        '</div>' +
      '</div>' +

      '<div class="productos-swal-section">' +
        '<div class="productos-swal-section-title"><i class="fas fa-percent"></i><span>Impuestos y reglas</span></div>' +
        '<div class="productos-swal-grid productos-swal-grid-4">' +
          '<div class="productos-swal-field"><span>ISV venta</span><strong>' + productosEscape(productosValor(data.isv_venta, 'No')) + ' · ' + productosEscape(isvTipo) + '</strong></div>' +
          '<div class="productos-swal-field"><span>ISV compra</span><strong>' + ((String(data.isv_compra || '').toLowerCase() === 'si' || Number(data.isv_compra) === 1) ? 'Sí' : 'No') + '</strong></div>' +
          '<div class="productos-swal-field"><span>Restaurante</span><strong>' + (Number(data.restaurante) === 1 ? 'Sí' : 'No') + '</strong></div>' +
          '<div class="productos-swal-field"><span>Almacén</span><strong>' + productosEscape(productosValor(data.almacen)) + '</strong></div>' +
        '</div>' +
      '</div>' +

      '<div class="productos-swal-section">' +
        '<div class="productos-swal-section-title"><i class="fas fa-boxes"></i><span>Control de inventario</span></div>' +
        '<div class="productos-swal-grid productos-swal-grid-4">' +
          '<div class="productos-swal-field"><span>Cantidad mínima</span><strong>' + productosToNumber(data.cantidad_minima) + '</strong></div>' +
          '<div class="productos-swal-field"><span>Cantidad máxima</span><strong>' + productosToNumber(data.cantidad_maxima) + '</strong></div>' +
          '<div class="productos-swal-field"><span>Cantidad mayoreo</span><strong>' + productosToNumber(data.cantidad_mayoreo) + '</strong></div>' +
          '<div class="productos-swal-field"><span>Producto superior</span><strong>' + productosEscape(productosValor(data.producto_superior)) + '</strong></div>' +
        '</div>' +
      '</div>' +
    '</div>';

  var content = document.createElement('div');
  content.innerHTML = html;

  swal({
    title: 'Detalle del producto',
    content: content,
    icon: null,
    className: 'productos-detail-swal',
    buttons: {
      confirm: {
        text: 'Cerrar',
        value: true,
        visible: true,
        className: 'btn-primary'
      }
    },
    closeOnEsc: true,
    closeOnClickOutside: false
  });

  /*
   * SweetAlert v1 trata el texto del botón como texto plano.
   * Insertamos el icono después de renderizar sin cambiar la lógica.
   */
  window.setTimeout(function() {
    var botonCerrar = document.querySelector(
      '.swal-modal.productos-detail-swal .swal-button--confirm'
    );

    if (botonCerrar) {
      botonCerrar.innerHTML =
        '<i class="fas fa-times mr-1" aria-hidden="true"></i>' +
        '<span>Cerrar</span>';
    }
  }, 0);
}

function productosEditar(data) {
  if (!data) {
    showNotify('error', 'Error', 'No se encontró el producto seleccionado.');
    return;
  }

  var url = '<?php echo SERVERURL;?>core/editarProductos.php';

  $('#formProductos')[0].reset();

  if (typeof window.resetProductoImagen === 'function') {
    window.resetProductoImagen();
  }

  $('#formProductos #productos_id').val(data.productos_id);

  $.ajax({
    type: 'POST',
    url: url,
    data: $('#formProductos').serialize(),
    success: function(registro) {
      var datos;

      try {
        datos = JSON.parse(registro);
      } catch (e) {
        try {
          datos = eval(registro);
        } catch (evalError) {
          datos = null;
        }
      }

      if (!Array.isArray(datos)) {
        showNotify('error', 'Error', 'No se pudo interpretar la información del producto.');
        return;
      }

      $('#formProductos').attr({'data-form': 'update'});
      $('#formProductos').attr({'action': '<?php echo SERVERURL;?>ajax/modificarProductosAjax.php'});
      $('#reg_producto').hide();
      $('#edi_producto').show();
      $('#delete_producto').hide();
      $('#formProductos #proceso_productos').val("Editar Productos");

      evaluarCategoriaDetalle(datos[13]);
      $('#formProductos #medida').val(datos[1]).selectpicker('refresh');
      $('#formProductos #almacen').val(datos[0]).selectpicker('refresh');
      $('#formProductos #producto').val(datos[2]);
      $('#formProductos #descripcion').val(datos[3]);
      $('#formProductos #precio_compra').val(datos[4]);
      $('#formProductos #precio_venta').val(datos[5]);
      $('#formProductos #tipo_producto').val(datos[6]).selectpicker('refresh');
      $('#formProductos #producto_empresa_id').val(datos[11]).selectpicker('refresh');
      $('#formProductos #porcentaje_venta').val(datos[13]);
      $('#formProductos #cantidad_minima').val(datos[14]);
      $('#formProductos #cantidad_maxima').val(datos[15]);
      $('#formProductos #producto_categoria').val(datos[16]);
      $('#formProductos #precio_mayoreo').val(datos[17]);
      $('#formProductos #cantidad_mayoreo').val(datos[18]);
      $('#formProductos #bar_code_product').val(datos[19]);
      $('#formProductos #producto_superior').val(datos[20]);

      $('#formProductos #producto_isv_factura').prop('checked', datos[7] == 1);
      $('#formProductos #producto_isv_compra').prop('checked', datos[8] == 1);
      $('#formProductos #producto_activo').prop('checked', datos[9] == 1);

      var preview = document.getElementById('productoPreview');
      var productoInfo = document.getElementById('productoInfo');

      if (datos[21] && datos[21] !== '') {
        if (preview) {
          preview.innerHTML = '';

          var img = document.createElement('img');
          img.src = datos[21];
          preview.appendChild(img);
          preview.style.display = 'block';

          var removeBtn = document.createElement('button');
          removeBtn.type = 'button';
          removeBtn.className = 'btn-remove-image';
          removeBtn.title = 'Eliminar imagen';
          removeBtn.innerHTML = '×';

          removeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            if (typeof window.resetProductoImagen === 'function') {
              window.resetProductoImagen();
            }
          });

          preview.appendChild(removeBtn);
        }

        if (productoInfo) productoInfo.textContent = 'Imagen cargada';
        $('#formProductos #preview').attr('src', datos[21]);
      } else {
        if (preview) {
          preview.innerHTML = '';
          preview.style.display = 'none';
        }

        if (productoInfo) productoInfo.textContent = 'Ningún archivo seleccionado';
        $('#formProductos #preview').attr('src', '<?php echo SERVERURL;?>vistas/plantilla/img/products/image_preview.png');
      }

      $('#formProductos #producto').prop("readonly", false);
      $('#formProductos #cantidad').prop("readonly", true);
      $('#formProductos #precio_compra').prop("readonly", false);
      $('#formProductos #precio_venta').prop("readonly", false);
      $('#formProductos #descripcion').prop("readonly", false);
      $('#formProductos #cantidad_minima').prop("readonly", false);
      $('#formProductos #cantidad_maxima').prop("readonly", false);
      $('#formProductos #cantidad_mayoreo').prop("readonly", false);
      $('#formProductos #porcentaje_venta').prop("readonly", false);
      $('#formProductos #producto_isv_factura').prop("disabled", false);
      $('#formProductos #producto_isv_compra').prop("disabled", false);
      $('#formProductos #producto_activo').prop("disabled", false);
      $('#formProductos #grupo_editar_bacode').show();

      $('#formProductos #medida').prop("disabled", true);
      $('#formProductos #producto_superior').prop("disabled", true);
      $('#formProductos #almacen').prop("disabled", true);
      $('#formProductos #tipo_producto').prop("disabled", true);
      $('#formProductos #producto_categoria').prop("disabled", true);
      $('#formProductos #bar_code_product').prop("readonly", true);
      $('#formProductos #producto_empresa_id').prop("disabled", true);
      $('#formProductos #cantidad').prop("disabled", true);
      $('#formProductos #buscar_producto_empresa').hide();
      $('#formProductos #buscar_producto_categorias').hide();
      $('#formProductos #estado_producto').show();
      $('#formProductos #cantidad').hide();
      $('#div_cantidad_editar_producto').hide();

      setISVFromData(datos, data);
      actualizarLabelsISVProductos();
      recargarLabelsISVProductos();

      $('#modal_registrar_productos').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
      });
    },
    error: function() {
      showNotify('error', 'Error', 'No se pudo cargar el producto para edición.');
    }
  });
}

function productosEliminar(data) {
  if (!data) {
    showNotify('error', 'Error', 'No se encontró el producto seleccionado.');
    return;
  }

  $('#formProductos #productos_id').val(data.productos_id);

  $.ajax({
    type: 'POST',
    url: '<?php echo SERVERURL;?>core/editarProductos.php',
    data: $('#formProductos').serialize(),
    success: function(registro) {
      var datos;

      try {
        datos = JSON.parse(registro);
      } catch (e) {
        try {
          datos = eval(registro);
        } catch (evalError) {
          datos = [];
        }
      }

      var nombre = data.nombre || data.producto || datos[2] || 'Producto';
      var barcode = data.barCode || data.bar_code_product || datos[19] || '';
      var imgUrl = (datos[21] && /^https?:\/\//i.test(datos[21]))
        ? datos[21]
        : productosImagenUrl(data);

      var cont = document.createElement('div');
      cont.style.textAlign = 'left';
      cont.innerHTML =
        '<div style="display:flex;gap:12px;align-items:center;">' +
          '<img src="' + productosEscape(imgUrl) + '" alt="Imagen" style="width:70px;height:70px;object-fit:cover;border-radius:6px;border:1px solid #e5e5e5;">' +
          '<div>' +
            '<div style="font-weight:600;margin-bottom:4px;">' + productosEscape(nombre) + '</div>' +
            '<div style="font-size:.9rem;color:#555;"><strong>Código de barras:</strong> ' + (barcode ? productosEscape(barcode) : '&mdash;') + '</div>' +
          '</div>' +
        '</div>' +
        '<div style="margin-top:12px;">¿Desea eliminar permanentemente este producto?</div>';

      swal({
        title: 'Confirmar eliminación',
        content: cont,
        icon: 'warning',
        buttons: {
          cancel: {
            text: 'Cancelar',
            visible: true,
            className: 'btn-light'
          },
          confirm: {
            text: 'Sí, eliminar',
            value: true,
            className: 'btn-danger',
            closeModal: false
          }
        },
        dangerMode: true,
        closeOnEsc: false,
        closeOnClickOutside: false
      }).then(function(confirmar) {
        if (!confirmar) return;

        $.ajax({
          type: 'POST',
          url: '<?php echo SERVERURL;?>ajax/eliminarProductosAjax.php',
          data: { productos_id: data.productos_id },
          dataType: 'json',
          beforeSend: function() {
            if (typeof showLoading === 'function') {
              showLoading('Eliminando producto...');
            }
          },
          success: function(resp) {
            swal.close();

            if (resp && resp.status === 'success') {
              showNotify('success', resp.title || 'Eliminado', resp.message || 'Producto eliminado');
              listar_productos();
            } else {
              showNotify('error', (resp && resp.title) || 'Error', (resp && resp.message) || 'No se pudo eliminar');
            }
          },
          error: function() {
            swal.close();
            showNotify('error', 'Error', 'Error al procesar la solicitud.');
          }
        });
      });
    },
    error: function() {
      showNotify('error', 'Error', 'No se pudieron obtener los datos del producto.');
    }
  });
}

/* =========================================================
   EXCEL PREMIUM - XLSX OOXML
   ========================================================= */

function productosExcelEscape(value) {
  return String(value === null || typeof value === 'undefined' ? '' : value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function productosExcelColName(index) {
  var name = '';

  while (index >= 0) {
    name = String.fromCharCode((index % 26) + 65) + name;
    index = Math.floor(index / 26) - 1;
  }

  return name;
}

function productosExcelCell(ref, value, styleId, numeric) {
  if (numeric) {
    var n = Number(value);
    if (!isFinite(n)) n = 0;
    return '<c r="' + ref + '" s="' + styleId + '" t="n"><v>' + n + '</v></c>';
  }

  var raw = String(value === null || typeof value === 'undefined' ? '' : value);
  var preserve = /^\s|\s$/.test(raw) ? ' xml:space="preserve"' : '';

  return '<c r="' + ref + '" s="' + styleId + '" t="inlineStr"><is><t' + preserve + '>' +
    productosExcelEscape(raw) +
  '</t></is></c>';
}

function productosGenerarXlsx(rows) {
  if (typeof JSZip === 'undefined') return null;

  var headers = [
    'Producto', 'Código', 'Categoría', 'Medida',
    'Precio compra', 'Precio venta', 'Ganancia', 'Precio mayoreo',
    'ISV venta', 'Almacén', 'Estado'
  ];

  var activos = rows.filter(function(r) { return Number(r.estado) === 1; }).length;
  var conIsv = rows.filter(function(r) {
    return String(r.isv_venta || '').toLowerCase() === 'si' || Number(r.isv_venta) === 1;
  }).length;
  var totalVenta = rows.reduce(function(acc, r) {
    return acc + productosToNumber(r.precio_venta);
  }, 0);

  var lastCol = 'K';
  var headerRow = 7;
  var firstDataRow = 8;
  var lastRow = Math.max(headerRow, headerRow + rows.length);
  var sheetRows = [];

  sheetRows.push(
    '<row r="1" ht="30" customHeight="1">' +
      productosExcelCell('A1', 'IZZY • REPORTE DE PRODUCTOS', 1, false) +
    '</row>'
  );

  sheetRows.push(
    '<row r="2" ht="20" customHeight="1">' +
      productosExcelCell(
        'A2',
        'Catálogo general de productos • Generado: ' + new Date().toLocaleDateString('es-HN'),
        2,
        false
      ) +
    '</row>'
  );

  sheetRows.push(
    '<row r="3" ht="18" customHeight="1">' +
      productosExcelCell('A3', 'REGISTROS', 6, false) +
      productosExcelCell('D3', 'ACTIVOS', 6, false) +
      productosExcelCell('G3', 'CON ISV', 6, false) +
      productosExcelCell('J3', 'VALOR VENTA', 6, false) +
    '</row>'
  );

  sheetRows.push(
    '<row r="4" ht="26" customHeight="1">' +
      productosExcelCell('A4', rows.length, 7, true) +
      productosExcelCell('D4', activos, 7, true) +
      productosExcelCell('G4', conIsv, 7, true) +
      productosExcelCell('J4', totalVenta, 11, true) +
    '</row>'
  );

  sheetRows.push(
    '<row r="5" ht="18" customHeight="1">' +
      productosExcelCell(
        'A5',
        'Filtros: Estado ' + ($('#estado_producto option:selected').text() || 'Todos') +
        ' | Categoría ' + ($('#categoria_producto_filtro option:selected').text() || 'Todas') +
        ' | ISV ' + ($('#isv_producto_filtro option:selected').text() || 'Todos') +
        ' | Búsqueda: ' + ($.trim($('#buscar_productos_general').val()) || 'Sin búsqueda'),
        8,
        false
      ) +
    '</row>'
  );

  sheetRows.push(
    '<row r="6" ht="18" customHeight="1">' +
      productosExcelCell('A6', 'Detalle de productos filtrados', 8, false) +
    '</row>'
  );

  sheetRows.push(
    '<row r="' + headerRow + '" ht="26" customHeight="1">' +
      headers.map(function(header, i) {
        return productosExcelCell(productosExcelColName(i) + headerRow, header, 3, false);
      }).join('') +
    '</row>'
  );

  rows.forEach(function(r, idx) {
    var rr = firstDataRow + idx;
    var values = [
      productosValor(r.nombre, ''),
      productosValor(r.barCode, ''),
      productosValor(r.categoria, ''),
      productosValor(r.medida, ''),
      productosToNumber(r.precio_compra),
      productosToNumber(r.precio_venta),
      productosToNumber(r.porcentaje_venta),
      productosToNumber(r.precio_mayoreo),
      (String(r.isv_venta || '').toLowerCase() === 'si' || Number(r.isv_venta) === 1) ? 'Sí' : 'No',
      productosValor(r.almacen, ''),
      Number(r.estado) === 1 ? 'Activo' : 'Inactivo'
    ];

    var cells = values.map(function(value, col) {
      var numeric = col >= 4 && col <= 7;
      var style = numeric ? 11 : 4;

      if (col === 10) {
        style = String(value).toLowerCase() === 'activo' ? 9 : 10;
      }

      return productosExcelCell(
        productosExcelColName(col) + rr,
        value,
        style,
        numeric
      );
    }).join('');

    sheetRows.push('<row r="' + rr + '" ht="22" customHeight="1">' + cells + '</row>');
  });

  var sheetXml =
    '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
    '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
      '<dimension ref="A1:' + lastCol + lastRow + '"/>' +
      '<sheetViews><sheetView workbookViewId="0" showGridLines="0">' +
        '<pane ySplit="7" topLeftCell="A8" activePane="bottomLeft" state="frozen"/>' +
        '<selection pane="bottomLeft" activeCell="A8" sqref="A8"/>' +
      '</sheetView></sheetViews>' +
      '<sheetFormatPr defaultRowHeight="15"/>' +
      '<cols>' +
        '<col min="1" max="1" width="30" customWidth="1"/>' +
        '<col min="2" max="2" width="18" customWidth="1"/>' +
        '<col min="3" max="4" width="18" customWidth="1"/>' +
        '<col min="5" max="8" width="16" customWidth="1"/>' +
        '<col min="9" max="9" width="14" customWidth="1"/>' +
        '<col min="10" max="10" width="20" customWidth="1"/>' +
        '<col min="11" max="11" width="14" customWidth="1"/>' +
      '</cols>' +
      '<sheetData>' + sheetRows.join('') + '</sheetData>' +
      '<autoFilter ref="A7:K' + lastRow + '"/>' +
      '<mergeCells count="10">' +
        '<mergeCell ref="A1:K1"/>' +
        '<mergeCell ref="A2:K2"/>' +
        '<mergeCell ref="A3:C3"/><mergeCell ref="A4:C4"/>' +
        '<mergeCell ref="D3:F3"/><mergeCell ref="D4:F4"/>' +
        '<mergeCell ref="G3:I3"/><mergeCell ref="G4:I4"/>' +
        '<mergeCell ref="J3:K3"/><mergeCell ref="J4:K4"/>' +
      '</mergeCells>' +
      '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>' +
      '<pageSetup orientation="landscape" paperSize="1" fitToWidth="1" fitToHeight="0"/>' +
    '</worksheet>';

  var stylesXml =
    '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
    '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
      '<numFmts count="1"><numFmt numFmtId="164" formatCode="L #,##0.00"/></numFmts>' +
      '<fonts count="7">' +
        '<font><sz val="10"/><name val="Calibri"/><family val="2"/></font>' +
        '<font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
        '<font><sz val="9"/><color rgb="FF5E6C84"/><name val="Calibri"/></font>' +
        '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
        '<font><sz val="10"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
        '<font><b/><sz val="8"/><color rgb="FF6B778C"/><name val="Calibri"/></font>' +
        '<font><b/><sz val="15"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
      '</fonts>' +
      '<fills count="7">' +
        '<fill><patternFill patternType="none"/></fill>' +
        '<fill><patternFill patternType="gray125"/></fill>' +
        '<fill><patternFill patternType="solid"><fgColor rgb="FF17324D"/></patternFill></fill>' +
        '<fill><patternFill patternType="solid"><fgColor rgb="FF0EA5A8"/></patternFill></fill>' +
        '<fill><patternFill patternType="solid"><fgColor rgb="FFF7F9FC"/></patternFill></fill>' +
        '<fill><patternFill patternType="solid"><fgColor rgb="FFE3FCEF"/></patternFill></fill>' +
        '<fill><patternFill patternType="solid"><fgColor rgb="FFFFEBE6"/></patternFill></fill>' +
      '</fills>' +
      '<borders count="2">' +
        '<border><left/><right/><top/><bottom/><diagonal/></border>' +
        '<border><left style="thin"><color rgb="FFDDE3EA"/></left><right style="thin"><color rgb="FFDDE3EA"/></right><top style="thin"><color rgb="FFDDE3EA"/></top><bottom style="thin"><color rgb="FFDDE3EA"/></bottom><diagonal/></border>' +
      '</borders>' +
      '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' +
      '<cellXfs count="12">' +
        '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' +
        '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>' +
        '<xf numFmtId="0" fontId="2" fillId="4" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>' +
        '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
        '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' +
        '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
        '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
        '<xf numFmtId="0" fontId="6" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
        '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' +
        '<xf numFmtId="0" fontId="4" fillId="5" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
        '<xf numFmtId="0" fontId="4" fillId="6" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
        '<xf numFmtId="164" fontId="4" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>' +
      '</cellXfs>' +
      '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>' +
    '</styleSheet>';

  var workbookXml =
    '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
    '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
      '<bookViews><workbookView activeTab="0"/></bookViews>' +
      '<sheets><sheet name="Productos" sheetId="1" r:id="rId1"/></sheets>' +
    '</workbook>';

  var workbookRels =
    '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
    '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
      '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' +
      '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' +
    '</Relationships>';

  var rootRels =
    '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
    '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
      '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' +
    '</Relationships>';

  var contentTypes =
    '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
    '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' +
      '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' +
      '<Default Extension="xml" ContentType="application/xml"/>' +
      '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' +
      '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' +
      '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' +
    '</Types>';

  var zip = new JSZip();

  zip.file('[Content_Types].xml', contentTypes);
  zip.folder('_rels').file('.rels', rootRels);
  zip.folder('xl').file('workbook.xml', workbookXml);
  zip.folder('xl').file('styles.xml', stylesXml);
  zip.folder('xl').folder('_rels').file('workbook.xml.rels', workbookRels);
  zip.folder('xl').folder('worksheets').file('sheet1.xml', sheetXml);

  var opciones = {
    type: 'blob',
    mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    compression: 'DEFLATE'
  };

  if (typeof zip.generateAsync === 'function') {
    return zip.generateAsync(opciones);
  }

  if (typeof zip.generate === 'function') {
    try {
      return Promise.resolve(zip.generate(opciones));
    } catch (errorGenerate) {
      console.error('Error generando XLSX Productos con JSZip legado:', errorGenerate);
      return Promise.reject(errorGenerate);
    }
  }

  return Promise.reject(new Error('La versión de JSZip no soporta generateAsync() ni generate().'));
}

function productosExportarExcel() {
  var rows = productosUIState.filtered || [];

  if (!rows.length) {
    showNotify('warning', 'Sin información', 'No hay productos para exportar.');
    return;
  }

  var promesa = productosGenerarXlsx(rows);

  if (!promesa) {
    showNotify('error', 'Excel no disponible', 'JSZip no está disponible en esta pantalla.');
    return;
  }

  promesa.then(function(blob) {
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');

    a.href = url;
    a.download = 'Reporte_Productos.xlsx';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

    setTimeout(function() {
      URL.revokeObjectURL(url);
    }, 1000);
  }).catch(function(error) {
    console.error('Error al generar Excel Productos:', error);
    showNotify('error', 'Error al generar Excel', 'No se pudo generar el archivo Excel.');
  });
}

/* =========================================================
   PDF PREMIUM
   ========================================================= */

function productosObtenerLogoPdf(callback) {
  if (typeof imagen === 'string' && imagen.indexOf('data:image/') === 0) {
    callback(imagen);
    return;
  }

  $.ajax({
    type: 'GET',
    url: '<?php echo SERVERURL;?>core/get_image.php',
    dataType: 'text',
    timeout: 15000
  }).done(function(imageUrl) {
    imageUrl = $.trim(imageUrl || '');

    if (!imageUrl) {
      showNotify('error', 'Logo no disponible', 'No se pudo obtener el logo para el reporte PDF.');
      return;
    }

    var img = new Image();
    img.crossOrigin = 'Anonymous';

    img.onload = function() {
      try {
        var canvas = document.createElement('canvas');
        var ctx = canvas.getContext('2d');

        canvas.width = img.naturalWidth || img.width;
        canvas.height = img.naturalHeight || img.height;
        ctx.drawImage(img, 0, 0);

        var dataUrl = canvas.toDataURL('image/png');

        if (!dataUrl || dataUrl.indexOf('data:image/') !== 0) {
          throw new Error('El logo no pudo convertirse a Data URL.');
        }

        imagen = dataUrl;
        callback(dataUrl);
      } catch (error) {
        console.error('Error preparando logo PDF Productos:', error);
        showNotify('error', 'Logo no disponible', 'No se pudo preparar el logo para el reporte PDF.');
      }
    };

    img.onerror = function() {
      showNotify('error', 'Logo no disponible', 'No se pudo cargar el logo para el reporte PDF.');
    };

    img.src = imageUrl;
  }).fail(function(xhr) {
    console.error('Error obteniendo logo PDF Productos:', xhr.responseText);
    showNotify('error', 'Logo no disponible', 'No se pudo obtener el logo para el reporte PDF.');
  });
}

function productosExportarPdf() {
  var rows = productosUIState.filtered || [];

  if (!rows.length) {
    showNotify('warning', 'Sin información', 'No hay productos para mostrar en PDF.');
    return;
  }

  if (typeof pdfMake === 'undefined' || typeof abrirModalPdfPublico !== 'function') {
    showNotify(
      'error',
      'PDF no disponible',
      'No se encontraron los componentes necesarios para previsualizar el PDF.'
    );
    return;
  }

  productosObtenerLogoPdf(function(logoDataUrl) {
    var activos = rows.filter(function(r) { return Number(r.estado) === 1; }).length;
    var conIsv = rows.filter(function(r) {
      return String(r.isv_venta || '').toLowerCase() === 'si' || Number(r.isv_venta) === 1;
    }).length;
    var totalVenta = rows.reduce(function(acc, r) {
      return acc + productosToNumber(r.precio_venta);
    }, 0);

    var content = [
      {
        table: {
          widths: [100, '*', 160],
          body: [[
            {
              table: {
                widths: ['*'],
                body: [[{
                  image: logoDataUrl,
                  fit: [62, 36],
                  alignment: 'center',
                  margin: [7, 5, 7, 5],
                  fillColor: '#FFFFFF'
                }]]
              },
              layout: {
                hLineColor: function() { return '#DDE3EA'; },
                vLineColor: function() { return '#DDE3EA'; },
                hLineWidth: function() { return .5; },
                vLineWidth: function() { return .5; },
                paddingLeft: function() { return 0; },
                paddingRight: function() { return 0; },
                paddingTop: function() { return 0; },
                paddingBottom: function() { return 0; }
              },
              fillColor: '#17324D',
              margin: [8, 7, 8, 7]
            },
            {
              stack: [
                { text: 'REPORTE DE PRODUCTOS', bold: true, fontSize: 16, color: '#FFFFFF' },
                { text: 'Catálogo general, precios, impuestos y control', fontSize: 7.5, color: '#D8E5F0', margin: [0, 2, 0, 0] }
              ],
              fillColor: '#17324D',
              margin: [0, 10, 0, 10]
            },
            {
              stack: [
                { text: 'REPORTE EJECUTIVO', bold: true, fontSize: 6.5, color: '#72E2E5', alignment: 'right' },
                { text: new Date().toLocaleDateString('es-HN'), bold: true, fontSize: 9, color: '#FFFFFF', alignment: 'right' },
                { text: rows.length + ' registro(s) filtrado(s)', fontSize: 6.5, color: '#D8E5F0', alignment: 'right' }
              ],
              fillColor: '#17324D',
              margin: [0, 10, 12, 10]
            }
          ]]
        },
        layout: 'noBorders',
        margin: [0, 0, 0, 10]
      },
      {
        table: {
          widths: ['*'],
          body: [[{
            text:
              'Filtros aplicados: Estado ' + ($('#estado_producto option:selected').text() || 'Todos') +
              ' | Categoría ' + ($('#categoria_producto_filtro option:selected').text() || 'Todas') +
              ' | ISV ' + ($('#isv_producto_filtro option:selected').text() || 'Todos') +
              ' | Búsqueda: ' + ($.trim($('#buscar_productos_general').val()) || 'Sin búsqueda'),
            fontSize: 7,
            color: '#52627A',
            fillColor: '#F7F9FC',
            margin: [8, 7, 8, 7]
          }]]
        },
        layout: {
          hLineColor: function() { return '#DDE3EA'; },
          vLineColor: function() { return '#DDE3EA'; },
          hLineWidth: function() { return .6; },
          vLineWidth: function() { return .6; }
        },
        margin: [0, 0, 0, 10]
      },
      {
        table: {
          widths: ['*', '*', '*', '*'],
          body: [[
            {
              stack: [
                {text: 'REGISTROS', fontSize: 6.3, bold: true, color: '#6B778C'},
                {text: String(rows.length), fontSize: 13, bold: true, color: '#172B4D'}
              ],
              fillColor: '#F7F9FC', margin: [8, 7, 8, 7]
            },
            {
              stack: [
                {text: 'ACTIVOS', fontSize: 6.3, bold: true, color: '#6B778C'},
                {text: String(activos), fontSize: 13, bold: true, color: '#14804A'}
              ],
              fillColor: '#F7F9FC', margin: [8, 7, 8, 7]
            },
            {
              stack: [
                {text: 'CON ISV', fontSize: 6.3, bold: true, color: '#6B778C'},
                {text: String(conIsv), fontSize: 13, bold: true, color: '#2F9DDD'}
              ],
              fillColor: '#F7F9FC', margin: [8, 7, 8, 7]
            },
            {
              stack: [
                {text: 'VALOR VENTA', fontSize: 6.3, bold: true, color: '#6B778C'},
                {text: productosFormatoDinero(totalVenta), fontSize: 13, bold: true, color: '#6554C0'}
              ],
              fillColor: '#F7F9FC', margin: [8, 7, 8, 7]
            }
          ]]
        },
        layout: {
          hLineColor: function() { return '#DDE3EA'; },
          vLineColor: function() { return '#DDE3EA'; },
          hLineWidth: function() { return .6; },
          vLineWidth: function() { return .6; }
        },
        margin: [0, 0, 0, 12]
      }
    ];

    if (productosUIState.view === 'miniatura') {
      content.push({
        text: 'VISTA MINIATURA',
        bold: true,
        fontSize: 7,
        color: '#17324D',
        margin: [0, 0, 0, 7]
      });

      for (var i = 0; i < rows.length; i += 2) {
        var makeCard = function(r) {
          return {
            table: {
              widths: ['*'],
              body: [[{
                stack: [
                  {text: productosValor(r.nombre, 'Sin nombre'), bold: true, fontSize: 10, color: '#172B4D'},
                  {text: 'Código: ' + productosValor(r.barCode, 'Sin código'), fontSize: 7, color: '#6B778C', margin: [0, 2, 0, 5]},
                  {text: 'Categoría: ' + productosValor(r.categoria, 'Sin categoría'), fontSize: 7, color: '#253858'},
                  {text: 'Medida: ' + productosValor(r.medida, 'Sin medida'), fontSize: 7, color: '#253858', margin: [0, 2, 0, 0]},
                  {text: 'Compra: ' + productosFormatoDinero(r.precio_compra), fontSize: 7, color: '#253858', margin: [0, 2, 0, 0]},
                  {text: 'Venta: ' + productosFormatoDinero(r.precio_venta), fontSize: 7, color: '#253858', margin: [0, 2, 0, 0]},
                  {text: Number(r.estado) === 1 ? 'Activo' : 'Inactivo', fontSize: 7, bold: true, color: Number(r.estado) === 1 ? '#14804A' : '#C9372C', margin: [0, 4, 0, 0]}
                ],
                margin: [9, 8, 9, 8]
              }]]
            },
            layout: {
              hLineColor: function() { return '#DDE3EA'; },
              vLineColor: function() { return '#DDE3EA'; },
              hLineWidth: function() { return .6; },
              vLineWidth: function() { return .6; }
            }
          };
        };

        content.push({
          columns: [
            {width: '*', stack: [makeCard(rows[i])]},
            {width: 10, text: ''},
            rows[i + 1] ? {width: '*', stack: [makeCard(rows[i + 1])]} : {width: '*', text: ''}
          ],
          margin: [0, 0, 0, 8]
        });
      }
    } else {
      content.push({
        text: 'VISTA DETALLE',
        bold: true,
        fontSize: 7,
        color: '#17324D',
        margin: [0, 0, 0, 7]
      });

      var body = [[
        {text: 'PRODUCTO', style: 'th', fillColor: '#17324D'},
        {text: 'CÓDIGO', style: 'th', fillColor: '#17324D'},
        {text: 'CATEGORÍA', style: 'th', fillColor: '#17324D'},
        {text: 'COMPRA', style: 'th', fillColor: '#17324D'},
        {text: 'VENTA', style: 'th', fillColor: '#17324D'},
        {text: 'ISV', style: 'th', fillColor: '#17324D'},
        {text: 'ALMACÉN', style: 'th', fillColor: '#17324D'},
        {text: 'ESTADO', style: 'th', fillColor: '#17324D'}
      ]];

      rows.forEach(function(r, index) {
        var fill = index % 2 === 0 ? '#FFFFFF' : '#F7F9FC';

        body.push([
          {text: productosValor(r.nombre, ''), style: 'td', fillColor: fill},
          {text: productosValor(r.barCode, 'Sin código'), style: 'td', fillColor: fill},
          {text: productosValor(r.categoria, ''), style: 'td', fillColor: fill},
          {text: productosFormatoDinero(r.precio_compra), style: 'tdMoney', fillColor: fill},
          {text: productosFormatoDinero(r.precio_venta), style: 'tdMoney', fillColor: fill},
          {text: (String(r.isv_venta || '').toLowerCase() === 'si' || Number(r.isv_venta) === 1) ? 'Sí' : 'No', style: 'tdCenter', fillColor: fill},
          {text: productosValor(r.almacen, ''), style: 'td', fillColor: fill},
          {
            text: Number(r.estado) === 1 ? 'Activo' : 'Inactivo',
            style: 'tdCenter',
            bold: true,
            color: Number(r.estado) === 1 ? '#14804A' : '#C9372C',
            fillColor: fill
          }
        ]);
      });

      content.push({
        table: {
          headerRows: 1,
          widths: [130, 75, 90, 70, 70, 45, 80, 55],
          body: body
        },
        layout: {
          hLineColor: function() { return '#DDE3EA'; },
          vLineColor: function() { return '#DDE3EA'; },
          hLineWidth: function() { return .55; },
          vLineWidth: function() { return .55; },
          paddingLeft: function() { return 5; },
          paddingRight: function() { return 5; },
          paddingTop: function() { return 6; },
          paddingBottom: function() { return 6; }
        }
      });
    }

    var doc = {
      pageSize: 'LETTER',
      pageOrientation: 'landscape',
      pageMargins: [28, 28, 28, 34],
      header: function() {
        return {
          margin: [28, 12, 28, 0],
          canvas: [{
            type: 'line',
            x1: 0, y1: 0, x2: 736, y2: 0,
            lineWidth: 2,
            lineColor: '#0EA5A8'
          }]
        };
      },
      footer: function(currentPage, pageCount) {
        return {
          margin: [28, 8, 28, 0],
          columns: [
            {text: 'IZZY • Productos', fontSize: 7, color: '#7A869A'},
            {text: 'Página ' + currentPage + ' de ' + pageCount, fontSize: 7, color: '#7A869A', alignment: 'right'}
          ]
        };
      },
      content: content,
      styles: {
        th: {fontSize: 6.2, bold: true, color: '#FFFFFF', alignment: 'center'},
        td: {fontSize: 6.3, color: '#253858'},
        tdCenter: {fontSize: 6.3, color: '#253858', alignment: 'center'},
        tdMoney: {fontSize: 6.3, color: '#253858', alignment: 'right'}
      }
    };

    pdfMake.createPdf(doc).getDataUrl(function(url) {
      abrirModalPdfPublico(
        url,
        'Reporte de Productos',
        'Reporte_Productos.pdf'
      );
    });
  });
}

function productosInicializarEventosUI() {
  $('#form_main_productos')
    .off('submit.productosUI')
    .on('submit.productosUI', function(e) {
      e.preventDefault();
      productosUIState.page = 1;
      listar_productos();
    });

  $('#form_main_productos')
    .off('reset.productosUI')
    .on('reset.productosUI', function() {
      var form = this;

      setTimeout(function() {
        $(form).find('.selectpicker').val('').selectpicker('refresh');
        $('#estado_producto').val('1').selectpicker('refresh');
        productosUIState.search = '';
        $('#buscar_productos_general').val('');
        productosUIState.page = 1;
        listar_productos();
      }, 0);
    });

  $('#buscar_productos_general')
    .off('input.productosUI')
    .on('input.productosUI', function() {
      productosUIState.search = $(this).val() || '';
      productosUIState.page = 1;
      productosAplicarFiltrosUI();
    });

  $('#limpiarBuscarProductos')
    .off('click.productosUI')
    .on('click.productosUI', function() {
      productosUIState.search = '';
      $('#buscar_productos_general').val('').focus();
      productosUIState.page = 1;
      productosAplicarFiltrosUI();
    });

  $('#productosPageSize')
    .off('change.productosUI')
    .on('change.productosUI', function() {
      var n = parseInt($(this).val(), 10);
      if (!n || n < 1) return;

      productosUIState.pageSize = n;

      if (productosUIState.view === 'miniatura') {
        productosUIState.pageSizeMiniatura = n;
      } else {
        productosUIState.pageSizeDetalle = n;
      }

      productosUIState.page = 1;
      productosRender();
    });

  $('.productos-view-btn')
    .off('click.productosUI')
    .on('click.productosUI', function() {
      productosCambiarVista($(this).data('view'));
    });

  $('#productosPaginacion')
    .off('click.productosUI', '.productos-page-btn')
    .on('click.productosUI', '.productos-page-btn', function() {
      if (this.disabled) return;

      var p = parseInt($(this).attr('data-page'), 10);
      if (!p) return;

      productosUIState.page = p;
      productosRender();
    });

  $('#btnActualizarProductos').off('click.productosUI').on('click.productosUI', listar_productos);

  $('#btnNuevoProducto').off('click.productosUI').on('click.productosUI', function() {
    if (typeof modal_productos === 'function') {
      modal_productos();
    } else {
      showNotify('error', 'Error', 'No se encontró la función para registrar productos.');
    }
  });

  $('#btnExcelProductos').off('click.productosUI').on('click.productosUI', productosExportarExcel);
  $('#btnPdfProductos').off('click.productosUI').on('click.productosUI', productosExportarPdf);

  $('#productosListado')
    .off('click.productosUI')
    .on('click.productosUI', '.productos-ver-action', function(e) {
      e.preventDefault();
      productosVerDetalle(productosRowPorId($(this).data('id')));
    })
    .on('click.productosUI', '.productos-editar-action', function(e) {
      e.preventDefault();
      productosEditar(productosRowPorId($(this).data('id')));
    })
    .on('click.productosUI', '.productos-eliminar-action', function(e) {
      e.preventDefault();
      productosEliminar(productosRowPorId($(this).data('id')));
    });

  var responsiveTimer = null;

  $(window)
    .off('resize.productosResponsive orientationchange.productosResponsive')
    .on('resize.productosResponsive orientationchange.productosResponsive', function() {
      clearTimeout(responsiveTimer);

      responsiveTimer = setTimeout(function() {
        var objetivo = productosEsMovil()
          ? 'miniatura'
          : productosUIState.preferredView;

        productosActualizarDisponibilidadVista();

        if (productosUIState.view !== objetivo) {
          productosUIState.view = objetivo;
          productosUIState.page = 1;
          productosActualizarBotonesVista();
          productosSincronizarPageSize();
          productosRender();
        }
      }, 120);
    });
}

$(function() {
  if (!$('#productosListado').length) return;

  productosConfigurarPanel(
    '#btnToggleFiltrosProductos',
    '#productosFiltrosContenido',
    PRODUCTOS_STORAGE_FILTROS,
    true
  );

  productosConfigurarPanel(
    '#btnToggleKpisProductos',
    '#productosKpisContenido',
    PRODUCTOS_STORAGE_KPIS,
    true
  );

  productosInicializarVista();
  productosInicializarEventosUI();
});



function evaluarCategoria() {
  if ($('#formProductos #tipo_producto').find('option:selected').text() == "Servicio") {
    $('#formProductos #cantidad').prop('readonly', true);
    $('#formProductos #precio_compra').prop('readonly', false);
    $('#formProductos #precio_venta').prop('readonly', false);
    $('#formProductos #precio_mayoreo').prop('readonly', false);
    $('#formProductos #cantidad_minima, #formProductos #cantidad_maxima').prop('readonly', true);
    $('#formProductos #cantidad').val(1);
    $('#formProductos #precio_compra').val(0);
  } else if ($('#formProductos #tipo_producto').find('option:selected').text() == "Insumos") {
    $('#formProductos #cantidad').prop('readonly', false);
    $('#formProductos #precio_compra').prop('readonly', false);
    $('#formProductos #precio_venta, #formProductos #precio_mayoreo').prop('readonly', true);
    $('#formProductos #cantidad_minima, #formProductos #cantidad_maxima').prop('readonly', false);
    $('#formProductos #cantidad').val(1);
    $('#formProductos #precio_venta, #formProductos #precio_mayoreo').val(0);
    $('#formProductos #cantidad, #formProductos #precio_compra, #formProductos #precio_venta, #formProductos #precio_mayoreo, #formProductos #cantidad_minima, #formProductos #cantidad_maxima').prop('readonly', false);
    $('#formProductos #cantidad, #formProductos #precio_compra').val('');
  }
}


function evaluarCategoriaDetalle(TipoProducto) {
  if (TipoProducto == "Servicio") {
    $('#formProductos #cantidad').prop('readonly', true);
    $('#formProductos #precio_compra').prop('readonly', true);
    $('#formProductos #precio_venta, #formProductos #precio_mayoreo').prop('readonly', false);
    $('#formProductos #cantidad_minima, #formProductos #cantidad_maxima').prop('readonly', true);
    $('#formProductos #cantidad').val(1);
    $('#formProductos #precio_compra').val(0);
  } else if (TipoProducto == "Insumos") {
    $('#formProductos #cantidad, #formProductos #precio_compra').prop('readonly', false);
    $('#formProductos #precio_venta, #formProductos #precio_mayoreo').prop('readonly', true);
    $('#formProductos #cantidad_minima, #formProductos #cantidad_maxima').prop('readonly', false);
    $('#formProductos #concentracion').val("");
    $('#formProductos #cantidad').val(1);
    $('#formProductos #precio_venta').val(0);
  } else {
    $('#formProductos #cantidad, #formProductos #precio_compra, #formProductos #precio_venta, #formProductos #precio_mayoreo, #formProductos #cantidad_minima, #formProductos #cantidad_maxima').prop('readonly', false);
    $('#formProductos #cantidad, #formProductos #precio_compra').val('');
  }
}


// ====== AJAX para llenar combos ======
function getEstadoProducto() {
  const url = '<?php echo SERVERURL;?>core/getEstado.php';

  return $.ajax({
    type: "POST",
    url: url,
    async: true
  })
  .then(function(data) {
    $('#form_main_productos #estado_producto').html(data).selectpicker('refresh');
  });
}

function getCategoriasProducto() {
  const url = '<?php echo SERVERURL;?>core/getTipoProductoMovimientos.php';

  return $.ajax({
    type: "POST",
    url: url,
    async: true
  })
  .then(function(data) {
    $('#form_main_productos #categoria_producto_filtro').html(data).selectpicker('refresh');
  });
}

function getEmpresaProductos() {
  return Promise.resolve();
}
</script>