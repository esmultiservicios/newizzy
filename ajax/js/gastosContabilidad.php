<script>
// ===============================
//  Subida de archivo (PDF) - soporta #btnSelectPdf y el markup viejo
// ===============================
function setupFileUpload() {
  const fileDropArea   = document.getElementById('fileDropArea');
  const fileInput      = document.querySelector('.file-upload-input'); // <input type="file">
  const fileInfo       = document.getElementById('fileInfo');
  const filePreview    = document.getElementById('filePreview');
  const selectFileText = document.querySelector('.select-file-text'); // markup viejo (span clickeable)
  const btnSelectPdf   = document.getElementById('btnSelectPdf');     // NUEVO botón

  if (!fileDropArea || !fileInput || fileInput.dataset.initialized) return;
  fileInput.dataset.initialized = "true";

  let isProcessing = false;

  ['dragenter','dragover','dragleave','drop'].forEach(ev => {
    fileDropArea.addEventListener(ev, preventDefaults, false);
  });
  ['dragenter','dragover'].forEach(ev => fileDropArea.addEventListener(ev, highlight, false));
  ['dragleave','drop'].forEach(ev => fileDropArea.addEventListener(ev, unhighlight, false));

  fileDropArea.addEventListener('drop', handleDrop, false);
  document.addEventListener('paste', handlePaste);

  // Soporta AMBOS: el texto clickeable viejo y el nuevo botón
  if (selectFileText) {
    selectFileText.addEventListener('click', (e) => {
      e.stopPropagation();
      fileInput.click();
    });
  }
  if (btnSelectPdf) {
    btnSelectPdf.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      fileInput.click();
    });
  }

  fileInput.addEventListener('change', handleFiles, false);

  function preventDefaults(e){ e.preventDefault(); e.stopPropagation(); }
  function highlight(){ fileDropArea.classList.add('highlight'); }
  function unhighlight(){ fileDropArea.classList.remove('highlight'); }

  function handleDrop(e){
    const dt = e.dataTransfer;
    const files = dt.files;
    processFiles(files);
  }

  function handlePaste(e){
    const items = (e.clipboardData || e.originalEvent.clipboardData).items;
    let file = null;
    for (let i=0;i<items.length;i++){
      if (items[i].kind === 'file' && items[i].type === 'application/pdf'){
        file = items[i].getAsFile();
        break;
      }
    }
    if (file){
      const dataTransfer = new DataTransfer();
      dataTransfer.items.add(file);
      processFiles(dataTransfer.files);
    }
  }

  function handleFiles(e){
    if (isProcessing) return;
    isProcessing = true;
    processFiles(e.target.files);
    isProcessing = false;
  }

  function processFiles(files){
    if (!files || !files.length) return;
    const file = files[0];

    if (file.type !== 'application/pdf'){
      showNotify('error','Error','Solo se permiten archivos PDF');
      clearFile();
      return;
    }
    if (file.size > 5*1024*1024){
      showNotify('error','Error','El archivo no debe exceder los 5MB');
      clearFile();
      return;
    }

    fileInfo.textContent = `Archivo seleccionado: ${file.name} (${(file.size/1024/1024).toFixed(2)} MB)`;
    filePreview.innerHTML = `
      <div class="pdf-preview">
        <i class="fas fa-file-pdf fa-2x" style="color:#dc3545;"></i>
        <p style="margin:0 8px; flex:1;">${file.name}</p>
        <button type="button" class="btn-remove-pdf" title="Eliminar archivo">
          <i class="fas fa-trash-alt"></i>
        </button>
      </div>`;
    filePreview.style.display = 'flex';
    filePreview.style.alignItems = 'center';

    filePreview.querySelector('.btn-remove-pdf').addEventListener('click', clearFile);
  }

  function clearFile(){
    fileInput.value = '';
    fileInfo.textContent = 'Ningún archivo seleccionado';
    filePreview.innerHTML = '';
    filePreview.style.display = 'none';
  }
}
document.addEventListener('DOMContentLoaded', setupFileUpload);

// ===============================
//  Utils formato dinero (miles coma, decimales punto)
// ===============================
function toNumber(val){
  if (val == null) return 0;
  if (typeof val === 'number') return val;
  return parseFloat(String(val).replace(/[^\d.-]/g,'')) || 0;
}
function formatMoney(n){
  try{
    return Number(n).toLocaleString('es-HN', { minimumFractionDigits:2, maximumFractionDigits:2 });
  }catch(e){
    var s = (Number(n)||0).toFixed(2);
    return s.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }
}
function moneyRender(data, type){
  var n = toNumber(data);
  if (type === 'display'){
    var color = n < 0 ? 'red' : 'green';
    return '<span style="color:'+color+';font-size:inherit;font-weight:inherit;line-height:inherit">L ' + formatMoney(n) + '</span>';
  }
  return n; // ordenar/filtrar como número
}

// ===============================
//  Ready
// ===============================
$(() => {
  listar_gastos_contabilidad();
  getEmpresaEgresos();
  getCuentaEgresos();
  getProveedorEgresos();
  getCategoriaGastos();

  $('#formMainGastosContabilidad #search').on('click', function(e){
    e.preventDefault();
    listar_gastos_contabilidad();
  });

  $('#formMainGastosContabilidad').on('reset', function(){
    $(this).find('.selectpicker').val('').selectpicker('refresh');
    listar_gastos_contabilidad();
  });
});

// ===============================
//  Totales footer
// ===============================
var total_gastos_footer = function(){
  var fechai = $("#formMainGastosContabilidad #fechai").val();
  var fechaf = $("#formMainGastosContabilidad #fechaf").val();

  $.ajax({
    url: '<?php echo SERVERURL;?>core/totalGastosFooter.php',
    type: 'POST',
    data: { fechai:fechai, fechaf:fechaf }
  })
  .done(function(data){
    data = JSON.parse(data || "{}");
    $("#total-footer-gastos").html('L ' + formatMoney(data.total));
    $("#subtotal-g").html('L ' + formatMoney(data.subtotal));
    $("#impuesto-g").html('L ' + formatMoney(data.impuesto));
    $("#descuento-g").html('L ' + formatMoney(data.descuento));
    $("#nc-g").html('L ' + formatMoney(data.nc));
  })
  .fail(function(){ console.log("total gastos error"); });
};

// ===============================
//  DataTable Gastos
// ===============================
var listar_gastos_contabilidad = function(){
  var estado = $("#formMainGastosContabilidad #estado_egresos").val() || 1;
  var fechai = $("#formMainGastosContabilidad #fechai").val();
  var fechaf = $("#formMainGastosContabilidad #fechaf").val();

  // Limpiar posible estado guardado (para que no cambie el orden)
  try{
    var _dtKey = 'DataTables_' + 'dataTableGastosContabilidad' + '_' + window.location.pathname;
    localStorage.removeItem(_dtKey);
  }catch(e){}

  var table_gastos_contabilidad = $("#dataTableGastosContabilidad").DataTable({
    destroy: true,
    stateSave: false,
    orderMulti: false,
    ajax: {
      method: "POST",
      url: "<?php echo SERVERURL;?>core/llenarDataTableEgresosContabilidad.php",
      data: { fechai:fechai, fechaf:fechaf, estado:estado }
    },
    columns: [
      { data: "fecha_registro" },   // ordenaremos por aquí DESC
      { data: "egresos_id" },
      { data: "categoria" },
      { data: "fecha" },
      { data: "nombre" },
      { data: "proveedor" },
      {
        data: "factura",
        render: function(data, type, row){
          if (type !== 'display') return data;
          let numeroFactura = data ? data : '';
          let icono = '';
          if (row.factura_pdf && row.factura_pdf !== '') {
            icono = `
              <a href="<?php echo SERVERURL; ?>vistas/plantilla/gastos/${row.factura_pdf}"
                 target="_blank"
                 class="btn btn-sm btn-outline-danger d-flex align-items-center justify-content-center factura-btn"
                 title="Ver/Descargar PDF" data-toggle="tooltip">
                 <i class="fas fa-file-pdf"></i>
              </a>`;
          }
          return `
            <div class="d-flex justify-content-between align-items-center w-100">
              <span>${numeroFactura}</span>
              ${icono}
            </div>`;
        }
      },
      { data: "subtotal",  className:"dt-body-right", render: moneyRender },
      { data: "impuesto",  className:"dt-body-right", render: moneyRender },
      { data: "descuento", className:"dt-body-right", render: moneyRender },
      { data: "nc",        className:"dt-body-right", render: moneyRender },
      { data: "total",     className:"dt-body-right", render: moneyRender },
      { data: "observacion" },
      {
        data: "estado",
        render: function(data, type){
          if (type !== 'display') return data;
          var ok = parseInt(data,10) === 1;
          var icon = ok ? '<i class="fas fa-check-circle mr-1"></i>' : '<i class="fas fa-times-circle mr-1"></i>';
          var cls  = ok ? 'badge badge-pill badge-success' : 'badge badge-pill badge-danger';
          return '<span class="'+cls+'" style="font-size:.95rem;padding:.5em .8em;font-weight:600;">'+icon+(ok?'Activo':'Inactivo')+'</span>';
        }
      },
      { defaultContent: "<button class='table_editar btn ocultar'><span class='fas fa-edit'></span>Editar</button>" },
      { defaultContent: "<button class='table_reportes print_gastos btn btn-success btn ocultar'><span class='fas fa-file-download fa-lg'></span>Reporte</button>" },
      { defaultContent: "<button class='table_cancelar anular_factura btn btn-danger ocultar'><span class='fas fa-ban fa-lg'></span> Anular</button>" }
    ],
    // Última fecha primero
    order: [[0, 'desc']],
    lengthMenu: lengthMenu10,
    language: idioma_español,
    dom: dom,
    columnDefs: [
      { width: "7.14%", targets: 0 },
      { width: "7.14%", targets: 1 },
      { width: "7.14%", targets: 2 },
      { width: "7.14%", targets: 3 },
      { width: "7.14%", targets: 4 },
      { width: "7.14%", targets: 5 },
      { width: "7.14%", targets: 6 },
      { width: "7.14%", targets: 7 },
      { width: "7.14%", targets: 8 },
      { width: "7.14%", targets: 9 },
      { width: "7.14%", targets:10 },
      { width: "7.14%", targets:11 },
      { width: "7.14%", targets:12 },
      { width: "7.14%", targets:13 }
    ],
    buttons: [
      {
        text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
        titleAttr: 'Actualizar Registro Gastos',
        className: 'table_actualizar btn btn-secondary ocultar',
        action: function(){ listar_gastos_contabilidad(); }
      },
      {
        text: '<i class="fas fas fa-plus fa-lg crear"></i> Ingresar',
        titleAttr: 'Agregar Egresos',
        className: 'table_crear btn btn-primary ocultar',
        action: function(){ modal_egresos_contabilidad(); }
      },
      {
        text: '<i class="fas fa-layer-group fa-lg crear"></i> Categorías',
        titleAttr: 'Categorías',
        className: 'table_crear btn btn-primary ocultar',
        action: function(){ modal_categorias_contabilidad(); }
      },
      {
        text: '<i class="fas fa-layer-group fa-lg crear"></i> Reporte',
        titleAttr: 'Reporte Categorías',
        className: 'table_crear btn btn-primary ocultar',
        action: function(){ modal_reporte_categorias_contabilidad(); }
      },
      {
        extend: 'excelHtml5',
        footer: true,
        text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
        titleAttr: 'Excel',
        title: 'Reporte Registro Gastos',
        messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' + convertDateFormat(fechaf),
        messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
        exportOptions: { columns: [0,1,2,3,4,5,6,7,8,9,10,11] },
        className: 'table_reportes btn btn-success ocultar'
      },
      {
        extend: 'pdfHtml5',
        footer: true,
        text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
        titleAttr: 'PDF',
        orientation: 'landscape',
        pageSize: 'LEGAL',
        title: 'Reporte Registro Gastos',
        messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' + convertDateFormat(fechaf),
        messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
        className: 'table_reportes btn btn-danger ocultar',
        exportOptions: { columns: [0,1,2,3,4,5,6,7,8,9,10,11] },
        customize: function(doc){
          if (typeof imagen !== 'undefined' && imagen){
            doc.content.splice(0,0,{ image:imagen, width:100, height:45, margin:[0,0,0,12] });
          }
        }
      }
    ],
    initComplete: function(){
      this.api().order([0,'desc']).draw();
      $('#buscar').focus();
    },
    drawCallback: function(){
      getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
    }
  });

  // Tooltips al redibujar
  $('#dataTableGastosContabilidad').on('draw.dt', function(){
    $('[data-toggle="tooltip"]').tooltip();
  });

  table_gastos_contabilidad.search('').draw();
  $('#buscar').focus();

  edit_reporte_gastos_dataTable("#dataTableGastosContabilidad tbody", table_gastos_contabilidad);
  view_reporte_gastos_dataTable("#dataTableGastosContabilidad tbody", table_gastos_contabilidad);
  anular_gastos_dataTable("#dataTableGastosContabilidad tbody", table_gastos_contabilidad);
  total_gastos_footer();
};

// ===============================
//  Reset UI PDF (editar)
// ===============================
function resetPdfUI(){
  const $preview = $('#filePreview');
  const $info    = $('#fileInfo');
  const $form    = $('#formEgresosContables');
  const fileInput = document.querySelector('.file-upload-input');

  $preview.stop(true,true).hide().empty();
  $info.text('Ningún archivo seleccionado');
  if (fileInput) fileInput.value = '';
  $form.find('input[name="remove_existing_file"]').remove();
}

// ===============================
//  Acciones de tabla (editar / reporte / anular)
// ===============================
var edit_reporte_gastos_dataTable = function (tbody, table) {
  $(tbody).off("click", "button.table_editar");
  $(tbody).on("click", "button.table_editar", function () {
    var data = table.row($(this).parents("tr")).data();
    var url  = '<?php echo SERVERURL;?>core/editarGastos.php';

    // ID al form + reset UI PDF
    $('#formEgresosContables #egresos_id').val(data.egresos_id);
    resetPdfUI();

    // 1) Cargar proveedores y luego el gasto
    $.ajax({
      url: "<?php echo SERVERURL; ?>core/getProveedores.php",
      type: "POST",
      dataType: "json",
      beforeSend: function () {
        $('#formEgresosContables #proveedor_egresos')
          .html('<option value="">Cargando proveedores...</option>')
          .selectpicker('refresh');
      }
    })
    .done(function (response) {
      const select = $('#formEgresosContables #proveedor_egresos');
      select.empty();

      if (response.success && response.data.length > 0) {
        select.append('<option value="">Seleccione proveedor</option>');
        response.data.forEach(proveedor => {
          select.append(
            `<option value="${proveedor.proveedores_id}" data-subtext="${proveedor.rtn || 'Sin RTN o Identidad'}">${proveedor.nombre}</option>`
          );
        });
        select.selectpicker('refresh');

        // 2) Cargar el registro del egreso
        $.ajax({
          type: 'POST',
          url: url,
          data: $('#formEgresosContables').serialize(),
          success: function (registro) {
            var valores = eval(registro); // [proveedor_id, cuenta_id, empresa_id, fecha, factura, subtotal, isv, descuento, nc, total, obs, pdf]

            // Preparar form para UPDATE
            $('#formEgresosContables').attr({
              'data-form': 'update',
              'action': '<?php echo SERVERURL;?>ajax/modificarGastosAjax.php'
            })[0].reset();

            // Mostrar/ocultar botones
            $('#reg_egresosContabilidad').hide();
            $('#edi_egresosContabilidad').show();
            $('#delete_egresosContabilidad').hide();

            // Campos
            $('#formEgresosContables #pro_egresos_contabilidad').val("Editar Egresos");

            // Fecha del registro (NO se modifica ni se recuerda)
            var fechaReg = valores[3];
            var $form  = $('#formEgresosContables');
            var $fecha = $form.find('#fecha_egresos');

            $fecha.val(fechaReg).prop('disabled', true);

            // Modo editar: oculta hint por CSS y desactiva cualquier “remember”
            $form.addClass('modo-editar');
            $fecha.off(); // corta handlers directos
            $fecha.removeAttr('data-remember data-rem-key');

            // Resto de campos
            $('#formEgresosContables #factura_egresos').val(valores[4]);
            $('#formEgresosContables #subtotal_egresos').val(valores[5]);
            $('#formEgresosContables #isv_egresos').val(valores[6]);
            $('#formEgresosContables #descuento_egresos').val(valores[7]);
            $('#formEgresosContables #nc_egresos').val(valores[8]);
            $('#formEgresosContables #total_egresos').val(valores[9]);
            $('#formEgresosContables #observacion_egresos').val(valores[10]);

            // Selects
            $('#formEgresosContables #cuenta_egresos').val(valores[1]).selectpicker('refresh');
            $('#formEgresosContables #empresa_egresos').val(valores[2]).selectpicker('refresh');

            // PDF existente
            if (valores[11] && valores[11] !== '') {
              $('#filePreview').html(`
                <div class="existing-file d-flex align-items-center p-2 border rounded bg-light">
                  <i class="fas fa-file-pdf fa-2x text-danger mr-3"></i>
                  <div class="flex-grow-1">
                    <div class="small text-muted">Archivo actual</div>
                    <div class="font-weight-bold">${valores[11]}</div>
                  </div>
                  <div class="btn-group ml-2">
                    <a href="<?php echo SERVERURL; ?>vistas/plantilla/gastos/${valores[11]}"
                       target="_blank"
                       class="btn btn-danger btn-sm">
                      <i class="fas fa-file-pdf mr-1"></i> Ver/Descargar PDF
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm" id="removeFile">
                      <i class="fas fa-exchange-alt mr-1"></i> Cambiar archivo
                    </button>
                  </div>
                </div>
              `).show();

              $('#fileInfo').text('Archivo actual: ' + valores[11]);

              $('#removeFile').on('click', function () {
                $('#filePreview').hide().html('');
                $('#fileInfo').text('Ningún archivo seleccionado');
                $('#factura_pdf').val('');
                $('<input>').attr({ type:'hidden', name:'remove_existing_file', value:'1' })
                  .appendTo('#formEgresosContables');
              });
            }

            setupFileUpload();

            // Seleccionar proveedor del registro (si existe en el combo)
            var proveedorId = valores[0];
            if (proveedorId) {
              var optionExists = select.find('option[value="' + proveedorId + '"]').length > 0;
              select.val(optionExists ? proveedorId : '').selectpicker('refresh');
            }

            // Deshabilitar campos que no deben editarse
            $('#formEgresosContables #cuenta_egresos').prop('disabled', true);
            $('#formEgresosContables #empresa_egresos').prop('disabled', true);
            $('#formEgresosContables #subtotal_egresos').prop('disabled', true);
            $('#formEgresosContables #isv_egresos').prop('disabled', true);
            $('#formEgresosContables #descuento_egresos').prop('disabled', true);
            $('#formEgresosContables #nc_egresos').prop('disabled', true);
            $('#formEgresosContables #total_egresos').prop('disabled', true);
            $('#formEgresosContables #buscar_cuenta_egresos').hide();
            $('#formEgresosContables #buscar_empresa_egresos').hide();

            // Abrir modal
            $('#modalEgresosContables').modal({ show: true, keyboard: false, backdrop: 'static' });

            // Reforzar valor en el input (sin tocar localStorage)
            $('#modalEgresosContables').one('shown.bs.modal', function(){
              var intentos = 0;
              var fix = function(){
                var $f = $('#formEgresosContables #fecha_egresos');
                $f.off();
                $f.val(fechaReg).attr('value', fechaReg).prop('defaultValue', fechaReg);
                if (++intentos < 8) setTimeout(fix, 60);
              };
              fix();
            });
          },
          error: function (xhr) {
            console.error('Error al cargar datos del gasto:', xhr.responseText);
            showNotify("error", "Error", "No se pudieron cargar los datos del gasto");
          }
        });
      } else {
        select.append('<option value="">No hay proveedores disponibles</option>').selectpicker('refresh');
        showNotify("warning", "Advertencia", "No hay proveedores disponibles para seleccionar");
      }
    })
    .fail(function (xhr) {
      console.error('Error al cargar proveedores:', xhr.responseText);
      $('#formEgresosContables #proveedor_egresos')
        .html('<option value="">Error al cargar proveedores</option>')
        .selectpicker('refresh');
      showNotify("error", "Error", "No se pudieron cargar los proveedores");
    });
  });
};

$(document).on('hidden.bs.modal', '#modalEgresosContables', function(){
  $(this).removeData('mode');
  // salir de modo editar y re-habilitar campo para "Nuevo"
  $('#formEgresosContables').removeClass('modo-editar');
  $('#formEgresosContables #fecha_egresos')
    .prop('disabled', false)
    .removeAttr('data-original-fecha');
});

var view_reporte_gastos_dataTable = function(tbody, table){
  $(tbody).off("click","button.print_gastos");
  $(tbody).on("click","button.print_gastos", function(e){
    e.preventDefault();
    var data = table.row($(this).parents("tr")).data();
    printGastos(data.egresos_id);
  });
};
function printGastos(egresos_id){
  var url = '<?php echo SERVERURL; ?>core/generaGastos.php?egresos_id=' + egresos_id;
  window.open(url);
}

var anular_gastos_dataTable = function (tbody, table){
  $(tbody).off("click","button.anular_factura");
  $(tbody).on("click","button.anular_factura", function(e){
    e.preventDefault();

    const rowData = table.row($(this).parents("tr")).data();
    if (!rowData){ showNotify("error","Error","No se pudo obtener la fila seleccionada."); return; }

    const egresos_id = rowData.egresos_id;
    const content = document.createElement("div");
    content.innerHTML = `
      <p style="margin:0 0 6px 0;">Se marcará como <b>ANULADO</b> y se registrará el <b>reintegro</b> en la cuenta.</p>
      <p style="margin:0;"><b>Esto NO es un ingreso por venta</b>; es un <u>reintegro por cancelación</u>.</p>`;

    swal({
      title: "¿Anular egreso?",
      content: content,
      icon: "warning",
      buttons: { cancel:{text:"Cancelar",visible:true}, confirm:{text:"Sí, anular"} },
      dangerMode: true, closeOnEsc:false, closeOnClickOutside:false
    }).then(function(ok){
      if (!ok) return;

      const payload = {
        egresos_id: egresos_id,
        proveedor_egresos: (rowData.proveedores_id != null ? rowData.proveedores_id : rowData.proveedor_egresos),
        cuenta_egresos:    (rowData.cuentas_id     != null ? rowData.cuentas_id     : rowData.cuenta_egresos),
        fecha_egresos: rowData.fecha,
        factura_egresos: rowData.factura,
        subtotal_egresos:  toNumber(rowData.subtotal_raw  ?? rowData.subtotal),
        isv_egresos:       toNumber(rowData.isv_raw       ?? rowData.impuesto_raw ?? rowData.impuesto),
        descuento_egresos: toNumber(rowData.descuento_raw ?? rowData.descuento),
        nc_egresos:        toNumber(rowData.nc_raw        ?? rowData.nc),
        total_egresos:     toNumber(rowData.total_raw     ?? rowData.total),
      };
      const obsOriginal = rowData.observacion ? ` | Obs: ${rowData.observacion}` : "";
      payload.observacion_egresos = `[ANULACIÓN] Reintegro por cancelación del egreso ID ${egresos_id}` + obsOriginal;

      if (!payload.cuenta_egresos){ showNotify("error","Error","No se pudo determinar la cuenta del egreso."); return; }
      if (!payload.proveedor_egresos){ showNotify("error","Error","No se pudo determinar el proveedor del egreso."); return; }

      $.ajax({
        url: "<?php echo SERVERURL;?>ajax/cancelEgresoContabilidadAjax.php",
        type: "POST",
        data: payload,
        success: function(){
          listar_gastos_contabilidad();
          total_gastos_footer();
          showNotify("success","Egreso anulado","Se marcó como anulado y se registró el reintegro correctamente.");
        },
        error: function(xhr){
          showNotify("error","Error","No se pudo anular el egreso: " + xhr.statusText);
        }
      });
    });
  });
};

// ===============================
//  Modales y auxiliares (sin cambios sustanciales)
// ===============================
function modal_egresos_contabilidad(){
  getCategoriaGastos();
  $('#formEgresosContables').removeClass('modo-editar');

  // limpiar UI PDF
  $('#formEgresosContables #filePreview').hide();
  $('#formEgresosContables #fileInfo').text('Ningún archivo seleccionado');
  $('#formEgresosContables #factura_pdf').val('');
  setupFileUpload();

  // preparar form para "guardar"
  $('#formEgresosContables').attr({
    'data-form':'save',
    'action':'<?php echo SERVERURL;?>ajax/addEgresoContabilidadAjax.php'
  });

  // reset total del form
  $('#formEgresosContables')[0].reset();
  $('#formEgresosContables select.selectpicker').val('').selectpicker('refresh');
  $('#formEgresosContables input[type="text"], #formEgresosContables input[type="number"], #formEgresosContables textarea').val('');

  // >>> RE-APLICAR FECHA MEMORIZADA INMEDIATAMENTE DESPUÉS DEL RESET <<<
  // Usa la misma key que pusiste en data-rem-key del input de fecha.
  setTimeout(function () {
    var remembered = localStorage.getItem('egresos:lastFecha');
    if (!remembered) {
      var d = new Date();
      var mm = String(d.getMonth() + 1).padStart(2, '0');
      var dd = String(d.getDate()).padStart(2, '0');
      remembered = d.getFullYear() + '-' + mm + '-' + dd;
    }
    var $f = $('#formEgresosContables #fecha_egresos');
    if ($f.length) {
      $f.val(remembered)
        .prop('defaultValue', remembered)
        .attr('value', remembered)
        .trigger('change'); // vuelve a guardar por si acaso
    }
  }, 0);

  // botones
  $('#reg_egresosContabilidad').show();
  $('#edi_egresosContabilidad').hide();
  $('#delete_egresosContabilidad').hide();

  // habilitar campos
  $('#formEgresosContables #cuenta_codigo').prop("readonly", false);
  $('#formEgresosContables #cuenta_nombre').prop("readonly", false);
  $('#formEgresosContables #cuentas_activo').prop('disabled', false).prop('checked', false);
  $('#formEgresosContables #buscar_cuenta_egresos').show();
  $('#formEgresosContables #buscar_empresa_egresos').show();
  $('#formEgresosContables #cuenta_egresos').prop('disabled', false).val('');
  $('#formEgresosContables #empresa_egresos').prop('disabled', false).val('');
  $('#formEgresosContables #subtotal_egresos').prop('disabled', false).val('');
  $('#formEgresosContables #isv_egresos').prop('disabled', false).val('');
  $('#formEgresosContables #descuento_egresos').prop('disabled', false).val('');
  $('#formEgresosContables #nc_egresos').prop('disabled', false).val('');
  $('#formEgresosContables #total_egresos').prop('disabled', false).val('');

  $('#formEgresosContables #pro_egresos_contabilidad').val("Registrar Egresos");

  // limpiar PDF otra vez por si acaso
  $('#filePreview').html('').hide();
  $('#fileInfo').text('Ningún archivo seleccionado');
  $('#factura_pdf').val('');
  setupFileUpload();

  // abrir modal
  $('#modalEgresosContables').modal({
    show:true, keyboard:false, backdrop:'static'
  });
}

function modal_categorias_contabilidad(){
  $('#formCategoriaEgresos')[0].reset();
  $('#regCategoriaEgresos').show();
  listar_categoria_egresos();
  $('#modalCategoriasEgresos').modal({ show:true, keyboard:false, backdrop:'static' });
}

$("#formCategoriaEgresos").on('submit', function(e){
  e.preventDefault();
  var form = $(this);
  var categoria = $('#categoria').val().trim();
  var url = '<?php echo SERVERURL;?>ajax/addCategoriaEgresos.php';
  var formData = form.serialize();

  swal({
    title: "¿Estás seguro?",
    text: "¿Desea registrar la categoría: " + categoria + "?",
    icon: "warning",
    buttons: { cancel:{text:"Cancelar",visible:true}, confirm:{text:"¡Sí, registrar!"} },
    dangerMode: true, closeOnEsc:false, closeOnClickOutside:false
  }).then((ok)=>{
    if (!ok) return;
    $.ajax({
      type:'POST', url:url, data:formData, dataType:'json',
      success:function(r){
        if (r && r.success){
          showNotify('success', r.title||'Éxito', r.text||'Operación realizada correctamente');
          $('#formCategoriaEgresos')[0].reset();
          listar_categoria_egresos();
        }else{
          showNotify('error', r.title||'Error', r.text||'Ocurrió un error');
        }
      },
      error:function(xhr){ showNotify('error','Error','Error en la conexión: ' + xhr.statusText); }
    });
  });
});

function modal_editar_categorias_contabilidad(categoria_gastos_id, categoria){
  $('#formUpdateCategoriaEgresos').attr({ 'data-form':'update', 'action':'<?php echo SERVERURL;?>ajax/modificarCategoriaEgresos.php' })[0].reset();
  $('#ediCategoriaEgresos').show();
  $('#formUpdateCategoriaEgresos #categoria_gastos_id').val(categoria_gastos_id);
  $('#formUpdateCategoriaEgresos #categoria').val(categoria);
  $('#formUpdateCategoriaEgresos #pro_categoriaEgresos').val("Editar Categorias");

  $('#modalUpdateCategoriasEgresos').modal({ show:true, keyboard:false, backdrop:'static' });

  $('#formUpdateCategoriaEgresos').off('submit').on('submit', function(e){
    e.preventDefault();
    var form = $(this);
    var url  = form.attr('action');
    var formData = form.serialize();
    var categoria = $('#formUpdateCategoriaEgresos #categoria').val().trim();

    swal({
      title:"¿Estás seguro?",
      text:"¿Desea actualizar la categoría a: " + categoria + "?",
      icon:"warning",
      buttons:{ cancel:{text:"Cancelar",visible:true}, confirm:{text:"¡Sí, actualizar!"} },
      dangerMode:true, closeOnEsc:false, closeOnClickOutside:false
    }).then((ok)=>{
      if (!ok) return;
      $.ajax({
        type:'POST', url:url, data:formData, dataType:'json',
        success:function(r){
          if (r && r.success){
            showNotify('success', r.title||'Éxito', r.text||'Cambios guardados correctamente');
            if (r.function) { try{ eval(r.function); }catch(e){} }
            listar_categoria_egresos();
          }else{
            showNotify('error', r.title||'Error', r.text||'Ocurrió un error al actualizar');
            if (r.redirect) window.location.href = r.redirect;
          }
        },
        error:function(xhr){ showNotify('error','Error','Error en la conexión: ' + xhr.statusText); }
      });
    });
  });
}

function modal_reporte_categorias_contabilidad(){
  listar_reporte_categoria_egresos();
  $('#modalReporteCategorias').modal({ show:true, keyboard:false, backdrop:'static' });
}

function getProveedorEgresos(){
  $.ajax({
    url: "<?php echo SERVERURL; ?>core/getProveedores.php",
    type: "POST",
    dataType: "json",
    success: function(response){
      const select = $('#formEgresosContables #proveedor_egresos');
      select.empty();
      if (response.success){
        response.data.forEach(p=>{
          select.append(`<option value="${p.proveedores_id}" data-subtext="${p.rtn || 'Sin RTN o Identidad'}">${p.nombre}</option>`);
        });
      }else{
        select.append('<option value="">No hay colaboradores disponibles</option>');
      }
      select.selectpicker('refresh');
    },
    error: function(xhr){
      showNotify("error","Error","Error de conexión al cargar colaboradores");
      $('#formEgresosContables #proveedor_egresos').html('<option value="">Error al cargar</option>').selectpicker('refresh');
    }
  });
}
function getCategoriaGastos(){
  var url = '<?php echo SERVERURL;?>core/getCategoriaGastos.php';
  $.ajax({
    type:"POST", url:url, async:true,
    success:function(data){
      $('#formEgresosContables #categoria_gastos').html(data).selectpicker('refresh');
      $('#formEgresosContables #categoria_gastos').val(0).selectpicker('refresh');
    }
  });
}
function getCuentaEgresos(){
  var url = '<?php echo SERVERURL;?>core/getCuenta.php';
  $.ajax({
    type:"POST", url:url, async:true,
    success:function(data){
      $('#formEgresosContables #cuenta_egresos').html(data).selectpicker('refresh');
    }
  });
}
function getEmpresaEgresos(){
  var url = '<?php echo SERVERURL;?>core/getEmpresa.php';
  $.ajax({
    type:"POST", url:url, async:true,
    success:function(data){
      $('#formEgresosContables #empresa_egresos').html(data).selectpicker('refresh');
    }
  });
}

// Cálculos en formulario (sin cambios de lógica)
$(document).ready(function(){
  function recalc(){
    var s  = parseFloat($("#formEgresosContables #subtotal_egresos").val())  || 0;
    var i  = parseFloat($("#formEgresosContables #isv_egresos").val())       || 0;
    var d  = parseFloat($("#formEgresosContables #descuento_egresos").val()) || 0;
    var nc = parseFloat($("#formEgresosContables #nc_egresos").val())        || 0;
    var total = s + i - d - nc;
    $("#formEgresosContables #total_egresos").val(parseFloat(total).toFixed(2));
  }
  $("#formEgresosContables #subtotal_egresos").on("keyup", recalc);
  $("#formEgresosContables #isv_egresos").on("keyup", recalc);
  $("#formEgresosContables #descuento_egresos").on("keyup", recalc);
  $("#formEgresosContables #nc_egresos").on("keyup", recalc);
});

// Focos modales (igual)
$(document).ready(function(){
  $("#modalCategoriasEgresos").on('shown.bs.modal', function(){ $(this).find('#formCategoriaEgresos #categoria').focus(); });
  $("#modalReporteCategorias").on('shown.bs.modal', function(){ $(this).find('#formularioReporteCategorias #buscar').focus(); });
  $("#modalUpdateCategoriasEgresos").on('shown.bs.modal', function(){ $(this).find('#formUpdateCategoriaEgresos #categoria').focus(); });
  $("#modalCategoriasEgresos").on('shown.bs.modal', function(){ $(this).find('#formCategoriaEgresos #t').focus(); });
  $("#modal_registrar_proveedores").on('shown.bs.modal', function(){ $(this).find('#formProveedores #nombre_proveedores').focus(); });
});

// Lista de categorías (sin cambios de negocio)
var listar_categoria_egresos = function(){
  var table_categoria_egresos = $("#DatatableCategoriaEgresos").DataTable({
    destroy:true,
    ajax:{ method:"POST", url:"<?php echo SERVERURL; ?>core/llenarDataTableCategoriaEgresos.php" },
    columns:[
      { data:"nombre" },
      { defaultContent:"<button class='table_editar btn btn-dark ocultar'><span class='fas fa-edit fa-lg'></span>Editar</button>" },
      { defaultContent:"<button class='table_eliminar btn btn-dark ocultar'><span class='fa fa-trash fa-lg'></span>Eliminar</button>" }
    ],
    lengthMenu:lengthMenu,
    stateSave:true,
    bDestroy:true,
    language:idioma_español,
    dom:dom,
    columnDefs:[
      { width:"70%", targets:0 },
      { width:"15%", targets:1 },
      { width:"15%", targets:2 }
    ],
    autoWidth:false,
    buttons:[
      { text:'<i class="fas fa-sync-alt fa-lg"></i> Actualizar', titleAttr:'Actualizar Categoría Egresos', className:'table_actualizar btn btn-secondary ocultar', action:function(){ listar_categoria_egresos(); } },
      { extend:'excelHtml5', text:'<i class="fas fa-file-excel fa-lg"></i> Excel', titleAttr:'Excel', title:'Reporte Categoría Egresos', messageBottom:'Fecha de Reporte: ' + convertDateFormat(today()), className:'table_reportes btn btn-success ocultar', exportOptions:{ columns:[0] } },
      { extend:'pdf', text:'<i class="fas fa-file-pdf fa-lg"></i> PDF', titleAttr:'PDF', title:'Reporte Categoría Egresos', messageBottom:'Fecha de Reporte: ' + convertDateFormat(today()), className:'table_reportes btn btn-danger ocultar', exportOptions:{ columns:[0] },
        customize:function(doc){ doc.content.splice(1,0,{ margin:[0,0,0,12], alignment:'left', image:imagen, width:100, height:45 }); } }
    ],
    drawCallback:function(){ getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario()); }
  });
  table_categoria_egresos.search('').draw();
  $('#buscar').focus();

  edit_categoria_gastos_dataTable("#DatatableCategoriaEgresos tbody", table_categoria_egresos);
  delete_categoria_gastos_dataTable("#DatatableCategoriaEgresos tbody", table_categoria_egresos);
};

var edit_categoria_gastos_dataTable = function(tbody, table){
  $(tbody).off("click","button.table_editar");
  $(tbody).on("click","button.table_editar", function(e){
    e.preventDefault();
    var data = table.row($(this).parents("tr")).data();
    modal_editar_categorias_contabilidad(data.categoria_gastos_id, data.nombre);
  });
};

var delete_categoria_gastos_dataTable = function(tbody, table){
  $(tbody).off("click","button.table_eliminar");
  $(tbody).on("click","button.table_eliminar", function(e){
    e.preventDefault();
    var data = table.row($(this).parents("tr")).data();
    swal({
      title:"¿Estas seguro?",
      text:"¿Desea eliminar la categoria: " + data.nombre + "?",
      icon:"warning",
      buttons:{ cancel:{text:"Cancelar",visible:true}, confirm:{text:"¡Sí, eliminar la categoria!"} },
      dangerMode:true, closeOnEsc:false, closeOnClickOutside:false
    }).then((ok)=>{ if (ok===true) deleteCategoriaGastos(data.categoria_gastos_id, data.nombre); });
  });
};

function deleteCategoriaGastos(categoria_gastos_id, categoria){
  var url = '<?php echo SERVERURL;?>core/deleteCategoriaGastos.php';
  $.ajax({
    type:"POST", url:url, dataType:"json",
    data:{ categoria_gastos_id:categoria_gastos_id, categoria:categoria },
    success:function(r){
      if (r && r.success){ showNotify('success', r.title||'Éxito', r.text||'Categoría eliminada correctamente'); listar_categoria_egresos(); }
      else{ showNotify('error', r.title||'Error', r.text||'Ocurrió un error al eliminar'); }
    },
    error:function(xhr){ showNotify('error','Error','Error en la conexión: ' + xhr.statusText); }
  });
}

var listar_reporte_categoria_egresos = function(){
  var fechai = $("#formularioReporteCategorias #fechai").val();
  var fechaf = $("#formularioReporteCategorias #fechaf").val();

  var table_reporte_categoria = $("#DatatableReporteCategorias").DataTable({
    destroy:true,
    ajax:{ method:"POST", url:"<?php echo SERVERURL; ?>core/llenarDataTableReporteCategoriaEgresos.php", data:{ fechai:fechai, fechaf:fechaf } },
    columns:[
      { data:"categoria" },
      { data:"monto", className:"dt-body-right", render: moneyRender }
    ],
    lengthMenu:lengthMenu,
    stateSave:true,
    bDestroy:true,
    language:idioma_español,
    dom:dom,
    columnDefs:[ { width:"70%", targets:0 }, { width:"30%", targets:1 } ],
    autoWidth:false,
    buttons:[
      { text:'<i class="fas fa-sync-alt fa-lg"></i> Actualizar', titleAttr:'Actualizar Categoría Egresos', className:'table_actualizar btn btn-secondary ocultar', action:function(){ listar_reporte_categoria_egresos(); } },
      { extend:'excelHtml5', text:'<i class="fas fa-file-excel fa-lg"></i> Excel', titleAttr:'Excel', title:'Reporte Categoría Egresos', messageBottom:'Fecha de Reporte: ' + convertDateFormat(today()), className:'table_reportes btn btn-success ocultar', exportOptions:{ columns:[0,1] } },
      { extend:'pdf', text:'<i class="fas fa-file-pdf fa-lg"></i> PDF', titleAttr:'PDF', title:'Reporte Categoría Egresos', messageBottom:'Fecha de Reporte: ' + convertDateFormat(today()), className:'table_reportes btn btn-danger ocultar', exportOptions:{ columns:[0,1] },
        customize:function(doc){ doc.content.splice(1,0,{ margin:[0,0,0,12], alignment:'left', image:imagen, width:100, height:45 }); } }
    ],
    drawCallback:function(){ getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario()); }
  });
  table_reporte_categoria.search('').draw();
  $('#buscar').focus();
  total_reporte_categoria_gastos_footer();
};

$('#formularioReporteCategorias #search').on('click', function(e){
  e.preventDefault();
  listar_reporte_categoria_egresos();
});

var total_reporte_categoria_gastos_footer = function(){
  var fechai = $("#formularioReporteCategorias #fechai").val();
  var fechaf = $("#formularioReporteCategorias #fechaf").val();

  $.ajax({
    url: '<?php echo SERVERURL;?>core/totalReporteCategoriaGastosFooter.php',
    type: "POST",
    data: { fechai:fechai, fechaf:fechaf }
  })
  .done(function(data){
    data = JSON.parse(data || "{}");
    $("#monto-i").html("L. " + formatMoney(data.monto));
  })
  .fail(function(){ console.log("total ingreso error"); });
};

$('#btnNuevoProveedor').on('click', function(){ modal_proveedores(); });
</script>