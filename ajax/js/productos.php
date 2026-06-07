<script>
// ===================== INICIO APP =====================
$(function initApp() {
  // 1) Inicializa uploader
  initImageUpload();

  // 2) Carga filtros/empresa -> luego lista
  Promise.all([
    getEstadoProducto(),      // devuelve promesa
    getEmpresaProductos()     // devuelve promesa (si no usas empresa, retorna resolve())
  ])
  .then(() => listar_productos())
  .catch(err => {
    console.error('[INIT] Error inicializando:', err);
    if (typeof showNotify === 'function') {
      showNotify('error', 'Error', 'No se pudo inicializar la página de productos');
    }
  });

  // 3) Eventos UI
  $('#form_main_productos #search').on("click", function(e) {
    e.preventDefault();
    listar_productos();
  });

  $('#form_main_productos').on('reset', function() {
    $('#form_main_productos .selectpicker').val('').selectpicker('refresh');
    listar_productos();
  });

  $('#form_main_productos #buscar_productos').on('click', function(e) {
    e.preventDefault();
    listar_productos();
  });

  initISVSwitches();
});
// ===================== FIN INICIO APP =====================

/* =========================
   ISV: lógica de exclusión y habilitado
   ========================= */
  function initISVSwitches(){
    const $isvFactura = $('#formProductos #producto_isv_factura');
    const $isv1 = $('#formProductos #producto_isv1');
    const $isv2 = $('#formProductos #producto_isv2');

    // Limpia handlers previos (por si abres/cerras el modal varias veces)
    $isv1.off('change.isv');
    $isv2.off('change.isv');
    $isvFactura.off('change.isvmain');

    // Exclusión mutua
    $isv1.on('change.isv', function(){
      if (this.checked) { $isv2.prop('checked', false); }
    });
    $isv2.on('change.isv', function(){
      if (this.checked) { $isv1.prop('checked', false); }
    });

    // Si NO calcula ISV en factura → desactiva ISV1/ISV2
    function applyIsvMainState(){
      const enabled = $isvFactura.is(':checked');
      if (!enabled){
        $isv1.prop('checked', false);
        $isv2.prop('checked', false);
      }
      $isv1.prop('disabled', !enabled);
      $isv2.prop('disabled', !enabled);
    }

    $isvFactura.on('change.isvmain', applyIsvMainState);
    applyIsvMainState(); // aplica estado al cargar
  }


  // ===============================
  // EDITAR CÓDIGO DE BARRA PRODUCTO
  // ===============================
  $(document).on('click', '#grupo_editar_bacode .editar_barcode', function (e) {
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

      setTimeout(function () {
          $('#formEditarBarcode input[name="barcode"]').focus().select();
      }, 500);
  });

  // ===============================
// GENERAR CÓDIGO DE BARRA AUTOMÁTICO
// Formato: yyyymmddhhmmss
// ===============================
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

$(document).off('click', '#btnGenerarBarcode').on('click', '#btnGenerarBarcode', function(e) {
    e.preventDefault();

    const barcodeGenerado = generarBarcodeFechaHora();

    $('#formEditarBarcode input[name="barcode"]').val(barcodeGenerado).focus().select();

    if (typeof showNotify === 'function') {
        showNotify('success', 'Código generado', 'Se generó el código de barra automáticamente.');
    }
});


  // ===============================
  // GUARDAR CÓDIGO DE BARRA POR AJAX
  // ===============================
  $(document).off('submit', '#formEditarBarcode').on('submit', '#formEditarBarcode', function (e) {
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
          success: function (response) {
              if (response && (response.success === true || response.status === true)) {
                  showNotify("success", "Éxito", response.message || "Código de barra actualizado correctamente");

                  // Actualiza el campo principal del formulario de productos
                  $('#formProductos input[name="bar_code_product"]').val(response.barcode || barcode);

                  // Cierra modal
                  $('#modalEditarBarcode').modal('hide');

                  // Refresca DataTable de productos si existe
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
          error: function (xhr) {
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
          complete: function () {
              $btn.prop('disabled', false).html(textoOriginal);
          }
      });
  });


  // ===============================
  // LIMPIAR MODAL AL CERRAR
  // ===============================
  $('#modalEditarBarcode').on('hidden.bs.modal', function () {
      $('#formEditarBarcode')[0].reset();
  });

  /* Seteo seguro al abrir “Editar”
    - Soporta respuesta como array (datos[?]) o como objeto (datos.isv1/isv2)
  */
  function setISVFromData(datos, rowData){
    const $isvFactura = $('#formProductos #producto_isv_factura');
    const $isv1 = $('#formProductos #producto_isv1');           // 15%
    const $isv2 = $('#formProductos #producto_isv2');           // 18%
    const $rest = $('#formProductos #producto_restaurante');    // restaurante

    // 1) Tomar de la fila del DataTable (ahora vienen 0/1)
    let vIsv1 = rowData?.isv1;
    let vIsv2 = rowData?.isv2;
    let vRes  = rowData?.restaurante;

    // 2) Fallback: del arreglo 'datos' si tu editarProductos.php también los envía
    if (vIsv1 == null && Array.isArray(datos)) vIsv1 = datos[25]; // ajusta si difieren
    if (vIsv2 == null && Array.isArray(datos)) vIsv2 = datos[26];
    if (vRes  == null && Array.isArray(datos)) vRes  = datos[24];

    // 3) Normaliza
    vIsv1 = Number(vIsv1) === 1 ? 1 : 0;
    vIsv2 = Number(vIsv2) === 1 ? 1 : 0;
    vRes  = Number(vRes)  === 1 ? 1 : 0;

    // 4) Aplica
    $rest.prop('checked', vRes === 1);

    const on1 = vIsv1 === 1;
    const on2 = (vIsv2 === 1) && !on1;  // exclusión

    $isv1.prop('checked', on1);
    $isv2.prop('checked', on2);

    // 5) Reaplica reglas (exclusión y bloqueo por isv_factura)
    initISVSwitches();
  }

/* =========================
   Uploader de imagen (Producto)
   ========================= */
   function initImageUpload() {
  const dropArea   = document.getElementById('productoDropArea');
  const fileInput  = document.getElementById('imagen_producto');
  const preview    = document.getElementById('productoPreview');
  const fileInfo   = document.getElementById('productoInfo');

  // NUEVO: botón que dispara el chooser
  const btnSelect  = document.getElementById('btnSelectProductImage');

  // (Compat) si en algún lado aún existe un texto clickeable
  const selectLink = dropArea ? dropArea.querySelector('.select-file-text') : null;

  if (!dropArea || !fileInput || fileInput.dataset.initialized) return;
  fileInput.dataset.initialized = 'true';

  let isProcessing = false;

  // Drag & Drop
  ['dragenter','dragover','dragleave','drop'].forEach(ev =>
    dropArea.addEventListener(ev, preventDefaults, false)
  );
  ['dragenter','dragover'].forEach(ev =>
    dropArea.addEventListener(ev, () => dropArea.classList.add('drag-over'), false)
  );
  ['dragleave','drop'].forEach(ev =>
    dropArea.addEventListener(ev, () => dropArea.classList.remove('drag-over'), false)
  );
  dropArea.addEventListener('drop', e => {
    const files = e.dataTransfer?.files || [];
    if (files.length) handleFiles(files);
  });

  // Botón "Seleccionar imagen"
  const openChooser = (e) => { e.preventDefault(); e.stopPropagation(); fileInput.click(); };
  if (btnSelect) {
    btnSelect.addEventListener('click', openChooser);
    btnSelect.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') openChooser(e); });
  }
  // (Compat) por si mantienes el texto clickeable antiguo
  if (selectLink) {
    selectLink.addEventListener('click', openChooser);
    selectLink.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') openChooser(e); });
  }

  // File chooser
  fileInput.addEventListener('change', e => {
    if (isProcessing) return;
    isProcessing = true;
    handleFiles(e.target.files);
    isProcessing = false;
  });

  // Pegar desde el portapapeles
  document.addEventListener('paste', e => {
    const items = (e.clipboardData || e.originalEvent?.clipboardData)?.items || [];
    let file = null;
    for (let i = 0; i < items.length; i++) {
      if (items[i].kind === 'file' && items[i].type.startsWith('image/')) { file = items[i].getAsFile(); break; }
    }
    if (file) {
      e.preventDefault();
      const dt = new DataTransfer(); dt.items.add(file);
      handleFiles(dt.files);
    }
  });

  function preventDefaults(e) { e.preventDefault(); e.stopPropagation(); }

  function handleFiles(fileList) {
    if (!fileList || !fileList.length) return;
    const file = fileList[0];

    if (!file.type.startsWith('image/')) {
      (window.swal ? swal({ title: 'Error', text: 'Selecciona una imagen válida (JPG, PNG, GIF)', icon: 'error' }) : alert('Selecciona una imagen válida (JPG, PNG, GIF)'));
      resetImage(); return;
    }
    if (file.size > 2 * 1024 * 1024) {
      (typeof showNotify === 'function' ? showNotify('error', 'Error', 'La imagen no debe exceder 2MB') : alert('La imagen no debe exceder 2MB'));
      resetImage(); return;
    }

    const reader = new FileReader();
    reader.onload = ev => {
      preview.innerHTML = '';
      const img = document.createElement('img');
      img.src = ev.target.result; img.alt = file.name;
      preview.appendChild(img);

      const removeBtn = document.createElement('button');
      removeBtn.className = 'btn-remove-image';
      removeBtn.type = 'button';
      removeBtn.title = 'Eliminar imagen';
      removeBtn.innerHTML = '<i class="fas fa-trash-alt"></i>';
      removeBtn.addEventListener('click', e => { e.stopPropagation(); resetImage(); });
      preview.appendChild(removeBtn);

      preview.style.display = 'block';
      fileInfo.textContent = `${file.name} (${formatFileSize(file.size)})`;

      // Si estás editando y tienes un <img id="preview"> en el form
      if (window.jQuery && $("#formProductos #productos_id").val()) {
        $("#formProductos #preview").attr("src", ev.target.result);
      }
    };
    reader.readAsDataURL(file);
  }

  function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024, sizes = ['Bytes','KB','MB','GB'], i = Math.floor(Math.log(bytes)/Math.log(k));
    return (bytes/Math.pow(k,i)).toFixed(2) + ' ' + sizes[i];
  }

  function resetImage() {
    fileInput.value = '';
    preview.innerHTML = '';
    preview.style.display = 'none';
    fileInfo.textContent = 'Ningún archivo seleccionado';
    if (window.jQuery && $("#formProductos #productos_id").val()) {
      $("#formProductos #preview").attr("src", "<?php echo SERVERURL;?>vistas/plantilla/img/products/image_preview.png");
    }
  }

  // Exponer reset si lo usas fuera
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
                '<th>Imagen</th>' +
                '<th>Código</th>' +
                '<th>Producto</th>' +
                '<th>Medida</th>' +
                '<th>Categoría</th>' +
                '<th>Precio Compra</th>' +
                '<th>Precio Venta</th>' +
                '<th>Ganancia</th>' +
                '<th>ISV Venta</th>' +
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

    var formatoDinero = function(valor) {
        valor = parseFloat(valor || 0);

        return 'L ' + valor.toLocaleString('es-HN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    };

    var limpiarTexto = function(texto) {
        if (texto === null || texto === undefined || texto === '') {
            return '';
        }

        return String(texto)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

    if ($.fn.DataTable.isDataTable("#dataTableProductos")) {
        $("#dataTableProductos").DataTable().clear().destroy();
    }

    construirHeaderDataTableProductos();

    var table_productos = $("#dataTableProductos").DataTable({
        destroy: true,
        processing: true,
        responsive: true,
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
                if (json && Array.isArray(json.data)) return json.data;
                if (json && Array.isArray(json.aaData)) return json.aaData;
                if (Array.isArray(json)) return json;
                return [];
            }
        },

        columns: [
            {
                data: null,
                orderable: false,
                searchable: false,
                className: "text-center align-middle",
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
                data: "image",
                orderable: false,
                searchable: false,
                className: "text-center align-middle",
                render: function(data, type, row) {
                    var defaultImageUrl = '<?php echo SERVERURL;?>vistas/plantilla/img/products/image_preview.png';
                    var imageUrl = data ? ('<?php echo SERVERURL;?>vistas/plantilla/img/products/' + data) : defaultImageUrl;
                    var safeName = limpiarTexto(row && row.nombre ? row.nombre : 'Imagen de producto');

                    return '<a href="#" class="iv-trigger" data-iv-src="' + imageUrl + '" data-iv-fallback="' + defaultImageUrl + '" data-iv-title="' + safeName + '">' +
                        '<img class="table-image" src="' + imageUrl + '" alt="' + safeName + '" width="64" height="64" style="object-fit:cover;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.12)">' +
                    '</a>';
                }
            },

            {
                data: "barCode"
            },

            {
                data: null,
                render: function(data, type, row) {
                    var nombre = limpiarTexto(row.nombre || '');
                    var descripcion = limpiarTexto(row.descripcion || '');
                    var categoria = limpiarTexto(row.categoria || '');
                    var medida = limpiarTexto(row.medida || '');
                    var isvTexto = limpiarTexto(row.isv_venta || 'No');
                    var isvTipo = 'No aplica';

                    if (parseInt(row.isv1 || 0, 10) === 1) {
                        isvTipo = 'ISV 15%';
                    } else if (parseInt(row.isv2 || 0, 10) === 1) {
                        isvTipo = 'ISV 18%';
                    }

                    if (type !== 'display') {
                        return nombre;
                    }

                    return '' +
                        '<div class="producto-cell">' +
                            '<div class="producto-nombre font-weight-bold">' + nombre + '</div>' +
                            '<div class="producto-descripcion text-muted small">' + (descripcion || 'Sin descripción registrada') + '</div>' +
                            '<div class="mt-1">' +
                                '<span class="badge badge-light border mr-1"><i class="fas fa-layer-group mr-1"></i>' + categoria + '</span>' +
                                '<span class="badge badge-light border mr-1"><i class="fas fa-ruler mr-1"></i>' + medida + '</span>' +
                                '<span class="badge badge-light border"><i class="fas fa-percent mr-1"></i>' + isvTipo + '</span>' +
                            '</div>' +
                            '<button type="button" class="btn btn-sm mt-1 ver_detalle_producto btn-detalle-producto" title="Ver más información del producto">' +
                                '<i class="fas fa-info-circle mr-1"></i> Ver detalles' +
                            '</button>' +
                        '</div>';
                }
            },

            {
                data: "medida"
            },

            {
                data: "categoria"
            },

            {
                data: "precio_compra",
                render: function(data, type) {
                    if (type === 'display') {
                        return '<span style="color:green;font-weight:600;">' + formatoDinero(data) + '</span>';
                    }

                    return parseFloat(data || 0);
                }
            },

            {
                data: "precio_venta",
                render: function(data, type) {
                    if (type === 'display') {
                        return '<span style="color:green;font-weight:600;">' + formatoDinero(data) + '</span>';
                    }

                    return parseFloat(data || 0);
                }
            },

            {
                data: "porcentaje_venta",
                render: function(data, type) {
                    if (type === 'display') {
                        return '<span style="color:green;font-weight:600;">' + formatoDinero(data) + '</span>';
                    }

                    return parseFloat(data || 0);
                }
            },

            {
                data: "isv_venta",
                render: function(data, type, row) {
                    if (type !== 'display') {
                        return data;
                    }

                    var aplica = String(data || '').toLowerCase() === 'si' || parseInt(data || 0, 10) === 1;
                    var isvTipo = '';

                    if (parseInt(row.isv1 || 0, 10) === 1) {
                        isvTipo = '15%';
                    } else if (parseInt(row.isv2 || 0, 10) === 1) {
                        isvTipo = '18%';
                    }

                    if (aplica) {
                        return '<span class="badge badge-pill badge-info" title="Este producto calcula impuesto al vender">' +
                            '<i class="fas fa-check-circle mr-1"></i>Sí ' + isvTipo +
                        '</span>';
                    }

                    return '<span class="badge badge-pill badge-secondary" title="Este producto no calcula impuesto al vender">' +
                        '<i class="fas fa-times-circle mr-1"></i>No' +
                    '</span>';
                }
            },

            {
                data: "estado",
                render: function(data, type) {
                    if (type === 'display') {
                        var estadoText = data == 1 ? 'Activo' : 'Inactivo';
                        var icon = data == 1 ? '<i class="fas fa-check-circle mr-1"></i>' : '<i class="fas fa-times-circle mr-1"></i>';
                        var badgeClass = data == 1 ? 'badge badge-pill badge-success' : 'badge badge-pill badge-danger';

                        return '<span class="' + badgeClass + '" style="font-size:.95rem;padding:.5em .8em;font-weight:600;">' +
                            icon +
                            estadoText +
                        '</span>';
                    }

                    return data;
                }
            }
        ],

        order: [[3, 'asc']],

        columnDefs: [
            {
                targets: 0,
                width: "8%",
                orderable: false,
                searchable: false,
                className: "text-center text-nowrap align-middle"
            },
            {
                targets: 1,
                width: "8%",
                orderable: false,
                searchable: false,
                className: "text-center text-nowrap align-middle"
            },
            {
                targets: 2,
                width: "10%",
                className: "text-nowrap"
            },
            {
                targets: 3,
                width: "22%"
            },
            {
                targets: 4,
                width: "8%",
                className: "text-nowrap"
            },
            {
                targets: 5,
                width: "10%",
                className: "text-nowrap"
            },
            {
                targets: 6,
                width: "9%",
                className: "text-right text-nowrap"
            },
            {
                targets: 7,
                width: "9%",
                className: "text-right text-nowrap"
            },
            {
                targets: 8,
                width: "9%",
                className: "text-right text-nowrap"
            },
            {
                targets: 9,
                width: "8%",
                className: "text-center text-nowrap"
            },
            {
                targets: 10,
                width: "8%",
                className: "text-center text-nowrap"
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
                    columns: [2, 3, 4, 5, 6, 7, 8, 9, 10]
                }
            },
            {
                extend: 'pdf',
                orientation: 'landscape',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                title: 'Reporte Productos',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                exportOptions: {
                    columns: [2, 3, 4, 5, 6, 7, 8, 9, 10]
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

    var formatoDinero = function(valor) {
      valor = parseFloat(valor || 0);
      return 'L ' + valor.toLocaleString('es-HN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    };

    var isvTipo = 'No aplica';

    if (parseInt(data.isv1 || 0, 10) === 1) {
      isvTipo = 'ISV 15%';
    } else if (parseInt(data.isv2 || 0, 10) === 1) {
      isvTipo = 'ISV 18%';
    }

    var descripcion = data.descripcion && String(data.descripcion).trim() !== ''
      ? data.descripcion
      : 'Sin descripción registrada';

    var html = '' +
      '<div class="text-left">' +
        '<div class="mb-2"><strong>Producto:</strong><br>' + data.nombre + '</div>' +
        '<div class="mb-2"><strong>Descripción:</strong><br>' + descripcion + '</div>' +
        '<hr>' +
        '<div class="row">' +
          '<div class="col-md-6 mb-2"><strong>Código:</strong><br>' + (data.barCode || 'Sin código') + '</div>' +
          '<div class="col-md-6 mb-2"><strong>Categoría:</strong><br>' + (data.categoria || 'Sin categoría') + '</div>' +
          '<div class="col-md-6 mb-2"><strong>Medida:</strong><br>' + (data.medida || 'Sin medida') + '</div>' +
          '<div class="col-md-6 mb-2"><strong>Impuesto venta:</strong><br>' + (data.isv_venta || 'No') + ' - ' + isvTipo + '</div>' +
          '<div class="col-md-6 mb-2"><strong>Precio compra:</strong><br>' + formatoDinero(data.precio_compra) + '</div>' +
          '<div class="col-md-6 mb-2"><strong>Precio venta:</strong><br>' + formatoDinero(data.precio_venta) + '</div>' +
          '<div class="col-md-6 mb-2"><strong>Ganancia:</strong><br>' + formatoDinero(data.porcentaje_venta) + '</div>' +
          '<div class="col-md-6 mb-2"><strong>Precio mayoreo:</strong><br>' + formatoDinero(data.precio_mayoreo) + '</div>' +
          '<div class="col-md-6 mb-2"><strong>Cantidad mayoreo:</strong><br>' + (data.cantidad_mayoreo || 0) + '</div>' +
          '<div class="col-md-6 mb-2"><strong>Mínimo:</strong><br>' + (data.cantidad_minima || 0) + '</div>' +
          '<div class="col-md-6 mb-2"><strong>Máximo:</strong><br>' + (data.cantidad_maxima || 0) + '</div>' +
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


// ======= EDITAR / ELIMINAR (tu código original) =======
var editar_producto_dataTable = function(tbody, table) {
  $(tbody).off("click", "button.table_editar");
  $(tbody).on("click", "button.table_editar", function() {
    var data = table.row($(this).parents("tr")).data();
    var url = '<?php echo SERVERURL;?>core/editarProductos.php';

    // Reset y seteo id
    $('#formProductos')[0].reset();
    $('#formProductos #productos_id').val(data.productos_id);

    $.ajax({
      type: 'POST',
      url: url,
      data: $('#formProductos').serialize(),
      success: function(registro) {
        var datos = eval(registro);

        // ---- Modo edición / acciones ----
        $('#formProductos').attr({'data-form': 'update'});
        $('#formProductos').attr({'action': '<?php echo SERVERURL;?>ajax/modificarProductosAjax.php'});
        $('#reg_producto').hide(); 
        $('#edi_producto').show(); 
        $('#delete_producto').hide();
        $('#formProductos #proceso_productos').val("Editar Productos");

        // ---- Campos base ----
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

        // ---- Switches principales (ya venían) ----
        $('#formProductos #producto_isv_factura').prop('checked', datos[7] == 1);
        $('#formProductos #producto_isv_compra').prop('checked', datos[8] == 1);
        $('#formProductos #producto_activo').prop('checked', datos[9] == 1);

        // ---- Imagen ----
        if (datos[11] != "image_preview.png") {
          $('#formProductos #preview').attr('src', datos[21]);
          var preview = document.getElementById('productoPreview');
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
          removeBtn.addEventListener('click', function (e) {
            e.preventDefault(); e.stopPropagation();
            if (typeof window.resetProductoImagen === 'function') window.resetProductoImagen();
          });
          preview.appendChild(removeBtn);
          document.getElementById('productoInfo').textContent = 'Imagen cargada';
        } else {
          $("#formProductos #preview").attr("src", "<?php echo SERVERURL;?>vistas/plantilla/img/products/image_preview.png");
        }

        // ---- Habilita / deshabilita ----
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

        // ---- NUEVO: sincroniza switches especiales desde el listado/PHP ----
        //  - data.restaurante (0/1)     -> #producto_restaurante
        //  - data.isv1 (0/1) 15%        -> #producto_isv1
        //  - data.isv2 (0/1) 18%        -> #producto_isv2
        setISVFromData(datos, data);

        // ---- Abre modal ----
        $('#modal_registrar_productos').modal({ show: true, keyboard: false, backdrop: 'static' });
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
        var nombre   = (data && (data.nombre || data.producto)) || datos[2] || 'Producto';
        var barcode  = (data && (data.barCode || data.bar_code_product)) || datos[19] || '';
        var fileName = (data && (data.file || data.imagen || data.image)) || (datos[20] || '');
        var imgUrl   = (datos[21] && /^https?:\/\//i.test(datos[21])) ? datos[21]
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
          buttons: { cancel: { text: "Cancelar", visible: true, className: "btn-light" },
                     confirm: { text: "Sí, eliminar", value: true, className: "btn-danger", closeModal: false } },
          dangerMode: true, closeOnEsc: false, closeOnClickOutside: false
        }).then((confirmar) => {
          if (!confirmar) return;
          $.ajax({
            type: 'POST',
            url: '<?php echo SERVERURL;?>ajax/eliminarProductosAjax.php',
            data: { productos_id: productos_id },
            dataType: 'json',
            beforeSend: function(){ if (typeof showLoading === 'function') showLoading("Eliminando producto..."); },
            success: function(resp) {
              swal.close();
              if (resp && resp.status === "success") {
                if (typeof showNotify === 'function') showNotify("success", resp.title || "Eliminado", resp.message || "Producto eliminado");
                table.ajax.reload(null, false);
                table.search('').draw();
              } else {
                if (typeof showNotify === 'function') showNotify("error", (resp && resp.title) || "Error", (resp && resp.message) || "No se pudo eliminar");
              }
            },
            error: function(xhr){ swal.close(); if (typeof showNotify === 'function') showNotify("error","Error","Error al procesar la solicitud"); }
          });
        });
      },
      error: function(){ if (typeof showNotify === 'function') showNotify("error","Error","No se pudieron obtener los datos del producto"); }
    });
  });
};



// ====== Cambios en switches / helpers iguales a los tuyos ======
$(document).ready(function() {
  $('#formProductos #tipo_producto').on('change', evaluarCategoria);
  $("#formProductos #precio_venta, #formProductos #precio_compra").on("keyup", function() {
    var pc = parseFloat($("#formProductos #precio_compra").val()) || 0;
    var pv = parseFloat($("#formProductos #precio_venta").val()) || 0;
    $("#formProductos #porcentaje_venta").val((pv > pc) ? (pv - pc).toFixed(2) : "0");
  });
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

$('#formProductos #label_producto_activo').html("Activo");
$('#formProductos .switch').change(function() {
  $('#formProductos #label_'+this.name).html($(this).is(':checked') ? "Sí" : "No");
});

// ====== AJAX para llenar combos (PROMESAS) ======
function getEstadoProducto() {
  const url = '<?php echo SERVERURL;?>core/getEstado.php';
  return $.ajax({ type: "POST", url: url, async: true })
    .then(function(data) {
      $('#form_main_productos #estado_producto').html(data).selectpicker('refresh');
    });
}

// Si no usas este dato, deja Promise.resolve()
function getEmpresaProductos() {
  // Si no necesitas nada del servidor: return Promise.resolve();
  // Si sí necesitas, ajusta la URL real y el select a llenar:
  return Promise.resolve();
}
</script>
