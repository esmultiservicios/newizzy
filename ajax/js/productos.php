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

    $('#form_main_productos #buscar_productos_general').off('keyup.productos');
    $('#form_main_productos #buscar_productos_general').on('keyup.productos', function() {
      if ($.fn.DataTable.isDataTable("#dataTableProductos")) {
        $("#dataTableProductos").DataTable().ajax.reload(null, false);
      }
    });

    $('#form_main_productos #categoria_producto_filtro, #form_main_productos #isv_producto_filtro').off('changed.bs.select.productos change.productos');
    $('#form_main_productos #categoria_producto_filtro, #form_main_productos #isv_producto_filtro').on('changed.bs.select.productos change.productos', function() {
      if ($.fn.DataTable.isDataTable("#dataTableProductos")) {
        $("#dataTableProductos").DataTable().ajax.reload(null, false);
      }
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
  const $isvFactura = $('#formProductos #producto_isv_factura');
  const $isv1 = $('#formProductos #producto_isv1');
  const $isv2 = $('#formProductos #producto_isv2');

  $isv1.off('change.isv');
  $isv2.off('change.isv');
  $isvFactura.off('change.isvmain');

  $isv1.on('change.isv', function() {
    if (this.checked) {
      $isv2.prop('checked', false);
    }
  });

  $isv2.on('change.isv', function() {
    if (this.checked) {
      $isv1.prop('checked', false);
    }
  });

  function applyIsvMainState() {
    const enabled = $isvFactura.is(':checked');

    if (!enabled) {
      $isv1.prop('checked', false);
      $isv2.prop('checked', false);
    }

    $isv1.prop('disabled', !enabled);
    $isv2.prop('disabled', !enabled);
  }

  $isvFactura.on('change.isvmain', applyIsvMainState);
  applyIsvMainState();
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
      alert("Primero debe seleccionar o editar un producto existente para cambiar el código de barra.");
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

        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#dataTableProductos')) {
          $('#dataTableProductos').DataTable().ajax.reload(null, false);
        }

        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#DatatableProductos')) {
          $('#DatatableProductos').DataTable().ajax.reload(null, false);
        }

        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#tablaProductos')) {
          $('#tablaProductos').DataTable().ajax.reload(null, false);
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
        alert('Selecciona una imagen válida (JPG, PNG, GIF)');
      }

      resetImage();
      return;
    }

    if (file.size > 2 * 1024 * 1024) {
      if (typeof showNotify === 'function') {
        showNotify('error', 'Error', 'La imagen no debe exceder 2MB');
      } else {
        alert('La imagen no debe exceder 2MB');
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
   HEADER DINÁMICO - PRODUCTOS
   ========================================================= */
function construirHeaderDataTableProductos() {
  var $tabla = $("#dataTableProductos");

  $tabla.empty();

  $tabla.append(
    '<thead>' +
      '<tr>' +
        '<th>Acciones</th>' +
        '<th>Producto</th>' +
        '<th>Precios</th>' +
        '<th>Impuestos</th>' +
        '<th>Reglas / Control</th>' +
        '<th>Estado</th>' +
      '</tr>' +
    '</thead>'
  );
}


/* =========================
   DataTable: Productos
   ========================= */
var listar_productos = function() {
  var estado = $('#form_main_productos #estado_producto').val() === ""
    ? 1
    : $('#form_main_productos #estado_producto').val();

  if ($.fn.DataTable.isDataTable("#dataTableProductos")) {
    $("#dataTableProductos").DataTable().clear().destroy();
  }

  construirHeaderDataTableProductos();

  var table_productos = $("#dataTableProductos").DataTable({
    destroy: true,
    processing: true,
    responsive: false,
    autoWidth: false,
    scrollX: false,
    stateSave: true,
    language: idioma_español,
    lengthMenu: lengthMenu10,
    dom: dom,

    ajax: {
      method: "POST",
      url: "<?php echo SERVERURL;?>core/llenarDataTableProductos.php",
      dataType: 'json',
      data: function(d) {
        d.estado = estado;
      },
      error: function(xhr, textStatus, error) {
        console.error('[DataTables AJAX error]', textStatus, error, xhr.status, xhr.responseText);

        if (typeof showNotify === 'function') {
          showNotify('error', 'Error', 'No se pudieron cargar los productos');
        } else {
          alert('Error cargando productos: ' + xhr.status);
        }
      },
      dataSrc: function(json) {
        var rows = [];

        if (json && Array.isArray(json.data)) {
          rows = json.data;
        } else if (json && Array.isArray(json.aaData)) {
          rows = json.aaData;
        } else if (Array.isArray(json)) {
          rows = json;
        }

        rows = productosFiltrarRows(rows);
        productosActualizarResumen(rows);

        return rows;
      }
    },

    columns: [
      {
        data: null,
        orderable: false,
        searchable: false,
        className: "text-center text-nowrap align-middle productos-acciones-cell",
        render: function(data, type, row) {
          if (type !== 'display') {
            return '';
          }

          return '' +
            '<div class="dropdown acciones-dropdown">' +

              '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                '<i class="fas fa-cog"></i>' +
                '<span>Acciones</span>' +
              '</button>' +

              '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +

                '<button type="button" class="dropdown-item accion-item accion-editar table_editar">' +
                  '<span class="accion-icon accion-icon-editar">' +
                    '<i class="fas fa-edit"></i>' +
                  '</span>' +
                  '<span class="accion-label">Editar</span>' +
                '</button>' +

                '<button type="button" class="dropdown-item accion-item accion-eliminar table_eliminar">' +
                  '<span class="accion-icon accion-icon-eliminar">' +
                    '<i class="fas fa-trash-alt"></i>' +
                  '</span>' +
                  '<span class="accion-label">Eliminar</span>' +
                '</button>' +

              '</div>' +

            '</div>';
        }
      },

      {
        data: null,
        className: "align-middle productos-producto-cell",
        render: function(data, type, row) {
          var defaultImageUrl = '<?php echo SERVERURL;?>vistas/plantilla/img/products/image_preview.png';
          var imageUrl = row.image ? ('<?php echo SERVERURL;?>vistas/plantilla/img/products/' + row.image) : defaultImageUrl;

          var nombre = productosEscape(row.nombre);
          var descripcion = productosEscape(productosValor(row.descripcion, 'Sin descripción registrada'));
          var categoria = productosEscape(row.categoria);
          var medida = productosEscape(row.medida);
          var barcode = productosEscape(productosValor(row.barCode, 'Sin código'));

          if (type !== 'display') {
            return nombre + ' ' + descripcion + ' ' + categoria + ' ' + medida + ' ' + barcode;
          }

          return '' +
            '<div class="productos-product-box">' +
              '<div class="productos-product-img-box">' +
                '<a href="#" class="iv-trigger productos-zoom-trigger" data-iv-src="' + imageUrl + '" data-iv-fallback="' + defaultImageUrl + '" data-iv-title="' + nombre + '">' +
                  '<img class="productos-product-img table-image" src="' + imageUrl + '" alt="' + nombre + '" loading="lazy" onerror="this.onerror=null;this.src=\'' + defaultImageUrl + '\';">' +
                '</a>' +
              '</div>' +
              '<div class="productos-product-info">' +
                '<h6 class="productos-product-name">' + nombre + '</h6>' +
                '<div class="productos-product-desc">' + descripcion + '</div>' +
                '<div class="productos-product-meta">' +
                  '<span><i class="fas fa-barcode mr-1"></i>' + barcode + '</span>' +
                  '<span><i class="fas fa-layer-group mr-1"></i>' + categoria + '</span>' +
                  '<span><i class="fas fa-ruler-combined mr-1"></i>' + medida + '</span>' +
                '</div>' +
                '<button type="button" class="btn btn-sm ver_detalle_producto productos-detail-btn" title="Ver más información del producto">' +
                  '<i class="fas fa-info-circle mr-1"></i> Ver detalles' +
                '</button>' +
              '</div>' +
            '</div>';
        }
      },

      {
        data: null,
        className: "align-middle productos-precios-cell",
        render: function(data, type, row) {
          var compra = productosToNumber(row.precio_compra);
          var venta = productosToNumber(row.precio_venta);
          var ganancia = productosToNumber(row.porcentaje_venta);
          var mayoreo = productosToNumber(row.precio_mayoreo);

          if (type !== 'display') {
            return compra + ' ' + venta + ' ' + ganancia + ' ' + mayoreo;
          }

          return '' +
            '<div class="productos-detail-list">' +
              '<div class="productos-detail-item">' +
                '<span class="productos-detail-icon productos-icon-compra"><i class="fas fa-shopping-cart"></i></span>' +
                '<span><strong>Compra:</strong> ' + productosBadgeDinero(compra, 'compra') + '</span>' +
              '</div>' +
              '<div class="productos-detail-item">' +
                '<span class="productos-detail-icon productos-icon-venta"><i class="fas fa-tag"></i></span>' +
                '<span><strong>Venta:</strong> ' + productosBadgeDinero(venta, 'venta') + '</span>' +
              '</div>' +
              '<div class="productos-detail-item">' +
                '<span class="productos-detail-icon productos-icon-ganancia"><i class="fas fa-coins"></i></span>' +
                '<span><strong>Ganancia:</strong> ' + productosBadgeDinero(ganancia, 'ganancia') + '</span>' +
              '</div>' +
              '<div class="productos-detail-item">' +
                '<span class="productos-detail-icon productos-icon-mayoreo"><i class="fas fa-boxes"></i></span>' +
                '<span><strong>Mayoreo:</strong> ' + productosBadgeDinero(mayoreo, 'mayoreo') + '</span>' +
              '</div>' +
            '</div>';
        }
      },

      {
        data: null,
        className: "align-middle productos-impuestos-cell",
        render: function(data, type, row) {
          var isvVenta = productosIsvBadge(row);
          var isvCompra = String(row.isv_compra || '').toLowerCase() === 'si' || parseInt(row.isv_compra || 0, 10) === 1;
          var restaurante = parseInt(row.restaurante || 0, 10) === 1;

          if (type !== 'display') {
            return row.isv_venta + ' ' + row.isv_compra + ' ' + row.isv1 + ' ' + row.isv2 + ' ' + row.restaurante;
          }

          return '' +
            '<div class="productos-detail-list">' +
              '<div class="productos-detail-item">' +
                '<span class="productos-detail-icon productos-icon-isv"><i class="fas fa-percent"></i></span>' +
                '<span><strong>ISV venta:</strong> ' + isvVenta + '</span>' +
              '</div>' +
              '<div class="productos-detail-item">' +
                '<span class="productos-detail-icon productos-icon-isv"><i class="fas fa-file-invoice-dollar"></i></span>' +
                '<span><strong>ISV compra:</strong> ' +
                  (
                    isvCompra
                      ? '<span class="productos-isv-badge productos-isv-si"><i class="fas fa-check-circle"></i> Sí</span>'
                      : '<span class="productos-isv-badge productos-isv-no"><i class="fas fa-times-circle"></i> No</span>'
                  ) +
                '</span>' +
              '</div>' +
              '<div class="productos-detail-item">' +
                '<span class="productos-detail-icon productos-icon-restaurante"><i class="fas fa-utensils"></i></span>' +
                '<span><strong>Restaurante:</strong> ' +
                  (
                    restaurante
                      ? '<span class="productos-isv-badge productos-isv-si"><i class="fas fa-check-circle"></i> Sí</span>'
                      : '<span class="productos-isv-badge productos-isv-no"><i class="fas fa-times-circle"></i> No</span>'
                  ) +
                '</span>' +
              '</div>' +
            '</div>';
        }
      },

      {
        data: null,
        className: "align-middle productos-control-cell",
        render: function(data, type, row) {
          var minima = productosToNumber(row.cantidad_minima);
          var maxima = productosToNumber(row.cantidad_maxima);
          var cantidadMayoreo = productosToNumber(row.cantidad_mayoreo);
          var superior = productosValor(row.producto_superior, 'No registrado');
          var almacen = productosValor(row.almacen, 'No registrado');

          if (type !== 'display') {
            return minima + ' ' + maxima + ' ' + cantidadMayoreo + ' ' + superior + ' ' + almacen;
          }

          return '' +
            '<div class="productos-detail-list">' +
              '<div class="productos-detail-item">' +
                '<span class="productos-detail-icon productos-icon-stock"><i class="fas fa-arrow-down"></i></span>' +
                '<span><strong>Mínimo:</strong> <span class="productos-control-badge">' + minima + '</span></span>' +
              '</div>' +
              '<div class="productos-detail-item">' +
                '<span class="productos-detail-icon productos-icon-stock"><i class="fas fa-arrow-up"></i></span>' +
                '<span><strong>Máximo:</strong> <span class="productos-control-badge">' + maxima + '</span></span>' +
              '</div>' +
              '<div class="productos-detail-item">' +
                '<span class="productos-detail-icon productos-icon-stock"><i class="fas fa-boxes"></i></span>' +
                '<span><strong>Cant. mayoreo:</strong> <span class="productos-control-badge">' + cantidadMayoreo + '</span></span>' +
              '</div>' +
              '<div class="productos-detail-item">' +
                '<span class="productos-detail-icon productos-icon-almacen"><i class="fas fa-warehouse"></i></span>' +
                '<span><strong>Almacén:</strong> ' + productosEscape(almacen) + '</span>' +
              '</div>' +
            '</div>';
        }
      },

      {
        data: "estado",
        className: "text-center align-middle productos-estado-cell",
        render: function(data, type, row) {
          if (type !== 'display') {
            return data;
          }

          return productosEstadoBadge(data);
        }
      }
    ],

    order: [[1, 'asc']],

    columnDefs: [
      {
        targets: 0,
        width: "8%",
        orderable: false,
        searchable: false,
        className: "text-center text-nowrap align-middle productos-acciones-cell"
      },
      {
        targets: 1,
        width: "34%",
        className: "align-middle productos-producto-cell"
      },
      {
        targets: 2,
        width: "17%",
        className: "align-middle productos-precios-cell"
      },
      {
        targets: 3,
        width: "16%",
        className: "align-middle productos-impuestos-cell"
      },
      {
        targets: 4,
        width: "17%",
        className: "align-middle productos-control-cell"
      },
      {
        targets: 5,
        width: "8%",
        className: "text-center text-nowrap align-middle productos-estado-cell"
      }
    ],

    buttons: [
      {
        text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
        titleAttr: 'Actualizar Productos',
        className: 'table_actualizar btn btn-secondary ocultar',
        action: function() {
          listar_productos();
        }
      },
      {
        text: '<i class="fas fas fa-plus fa-lg"></i> Ingresar',
        titleAttr: 'Agregar Productos',
        className: 'table_crear btn btn-primary ocultar',
        action: function() {
          modal_productos();
        }
      },
      {
        extend: 'excelHtml5',
        text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
        titleAttr: 'Excel',
        title: 'Reporte Productos',
        messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
        className: 'table_reportes btn btn-success ocultar',
        exportOptions: {
          columns: [1, 2, 3, 4, 5]
        }
      },
      {
        extend: 'pdf',
        orientation: 'landscape',
        pageSize: 'LEGAL',
        text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
        titleAttr: 'PDF',
        title: 'Reporte Productos',
        messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
        exportOptions: {
          columns: [1, 2, 3, 4, 5]
        },
        className: 'table_reportes btn btn-danger ocultar',
        customize: function(doc) {
          if (typeof imagen !== 'undefined' && imagen) {
            doc.content.splice(0, 0, {
              image: imagen,
              width: 100,
              height: 45,
              margin: [0, 0, 0, 12]
            });
          }
        }
      }
    ],

    drawCallback: function() {
      getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());

      if (typeof cerrarDropdownAcciones === "function") {
        cerrarDropdownAcciones();
      }

      $('[title]').tooltip({
        container: 'body',
        placement: 'top'
      });
    }
  });

  table_productos.search('').draw();
  $('#buscar').focus();

  editar_producto_dataTable("#dataTableProductos tbody", table_productos);
  eliminar_producto_dataTable("#dataTableProductos tbody", table_productos);
  ver_detalle_producto_dataTable("#dataTableProductos tbody", table_productos);
};


var ver_detalle_producto_dataTable = function(tbody, table) {
  $(tbody).off("click", "button.ver_detalle_producto");

  $(tbody).on("click", "button.ver_detalle_producto", function(e) {
    e.preventDefault();

    var data = table.row($(this).parents("tr")).data();

    if (!data) {
      return;
    }

    var isvTipo = 'No aplica';

    if (parseInt(data.isv1 || 0, 10) === 1) {
      isvTipo = getTextoISVProducto(1);
    } else if (parseInt(data.isv2 || 0, 10) === 1) {
      isvTipo = getTextoISVProducto(2);
    }

    var descripcion = data.descripcion && String(data.descripcion).trim() !== ''
      ? data.descripcion
      : 'Sin descripción registrada';

    var html = '' +
      '<div class="text-left productos-modal-detalle">' +
        '<div class="mb-2"><strong>Producto:</strong><br>' + productosEscape(data.nombre) + '</div>' +
        '<div class="mb-2"><strong>Descripción:</strong><br>' + productosEscape(descripcion) + '</div>' +
        '<hr>' +
        '<div class="row">' +
          '<div class="col-md-6 mb-2"><strong>Código:</strong><br>' + productosEscape(productosValor(data.barCode, 'Sin código')) + '</div>' +
          '<div class="col-md-6 mb-2"><strong>Categoría:</strong><br>' + productosEscape(productosValor(data.categoria, 'Sin categoría')) + '</div>' +
          '<div class="col-md-6 mb-2"><strong>Medida:</strong><br>' + productosEscape(productosValor(data.medida, 'Sin medida')) + '</div>' +
          '<div class="col-md-6 mb-2"><strong>Impuesto venta:</strong><br>' + productosEscape(productosValor(data.isv_venta, 'No')) + ' - ' + productosEscape(isvTipo) + '</div>' +
          '<div class="col-md-6 mb-2"><strong>Precio compra:</strong><br>' + productosFormatoDinero(data.precio_compra) + '</div>' +
          '<div class="col-md-6 mb-2"><strong>Precio venta:</strong><br>' + productosFormatoDinero(data.precio_venta) + '</div>' +
          '<div class="col-md-6 mb-2"><strong>Ganancia:</strong><br>' + productosFormatoDinero(data.porcentaje_venta) + '</div>' +
          '<div class="col-md-6 mb-2"><strong>Precio mayoreo:</strong><br>' + productosFormatoDinero(data.precio_mayoreo) + '</div>' +
          '<div class="col-md-6 mb-2"><strong>Cantidad mayoreo:</strong><br>' + productosToNumber(data.cantidad_mayoreo) + '</div>' +
          '<div class="col-md-6 mb-2"><strong>Mínimo:</strong><br>' + productosToNumber(data.cantidad_minima) + '</div>' +
          '<div class="col-md-6 mb-2"><strong>Máximo:</strong><br>' + productosToNumber(data.cantidad_maxima) + '</div>' +
          '<div class="col-md-6 mb-2"><strong>Almacén:</strong><br>' + productosEscape(productosValor(data.almacen, 'No registrado')) + '</div>' +
          '<div class="col-md-6 mb-2"><strong>Estado:</strong><br>' + (parseInt(data.estado, 10) === 1 ? 'Activo' : 'Inactivo') + '</div>' +
        '</div>' +
      '</div>';

    swal({
      title: 'Detalle del producto',
      content: {
        element: 'div',
        attributes: {
          innerHTML: html
        }
      },
      icon: 'info',
      button: 'Cerrar'
    });
  });
};


var editar_producto_dataTable = function(tbody, table) {
  $(tbody).off("click", "button.table_editar");

  $(tbody).on("click", "button.table_editar", function() {
    var data = table.row($(this).parents("tr")).data();
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
        var datos = eval(registro);

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

            const img = document.createElement('img');
            img.src = datos[21];
            preview.appendChild(img);
            preview.style.display = 'block';

            const removeBtn = document.createElement('button');
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

          if (productoInfo) {
            productoInfo.textContent = 'Imagen cargada';
          }

          $('#formProductos #preview').attr('src', datos[21]);
        } else {
          if (preview) {
            preview.innerHTML = '';
            preview.style.display = 'none';
          }

          if (productoInfo) {
            productoInfo.textContent = 'Ningún archivo seleccionado';
          }

          $("#formProductos #preview").attr("src", "<?php echo SERVERURL;?>vistas/plantilla/img/products/image_preview.png");
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
      }
    });
  });
};


var eliminar_producto_dataTable = function(tbody, table) {
  $(tbody).off("click", "button.table_eliminar");

  $(tbody).on("click", "button.table_eliminar", function() {
    var row = $(this).parents("tr");
    var data = table.row(row).data();
    var urlDetalles = '<?php echo SERVERURL;?>core/editarProductos.php';

    var productos_id = data.productos_id;
    $('#formProductos #productos_id').val(productos_id);

    $.ajax({
      type: 'POST',
      url: urlDetalles,
      data: $('#formProductos').serialize(),
      success: function(registro) {
        var datos = eval(registro);
        var nombre = (data && (data.nombre || data.producto)) || datos[2] || 'Producto';
        var barcode = (data && (data.barCode || data.bar_code_product)) || datos[19] || '';
        var fileName = (data && (data.file || data.imagen || data.image)) || (datos[20] || '');
        var imgUrl = (datos[21] && /^https?:\/\//i.test(datos[21])) ? datos[21]
          : (fileName && fileName !== 'image_preview.png')
            ? '<?php echo SERVERURL;?>vistas/plantilla/img/products/' + fileName
            : '<?php echo SERVERURL;?>vistas/plantilla/img/products/image_preview.png';

        var cont = document.createElement('div');
        cont.style.textAlign = 'left';
        cont.innerHTML = `
          <div style="display:flex; gap:12px; align-items:center;">
            <img src="${imgUrl}" alt="Imagen" style="width:70px;height:70px;object-fit:cover;border-radius:6px;border:1px solid #e5e5e5;">
            <div>
              <div style="font-weight:600; margin-bottom:4px;">${nombre}</div>
              <div style="font-size:.9rem; color:#555;"><strong>Código de barras:</strong> ${barcode ? barcode : '&mdash;'}</div>
            </div>
          </div>
          <div style="margin-top:12px;">¿Desea eliminar permanentemente este producto?</div>`;

        swal({
          title: "Confirmar eliminación",
          content: cont,
          icon: "warning",
          buttons: {
            cancel: {
              text: "Cancelar",
              visible: true,
              className: "btn-light"
            },
            confirm: {
              text: "Sí, eliminar",
              value: true,
              className: "btn-danger",
              closeModal: false
            }
          },
          dangerMode: true,
          closeOnEsc: false,
          closeOnClickOutside: false
        }).then((confirmar) => {
          if (!confirmar) return;

          $.ajax({
            type: 'POST',
            url: '<?php echo SERVERURL;?>ajax/eliminarProductosAjax.php',
            data: {
              productos_id: productos_id
            },
            dataType: 'json',
            beforeSend: function() {
              if (typeof showLoading === 'function') {
                showLoading("Eliminando producto...");
              }
            },
            success: function(resp) {
              swal.close();

              if (resp && resp.status === "success") {
                if (typeof showNotify === 'function') {
                  showNotify("success", resp.title || "Eliminado", resp.message || "Producto eliminado");
                }

                table.ajax.reload(null, false);
                table.search('').draw();
              } else {
                if (typeof showNotify === 'function') {
                  showNotify("error", (resp && resp.title) || "Error", (resp && resp.message) || "No se pudo eliminar");
                }
              }
            },
            error: function(xhr) {
              swal.close();

              if (typeof showNotify === 'function') {
                showNotify("error", "Error", "Error al procesar la solicitud");
              }
            }
          });
        });
      },
      error: function() {
        if (typeof showNotify === 'function') {
          showNotify("error", "Error", "No se pudieron obtener los datos del producto");
        }
      }
    });
  });
};


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