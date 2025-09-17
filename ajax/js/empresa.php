<script>
$(() => {
    listar_empresa();
    GetEstadoBotonFirma();

    $('#form_main_empresa #search').on("click", function (e) {
        e.preventDefault();
        listar_empresa();
    });

    // Evento para el botón de Limpiar (reset)
    $('#form_main_empresa').on('reset', function () {
        // Limpia y refresca los selects
        $(this).find('.selectpicker')
            .val('')
            .selectpicker('refresh');
        listar_empresa();
    });

  // ====== NUEVO: base URL a la carpeta enterprise (coincide con ENTERPRISE_PATH del backend)
  const ENTERPRISE_URL = '<?php echo rtrim(SERVERURL, "/") . ENTERPRISE_PATH; ?>'; // p.ej. https://tuapp.com/vistas/plantilla/img/enterprise/

  const cfgs = [
    { drop: '#logoDropArea',  input: '#logotipo',         preview: '#logoPreview',  info: '#logoInfo',  maxMB: 2 },
    { drop: '#firmaDropArea', input: '#firma_documento',  preview: '#firmaPreview', info: '#firmaInfo', maxMB: 2 },
  ];

  let lastAreaCtx = null; // para pegar (Ctrl+V) en el último área activa

  cfgs.forEach(setupUploader);

  // Pegar en cualquier parte del documento: va al último área activa (o a la primera disponible)
  document.addEventListener('paste', function (e) {
    const items = (e.clipboardData || e.originalEvent?.clipboardData)?.items || [];
    let file = null;
    for (let i = 0; i < items.length; i++) {
      if (items[i].kind === 'file' && items[i].type.startsWith('image/')) {
        file = items[i].getAsFile();
        break;
      }
    }
    if (!file) return;

    e.preventDefault();
    const ctx = lastAreaCtx || getFirstAvailableCtx();
    if (!ctx) return;

    const dt = new DataTransfer();
    dt.items.add(file);
    handleFiles(dt.files, ctx);
  });

  function getFirstAvailableCtx() {
    for (const c of cfgs) {
      const drop = document.querySelector(c.drop);
      const input = document.querySelector(c.input);
      const preview = document.querySelector(c.preview);
      const info = document.querySelector(c.info);
      if (drop && input && preview && info) return { drop, input, preview, info, maxMB: c.maxMB };
    }
    return null;
  }

  function setupUploader({ drop, input, preview, info, maxMB }) {
    const dropArea = document.querySelector(drop);
    const fileInput = document.querySelector(input);
    const previewEl = document.querySelector(preview);
    const infoEl = document.querySelector(info);
    if (!dropArea || !fileInput || !previewEl || !infoEl) return;

    // Evitar doble init por recarga
    if (fileInput.dataset.initialized) return;
    fileInput.dataset.initialized = 'true';

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
      if (files.length) handleFiles(files, { drop: dropArea, input: fileInput, preview: previewEl, info: infoEl, maxMB });
    });

    // Guardar área activa para Ctrl+V
    ['mouseenter','focusin'].forEach(ev => {
      dropArea.addEventListener(ev, () => {
        lastAreaCtx = { drop: dropArea, input: fileInput, preview: previewEl, info: infoEl, maxMB };
      });
    });

    // Selección por input
    fileInput.addEventListener('change', e => {
      handleFiles(e.target.files, { drop: dropArea, input: fileInput, preview: previewEl, info: infoEl, maxMB });
    });

    // Abrir file chooser desde botón reutilizable (.btn-file-chooser) o el viejo .select-file-text
    const chooseBtn  = dropArea.querySelector('.btn-file-chooser');
    const selectLink = dropArea.querySelector('.select-file-text');

    const openChooser = (e) => { e.preventDefault(); e.stopPropagation(); fileInput.click(); };

    if (chooseBtn)  chooseBtn.addEventListener('click', openChooser);
    if (selectLink) {
      selectLink.addEventListener('click', openChooser);
      selectLink.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') openChooser(e); });
    }

    function preventDefaults(e) { e.preventDefault(); e.stopPropagation(); }
  }

  function handleFiles(fileList, ctx) {
    if (!ctx || !fileList || !fileList.length) return;
    const { input, preview, info, maxMB } = ctx;
    const file = fileList[0];

    // Validaciones
    if (!file.type.startsWith('image/')) {
      (typeof showNotify === 'function'
        ? showNotify('error', 'Error', 'El archivo debe ser una imagen (JPG, PNG, GIF)')
        : alert('El archivo debe ser una imagen (JPG, PNG, GIF)'));
      resetField(ctx);
      return;
    }
    if (file.size > maxMB * 1024 * 1024) {
      (typeof showNotify === 'function'
        ? showNotify('error', 'Error', 'La imagen no debe exceder ' + maxMB + 'MB')
        : alert('La imagen no debe exceder ' + maxMB + 'MB'));
      resetField(ctx);
      return;
    }

    info.textContent = `${file.name} (${formatFileSize(file.size)})`;

    const reader = new FileReader();
    reader.onload = function (e) {
      preview.innerHTML = '';

      const wrapper = document.createElement('div');
      wrapper.style.position = 'relative';
      wrapper.style.display  = 'inline-block';

      const img = document.createElement('img');
      img.src = e.target.result;
      img.alt = file.name;
      img.className = 'img-thumbnail';
      img.style.maxWidth = '200px';
      img.style.maxHeight = '200px';

      const removeBtn = document.createElement('button');
      removeBtn.type = 'button';
      removeBtn.className = 'btn-remove-image';
      removeBtn.title = 'Eliminar imagen';
      removeBtn.innerHTML = '<i class="fas fa-trash-alt"></i>';
      // estilos inline mínimos si no tienes clase .btn-remove-image global
      removeBtn.style.position = 'absolute';
      removeBtn.style.top = '5px';
      removeBtn.style.right = '5px';
      removeBtn.style.background = 'rgba(220,53,69,.95)';
      removeBtn.style.color = '#fff';
      removeBtn.style.border = 'none';
      removeBtn.style.borderRadius = '50%';
      removeBtn.style.width = '32px';
      removeBtn.style.height = '32px';
      removeBtn.style.display = 'flex';
      removeBtn.style.alignItems = 'center';
      removeBtn.style.justifyContent = 'center';
      removeBtn.style.boxShadow = '0 2px 6px rgba(0,0,0,.18)';
      removeBtn.addEventListener('click', function (ev) {
        ev.stopPropagation();
        resetField(ctx);
      });

      wrapper.appendChild(img);
      wrapper.appendChild(removeBtn);
      preview.appendChild(wrapper);
      preview.style.display = 'block';
    };
    reader.readAsDataURL(file);
  }

  function resetField(ctx) {
    const { input, preview, info } = ctx;
    input.value = '';
    preview.innerHTML = '';
    preview.style.display = 'none';
    info.textContent = 'Ningún archivo seleccionado';
  }

  function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024, sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return (bytes / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i];
  }
});

//INICIO ACCIONES FROMULARIO EMPRESA
// INICIO ACCIONES FORMULARIO EMPRESA
var listar_empresa = function() {
  var estado = $('#form_main_empresa #estado_empresa').val();

  // ====== NUEVO: base URL a la carpeta enterprise (coincide con ENTERPRISE_PATH del backend)
  var ENTERPRISE_URL = '<?php echo rtrim(SERVERURL, "/") . ENTERPRISE_PATH; ?>'; // termina con /

  var table_empresa = $("#dataTableEmpresa").DataTable({
    "destroy": true,
    "ajax": {
      "method": "POST",
      "url": "<?php echo SERVERURL;?>core/llenarDataTableEmpresa.php",
      "data": { "estado": estado }
    },
    "columns": [
      // Columna de logo (miniatura + iv-trigger)
      {
        "data": "image",
        "orderable": false,
        "render": function(data, type, row, meta) {
          // default dentro de enterprise (ya no products)
          var defaultLogoUrl = ENTERPRISE_URL + 'image_preview.png';
          // si el backend devuelve solo el nombre de archivo (p.ej. logo_ab12cd.png)
          var imageUrl = data ? (ENTERPRISE_URL + data) : defaultLogoUrl;

          var safeTitle = (row && (row.nombre || row.razon_social))
            ? String(row.nombre || row.razon_social).replace(/"/g,'&quot;')
            : 'Logo';

          return '' +
            '<a href="#" class="iv-trigger" ' +
              'data-iv-src="' + imageUrl + '" ' +
              'data-iv-fallback="' + defaultLogoUrl + '" ' +
              'data-iv-title="' + safeTitle + '">' +
              '<img class="table-image" src="' + imageUrl + '" alt="' + safeTitle + '">' +
            '</a>';
        }
      },
      { "data": "razon_social" },
      { "data": "nombre" },
      { "data": "telefono" },
      { "data": "correo" },
      { "data": "rtn" },
      { "data": "ubicacion" },
      {
        "data": "estado",
        "render": function(data, type, row) {
          if (type === 'display') {
            var estadoText = data == 1 ? 'Activo' : 'Inactivo';
            var icon = data == 1
              ? '<i class="fas fa-check-circle mr-1"></i>'
              : '<i class="fas fa-times-circle mr-1"></i>';
            var badgeClass = data == 1
              ? 'badge badge-pill badge-success'
              : 'badge badge-pill badge-danger';

            return '<span class="' + badgeClass + '" style="font-size:0.95rem;padding:.5em .8em;font-weight:600;">' +
              icon + estadoText + '</span>';
          }
          return data;
        }
      },
      { "defaultContent": "<button class='table_editar btn ocultar'><span class='fas fa-edit fa-lg'></span>Editar</button>" },
      { "defaultContent": "<button class='table_eliminar btn ocultar'><span class='fa fa-trash fa-lg'></span>Eliminar</button>" }
    ],
    "lengthMenu": lengthMenu,
    "stateSave": true,
    "bDestroy": true,
    "language": idioma_español,
    "dom": dom,
    "buttons": [
      {
        text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
        titleAttr: 'Actualizar Empresa',
        className: 'table_actualizar btn btn-secondary ocultar',
        action: function() { listar_empresa(); }
      },
      {
        text: '<i class="fas fas fa-plus fa-lg"></i> Ingresar',
        titleAttr: 'Agregar Empresa',
        className: 'table_crear btn btn-primary ocultar',
        action: function() { modal_empresa(); }
      },
      {
        extend: 'excelHtml5',
        text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
        titleAttr: 'Excel',
        title: 'Reporte de Empresa',
        messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
        className: 'table_reportes btn btn-success ocultar',
        exportOptions: { columns: [0, 1, 2, 3, 4, 5] }
      },
      {
        extend: 'pdf',
        orientation: 'landscape',
        pageSize: 'LEGAL',
        text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
        titleAttr: 'PDF',
        title: 'Reporte de Empresa',
        messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today() ),
        className: 'table_reportes btn btn-danger ocultar',
        exportOptions: { columns: [0, 1, 2, 3, 4, 5] },
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

  table_empresa.search('').draw();
  $('#buscar').focus();

  editar_empresa_dataTable("#dataTableEmpresa tbody", table_empresa);
  eliminar_empresa_dataTable("#dataTableEmpresa tbody", table_empresa);
};

var editar_empresa_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_editar");
    $(tbody).on("click", "button.table_editar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarEmpresa.php';
        $('#formEmpresa #empresa_id').val(data.empresa_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formEmpresa').serialize(),
            success: function(registro) {
                var valores = eval(registro);
                $('#formEmpresa').attr({
                    'data-form': 'update'
                });
                $('#formEmpresa').attr({
                    'action': '<?php echo SERVERURL;?>ajax/modificarEmpreasAjax.php'
                });
                $('#formEmpresa')[0].reset();
                $('#reg_empresa').hide();
                $('#edi_empresa').show();
                $('#delete_empresa').hide();
                $('#formEmpresa #empresa_empresa').val(valores[0]);
                $('#formEmpresa #telefono_empresa').val(valores[1]);
                $('#formEmpresa #correo_empresa').val(valores[2]);
                $('#formEmpresa #rtn_empresa').val(valores[3]);
                $('#formEmpresa #direccion_empresa').val(valores[4]);
                $('#formEmpresa #empresa_razon_social').val(valores[6]);
                $('#formEmpresa #empresa_otra_informacion').val(valores[7]);
                $('#formEmpresa #empresa_eslogan').val(valores[8]);
                $('#formEmpresa #empresa_celular').val(valores[9]);
                $('#formEmpresa #facebook_empresa').val(valores[10]);
                $('#formEmpresa #sitioweb_empresa').val(valores[11]);
                $('#formEmpresa #horario_empresa').val(valores[12]);

                if (valores[5] == 1) {
                    $('#formEmpresa #empresa_activo').attr('checked', true);
                } else {
                    $('#formEmpresa #empresa_activo').attr('checked', false);
                }

                // ====== AJUSTE: cargar imágenes existentes desde enterprise (no SERVERURLLOGO)
                if (valores[13] && valores[13] !== 'image_preview.png') {
                    cargarImagenExistente('logo', valores[13]);
                } else {
                    $('#logoPreview').html('').hide();
                    $('#logoInfo').text('Ningún archivo seleccionado');
                }

                if (valores[14] && valores[14] !== '') {
                    cargarImagenExistente('firma', valores[14]);
                } else {
                    $('#firmaPreview').html('').hide();
                    $('#firmaInfo').text('Ningún archivo seleccionado');
                }

                //HABILITAR OBJETOS
                $('#formEmpresa #empresa_empresa').attr('readonly', false);
                $('#formEmpresa #rtn_empresa').attr('readonly', false);
                $('#formEmpresa #telefono_empresa').attr('readonly', false);
                $('#formEmpresa #correo_empresa').attr('readonly', false);
                $('#formEmpresa #direccion_empresa').attr('readonly', false);
                $('#formEmpresa #empresa_activo').attr('disabled', false);
                $('#formEmpresa #empresa_razon_social').attr('readonly', false);
                $('#formEmpresa #empresa_otra_informacion').attr('readonly', false);
                $('#formEmpresa #empresa_eslogan').attr('disabled', false);
                $('#formEmpresa #empresa_celular').attr('disabled', false);

                $('#formEmpresa #proceso_empresa').val("Editar");
                $('#modal_registrar_empresa').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
    });
}

var eliminar_empresa_dataTable = function(tbody, table) {
  $(tbody).off("click", "button.table_eliminar");
  $(tbody).on("click", "button.table_eliminar", function() {
    var data = table.row($(this).parents("tr")).data();

    var empresa_id = data.empresa_id;
    var nombreEmpresa = data.nombre;

    var mensajeHTML = `¿Desea eliminar permanentemente la empresa?<br><br>
                       <strong>Nombre:</strong> ${nombreEmpresa}`;

    swal({
      title: "Confirmar eliminación",
      content: { element: "span", attributes: { innerHTML: mensajeHTML } },
      icon: "warning",
      buttons: {
        cancel: { text: "Cancelar", value: null, visible: true, className: "btn-light" },
        confirm:{ text: "Sí, eliminar", value: true, className: "btn-danger", closeModal: false }
      },
      dangerMode: true,
      closeOnEsc: false,
      closeOnClickOutside: false
    }).then((confirmar) => {
      if (!confirmar) return;

      $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL;?>ajax/eliminarEmpresaAjax.php', // asegúrate que este endpoint devuelve JSON
        data: { empresa_id: empresa_id },
        dataType: 'json',
        beforeSend: function() {
          if (typeof showLoading === 'function') showLoading("Eliminando registro...");
        },
        success: function(response) {
          swal.close();
          if (response && response.status === "success") {
            if (typeof showNotify === 'function') {
              showNotify("success", response.title || "Eliminación exitosa", response.message || "Empresa eliminada correctamente");
            }
            table.ajax.reload(null, false);
            table.search('').draw();
          } else {
            if (typeof showNotify === 'function') {
              showNotify("error", (response && response.title) || "Error", (response && response.message) || "No se pudo eliminar la empresa");
            }
          }
        },
        error: function(xhr) {
          swal.close();
          const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : "Ocurrió un error al procesar la solicitud";
          if (typeof showNotify === 'function') showNotify("error", "Error", msg);
          // console.error(xhr.responseText);
        }
      });
    });
  });
}
//FIN ACCIONES FROMULARIO EMPRESA

/*INICIO FORMULARIO EMPRESA*/
function modal_empresa() {
    $('#formEmpresa').attr({
        'data-form': 'save'
    });
    $('#formEmpresa').attr({
        'action': '<?php echo SERVERURL;?>ajax/agregarEmpresaAjax.php'
    });
    $('#formEmpresa')[0].reset();
    $('#reg_empresa').show();
    $('#edi_empresa').hide();
    $('#delete_empresa').hide();

    CleanEnterpriseImage();

    //HABILITAR OBJETOS
    $('#formEmpresa #empresa_empresa').attr('readonly', false);
    $('#formEmpresa #rtn_empresa').attr('readonly', false);
    $('#formEmpresa #telefono_empresa').attr('readonly', false);
    $('#formEmpresa #correo_empresa').attr('readonly', false);
    $('#formEmpresa #direccion_empresa').attr('readonly', false);
    $('#formEmpresa #empresa_activo').attr('disabled', false);
    $('#formEmpresa #empresa_razon_social').attr('readonly', false);
    $('#formEmpresa #empresa_otra_informacion').attr('readonly', false);
    $('#formEmpresa #empresa_eslogan').attr('disabled', false);
    $('#formEmpresa #empresa_celular').attr('disabled', false);

    $('#formEmpresa #proceso_empresa').val("Registro");
    $('#modal_registrar_empresa').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}
/*FIN FORMULARIO EMPRESA*/

// Función para cargar imágenes existentes al editar
function cargarImagenExistente(tipo, rutaImagen) {
    const ENTERPRISE_URL = '<?php echo rtrim(SERVERURL, "/") . ENTERPRISE_PATH; ?>';
    const preview = tipo === 'logo' ? $('#logoPreview') : $('#firmaPreview');
    const info = tipo === 'logo' ? $('#logoInfo') : $('#firmaInfo');
    const input = tipo === 'logo' ? $('#logotipo') : $('#firma_documento');
    
    if (rutaImagen && rutaImagen !== 'image_preview.png' && rutaImagen !== '') {
        const rutaCompleta = ENTERPRISE_URL + rutaImagen; // AJUSTE: enterprise
        preview.html(`
            <div style="position: relative; display: inline-block;">
                <img src="${rutaCompleta}" alt="Imagen existente" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                <button type="button" class="btn-remove-image" title="Eliminar imagen" style="position: absolute; top: 5px; right: 5px; background: rgba(255,0,0,0.7); color: white; border: none; border-radius: 50%; width: 25px; height: 25px; padding: 0;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `).show();
        info.text(rutaImagen);
        
        // Configurar botón para eliminar
        preview.find('.btn-remove-image').on('click', function(e) {
            e.stopPropagation();
            preview.html('').hide();
            info.text('Ningún archivo seleccionado');
            input.val('');
        });
    } else {
        preview.html('').hide();
        info.text('Ningún archivo seleccionado');
    }
}

$(document).ready(function() {
    $("#modal_registrar_empresa").on('shown.bs.modal', function() {
        $(this).find('#formEmpresa #empresa_razon_social').focus();
    });
});

$('#formEmpresa #label_empresa_activo').html("Activo");

$('#formEmpresa .switch').change(function() {
    if ($('input[name=empresa_activo]').is(':checked')) {
        $('#formEmpresa #label_empresa_activo').html("Activo");
        return true;
    } else {
        $('#formEmpresa #label_empresa_activo').html("Inactivo");
        return false;
    }
});

$('#toggle-firma').on('click', function(e) {
    e.preventDefault();
    const $toggleButton = $(this);

    // Determinar el estado basado en el texto del botón
    const estado = $toggleButton.text().includes('Ocultar Firma') ? 0 : 1;

    // Enviar el estado actualizado a la base de datos
    $.ajax({
        url: '<?php echo SERVERURL;?>core/SaveEstadoFirma.php',
        type: 'POST',
        data: {
            estado: estado
        },
        success: function(response) {
            try {
                const jsonResponse = JSON.parse(response);

                // Manejar la respuesta del servidor
                showNotify(jsonResponse.type, jsonResponse.title, jsonResponse.text);

                // Actualizar el estado del botón
                GetEstadoBotonFirma();
            } catch (error) {
                console.error('Error al analizar la respuesta JSON:', error);
            }
        },
        error: function(xhr, status, error) {
            $('.RespuestaAjax').html(
                '<p class="text-center text-danger">Hubo un problema al procesar la solicitud. Por favor, inténtelo de nuevo.</p>'
            );
        }
    });
});

function GetEstadoBotonFirma() {
    // Obtener el estado inicial y configurar el texto y el ícono del botón
    $.ajax({
        url: '<?php echo SERVERURL;?>core/GetEstadoBotonFirma.php',
        dataType: 'json',
        success: function(response) {
            if (response.error) {
                console.error('Error al obtener el estado de la firma:', response.error);
                return;
            }

            const isFirmaVisible = response.estado === 'visible';

            const $toggleButton = $('#toggle-firma');

            // Configurar el texto y el ícono del botón según el estado
            if (isFirmaVisible) {
                $toggleButton.html('<i class="fas fa-eye-slash"></i> Ocultar Firma');
            } else {
                $toggleButton.html('<i class="fas fa-eye"></i> Mostrar Firma');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al obtener el estado de la firma:', error);
        }
    });
}
</script> 