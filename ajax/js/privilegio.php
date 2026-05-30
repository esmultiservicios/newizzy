<script>
$(document).ready(function () {
  listar_privilegio();

  $('#form_main_privilegios #search').on('click', function (e) {
    e.preventDefault();
    listar_privilegio();
  });

  // Reset filtros
  $('#form_main_privilegios').on('reset', function () {
    $(this).find('.selectpicker').val('').selectpicker('refresh');
    listar_privilegio();
  });
});

/* ------------ Helpers ------------ */
function setAsignados($el, n) {
  n = Math.max(0, parseInt(n, 10) || 0);
  $el.text(`${n} ${n === 1 ? 'asignado' : 'asignados'}`);
}
function asigToBool(val) {
  if (typeof val === 'boolean') return val;
  if (typeof val === 'number') return val === 1;
  if (typeof val === 'string') return val === '1' || val.toLowerCase() === 'true';
  return false;
}

  /* =========================================================
   HEADER DINÁMICO - PRIVILEGIOS
   ========================================================= */
   function construirHeaderDataTablePrivilegio() {
    var $tabla = $("#dataTablePrivilegio");

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Privilegio</th>' +
                '<th>Estado</th>' +
                '<th>Menús</th>' +
                '<th>Submenús</th>' +
                '<th>Submenús 1</th>' +
            '</tr>' +
        '</thead>'
    );
}

/* ------------ DataTable Privilegios ------------ */
var listar_privilegio = function () {
    var estado = $('#form_main_privilegios #estado_privilegios').val();

    if ($.fn.DataTable.isDataTable("#dataTablePrivilegio")) {
        $("#dataTablePrivilegio").DataTable().clear().destroy();
    }

    construirHeaderDataTablePrivilegio();

    var table_privilegio = $('#dataTablePrivilegio').DataTable({
        destroy: true,
        ajax: {
            method: 'POST',
            url: '<?php echo SERVERURL;?>core/llenarDataTablePrivilegio.php',
            data: {
                estado: estado
            }
        },
        columns: [
            {
                data: null,
                orderable: false,
                searchable: false,
                className: "text-center align-middle",
                render: function(data, type, row) {
                    if (type !== "display") {
                        return "";
                    }

                    return '' +
                        '<div class="dropdown acciones-dropdown">' +

                            '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                                '<i class="fas fa-cog"></i>' +
                                '<span>Acciones</span>' +
                            '</button>' +

                            '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +

                                '<button type="button" class="dropdown-item accion-item table_accesos menu" ' +
                                    'data-plan-id="' + row.planes_id + '" ' +
                                    'data-plan-nombre="' + row.nombre + '">' +
                                    '<span class="accion-icon accion-icon-primary">' +
                                        '<i class="fas fa-link"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Asignar Menú</span>' +
                                '</button>' +

                                '<button type="button" class="dropdown-item accion-item table_accesos submenu" ' +
                                    'data-plan-id="' + row.planes_id + '" ' +
                                    'data-plan-nombre="' + row.nombre + '">' +
                                    '<span class="accion-icon accion-icon-primary">' +
                                        '<i class="fas fa-link"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Asignar Submenú</span>' +
                                '</button>' +

                                '<button type="button" class="dropdown-item accion-item table_accesos submenu1" ' +
                                    'data-plan-id="' + row.planes_id + '" ' +
                                    'data-plan-nombre="' + row.nombre + '">' +
                                    '<span class="accion-icon accion-icon-primary">' +
                                        '<i class="fas fa-link"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Asignar Submenú 1</span>' +
                                '</button>' +

                                '<div class="dropdown-divider"></div>' +

                                '<button type="button" class="dropdown-item accion-item accion-editar table_editar">' +
                                    '<span class="accion-icon accion-icon-editar">' +
                                        '<i class="fas fa-edit"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Editar</span>' +
                                '</button>' +

                                '<button type="button" class="dropdown-item accion-item accion-eliminar table_eliminar1 table_eliminar">' +
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
                data: 'nombre'
            },
            {
                data: 'estado',
                render: function(data, type) {
                    if (type === 'display') {
                        var estadoText = data == 1 ? 'Activo' : 'Inactivo';

                        var icon = data == 1
                            ? '<i class="fas fa-check-circle mr-1"></i>'
                            : '<i class="fas fa-times-circle mr-1"></i>';

                        var badgeClass = data == 1
                            ? 'badge badge-pill badge-success'
                            : 'badge badge-pill badge-danger';

                        return '<span class="' +
                            badgeClass +
                            '" style="font-size: 0.95rem; padding: 0.5em 0.8em; font-weight: 600;">' +
                            icon +
                            estadoText +
                            '</span>';
                    }

                    return data;
                }
            },
            {
                data: null,
                className: "text-center",
                render: function(data, type, row) {
                    const count = row.menus_asignados || 0;

                    return '<span class="badge badge-pill badge-primary" id="contador-menus-' + row.planes_id + '">' +
                        count + ' asignados' +
                    '</span>';
                }
            },
            {
                data: null,
                className: "text-center",
                render: function(data, type, row) {
                    const count = row.submenus_asignados || 0;

                    return '<span class="badge badge-pill badge-info" id="contador-submenus-' + row.planes_id + '">' +
                        count + ' asignados' +
                    '</span>';
                }
            },
            {
                data: null,
                className: "text-center",
                render: function(data, type, row) {
                    const count = row.submenus1_asignados || 0;

                    return '<span class="badge badge-pill badge-secondary" id="contador-submenus1-' + row.planes_id + '">' +
                        count + ' asignados' +
                    '</span>';
                }
            }
        ],
        lengthMenu: lengthMenu,
        stateSave: true,
        bDestroy: true,
        language: idioma_español,
        dom: dom,
        columnDefs: [
            {
                width: '12%',
                targets: 0,
                orderable: false,
                searchable: false,
                className: "text-center text-nowrap align-middle"
            },
            {
                width: '38%',
                targets: 1
            },
            {
                width: '12%',
                targets: 2,
                className: "text-center text-nowrap align-middle"
            },
            {
                width: '12%',
                targets: 3,
                className: "text-center text-nowrap"
            },
            {
                width: '13%',
                targets: 4,
                className: "text-center text-nowrap"
            },
            {
                width: '13%',
                targets: 5,
                className: "text-center text-nowrap"
            }
        ],
        buttons: [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Privilegios',
                className: 'btn btn-secondary',
                action: function () {
                    listar_privilegio();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg"></i> Ingresar',
                titleAttr: 'Agregar Privilegios',
                className: 'btn btn-primary',
                action: function () {
                    modal_privilegios();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte Privilegios',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'btn btn-success',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                title: 'Reporte Privilegios',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'btn btn-danger',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5]
                },
                customize: function (doc) {
                    if (imagen) {
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
        drawCallback: function () {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());

            if (typeof cerrarDropdownAcciones === "function") {
                cerrarDropdownAcciones();
            }
        }
    });

    table_privilegio.search('').draw();
    $('#buscar').focus();

    accesos_privilegio_menu_dataTable('#dataTablePrivilegio tbody', table_privilegio);
    accesos_privilegio_submenu_dataTable('#dataTablePrivilegio tbody', table_privilegio);
    accesos_privilegio_submenu1_dataTable('#dataTablePrivilegio tbody', table_privilegio);
    editar_privilegio_dataTable('#dataTablePrivilegio tbody', table_privilegio);
    eliminar_privilegio_dataTable('#dataTablePrivilegio tbody', table_privilegio);
};

/* ------------ Botones abrir modales ------------ */
var accesos_privilegio_menu_dataTable = function (tbody, table) {
  $(tbody).off('click', 'button.menu');
  $(tbody).on('click', 'button.menu', function () {
    var data = table.row($(this).parents('tr')).data();
    $('#formMenuAccesos #privilegio_id_accesos').val(data.privilegio_id);
    $('#modal_registrar_menuaccesos .modal-title').text(`Privilegios - Menús: ${data.nombre}`);
    listar_menuaccesos();
    $('#formMenuAccesos').attr({ 'data-form': 'save', action: '<?php echo SERVERURL;?>ajax/addMenuAccesosAjax.php' });
    $('#modal_registrar_menuaccesos').modal({ show: true, keyboard: false, backdrop: 'static' });
  });
};

var accesos_privilegio_submenu_dataTable = function (tbody, table) {
  $(tbody).off('click', 'button.submenu');
  $(tbody).on('click', 'button.submenu', function () {
    var data = table.row($(this).parents('tr')).data();
    $('#formSubMenuAccesos #privilegio_id_accesos').val(data.privilegio_id);
    $('#modal_registrar_submenuaccesos  .modal-title').text(`Privilegios - Submenus de nivel 1: ${data.nombre}`);
    listar_submenuaccesos();
    $('#formSubMenuAccesos').attr({ 'data-form': 'save', action: '<?php echo SERVERURL;?>ajax/addSubMenuAccesosAjax.php' });
    $('#modal_registrar_submenuaccesos').modal({ show: true, keyboard: false, backdrop: 'static' });
  });
};

var accesos_privilegio_submenu1_dataTable = function (tbody, table) {
  $(tbody).off('click', 'button.submenu1');
  $(tbody).on('click', 'button.submenu1', function () {
    var data = table.row($(this).parents('tr')).data();
    $('#formSubMenu1Accesos #privilegio_id_accesos').val(data.privilegio_id);
    $('#modal_registrar_submenu1accesos  .modal-title').text(`Privilegios - Menús: ${data.nombre}`);
    listar_submenu1accesos();
    $('#formSubMenu1Accesos').attr({ 'data-form': 'save', action: '<?php echo SERVERURL;?>ajax/addSubMenu1AccesosAjax.php' });
    $('#modal_registrar_submenu1accesos').modal({ show: true, keyboard: false, backdrop: 'static' });
  });
};

/* ------------ Listas dentro de modales ------------ */
var listar_menuaccesos = function () {
  var privilegio_id_accesos = $('#formMenuAccesos #privilegio_id_accesos').val();

  $('#dataTableMenuAccesos').DataTable({
    destroy: true,
    ajax: {
      method: 'POST',
      url: '<?php echo SERVERURL;?>core/llenarDataTableMenuAccesos.php',
      data: { privilegio_id_accesos },
      dataSrc: function (json) {
        let contador = 0;
        json.data.forEach((d) => {
          if (d.asignado) contador++;
        });
        setAsignados($(`#contador-menuaccesos-${privilegio_id_accesos}`), contador);

        if (json.data.length === 0) {
          showNotify('warning', 'Sin Plan Asignado', 'No tiene un plan asignado');
          return;
        }

        return json.data.map((menu, index) => ({
          '#': index + 1,
          menu: menu.descripcion,
          asignado: menu.asignado
            ? '<span class="badge badge-success">Asignado</span>'
            : '<span class="badge badge-secondary">No asignado</span>',
          acciones: `<button class="btn btn-sm ${menu.asignado ? 'btn-danger' : 'btn-success'} btn-toggle-menuacceso"
              data-menu-id="${menu.menu_id}" data-asignado="${menu.asignado ? 1 : 0}">
              ${menu.asignado ? '<i class="fas fa-times"></i> Quitar' : '<i class="fas fa-plus"></i> Asignar'}
            </button>`,
        }));
      },
    },
    columns: [{ data: '#' }, { data: 'menu' }, { data: 'asignado' }, { data: 'acciones' }],
    lengthMenu: lengthMenu20,
    stateSave: true,
    language: idioma_español,
    dom: dom,
    buttons: [],
  });
};

$(document).ready(function () {
  $('#modal_registrar_menuaccesos').on('shown.bs.modal', function () {
    $(this).find('#formMenuAccesos #buscar').focus();
  });
});

/* Submenu nivel 1 */
var listar_submenuaccesos = function () {
  var privilegio_id_accesos = $('#formSubMenuAccesos #privilegio_id_accesos').val();

  $('#dataTableSubMenuAccesos').DataTable({
    destroy: true,
    ajax: {
      method: 'POST',
      url: '<?php echo SERVERURL;?>core/llenarDataTableSubMenuAccesos.php',
      data: { privilegio_id_accesos },
      dataSrc: function (json) {
        let contador = 0;
        json.data.forEach((d) => {
          if (d.asignado) contador++;
        });
        setAsignados($(`#contador-submenuaccesos-${privilegio_id_accesos}`), contador);

        if (json.data.length === 0) {
          showNotify('warning', 'Sin Plan Asignado', 'No tiene un plan asignado');
          return;
        }

        return json.data.map((submenu, index) => ({
          '#': index + 1,
          menu: submenu.descripcion_padre,
          submenu: submenu.descripcion,
          asignado: submenu.asignado
            ? '<span class="badge badge-success">Asignado</span>'
            : '<span class="badge badge-secondary">No asignado</span>',
          acciones: `<button class="btn btn-sm ${submenu.asignado ? 'btn-danger' : 'btn-success'} btn-toggle-submenuacceso"
              data-submenu-id="${submenu.submenu_id}" data-asignado="${submenu.asignado ? 1 : 0}">
              ${submenu.asignado ? '<i class="fas fa-times"></i> Quitar' : '<i class="fas fa-plus"></i> Asignar'}
            </button>`,
        }));
      },
    },
    columns: [{ data: '#' }, { data: 'menu' }, { data: 'submenu' }, { data: 'asignado' }, { data: 'acciones' }],
    lengthMenu: lengthMenu20,
    stateSave: true,
    language: idioma_español,
    dom: dom,
    buttons: [],
  });
};

$(document).ready(function () {
  $('#modal_registrar_submenuaccesos').on('shown.bs.modal', function () {
    $(this).find('#formSubMenuAccesos #buscar').focus();
  });
});

/* Submenu nivel 2 */
var listar_submenu1accesos = function () {
  var privilegio_id_accesos = $('#formSubMenu1Accesos #privilegio_id_accesos').val();

  $('#dataTableSubMenu1Accesos').DataTable({
    destroy: true,
    ajax: {
      method: 'POST',
      url: '<?php echo SERVERURL;?>core/llenarDataTableSubMenu1Accesos.php',
      data: { privilegio_id_accesos },
      dataSrc: function (json) {
        let contador = 0;
        json.data.forEach((d) => {
          if (d.asignado) contador++;
        });
        setAsignados($(`#contador-submenu1accesos-${privilegio_id_accesos}`), contador);

        if (json.data.length === 0) {
          showNotify('warning', 'Sin Plan Asignado', 'No tiene un plan asignado');
          return;
        }

        return json.data.map((s1, index) => ({
          '#': index + 1,
          submenu: s1.descripcion,
          submenu1: s1.submenu_descripcion,
          asignado: s1.asignado
            ? '<span class="badge badge-success">Asignado</span>'
            : '<span class="badge badge-secondary">No asignado</span>',
          acciones: `<button class="btn btn-sm ${s1.asignado ? 'btn-danger' : 'btn-success'} btn-toggle-submenu1acceso"
              data-submenu1-id="${s1.submenu1_id}" data-asignado="${s1.asignado ? 1 : 0}">
              ${s1.asignado ? '<i class="fas fa-times"></i> Quitar' : '<i class="fas fa-plus"></i> Asignar'}
            </button>`,
        }));
      },
    },
    columns: [{ data: '#' }, { data: 'submenu' }, { data: 'submenu1' }, { data: 'asignado' }, { data: 'acciones' }],
    lengthMenu: lengthMenu20,
    stateSave: true,
    language: idioma_español,
    dom: dom,
    buttons: [],
  });
};

$(document).ready(function () {
  $('#modal_registrar_submenu1accesos').on('shown.bs.modal', function () {
    $(this).find('#formSubMenu1Accesos #buscar').focus();
  });
});

/* ------------ Editar / Eliminar (sin cambios de lógica) ------------ */
var editar_privilegio_dataTable = function (tbody, table) {
  $(tbody).off('click', 'button.table_editar');
  $(tbody).on('click', 'button.table_editar', function () {
    var data = table.row($(this).parents('tr')).data();
    var url = '<?php echo SERVERURL;?>core/editarPrivilegios.php';
    $('#formPrivilegios #privilegio_id_').val(data.privilegio_id);
    $('#formPrivilegios #privilegio_nombre').val(data.nombre);

    $.ajax({
      type: 'POST',
      url: url,
      data: $('#formPrivilegios').serialize(),
      success: function (registro) {
        var valores = eval(registro);
        $('#formPrivilegios').attr({ 'data-form': 'update', action: '<?php echo SERVERURL;?>ajax/modificarPrivilegioAjax.php' });
        $('#formPrivilegios')[0].reset();
        $('#reg_privilegios').hide();
        $('#edi_privilegios').show();
        $('#delete_privilegios').hide();
        $('#formPrivilegios #privilegios_nombre').val(valores[0]);

        if (valores[1] == 1) $('#formPrivilegios #privilegio_activo').attr('checked', true);
        else $('#formPrivilegios #privilegio_activo').attr('checked', false);

        $('#formPrivilegios #privilegios_nombre').attr('readonly', false);
        $('#formPrivilegios #privilegio_activo').attr('disabled', false);
        $('#formPrivilegios #estado_privilegios').show();

        $('#formPrivilegios #proceso_privilegios').val('Editar');
        $('#modal_registrar_privilegios').modal({ show: true, keyboard: false, backdrop: 'static' });
      },
    });
  });
};

var eliminar_privilegio_dataTable = function (tbody, table) {
  $(tbody).off('click', 'button.table_eliminar1');
  $(tbody).on('click', 'button.table_eliminar1', function () {
    var data = table.row($(this).parents('tr')).data();
    var privilegio_id = data.privilegio_id;
    var nombrePrivilegio = data.nombre;

    var mensajeHTML = `¿Desea eliminar permanentemente el privilegio?<br><br>
                        <strong>Nombre:</strong> ${nombrePrivilegio}`;

    swal({
      title: 'Confirmar eliminación',
      content: { element: 'span', attributes: { innerHTML: mensajeHTML } },
      icon: 'warning',
      buttons: {
        cancel: { text: 'Cancelar', value: null, visible: true, className: 'btn-light' },
        confirm: { text: 'Sí, eliminar', value: true, className: 'btn-danger', closeModal: false },
      },
      dangerMode: true,
      closeOnEsc: false,
      closeOnClickOutside: false,
    }).then((confirmar) => {
      if (confirmar) {
        $.ajax({
          type: 'POST',
          url: '<?php echo SERVERURL;?>ajax/eliminarPrivilegiosAjax.php',
          data: { privilegio_id: privilegio_id },
          dataType: 'json',
          before: function () {
            showLoading('Eliminando registro...');
          },
          success: function (response) {
            swal.close();
            if (response.status === 'success') {
              showNotify('success', response.title, response.message);
              table.ajax.reload(null, false);
              table.search('').draw();
            } else {
              showNotify('error', response.title, response.message);
            }
          },
          error: function () {
            swal.close();
            showNotify('error', 'Error', 'Ocurrió un error al procesar la solicitud');
          },
        });
      }
    });
  });
};

/* ------------ Botones toggle (actualizan contadores con texto) ------------ */
$(document).on('click', '.btn-toggle-menuacceso', function (e) {
  e.preventDefault();
  const btn = $(this);
  const menu_id = parseInt(btn.attr('data-menu-id'), 10);
  const asignado = asigToBool(btn.attr('data-asignado'));
  const privilegio_id = $('#formMenuAccesos #privilegio_id_accesos').val();
  const nuevoEstado = asignado ? 0 : 1;

  $.ajax({
    type: 'POST',
    url: '<?php echo SERVERURL;?>core/asignarMenuAcceso.php',
    dataType: 'json',
    data: { menu_id, privilegio_id, estado: nuevoEstado },
    success: function (res) {
      showNotify(res.type, res.title, res.message);

      btn.attr('data-asignado', asignado ? 0 : 1)
        .toggleClass('btn-success btn-danger')
        .html(asignado ? '<i class="fas fa-plus"></i> Asignar' : '<i class="fas fa-times"></i> Quitar');

      const badge = btn.closest('tr').find('span.badge');
      badge.toggleClass('badge-success badge-secondary')
           .text(asignado ? 'No asignado' : 'Asignado');

      const $c1 = $(`#contador-menuaccesos-${privilegio_id}`);
      const $c2 = $(`#contador-menus-${privilegio_id}`);

      const cur1 = parseInt($c1.text(), 10) || 0;
      const cur2 = parseInt($c2.text(), 10) || 0;

      setAsignados($c1, asignado ? cur1 - 1 : cur1 + 1);
      setAsignados($c2, asignado ? cur2 - 1 : cur2 + 1);
    },
    error: function (xhr) {
      console.error('Respuesta no JSON:', xhr.responseText);
      showNotify('error', 'Error', 'No se pudo procesar la solicitud.');
    },
  });
});

$(document).on('click', '.btn-toggle-submenuacceso', function (e) {
  e.preventDefault();
  const btn = $(this);
  const submenu_id = parseInt(btn.attr('data-submenu-id'), 10);
  const asignado = asigToBool(btn.attr('data-asignado'));
  const privilegio_id = $('#formSubMenuAccesos #privilegio_id_accesos').val();
  const nuevoEstado = asignado ? 0 : 1;

  $.ajax({
    type: 'POST',
    url: '<?php echo SERVERURL;?>core/asignarSubMenuAcceso.php',
    dataType: 'json',
    data: { submenu_id, privilegio_id, estado: nuevoEstado },
    success: function (res) {
      showNotify(res.type, res.title, res.message);

      btn.attr('data-asignado', asignado ? 0 : 1)
        .toggleClass('btn-success btn-danger')
        .html(asignado ? '<i class="fas fa-plus"></i> Asignar' : '<i class="fas fa-times"></i> Quitar');

      const badge = btn.closest('tr').find('span.badge');
      badge.toggleClass('badge-success badge-secondary')
           .text(asignado ? 'No asignado' : 'Asignado');

      const $c1 = $(`#contador-submenuaccesos-${privilegio_id}`);
      const $c2 = $(`#contador-submenus-${privilegio_id}`);

      const cur1 = parseInt($c1.text(), 10) || 0;
      const cur2 = parseInt($c2.text(), 10) || 0;

      setAsignados($c1, asignado ? cur1 - 1 : cur1 + 1);
      setAsignados($c2, asignado ? cur2 - 1 : cur2 + 1);
    },
    error: function (xhr) {
      console.error('Respuesta no JSON:', xhr.responseText);
      showNotify('error', 'Error', 'No se pudo procesar la solicitud.');
    },
  });
});

$(document).on('click', '.btn-toggle-submenu1acceso', function (e) {
  e.preventDefault();
  const btn = $(this);
  const submenu1_id = parseInt(btn.attr('data-submenu1-id'), 10);
  const asignado = asigToBool(btn.attr('data-asignado'));
  const privilegio_id = parseInt($('#formSubMenu1Accesos #privilegio_id_accesos').val(), 10) || 0;
  const nuevoEstado = asignado ? 0 : 1;

  if (!submenu1_id || !privilegio_id) {
    showNotify('error', 'Datos incompletos', 'No se pudo determinar el Submenú 2 o el privilegio.');
    return;
  }

  $.ajax({
    type: 'POST',
    url: '<?php echo SERVERURL;?>core/asignarSubMenu1Acceso.php',
    dataType: 'json',
    data: { submenu1_id, privilegio_id, estado: nuevoEstado },
    success: function (res) {
      showNotify(res.type, res.title, res.message);

      btn.attr('data-asignado', asignado ? 0 : 1)
        .toggleClass('btn-success btn-danger')
        .html(asignado ? '<i class="fas fa-plus"></i> Asignar' : '<i class="fas fa-times"></i> Quitar');

      const badge = btn.closest('tr').find('span.badge');
      badge.toggleClass('badge-success badge-secondary')
           .text(asignado ? 'No asignado' : 'Asignado');

      const $c1 = $(`#contador-submenu1accesos-${privilegio_id}`);
      const $c2 = $(`#contador-submenus1-${privilegio_id}`);

      const cur1 = parseInt($c1.text(), 10) || 0;
      const cur2 = parseInt($c2.text(), 10) || 0;

      setAsignados($c1, asignado ? cur1 - 1 : cur1 + 1);
      setAsignados($c2, asignado ? cur2 - 1 : cur2 + 1);
    },
    error: function (xhr) {
      console.error('Respuesta no JSON:', xhr.responseText);
      showNotify('error', 'Error', 'No se pudo procesar la solicitud.');
    },
  });
});

/* ------------ Modal Privilegios ------------ */
function modal_privilegios() {
  $('#formPrivilegios').attr({ 'data-form': 'save', action: '<?php echo SERVERURL;?>ajax/agregarPrivilegiosAjax.php' });
  $('#formPrivilegios')[0].reset();
  $('#reg_privilegios').show();
  $('#edi_privilegios').hide();
  $('#delete_privilegios').hide();
  $('#formPrivilegios #privilegios_nombre').attr('readonly', false);
  $('#formPrivilegios #privilegio_activo').attr('disabled', false);
  $('#formPrivilegios #estado_privilegios').hide();
  $('#formPrivilegios #proceso_privilegios').val('Registro');
  $('#modal_registrar_privilegios').modal({ show: true, keyboard: false, backdrop: 'static' });
}

$(document).ready(function () {
  $('#modal_registrar_privilegios').on('shown.bs.modal', function () {
    $(this).find('#formPrivilegios #privilegios_nombre').focus();
  });
});

$('#formPrivilegios #label_privilegio_activo').html('Activo');
$('#formPrivilegios .switch').change(function () {
  if ($('input[name=privilegio_activo]').is(':checked')) {
    $('#formPrivilegios #label_privilegio_activo').html('Activo');
    return true;
  } else {
    $('#formPrivilegios #label_privilegio_activo').html('Inactivo');
    return false;
  }
});
</script>