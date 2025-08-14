<script>
$(() => {
    getEstadoProducto();
    listar_productos();
    getEmpresaProductos();
    initImageUpload(); // Inicializar el drag and drop de imágenes

    // Evento para el botón de Buscar (submit)
    $('#form_main_productos #search').on("click", function(e) {
        e.preventDefault();
        listar_productos();
    });

    // Evento para el botón de Limpiar (reset)
    $('#form_main_productos').on('reset', function() {
        $('#form_main_productos .selectpicker')
            .val('')
            .selectpicker('refresh');
        listar_productos();
    });    
});

$('#form_main_productos #buscar_productos').on('click', function(e) {
    e.preventDefault();
    listar_productos();
});

/* =========================
   Uploader de imagen (Producto)
   - Drag & Drop
   - Pegar en cualquier parte (Ctrl+V)
   - Clic SOLO en “haz clic para seleccionar”
   ========================= */
   function initImageUpload() {
  const dropArea   = document.getElementById('productoDropArea');
  const fileInput  = document.getElementById('imagen_producto');
  const preview    = document.getElementById('productoPreview');
  const fileInfo   = document.getElementById('productoInfo');
  const selectLink = dropArea ? dropArea.querySelector('.select-file-text') : null;

  // Evita múltiples inicializaciones
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

  // SOLO aquí abrimos el chooser (no en todo el contenedor)
  if (selectLink) {
    const openChooser = (e) => {
      e.preventDefault();
      e.stopPropagation();
      fileInput.click();
    };
    selectLink.addEventListener('click', openChooser);
    selectLink.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') openChooser(e);
    });
  }

  // Selección por input
  fileInput.addEventListener('change', e => {
    if (isProcessing) return;
    isProcessing = true;
    handleFiles(e.target.files);
    isProcessing = false;
  });

  // Pegar en cualquier parte del documento
  document.addEventListener('paste', e => {
    const items = (e.clipboardData || e.originalEvent?.clipboardData)?.items || [];
    let file = null;
    for (let i = 0; i < items.length; i++) {
      if (items[i].kind === 'file' && items[i].type.startsWith('image/')) {
        file = items[i].getAsFile();
        break;
      }
    }
    if (file) {
      e.preventDefault(); // evita insertar la imagen en inputs/contenteditables
      const dt = new DataTransfer();
      dt.items.add(file);
      handleFiles(dt.files);
    }
  });

  // Helpers
  function preventDefaults(e) { e.preventDefault(); e.stopPropagation(); }

  function handleFiles(fileList) {
    if (!fileList || !fileList.length) return;
    const file = fileList[0];

    // Validaciones
    if (!file.type.startsWith('image/')) {
      (window.swal
        ? swal({ title: 'Error', text: 'Selecciona una imagen válida (JPG, PNG, GIF)', icon: 'error' })
        : alert('Selecciona una imagen válida (JPG, PNG, GIF)'));
      resetImage();
      return;
    }
    if (file.size > 2 * 1024 * 1024) {
      (typeof showNotify === 'function'
        ? showNotify('error', 'Error', 'La imagen no debe exceder 2MB')
        : alert('La imagen no debe exceder 2MB'));
      resetImage();
      return;
    }

    // Previsualizar
    const reader = new FileReader();
    reader.onload = ev => {
      preview.innerHTML = '';

      const img = document.createElement('img');
      img.src = ev.target.result;
      img.alt = file.name;
      preview.appendChild(img);

      // Botón eliminar
      const removeBtn = document.createElement('button');
      removeBtn.className = 'btn-remove-image';
      removeBtn.type = 'button';
      removeBtn.title = 'Eliminar imagen';
      removeBtn.innerHTML = '<i class="fas fa-trash-alt"></i>';
      removeBtn.addEventListener('click', e => {
        e.stopPropagation();
        resetImage();
      });
      preview.appendChild(removeBtn);

      preview.style.display = 'block';
      fileInfo.textContent = `${file.name} (${formatFileSize(file.size)})`;

      // Modo edición (opcional)
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

    // Modo edición (opcional)
    if (window.jQuery && $("#formProductos #productos_id").val()) {
      $("#formProductos #preview").attr("src", "<?php echo SERVERURL;?>vistas/plantilla/img/products/image_preview.png");
    }
  }

  // Exponer por si lo quieres llamar manualmente
  window.resetProductoImagen = resetImage;
}

document.addEventListener('DOMContentLoaded', initImageUpload);


/* =========================
   DataTable: Productos
   - Miniatura con fallback (via image_viewer.js)
   - Modal de imagen al clic (via image_viewer.js)
   ========================= */
  var listar_productos = function(estado) {
  var estado = $('#form_main_productos #estado_producto').val() === "" ? 1 : $('#form_main_productos #estado_producto').val();

  var table_productos = $("#dataTableProductos").DataTable({
    "destroy": true,
    "ajax": {
      "method": "POST",
      "url": "<?php echo SERVERURL;?>core/llenarDataTableProductos.php",
      "data": { "estado": estado }
    },
    "columns": [
      {
        "data": "image",
        "orderable": false,
        "render": function (data, type, row, meta) {
          var defaultImageUrl = '<?php echo SERVERURL;?>vistas/plantilla/img/products/image_preview.png';
          var imageUrl = data ? ('<?php echo SERVERURL;?>vistas/plantilla/img/products/' + data) : defaultImageUrl;
          var safeName = (row && row.nombre) ? String(row.nombre).replace(/"/g,'&quot;') : 'Imagen de producto';

          // .iv-trigger: el visor genérico leerá estos data-attrs
          return '' +
            '<a href="#" class="iv-trigger" ' +
              'data-iv-src="' + imageUrl + '" ' +
              'data-iv-fallback="' + defaultImageUrl + '" ' +
              'data-iv-title="' + safeName + '">' +
              '<img class="table-image" src="' + imageUrl + '" alt="' + safeName + '" ' +
                   'width="64" height="64" ' +
                   'style="object-fit:cover;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.12)">' +
            '</a>';
        }
      },
      { "data": "barCode" },
      { "data": "nombre" },
      { "data": "medida" },
      { "data": "categoria" },
      {
        "data": "precio_compra",
        render: function(data, type) {
          var number = $.fn.dataTable.render.number(',', '.', 2, 'L ').display(data);
          if (type === 'display') {
            let color = data < 0 ? 'red' : 'green';
            return '<span style="color:' + color + '">' + number + '</span>';
          }
          return number;
        },
      },
      {
        "data": "precio_venta",
        render: function(data, type) {
          var number = $.fn.dataTable.render.number(',', '.', 2, 'L ').display(data);
          if (type === 'display') {
            let color = data < 0 ? 'red' : 'green';
            return '<span style="color:' + color + '">' + number + '</span>';
          }
          return number;
        },
      },
      {
        "data": "porcentaje_venta",
        render: function(data, type) {
          var number = $.fn.dataTable.render.number(',', '.', 2, 'L ').display(data);
          if (type === 'display') {
            let color = data < 0 ? 'red' : 'green';
            return '<span style="color:' + color + '">' + number + '</span>';
          }
          return number;
        },
      },
      { "data": "isv_venta" },
      {
        "data": "estado",
        "render": function(data, type) {
          if (type === 'display') {
            var estadoText = data == 1 ? 'Activo' : 'Inactivo';
            var icon = data == 1 ? '<i class="fas fa-check-circle mr-1"></i>' : '<i class="fas fa-times-circle mr-1"></i>';
            var badgeClass = data == 1 ? 'badge badge-pill badge-success' : 'badge badge-pill badge-danger';
            return '<span class="' + badgeClass + '" style="font-size: 0.95rem; padding: 0.5em 0.8em; font-weight: 600;">' + icon + estadoText + '</span>';
          }
          return data;
        }
      },
      { "defaultContent": "<button class='table_editar btn btn-dark ocultar'><span class='fas fa-edit fa-lg'></span>Editar</button>" },
      { "defaultContent": "<button class='table_eliminar btn btn-dark ocultar'><span class='fa fa-trash fa-lg'></span>Eliminar</button>" }
    ],
    "lengthMenu": lengthMenu10,
    "stateSave": true,
    "bDestroy": true,
    "responsive": true,
    "language": idioma_español,
    "dom": dom,
    "buttons": [
      {
        text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
        titleAttr: 'Actualizar Productos',
        className: 'table_actualizar btn btn-secondary ocultar',
        action: function() { listar_productos(); }
      },
      {
        text: '<i class="fas fas fa-plus fa-lg"></i> Ingresar',
        titleAttr: 'Agregar Productos',
        className: 'table_crear btn btn-primary ocultar',
        action: function() { modal_productos(); }
      },
      {
        extend: 'excelHtml5',
        text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
        titleAttr: 'Excel',
        title: 'Reporte Productos',
        messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
        className: 'table_reportes btn btn-success ocultar',
        exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7] },
      },
      {
        extend: 'pdf',
        orientation: 'landscape',
        text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
        titleAttr: 'PDF',
        title: 'Reporte Productos',
        messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
        exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7] },
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
    "drawCallback": function(settings) {
      getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
    }
  });

  table_productos.search('').draw();
  $('#buscar').focus();

  editar_producto_dataTable("#dataTableProductos tbody", table_productos);
  eliminar_producto_dataTable("#dataTableProductos tbody", table_productos);
};

var editar_producto_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_editar");
    $(tbody).on("click", "button.table_editar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarProductos.php';
        $('#formProductos #productos_id').val(data.productos_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formProductos').serialize(),
            success: function(registro) {
                var datos = eval(registro);
                $('#formProductos').attr({
                    'data-form': 'update'
                });
                $('#formProductos').attr({
                    'action': '<?php echo SERVERURL;?>ajax/modificarProductosAjax.php'
                });
                $('#formProductos')[0].reset();
                $('#reg_producto').hide();
                $('#edi_producto').show();
                $('#delete_producto').hide();
                $('#formProductos #proceso_productos').val("Editar Productos");
                evaluarCategoriaDetalle(datos[13]);
                $('#formProductos #medida').val(datos[1]);
                $('#formProductos #medida').selectpicker('refresh');
                $('#formProductos #almacen').val(datos[0]);
                $('#formProductos #almacen').selectpicker('refresh');
                $('#formProductos #producto').val(datos[2]);
                $('#formProductos #descripcion').val(datos[3]);
                $('#formProductos #precio_compra').val(datos[4]);
                $('#formProductos #precio_venta').val(datos[5]);
                $('#formProductos #tipo_producto').val(datos[6]);
                $('#formProductos #tipo_producto').selectpicker('refresh');
                $('#formProductos #producto_empresa_id').val(datos[11]);
                $('#formProductos #producto_empresa_id').selectpicker('refresh');
                $('#formProductos #porcentaje_venta').val(datos[13]);
                $('#formProductos #cantidad_minima').val(datos[14]);
                $('#formProductos #cantidad_maxima').val(datos[15]);
                $('#formProductos #producto_categoria').val(datos[16]);
                $('#formProductos #precio_mayoreo').val(datos[17]);
                $('#formProductos #cantidad_mayoreo').val(datos[18]);
                $('#formProductos #bar_code_product').val(datos[19]);
                $('#formProductos #producto_superior').val(datos[20]);

                if (datos[7] == 1) {
                    $('#formProductos #producto_isv_factura').attr('checked', true);
                } else {
                    $('#formProductos #producto_isv_factura').attr('checked', false);
                }

                if (datos[8] == 1) {
                    $('#formProductos #producto_isv_compra').attr('checked', true);
                } else {
                    $('#formProductos #producto_isv_compra').attr('checked', false);
                }

                if (datos[9] == 1) {
                    $('#formProductos #producto_activo').attr('checked', true);
                } else {
                    $('#formProductos #producto_activo').attr('checked', false);
                }

                // Cargar imagen existente si hay
                if (datos[11] != "image_preview.png") {
                    $('#formProductos #preview').attr('src', datos[21]);
                    var preview = document.getElementById('productoPreview');
                    preview.innerHTML = '';
                    const img = document.createElement('img');
                    img.src = datos[21];
                    preview.appendChild(img);
                    preview.style.display = 'block';
                    
                    const removeBtn = document.createElement('button');
                    removeBtn.className = 'btn-remove-image';
                    removeBtn.innerHTML = '×';
                    removeBtn.onclick = function() {
                        resetFileInput();
                    };
                    preview.appendChild(removeBtn);
                    
                    document.getElementById('productoInfo').textContent = 'Imagen cargada';
                } else {
                    $("#formProductos #preview").attr("src", "<?php echo SERVERURL;?>vistas/plantilla/img/products/image_preview.png");
                    resetFileInput();
                }

                //HABILITAR OBJETOS
                $('#formProductos #producto').attr("readonly", false);
                $('#formProductos #cantidad').attr("readonly", true);
                $('#formProductos #precio_compra').attr("readonly", false);
                $('#formProductos #precio_venta').attr("readonly", false);
                $('#formProductos #descripcion').attr("readonly", false);
                $('#formProductos #cantidad_minima').attr("readonly", false);
                $('#formProductos #cantidad_maxima').attr("readonly", false);
                $('#formProductos #cantidad_mayoreo').attr("readonly", false);
                $('#formProductos #porcentaje_venta').attr("readonly", false);
                $('#formProductos #producto_isv_factura').attr("disabled", false);
                $('#formProductos #producto_isv_compra').attr("disabled", false);
                $('#formProductos #producto_activo').attr("disabled", false);
                $('#formProductos #grupo_editar_bacode').show();

                //DESHABILITAR OBJETOS
                $('#formProductos #medida').attr("disabled", true);
                $('#formProductos #producto_superior').attr("disabled", true);
                $('#formProductos #almacen').attr("disabled", true);
                $('#formProductos #tipo_producto').attr("disabled", true);
                $('#formProductos #producto_categoria').attr("disabled", true);
                $('#formProductos #bar_code_product').attr("readonly", true);
                $('#formProductos #producto_empresa_id').attr("disabled", true);
                $('#formProductos #cantidad').attr("disabled", true);
                $('#formProductos #buscar_producto_empresa').hide();
                $('#formProductos #buscar_producto_categorias').hide();
                $('#formProductos #estado_producto').show();

                //OCULTAR
                $('#formProductos #cantidad').hide();
                $('#div_cantidad_editar_producto').hide();

                $('#modal_registrar_productos').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
    });
}

var eliminar_producto_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_eliminar");
    $(tbody).on("click", "button.table_eliminar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarProductos.php';
        $('#formProductos #productos_id').val(data.productos_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formProductos').serialize(),
            success: function(registro) {
                var datos = eval(registro);
                $('#formProductos').attr({
                    'data-form': 'delete'
                });
                $('#formProductos').attr({
                    'action': '<?php echo SERVERURL;?>ajax/eliminarProductosAjax.php'
                });
                $('#formProductos')[0].reset();
                $('#reg_producto').hide();
                $('#edi_producto').hide();
                $('#delete_producto').show();
                $('#formProductos #proceso_productos').val("Eliminar Productos");
                $('#formProductos #medida').val(datos[0]);
                $('#formProductos #medida').selectpicker('refresh');
                $('#formProductos #almacen').val(datos[1]);
                $('#formProductos #almacen').selectpicker('refresh');
                $('#formProductos #producto').val(datos[2]);
                $('#formProductos #descripcion').val(datos[3]);
                $('#formProductos #precio_compra').val(datos[4]);
                $('#formProductos #precio_venta').val(datos[5]);
                $('#formProductos #tipo_producto').val(datos[6]);
                $('#formProductos #tipo_producto').selectpicker('refresh');
                $('#formProductos #producto_empresa_id').val(datos[11]);
                $('#formProductos #producto_empresa_id').selectpicker('refresh');
                $('#formProductos #porcentaje_venta').val(datos[13]);
                $('#formProductos #cantidad_minima').val(datos[14]);
                $('#formProductos #cantidad_maxima').val(datos[15]);
                $('#formProductos #producto_categoria').val(datos[16]);
                $('#formProductos #precio_mayoreo').val(datos[17]);
                $('#formProductos #cantidad_mayoreo').val(datos[18]);
                $('#formProductos #bar_code_product').val(datos[19]);

                if (datos[11] != "image_preview.png") {
                    $('#formProductos #preview').attr('src', datos[21]);
                } else {
                    $("#formProductos #preview").attr("src", "<?php echo SERVERURL;?>vistas/plantilla/img/products/image_preview.png");
                }

                if (datos[7] == 1) {
                    $('#formProductos #producto_isv_factura').attr('checked', true);
                } else {
                    $('#formProductos #producto_isv_factura').attr('checked', false);
                }

                if (datos[8] == 1) {
                    $('#formProductos #producto_isv_compra').attr('checked', true);
                } else {
                    $('#formProductos #producto_isv_compra').attr('checked', false);
                }

                if (datos[9] == 1) {
                    $('#formProductos #producto_activo').attr('checked', true);
                } else {
                    $('#formProductos #producto_activo').attr('checked', false);
                }

                //DESHABILITAR OBJETOS
                $('#formProductos #producto').attr("readonly", true);
                $('#formProductos #medida').attr("disabled", true);
                $('#formProductos #almacen').attr("disabled", true);
                $('#formProductos #cantidad').attr("readonly", true);
                $('#formProductos #precio_compra').attr("readonly", true);
                $('#formProductos #precio_venta').attr("readonly", true);
                $('#formProductos #descripcion').attr("readonly", true);
                $('#formProductos #cantidad_minima').attr("readonly", true);
                $('#formProductos #cantidad_maxima').attr("readonly", true);
                $('#formProductos #tipo_producto').attr("disabled", true);
                $('#formProductos #producto_categoria').attr("disabled", true);
                $('#formProductos #producto_isv_factura').attr("disabled", true);
                $('#formProductos #producto_isv_compra').attr("disabled", true);
                $('#formProductos #producto_activo').attr("disabled", true);
                $('#formProductos #bar_code_product').attr("readonly", true);
                $('#formProductos #producto_empresa_id').attr("disabled", true);
                $('#formProductos #precio_mayoreo').attr("readonly", true);
                $('#formProductos #porcentaje_venta').attr("readonly", true);
                $('#formProductos #cantidad_mayoreo').attr("readonly", true);
                $('#formProductos #almacen').attr("disabled", true);
                $('#formProductos #cantidad').attr("disabled", true);
                $('#formProductos #buscar_producto_empresa').hide();
                $('#formProductos #buscar_producto_categorias').hide();
                $('#formProductos #estado_producto').hide();
                $('#formProductos #grupo_editar_bacode').hide();

                $('#modal_registrar_productos').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
    });
}

//INICIO EDITAR CODIGO DE BARRA
$('#formProductos #grupo_editar_bacode').on('click', function(e) {
    e.preventDefault();

    $('#formEditarBarcode')[0].reset();
    $('#formEditarBarcode #pro_barcode').val("Editar");
    $('#formEditarBarcode #productos_id').val($('#formProductos #productos_id').val());
    $('#formEditarBarcode #producto').val($('#formProductos #producto').val());
    $('#modalEditarBarcode').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
});

$(document).ready(function() {
    $("#modalEditarBarcode").on('shown.bs.modal', function() {
        $(this).find('#formEditarBarcode #barcode').focus();
    });
});

$('#editar_barcode').on('click', function(e) {
    e.preventDefault();

    editBarCode($('#formEditarBarcode #productos_id').val(), $('#formEditarBarcode #barcode').val(), $(
        '#formEditarBarcode #producto').val());
});

function editBarCode(productos_id, barcode, producto) {
    swal({
        title: "¿Estas seguro?",
        text: "¿Desea editar el Código de Barra para el producto: " + producto + "?",
        icon: "info",
        buttons: {
            cancel: {
                text: "Cancelar",
                visible: true
            },
            confirm: {
                text: "¡Si, Deseo Editarlo!",
            }
        },
        closeOnEsc: false,
        closeOnClickOutside: false
    }).then((willConfirm) => {
        if (willConfirm === true) {
            editarCodigoBarra(productos_id, barcode);
        }
    });
}

function editarCodigoBarra(productos_id, barcode) {
    var url = '<?php echo SERVERURL; ?>core/editBarCode.php';

    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        data: 'productos_id=' + productos_id + '&barcode=' + barcode,
        success: function(data) {
            if (data == 1) {
                swal({
                    title: "Success",
                    text: "El Código de Barra ha sido actualizado satisfactoriamente",
                    icon: "success",
                    closeOnEsc: false,
                    closeOnClickOutside: false
                });
                listar_productos();
                $('#formProductos #bar_code_product').val(barcode);
            } else if (data == 2) {
                swal({
                    title: "Error",
                    text: "Error el El Código de Barra no se puede actualizar",
                    icon: "error",
                    dangerMode: true,
                    closeOnEsc: false,
                    closeOnClickOutside: false
                });
            } else if (data == 3) {
                swal({
                    title: "Error",
                    text: "El El Código de Barra ya existe",
                    icon: "error",
                    dangerMode: true,
                    closeOnEsc: false,
                    closeOnClickOutside: false
                });
            }
        }
    });
}
//FIN EDITAR CODIGO DE BARRA

$(document).ready(function() {
    $('#formProductos #tipo_producto').on('change', function() {
        evaluarCategoria();
    });
});

function evaluarCategoria() {
    if ($('#formProductos #tipo_producto').find('option:selected').text() == "Servicio") {
        $('#formProductos #cantidad').attr('readonly', true);
        $('#formProductos #precio_compra').attr('readonly', false);
        $('#formProductos #precio_venta').attr('readonly', false);
        $('#formProductos #precio_mayoreo').attr('readonly', false);
        $('#formProductos #cantidad_minima').attr('readonly', true);
        $('#formProductos #cantidad_maxima').attr('readonly', true);
        $('#formProductos #isv_si').attr('checked', false);
        $('#formProductos #isv_no').attr('checked', true);
        $('#formProductos #cantidad').val(1);
        $('#formProductos #precio_compra').val(0);
    } else if ($('#formProductos #tipo_producto').find('option:selected').text() == "Insumos") {
        $('#formProductos #cantidad').attr('readonly', false);
        $('#formProductos #precio_compra').attr('readonly', false);
        $('#formProductos #precio_venta').attr('readonly', true);
        $('#formProductos #precio_mayoreo').attr('readonly', true);
        $('#formProductos #cantidad_minima').attr('readonly', false);
        $('#formProductos #cantidad_maxima').attr('readonly', false);
        $('#formProductos #cantidad').val(1);
        $('#formProductos #precio_venta').val(0);
        $('#formProductos #precio_mayoreo').val(0);
        $('#formProductos #isv_si').attr('checked', true);
        $('#formProductos #isv_no').attr('checked', false);
    } else {
        $('#formProductos #cantidad').attr('readonly', false);
        $('#formProductos #precio_compra').attr('readonly', false);
        $('#formProductos #precio_venta').attr('readonly', false);
        $('#formProductos #precio_mayoreo').attr('readonly', false);
        $('#formProductos #cantidad_minima').attr('readonly', false);
        $('#formProductos #cantidad_maxima').attr('readonly', false);
        $('#formProductos #isv_si').attr('checked', true);
        $('#formProductos #isv_no').attr('checked', false);
        $('#formProductos #cantidad').val('');
        $('#formProductos #precio_compra').val('');
    }
}

function evaluarCategoriaDetalle(TipoProducto) {
    if (TipoProducto == "Servicio") {
        $('#formProductos #cantidad').attr('readonly', true);
        $('#formProductos #precio_compra').attr('readonly', true);
        $('#formProductos #precio_venta').attr('readonly', false);
        $('#formProductos #precio_mayoreo').attr('readonly', false);
        $('#formProductos #cantidad_minima').attr('readonly', true);
        $('#formProductos #cantidad_maxima').attr('readonly', true);
        $('#formProductos #isv_si').attr('checked', false);
        $('#formProductos #isv_no').attr('checked', true);
        $('#formProductos #cantidad').val(1);
        $('#formProductos #precio_compra').val(0);
    } else if (TipoProducto == "Insumos") {
        $('#formProductos #cantidad').attr('readonly', false);
        $('#formProductos #precio_compra').attr('readonly', false);
        $('#formProductos #precio_venta').attr('readonly', true);
        $('#formProductos #precio_mayoreo').attr('readonly', true);
        $('#formProductos #cantidad_minima').attr('readonly', false);
        $('#formProductos #cantidad_maxima').attr('readonly', false);
        $('#formProductos #concentracion').val("");
        $('#formProductos #cantidad').val(1);
        $('#formProductos #precio_venta').val(0);
        $('#formProductos #isv_si').attr('checked', true);
        $('#formProductos #isv_no').attr('checked', false);
    } else {
        $('#formProductos #cantidad').attr('readonly', false);
        $('#formProductos #precio_compra').attr('readonly', false);
        $('#formProductos #precio_venta').attr('readonly', false);
        $('#formProductos #precio_mayoreo').attr('readonly', false);
        $('#formProductos #cantidad_minima').attr('readonly', false);
        $('#formProductos #cantidad_maxima').attr('readonly', false);
        $('#formProductos #isv_si').attr('checked', true);
        $('#formProductos #isv_no').attr('checked', false);
        $('#formProductos #cantidad').val('');
        $('#formProductos #precio_compra').val('');
    }
}

$(document).ready(function() {
    $("#formProductos #precio_venta").on("keyup", function() {
        calcularGanancia();
    });

    $("#formProductos #precio_compra").on("keyup", function() {
        calcularGanancia();
    });

    function calcularGanancia() {
        var precio_compra = parseFloat($("#formProductos #precio_compra").val()) || 0;
        var precio_venta = parseFloat($("#formProductos #precio_venta").val()) || 0;

        if ($("#formProductos #precio_compra").val() !== "" && precio_venta > precio_compra) {
            var ganancia = precio_venta - precio_compra;
            $("#formProductos #porcentaje_venta").val(ganancia.toFixed(2));
        } else {
            $("#formProductos #porcentaje_venta").val("0");
        }
    }
});

$('#formProductos #label_producto_activo').html("Activo");

$('#formProductos .switch').change(function() {
    if ($('input[name=producto_activo]').is(':checked')) {
        $('#formProductos #label_producto_activo').html("Activo");
        return true;
    } else {
        $('#formProductos #label_producto_activo').html("Inactivo");
        return false;
    }
});

$('#formProductos #label_producto_isv_factura').html("Sí");

$('#formProductos .switch').change(function() {
    if ($('input[name=producto_isv_factura]').is(':checked')) {
        $('#formProductos #label_producto_isv_factura').html("Sí");
        return true;
    } else {
        $('#formProductos #label_producto_isv_factura').html("No");
        return false;
    }
});

$('#formProductos #label_producto_isv_compra').html("Sí");

$('#formProductos .switch').change(function() {
    if ($('input[name=producto_isv_compra]').is(':checked')) {
        $('#formProductos #label_producto_isv_compra').html("Sí");
        return true;
    } else {
        $('#formProductos #label_producto_isv_compra').html("No");
        return false;
    }
});

function getEstadoProducto() {
    var url = '<?php echo SERVERURL;?>core/getEstado.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#form_main_productos #estado_producto').html("");
            $('#form_main_productos #estado_producto').html(data);
            $('#form_main_productos #estado_producto').selectpicker('refresh');
        }
    });
}
</script>