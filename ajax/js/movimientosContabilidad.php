<script>
$(() => {
  listar_movimientos_contabilidad();

  // Buscar
  $('#formMainMovimientosContabilidad #search').on("click", function (e) {
    e.preventDefault();
    listar_movimientos_contabilidad();
  });

  // Limpiar (reset)
  $('#formMainMovimientosContabilidad').on('reset', function () {
    $(this).find('.selectpicker')
      .val('')
      .selectpicker('refresh');

    listar_movimientos_contabilidad();
  });
});

var listar_movimientos_contabilidad = function () {
  // Limpia estado guardado (por si acaso)
  try {
    var _dtKey = 'DataTables_' + 'dataTableMovimientosContabilidad' + '_' + window.location.pathname;
    localStorage.removeItem(_dtKey);
  } catch (e) { /* ignore */ }

  var fechai = $("#formMainMovimientosContabilidad #fechai").val();
  var fechaf = $("#formMainMovimientosContabilidad #fechaf").val();

  // ==== Utils (mismo criterio que en Reporte de Ventas) ====
  function toNumber(val) {
    if (typeof val === "number") return val;
    return parseFloat(String(val).replace(/[^\d.-]/g, "")) || 0;
  }

  function formatMoney(n) {
    try {
      return Number(n).toLocaleString('es-HN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    } catch (e) {
      var s = (Number(n) || 0).toFixed(2);
      return s.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
  }

  function moneyRender(data, type) {
    var n = toNumber(data);
    if (type === 'display') {
      var color = n < 0 ? 'red' : 'green';
      // font inherit para que NO cambie tamaño vs resto de celdas
      return '<span style="color:'+color+';font-size:inherit;font-weight:inherit;line-height:inherit">L ' + formatMoney(n) + '</span>';
    }
    return n; // sort/filter numérico
  }
  // =========================================================

  var table_movimientos_contabilidad = $("#dataTableMovimientosContabilidad").DataTable({
    destroy: true,
    ajax: {
      method: "POST",
      url: "<?php echo SERVERURL;?>core/llenarDataTableMovimientosCuentasContabilidad.php",
      data: { fechai: fechai, fechaf: fechaf }
    },
    columns: [
      { data: "fecha" },                   // 'YYYY-MM-DD HH:mm:ss'
      { data: "codigo" },
      { data: "nombre" },
      { data: "ingreso", className: "dt-body-right", render: moneyRender },
      { data: "egreso",  className: "dt-body-right", render: moneyRender },
      { data: "saldo",   className: "dt-body-right", render: moneyRender }
    ],
    lengthMenu: lengthMenu10,
    stateSave: false,
    orderMulti: false,
    language: idioma_español,
    dom: dom,
    order: [[0, "desc"]],                  // último movimiento (y su saldo) primero
    columnDefs: [
      { width: "16.66%", targets: 0 },
      { width: "10.66%", targets: 1 },
      { width: "22.66%", targets: 2 },
      { width: "16.66%", targets: 3 },
      { width: "16.66%", targets: 4 },
      { width: "16.66%", targets: 5 }
    ],
    buttons: [
      {
        text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
        titleAttr: 'Actualizar Registro Movimientos Contables',
        className: 'table_actualizar btn btn-secondary ocultar',
        action: function () { listar_movimientos_contabilidad(); }
      },
      {
        extend: 'excelHtml5',
        text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
        titleAttr: 'Excel',
        title: 'Reporte Registro Movimientos Contables',
        messageTop: function(){
          return 'Fecha desde: ' + convertDateFormat(fechai) + '  Fecha hasta: ' + convertDateFormat(fechaf);
        },
        messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
        className: 'table_reportes btn btn-success ocultar'
      },
      {
        extend: 'pdf',
        text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
        titleAttr: 'PDF',
        title: 'Reporte Registro Movimientos Contables',
        messageTop: function(){
          return 'Fecha desde: ' + convertDateFormat(fechai) + '  Fecha hasta: ' + convertDateFormat(fechaf);
        },
        messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
        className: 'table_reportes btn btn-danger ocultar',
        customize: function (doc) {
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
    initComplete: function () {
      this.api().order([0, 'desc']).draw();
      $('#buscar').focus();
    },
    drawCallback: function () {
      getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
    }
  });

  // Limpia búsqueda global al cargar
  table_movimientos_contabilidad.search('').draw();
};
// FIN ACCIONES FORMULARIO MOVIMIENTOS CONTABLES
</script>