<script>
var sueldo_diario = 0;

$(() => {
    getTipoContrato();
    getPagoPlanificado();
    getTipoEmpleado();
    getEmpresa();    
    getEmpleado();    
    getTipoNomina();
    getCuentaNominas();
    getEmpleadoVales();
    listar_vales();
    listar_nominas();

    $('#form_main_nominas #estado_nomina').val(0);
    $('#form_main_nominas #estado_nomina').selectpicker('refresh');

    $('#form_main_nominas #search').on("click", function(e) {
        e.preventDefault();
        listar_nominas();
    });

    // Evento para el botón de Limpiar (reset)
    $('#form_main_nominas').on('reset', function() {
        $(this).find('.selectpicker').val('').selectpicker('refresh');
        listar_nominas();
    });	   
});

/* =========================================================
   HEADER Y FOOTER DINÁMICO - NÓMINAS
   ========================================================= */

   function construirHeaderFooterDataTableNomina() {
    var $tabla = $("#dataTableNomina");

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Código</th>' +
                '<th>Detalle</th>' +
                '<th>Empresa</th>' +
                '<th>Fecha Inicio</th>' +
                '<th>Fecha Fin</th>' +
                '<th>Importe</th>' +
                '<th>Notas</th>' +
                '<th>Estado</th>' +
            '</tr>' +
        '</thead>' +
        '<tfoot class="bg-secondary">' +
            '<tr>' +
                '<td colspan="1">Total</td>' +
                '<td colspan="5"></td>' +
                '<td id="neto_importe"></td>' +
                '<td colspan="2"></td>' +
            '</tr>' +
        '</tfoot>'
    );
}


/* ============================
   LISTADO DE NÓMINAS
   ============================ */

var listar_nominas = function() {
    var estado = $("#form_main_nominas #estado_nomina").val() || 0;
    var tipo_contrato_id = $("#form_main_nominas #tipo_contrato_nomina").val() || 0;

    if ($.fn.DataTable.isDataTable("#dataTableNomina")) {
        $("#dataTableNomina").DataTable().clear().destroy();
    }

    construirHeaderFooterDataTableNomina();

    var table_nominas = $("#dataTableNomina").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableNomina.php",
            "data": {
                "estado": estado,
                "tipo_contrato_id": tipo_contrato_id
            }
        },
        "columns": [
            {
                "data": null,
                "orderable": false,
                "searchable": false,
                "className": "text-center align-middle",
                "render": function(data, type, row) {
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

                                '<button type="button" class="dropdown-item accion-item nomina_generar ocultar">' +
                                    '<span class="accion-icon">' +
                                        '<i class="fas fa-users-cog"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Generar Nómina</span>' +
                                '</button>' +

                                '<button type="button" class="dropdown-item accion-item voucher_pago ocultar">' +
                                    '<span class="accion-icon">' +
                                        '<i class="fas fa-file-invoice-dollar"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Voucher de Pago</span>' +
                                '</button>' +

                                '<button type="button" class="dropdown-item accion-item consolidado ocultar">' +
                                    '<span class="accion-icon">' +
                                        '<i class="fas fa-book"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Libro de Salarios</span>' +
                                '</button>' +

                                '<div class="dropdown-divider"></div>' +

                                '<button type="button" class="dropdown-item accion-item nomina_agregar ocultar">' +
                                    '<span class="accion-icon">' +
                                        '<i class="fas fa-folder-plus"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Crear</span>' +
                                '</button>' +

                                '<button type="button" class="dropdown-item accion-item accion-editar table_editar nomina_editar ocultar">' +
                                    '<span class="accion-icon accion-icon-editar">' +
                                        '<i class="fas fa-edit"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Editar</span>' +
                                '</button>' +

                                '<button type="button" class="dropdown-item accion-item accion-eliminar table_eliminar nomina_eliminar ocultar">' +
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
                "data": "nomina_id"
            },
            {
                "data": "detalle"
            },
            {
                "data": "empresa"
            },
            {
                "data": "fecha_inicio"
            },
            {
                "data": "fecha_fin"
            },
            {
                "data": "importe",
                render: function(data, type) {
                    var number = $.fn.dataTable.render
                        .number(',', '.', 2, 'L ')
                        .display(data);

                    if (type === 'display') {
                        let color = (data < 0) ? 'red' : 'green';

                        return '<span style="color:' + color + '">' + number + '</span>';
                    }

                    return number;
                }
            },
            {
                "data": "notas"
            },
            {
                "data": "estado",
                "render": function(data, type) {
                    if (type === 'display') {
                        var estadoText = data == 1 ? 'Generada' : 'Sin Generar';
                        var icon = data == 1 ?
                            '<i class="fas fa-check-circle mr-1"></i>' :
                            '<i class="fas fa-times-circle mr-1"></i>';
                        var badgeClass = data == 1 ?
                            'badge badge-pill badge-success' :
                            'badge badge-pill badge-danger';

                        return '<span class="' + badgeClass +
                            '" style="font-size: 0.95rem; padding: 0.5em 0.8em; font-weight: 600;">' +
                            icon + estadoText + '</span>';
                    }

                    return data;
                }
            }
        ],
        "lengthMenu": lengthMenu10,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [
            {
                width: "11%",
                targets: 0,
                orderable: false,
                searchable: false,
                className: "text-center text-nowrap align-middle"
            },
            {
                width: "6%",
                targets: 1
            },
            {
                width: "20%",
                targets: 2
            },
            {
                width: "12%",
                targets: 3
            },
            {
                width: "10%",
                targets: 4
            },
            {
                width: "10%",
                targets: 5
            },
            {
                width: "10%",
                targets: 6,
                className: "text-right text-nowrap align-middle"
            },
            {
                width: "15%",
                targets: 7
            },
            {
                width: "6%",
                targets: 8,
                className: "text-center text-nowrap align-middle"
            }
        ],
        "fnRowCallback": function(nRow, aData) {
            var number = $.fn.dataTable.render
                .number(',', '.', 2, 'L ')
                .display(aData['neto_importe']);

            $('#neto_importe').html(number);
        },
        "buttons": [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar listar_nominas',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_nominas();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg"></i> Registrar Nomina',
                titleAttr: 'Agregar Nomina',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modal_nominas();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg"></i> Registrar Vales',
                titleAttr: 'Agregar Nomina',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modal_vales();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Nomina Empleados',
                messageTop: 'Fecha: ' + convertDateFormat(today()),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                orientation: 'landscape',
                title: 'Nomina Empleados',
                messageTop: 'Fecha: ' + convertDateFormat(today()),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7]
                },
                customize: function(doc) {
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
        "drawCallback": function() {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());

            if (typeof cerrarDropdownAcciones === "function") {
                cerrarDropdownAcciones();
            }
        }
    });

    table_nominas.search('').draw();
    $('#buscar').focus();

    generar_nominas_dataTable("#dataTableNomina tbody", table_nominas);
    voucher_nominas_dataTable("#dataTableNomina tbody", table_nominas);
    libro_salarios_nominas_dataTable("#dataTableNomina tbody", table_nominas);
    crear_nominas_dataTable("#dataTableNomina tbody", table_nominas);
    editar_nominas_dataTable("#dataTableNomina tbody", table_nominas);
    eliminar_nominas_dataTable("#dataTableNomina tbody", table_nominas);
};

// Dentro de tu archivo JS, deja este handler tal cual
var generar_nominas_dataTable = function(tbody, table) {
    $(tbody).off("click", "a.nomina_generar");
    $(tbody).on("click", "a.nomina_generar", function() {
        var data = table.row($(this).parents("tr")).data();

        if ($('#form_main_nominas #estado_nomina').val() == 0) {
            // CONFIRMACIÓN (sí/no) → swal
            swal({
                title: "¿Estas seguro?",
                text: "¿Desea generar esta nomina?",
                icon: "warning",
                buttons: {
                    cancel: { text: "Cancelar", visible: true },
                    confirm: { text: "¡Sí, generar la nómina!" }
                },
                dangerMode: true,
                closeOnEsc: false,
                closeOnClickOutside: false
            }).then((ok) => {
                if (ok === true) {
                    genearNomina(data.nomina_id, data.empresa_id);
                }
            });
        } else {
            showNotify('error', 'Error', 'Lo sentimos, esta nomina ya ha sido generada');
        }
    });
};

// Llamada AJAX que espera JSON y, en éxito, muestra swal con 3 botones
// Voucher/Libro no cierran; solo "Cerrar" cierra. Se permite pulsarlos varias veces.
function genearNomina(nomina_id, empresa_id) {
    var url = '<?php echo SERVERURL; ?>core/generarNomina.php';

    $.ajax({
        type: "POST",
        url: url,
        data: { nomina_id: nomina_id, empresa_id: empresa_id },
        dataType: 'json',
        cache: false
    })
    .done(function(res){
        if (Number(res.status) === 1) {
            if (typeof listar_nominas === 'function') listar_nominas();

            // Abre el diálogo y engancha handlers sobre los botones del swal.
            function abrirDialogoImpresion(id, title, message){
                swal({
                    title: title || 'Nómina generada',
                    text:  message || 'La nómina se generó correctamente.',
                    icon:  'success',
                    buttons: {
                        voucher: { 
                            text: 'Imprimir Vouchers', 
                            value: 'voucher', 
                            className: 'btn btn-primary',
                            closeModal: false // mantener abierto
                        },
                        libro:   { 
                            text: 'Libro de Salarios', 
                            value: 'libro', 
                            className: 'btn btn-success',
                            closeModal: false // mantener abierto
                        },
                        cancel:  { 
                            text: 'Cerrar', 
                            value: null, 
                            visible: true, 
                            className: 'btn btn-light'
                            // (por defecto cierra)
                        }
                    },
                    closeOnClickOutside: false,
                    closeOnEsc: false
                });

                // Espera a que el DOM del swal exista y engancha eventos.
                setTimeout(function(){
                    // Evita duplicados con namespace
                    $(document)
                        .off('click.swalVoucher', '.swal-button--voucher')
                        .on('click.swalVoucher',  '.swal-button--voucher', function(e){
                            e.preventDefault();
                            if (typeof PrintVoucherPago === 'function') {
                                PrintVoucherPago(id);
                            }
                            // Quita el spinner y re-activa los botones
                            if (typeof swal.stopLoading === 'function') swal.stopLoading();
                        });

                    $(document)
                        .off('click.swalLibro', '.swal-button--libro')
                        .on('click.swalLibro',  '.swal-button--libro', function(e){
                            e.preventDefault();
                            if (typeof PrintLibroSalarios === 'function') {
                                PrintLibroSalarios(id);
                            }
                            if (typeof swal.stopLoading === 'function') swal.stopLoading();
                        });
                }, 0);
            }

            abrirDialogoImpresion(res.nomina_id, res.title, res.message);

        } else if (Number(res.status) === 6) {
            showNotify('warning', res.title || 'Advertencia', res.message || 'Configura la cuenta de la nómina.');
        } else if (Number(res.status) === 8) {
            showNotify('warning', res.title || 'Sin empleados', res.message || 'Agrega empleados al detalle antes de generar.');
        } else {
            showNotify('error', res.title || 'Error', res.message || 'No se pudo generar la nómina.');
        }
    })
    .fail(function(xhr){
        let msg = 'Error de conexión al generar la nómina.';
        try {
            const j = JSON.parse(xhr.responseText);
            if (j && j.message) msg = j.message;
        } catch(e){}
        showNotify('error', 'Error', msg);
    });
}

var crear_nominas_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.nomina_agregar");
    $(tbody).on("click", "button.nomina_agregar", function() {
        var data = table.row($(this).parents("tr")).data();
        $('#formNominaDetalles #nomina_id').val(data.nomina_id);
        $('#formNominaDetalles #nominad_numero').val(data.nomina_id);
        $("#form_main_nominas_detalles #nomina_id").val(data.nomina_id);
        $('#formNominaDetalles #nominad_detalle').val(data.detalle);
        $('#formNominaDetalles #pago_planificado_id').val(data.pago_planificado_id);
        $('#form_main_nominas_detalles #estado_nomina_detalles').val(data.estado).selectpicker('refresh');
        $('#form_main_nominas_detalles #fecha_inicio').val(data.fecha_inicio);
        $('#form_main_nominas_detalles #fecha_fin').val(data.fecha_fin);

        $("#nomina_principal").hide();
        $("#nomina_detalles").show();
        listar_nominas_detalles();
    });
};

var voucher_nominas_dataTable = function(tbody, table) {
    $(tbody).off("click", "a.voucher_pago");
    $(tbody).on("click", "a.voucher_pago", function() {
        var data = table.row($(this).parents("tr")).data();
        if (data.estado == 0) {
            showNotify('error', 'Error', 'Lo sentimos, la nomina no esta generada no se puede mostrar el reporte');
        } else {
            PrintVoucherPago(data.nomina_id);
        }
    });
};

var libro_salarios_nominas_dataTable = function(tbody, table) {
    $(tbody).off("click", "a.consolidado");
    $(tbody).on("click", "a.consolidado", function() {
        var data = table.row($(this).parents("tr")).data();
        if (data.estado == 0) {
            showNotify('error', 'Error', 'Lo sentimos, la nomina no esta generada no se puede mostrar el reporte');
        } else {
            PrintLibroSalarios(data.nomina_id);
        }
    });
};

function PrintVoucherPago(nomina_id){
    params = {
        "id": nomina_id,
        "type": "Voucher_izzy",
        "db": "<?php echo $GLOBALS['db']; ?>",
    }; 
    viewReport(params);   
}

function PrintLibroSalarios(nomina_id){
    params = {
        "id": nomina_id,
        "type": "Libro_salario_izzy",
        "db": "<?php echo $GLOBALS['db']; ?>",
    }; 
    viewReport(params);
}

/* ============================
   EDITAR NÓMINA (usa el FORM)
   ============================ */
var editar_nominas_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.nomina_editar");
    $(tbody).on("click", "button.nomina_editar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarNominas.php';
        $('#formNomina #nomina_id').val(data.nomina_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formNomina').serialize(),
            success: function(registro) {
                var valores = eval(registro);

                // Configurar el FORM para UPDATE (se envía por submit ajax abajo)
                $('#formNomina').attr({'data-form': 'update'});
                $('#formNomina').attr({'action': '<?php echo SERVERURL;?>ajax/modificarNominaAjax.php'});

                // UI
                $('#formNomina')[0].reset();
                $('#reg_nomina').hide();
                $('#edi_nomina').show();
                $('#delete_nomina').hide();

                // Cargar valores
                $('#formNomina #nomina_detale').val(valores[0]);
                $('#formNomina #nomina_pago_planificado_id').val(valores[1]).selectpicker('refresh');
                $('#formNomina #nomina_empresa_id').val(valores[2]).selectpicker('refresh');
                $('#formNomina #nomina_fecha_inicio').val(valores[3]);
                $('#formNomina #nomina_fecha_fin').val(valores[4]);
                $('#formNomina #nomina_importe').val(valores[5]);
                $('#formNomina #nomina_notas').val(valores[6]);
                $('#formNomina #tipo_nomina').val(valores[8]).selectpicker('refresh');
                $('#formNomina #pago_nomina').val(valores[9]).selectpicker('refresh');

                if (data.estado == 1) {
                    $('#edi_nomina').attr('disabled', true);
                    $('#formNomina #nomina_activo').prop('checked', true);
                    $('#formNomina #label_nomina_activo').html("Generada");
                } else {
                    $('#edi_nomina').attr('disabled', false);
                    $('#formNomina #nomina_activo').prop('checked', false);
                    $('#formNomina #label_nomina_activo').html("Sin Generar");
                }

                // Habilitar campos
                $('#formNomina #nomina_detale').prop('disabled', false);
                $('#formNomina #nomina_pago_planificado_id').prop('disabled', false);
                $('#formNomina #nomina_empresa_id').prop('disabled', false);
                $('#formNomina #tipo_nomina').prop('disabled', false);
                $('#formNomina #nomina_fecha_inicio').prop('readonly', false);
                $('#formNomina #nomina_fecha_fin').prop('readonly', false);
                $('#formNomina #nomina_importe').prop('readonly', false);
                $('#formNomina #nomina_notas').prop('disabled', false);
                $('#formNomina #search_nomina_notas_start').prop('disabled', false);
                $('#formNomina #search_nomina_notas_stop').prop('disabled', false);
                $('#formNomina #nomina_activo').prop('disabled', false);
                $('#formNomina #estado_nomina').show();

                $('#formNomina #proceso_nomina').val("Editar");

                $('#modal_registrar_nomina').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
    });
};

var eliminar_nominas_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.nomina_eliminar");
    $(tbody).on("click", "button.nomina_eliminar", function() {
        var data = table.row($(this).parents("tr")).data();

        var nomina_id = data.nomina_id;
        var detalleNomina = data.detalle; 
        
        var mensajeHTML = `¿Desea eliminar permanentemente la nomina?<br><br>
                        <strong>Nomina:</strong> ${detalleNomina}<br>
                        <strong>Número Nomina:</strong> ${nomina_id}`;
        
        // CONFIRMACIÓN → swal
        swal({
            title: "Confirmar eliminación",
            content: { element: "span", attributes: { innerHTML: mensajeHTML } },
            icon: "warning",
            buttons: {
                cancel: { text: "Cancelar", value: null, visible: true, className: "btn-light" },
                confirm: { text: "Sí, eliminar", value: true, className: "btn-danger", closeModal: false }
            },
            dangerMode: true,
            closeOnEsc: false,
            closeOnClickOutside: false
        }).then((confirmar) => {
            if (confirmar) {
                $.ajax({
                    type: 'POST',
                    url: '<?php echo SERVERURL;?>ajax/eliminarNominaAjax.php',
                    data: { nomina_id: nomina_id },
                    dataType: 'json',
                    beforeSend: function(){
                        showLoading("Eliminando registro...");
                    },
                    success: function(response) {
                        swal.close();
                        if(response.status === "success") {
                            showNotify("success", response.title || "Éxito", response.message || "Eliminado correctamente");
                            table.ajax.reload(null, false);
                            table.search('').draw();                    
                        } else {
                            showNotify("error", response.title || "Error", response.message || "No se pudo eliminar");
                        }
                    },
                    error: function() {
                        swal.close();
                        showNotify("error", "Error", "Ocurrió un error al procesar la solicitud");
                    }
                });
            }
        });		
    });
};

/* ============================
   MODAL NÓMINAS (abre y prepara el form)
   ============================ */
function modal_nominas() {
    $('#formNomina').attr({ 'data-form': 'save' });
    $('#formNomina').attr({ 'action': '<?php echo SERVERURL;?>ajax/addNominaAjax.php' });

    $('#formNomina')[0].reset();
    $('#reg_nomina').show();
    $('#edi_nomina').hide();
    $('#delete_nomina').hide();

    $('#formNomina #nomina_empresa_id').val(1).selectpicker('refresh');
    $('#formNomina #tipo_nomina').val(1).selectpicker('refresh');

    $("#formNomina #grupo_salario").hide();

    $('#formNomina #nomina_detale').prop('disabled', false);
    $('#formNomina #nomina_pago_planificado_id').prop('disabled', false);
    $('#formNomina #nomina_empresa_id').prop('disabled', false);
    $('#formNomina #tipo_nomina').prop('disabled', false);
    $('#formNomina #nomina_fecha_inicio').prop('readonly', false);
    $('#formNomina #nomina_fecha_fin').prop('readonly', false);
    $('#formNomina #nomina_importe').prop('readonly', false);
    $('#formNomina #nomina_notas').prop('disabled', false);
    $('#formNomina #search_nomina_notas_start').prop('disabled', false);
    $('#formNomina #search_nomina_notas_stop').prop('disabled', false);
    $('#formNomina #nomina_activo').prop('disabled', false);
    $('#formNomina #estado_nomina').hide();

    $('#formNomina #proceso_nomina').val("Registro Nomina Empleados");

    $('#modal_registrar_nomina').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

/* ============================
   SUBMIT AJAX #formNomina (JSON)
   ============================ */
$(document).off('submit', '#formNomina').on('submit', '#formNomina', function (e) {
    e.preventDefault();

    var $form = $(this);
    var url = $form.attr('action') || '<?php echo SERVERURL;?>ajax/addNominaAjax.php';
    var modo = ($form.attr('data-form') || 'save').toLowerCase();
    var $btn = (modo === 'update') ? $('#edi_nomina') : (modo === 'delete') ? $('#delete_nomina') : $('#reg_nomina');

    if (!$form[0].checkValidity()) {
        $form[0].reportValidity();
        return;
    }

    $btn.prop('disabled', true).append(' <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

    $.ajax({
        url: url,
        type: 'POST',
        data: $form.serialize(),
        dataType: 'json',
        cache: false
    })
    .done(function (res) {
        if (res.status === 'success') {
            $('#modal_registrar_nomina').modal('hide');
            $form[0].reset();
            $form.find('.selectpicker').selectpicker('refresh');

            if (res.run) { try { eval(res.run); } catch(e){} }

            // NOTIFICACIÓN → showNotify
            showNotify('success', res.title || '¡Listo!', res.message || 'Operación realizada correctamente.');
        } else if (res.status === 'unauthorized') {
            showNotify('error', res.title || 'Sesión expirada', res.message || 'Debes iniciar sesión nuevamente.');
            if (res.redirect) { setTimeout(function(){ window.location.href = res.redirect; }, 1200); }
        } else {
            var extra = (res.missing && res.missing.length) ? " Faltan: " + res.missing.join(', ') : '';
            showNotify('error', res.title || 'Error', (res.message || 'Operación no realizada.') + extra);
        }
    })
    .fail(function (xhr) {
        let msg = 'Error de conexión. Intenta de nuevo.';
        try {
            const json = JSON.parse(xhr.responseText);
            if (json && json.message) msg = json.message;
        } catch(e){}
        showNotify('error', 'Error', msg);
    })
    .always(function () {
        $btn.prop('disabled', false).find('.spinner-border').remove();
    });
});

/* ============================
   MODAL VALES (abre y prepara)
   ============================ */
function modal_vales() {
    $('#formVales').attr({ 'data-form': 'save' });
    $('#formVales').attr({ 'action': '<?php echo SERVERURL;?>ajax/addValesAjax.php' });
    $('#formVales')[0].reset();

    $('#reg_vale').show();
    $('#edi_vale').hide();
    $('#delete_vale').hide();

    $('#formVales #vale_empleado').prop('disabled', false);
    $('#formVales #vale_monto').prop('disabled', false);
    $('#formVales #vale_notas').prop('disabled', false);

    $('#formVales #proceso_vale').val("Registro Vale Empleados");

    $('#modalRegistrarVales').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

/* ============================
   MODAL NOMINA DETALLES (ABRIR NUEVO)
   ============================ */
function modalNominasDetalles() {
    if ($('#form_main_nominas #estado_nomina').val() == 0) {
        $('#formNominaDetalles').attr({ 'data-form': 'save' });
        $('#formNominaDetalles').attr({ 'action': '<?php echo SERVERURL;?>ajax/addNominaDetallesAjax.php' });

        var nomina_id = $('#formNominaDetalles #nomina_id').val();
        var numero_nomima = $('#formNominaDetalles #nominad_numero').val();
        var detalle = $('#formNominaDetalles #nominad_detalle').val();

        $('#formNominaDetalles')[0].reset();
        getEmpleado();

        $('#formNominaDetalles #nomina_id').val(nomina_id);
        $('#formNominaDetalles #nominad_numero').val(numero_nomima);
        $('#formNominaDetalles #nominad_detalle').val(detalle);

        $('#formNominaDetalles #fecha_inicio').val($('#form_main_nominas_detalles #fecha_inicio').val());
        $('#formNominaDetalles #fecha_fin').val($('#form_main_nominas_detalles #fecha_fin').val());

        $('#reg_nominaD').show();
        $('#edi_nominaD').hide();
        $('#delete_nominaD').hide();

        // Habilitar campos
        $('#formNominaDetalles #nominad_empleados').prop('disabled', false);
        $('#formNominaDetalles #nominad_retroactivo').prop('readonly', false);
        $('#formNominaDetalles #nominad_bono').prop('readonly', false);
        $('#formNominaDetalles #nominad_otros_ingresos').prop('readonly', false);
        $('#formNominaDetalles #nominad_horas25').prop('readonly', false);
        $('#formNominaDetalles #nominad_horas50').prop('readonly', false);
        $('#formNominaDetalles #nominad_horas75').prop('readonly', false);
        $('#formNominaDetalles #nominad_horas100').prop('readonly', false);
        $('#formNominaDetalles #nominad_deducciones').prop('readonly', false);
        $('#formNominaDetalles #nominad_prestamo').prop('readonly', false);
        $('#formNominaDetalles #nominad_ihss').prop('readonly', false);
        $('#formNominaDetalles #nominad_rap').prop('readonly', false);
        $('#formNominaDetalles #nominad_isr').prop('readonly', false);
        $('#formNominaDetalles #nominad_incapacidad_ihss').prop('readonly', false);
        $('#formNominaDetalles #nomina_detalles_notas').prop('readonly', false);
        $('#formNominaDetalles #nominad_neto_ingreso').prop('readonly', true);
        $('#formNominaDetalles #nominad_neto_egreso').prop('readonly', true);
        $('#formNominaDetalles #nominad_neto').prop('readonly', true);
        $('#formNominaDetalles #nomina_detalles_activo').prop('disabled', false);
        $('#formNominaDetalles #estado_nomina_detalles').hide();

        $('#formNominaDetalles #proceso_nomina_detalles').val("Registro");

        $('#modal_registrar_nomina_detalles').modal({
            show: true,
            keyboard: false,
            backdrop: 'static'
        });
    } else {
        showNotify('error', 'Error', 'Lo sentimos, esta nomina ya ha sido generada, no puede agregar más empleados');
    }
}

/* ============================
   SUBMIT AJAX #formNominaDetalles (JSON)
   Sirve para save/update/delete según data-form
   ============================ */
$(document).off('submit', '#formNominaDetalles').on('submit', '#formNominaDetalles', function (e) {
    e.preventDefault();

    var $form = $(this);
    var url = $form.attr('action') || '<?php echo SERVERURL;?>ajax/addNominaDetallesAjax.php';
    var modo = ($form.attr('data-form') || 'save').toLowerCase();
    var $btn = (modo === 'update') ? $('#edi_nominaD') : (modo === 'delete') ? $('#delete_nominaD') : $('#reg_nominaD');

    if (!$form[0].checkValidity()) {
        $form[0].reportValidity();
        return;
    }

    $btn.prop('disabled', true).append(' <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

    $.ajax({
        url: url,
        type: 'POST',
        data: $form.serialize(),
        dataType: 'json',
        cache: false
    })
    .done(function (res) {
        if (res.status === 'success') {
            $('#modal_registrar_nomina_detalles').modal('hide');
            if (res.run) { try { eval(res.run); } catch(e){} }
            // NOTIFICACIÓN → showNotify
            showNotify('success', res.title || '¡Listo!', res.message || 'Operación realizada correctamente.');
        } else if (res.status === 'unauthorized') {
            showNotify('error', res.title || 'Sesión expirada', res.message || 'Debes iniciar sesión nuevamente.');
            if (res.redirect) { setTimeout(function(){ window.location.href = res.redirect; }, 1200); }
        } else {
            var extra = (res.missing && res.missing.length) ? " Faltan: " + res.missing.join(', ') : '';
            showNotify('error', res.title || 'Error', (res.message || 'Operación no realizada.') + extra);
        }
    })
    .fail(function () {
        showNotify('error', 'Error', 'Error de conexión. Intenta de nuevo.');
    })
    .always(function () {
        $btn.prop('disabled', false).find('.spinner-border').remove();
    });
});

/* ============================
   SUBMIT AJAX #formVales (JSON)
   ============================ */
$(document).off('submit', '#formVales').on('submit', '#formVales', function (e) {
    e.preventDefault();

    var $form = $(this);
    var url = $form.attr('action') || '<?php echo SERVERURL;?>ajax/addValesAjax.php';
    var modo = ($form.attr('data-form') || 'save').toLowerCase();
    var $btn = (modo === 'update') ? $('#edi_vale') : (modo === 'delete') ? $('#delete_vale') : $('#reg_vale');

    if (!$form[0].checkValidity()) {
        $form[0].reportValidity();
        return;
    }

    $btn.prop('disabled', true).append(' <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

    $.ajax({
        url: url,
        type: 'POST',
        data: $form.serialize(),
        dataType: 'json',
        cache: false
    })
    .done(function (res) {
        if (res.status === 'success') {
            $('#modalRegistrarVales').modal('hide');
            listar_vales();
            // NOTIFICACIÓN → showNotify
            showNotify('success', res.title || '¡Listo!', res.message || 'Operación realizada correctamente.');
        } else if (res.status === 'unauthorized') {
            showNotify('error', res.title || 'Sesión expirada', res.message || 'Debes iniciar sesión nuevamente.');
            if (res.redirect) { setTimeout(function(){ window.location.href = res.redirect; }, 1200); }
        } else {
            var extra = (res.missing && res.missing.length) ? " Faltan: " + res.missing.join(', ') : '';
            showNotify('error', res.title || 'Error', (res.message || 'Operación no realizada.') + extra);
        }
    })
    .fail(function () {
        showNotify('error', 'Error', 'Error de conexión. Intenta de nuevo.');
    })
    .always(function () {
        $btn.prop('disabled', false).find('.spinner-border').remove();
    });
});

/* ============================
   FOCUS AL ABRIR MODAL NOMINA
   ============================ */
$(() => {
    $("#modal_registrar_nomina").on('shown.bs.modal', function() {
        $(this).find('#formNomina #nomina_detale').focus();
    });
});

$('#formNomina #label_nomina_activo').html("Sin Generar");

$('#formNomina .switch').change(function() {
    if ($('input[name=nomina_activo]').is(':checked')) {
        $('#formNomina #label_nomina_activo').html("Generada");
        return true;
    } else {
        $('#formNomina #label_nomina_activo').html("Sin Generar");
        return false;
    }
});

$('#formNominaDetalles #label_nomina_detalles_activo').html("Sin Generar");

$('#formNominaDetalles .switch').change(function() {
    if ($('input[name=nomina_detalles_activo]').is(':checked')) {
        $('#formNominaDetalles #label_nomina_detalles_activo').html("Generada");
        return true;
    } else {
        $('#formNominaDetalles #label_nomina_detalles_activo').html("Sin Generar");
        return false;
    }
});

/* ============================
   CARGAS DE SELECTS
   ============================ */
function getTipoNomina() {
    var url = '<?php echo SERVERURL;?>core/getTipoNomina.php';
    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formNomina #tipo_nomina').html("").html(data).selectpicker('refresh');
            $('#formNomina #tipo_nomina').val(1).selectpicker('refresh');
        }
    });
}

function getTipoContrato() {
    var url = '<?php echo SERVERURL;?>core/getTipoContrato.php';
    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#form_main_nominas #tipo_contrato_nomina').html("").html(data).selectpicker('refresh');
        }
    });
}

function getPagoPlanificado() {
    var url = '<?php echo SERVERURL;?>core/getPagoPlanificado.php';
    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#form_main_nominas #pago_planificado_nomina').html("").html(data).selectpicker('refresh');
            $('#formNomina #nomina_pago_planificado_id').html("").html(data).selectpicker('refresh');
        }
    });
}

function getTipoEmpleado() {
    var url = '<?php echo SERVERURL;?>core/getTipoEmpleado.php';
    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#form_main_contrato #tipo_empleado').html("").html(data).selectpicker('refresh');
        }
    });
}

function getEmpresa() {
    $.ajax({
        url: "<?php echo SERVERURL; ?>core/getEmpresa.php",
        type: "POST",
        dataType: "json",
        success: function(response) {
            const select = $('#formNomina #nomina_empresa_id');
            select.empty();
            
            if(response.success) {
                response.data.forEach(empresa => {
                    select.append(`<option value="${empresa.empresa_id}">${empresa.nombre}</option>`);
                });
                if(response.data.length > 0) {
                    select.val(1);
                    select.selectpicker('refresh');
                }
            } else {
                select.append('<option value="">No hay empresas disponibles</option>');
                showNotify("warning", "Advertencia", response.message || "No se encontraron empresas");
            }
            select.selectpicker('refresh');
        },
        error: function() {
            showNotify("error", "Error", "Error de conexión al cargar empresas");
            $('#formNomina #nomina_empresa_id').html('<option value="">Error al cargar</option>').selectpicker('refresh');
        }
    });
}

function getEmpleado() {
  var url = '<?php echo SERVERURL;?>core/getEmpleado.php';
  $.ajax({
    type: "POST",
    url: url,
    async: true,
    success: function(data) {
      // prepend el placeholder en todos los combos de empleados
      var opciones = '<option value="">Seleccione</option>' + data;

      // Modal Nomina Detalles (alta/edición)
      $('#formNominaDetalles #nominad_empleados')
        .html(opciones).selectpicker('refresh');

      // Filtro en la vista de detalles (ESTE ES EL QUE TE FALTABA)
      $('#form_main_nominas_detalles #detalle_nomina_empleado')
        .html(opciones).selectpicker('refresh');

      // Por si quieres también mantener este otro (vales)
      $('#formVales #vale_empleado')
        .html(opciones).selectpicker('refresh');
    },
    error: function(){
      showNotify('error', 'Error', 'Error de conexión al cargar empleados');
    }
  });
}

function getEmpleadoVales() {
    var url = '<?php echo SERVERURL;?>core/getEmpleado.php';
    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formVales #vale_empleado').html("").html(data).selectpicker('refresh');
        }
    });
}

/* ============================
   VOLVER A LISTA DETALLES
   ============================ */
$("#volver_nomina").on("click", function(e) {
    e.preventDefault();
    $("#nomina_detalles").hide();
    $("#nomina_principal").show();
});

/* =========================================================
   HEADER Y FOOTER DINÁMICO - DETALLES DE NÓMINA
   ========================================================= */

   function construirHeaderFooterDataTableNominaDetalles() {
    var $tabla = $("#dataTableNominaDetalles");

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Nomina</th>' +
                '<th>Contrato</th>' +
                '<th>Empresa</th>' +
                '<th>Empleado</th>' +
                '<th>Neto Ingresos</th>' +
                '<th>Neto Egresos</th>' +
                '<th>Neto</th>' +
                '<th>Notas</th>' +
                '<th>Estado</th>' +
            '</tr>' +
        '</thead>' +
        '<tfoot class="bg-secondary">' +
            '<tr>' +
                '<td colspan="1">Total</td>' +
                '<td colspan="4"></td>' +
                '<td id="neto_ingreso"></td>' +
                '<td id="neto_egreso"></td>' +
                '<td id="neto"></td>' +
                '<td colspan="2"></td>' +
            '</tr>' +
        '</tfoot>'
    );
}


/* ============================
   LISTADO DETALLES DE NÓMINA
   ============================ */

var listar_nominas_detalles = function() {
    var estado = $("#form_main_nominas_detalles #estado_nomina_detalles").val() || 0;
    var empleado = $("#form_main_nominas_detalles #detalle_nomina_empleado").val() || 0;
    var nomina_id = $("#form_main_nominas_detalles #nomina_id").val() || 0;

    $("#nominad_neto_ingreso1").val("");
    $("#nominad_neto_egreso1").val("");
    $("#nominad_neto1").val("");

    if ($.fn.DataTable.isDataTable("#dataTableNominaDetalles")) {
        $("#dataTableNominaDetalles").DataTable().clear().destroy();
    }

    construirHeaderFooterDataTableNominaDetalles();

    var table_nominas_detalles = $("#dataTableNominaDetalles").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableNominaDetalles.php",
            "data": {
                "estado": estado,
                "empleado": empleado,
                "nomina_id": nomina_id
            }
        },
        "columns": [
            {
                "data": null,
                "orderable": false,
                "searchable": false,
                "className": "text-center align-middle",
                "render": function(data, type, row) {
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

                                '<button type="button" class="dropdown-item accion-item accion-editar table_editar nomina_detalles_editar ocultar">' +
                                    '<span class="accion-icon accion-icon-editar">' +
                                        '<i class="fas fa-edit"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Editar</span>' +
                                '</button>' +

                                '<button type="button" class="dropdown-item accion-item accion-eliminar table_eliminar nomina_detalles_eliminar ocultar">' +
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
                "data": "nomina_id"
            },
            {
                "data": "contrato"
            },
            {
                "data": "empresa"
            },
            {
                "data": "empleado"
            },
            {
                "data": "neto_ingresos",
                render: function(data, type) {
                    var number = $.fn.dataTable.render
                        .number(',', '.', 2, 'L ')
                        .display(data);

                    if (type === 'display') {
                        let color = (data < 0) ? 'red' : 'green';

                        return '<span style="color:' + color + '">' + number + '</span>';
                    }

                    return number;
                }
            },
            {
                "data": "neto_egresos",
                render: function(data, type) {
                    var number = $.fn.dataTable.render
                        .number(',', '.', 2, 'L ')
                        .display(data);

                    if (type === 'display') {
                        let color = (data < 0) ? 'red' : 'green';

                        return '<span style="color:' + color + '">' + number + '</span>';
                    }

                    return number;
                }
            },
            {
                "data": "neto",
                render: function(data, type) {
                    var number = $.fn.dataTable.render
                        .number(',', '.', 2, 'L ')
                        .display(data);

                    if (type === 'display') {
                        let color = (data < 0) ? 'red' : 'green';

                        return '<span style="color:' + color + '">' + number + '</span>';
                    }

                    return number;
                }
            },
            {
                "data": "notas"
            },
            {
                "data": "estado",
                "render": function(data, type) {
                    if (type === 'display') {
                        var estadoText = data == 1 ? 'Generada' : 'Sin Generar';
                        var icon = data == 1 ?
                            '<i class="fas fa-check-circle mr-1"></i>' :
                            '<i class="fas fa-times-circle mr-1"></i>';
                        var badgeClass = data == 1 ?
                            'badge badge-pill badge-success' :
                            'badge badge-pill badge-danger';

                        return '<span class="' + badgeClass +
                            '" style="font-size: 0.95rem; padding: 0.5em 0.8em; font-weight: 600;">' +
                            icon + estadoText + '</span>';
                    }

                    return data;
                }
            }
        ],
        "lengthMenu": lengthMenu,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [
            {
                width: "10%",
                targets: 0,
                orderable: false,
                searchable: false,
                className: "text-center text-nowrap align-middle"
            },
            {
                width: "5%",
                targets: 1
            },
            {
                width: "8%",
                targets: 2
            },
            {
                width: "12%",
                targets: 3
            },
            {
                width: "22%",
                targets: 4
            },
            {
                width: "10%",
                targets: 5,
                className: "text-right text-nowrap align-middle"
            },
            {
                width: "10%",
                targets: 6,
                className: "text-right text-nowrap align-middle"
            },
            {
                width: "10%",
                targets: 7,
                className: "text-right text-nowrap align-middle"
            },
            {
                width: "10%",
                targets: 8
            },
            {
                width: "3%",
                targets: 9,
                className: "text-center text-nowrap align-middle"
            }
        ],
        "fnRowCallback": function(nRow, aData) {
            var neto_ingreso = $.fn.dataTable.render
                .number(',', '.', 2, 'L ')
                .display(aData['total_neto_ingreso']);

            var neto_egreso = $.fn.dataTable.render
                .number(',', '.', 2, 'L ')
                .display(aData['total_neto_egreso']);

            var neto_neto = $.fn.dataTable.render
                .number(',', '.', 2, 'L ')
                .display(aData['total_neto']);

            $('#neto_ingreso').html(neto_ingreso);
            $('#neto_egreso').html(neto_egreso);
            $('#neto').html(neto_neto);
        },
        "buttons": [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar listar_nominas',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_nominas_detalles();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg"></i> Agregar',
                titleAttr: 'Agregar Empleados',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modalNominasDetalles();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Nomina Empleados',
                messageTop: 'Fecha: ' + convertDateFormat(today()),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7, 8]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                orientation: 'landscape',
                title: 'Nomina Empleados',
                messageTop: 'Fecha: ' + convertDateFormat(today()),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7, 8]
                },
                customize: function(doc) {
                    if (imagen) {
                        doc.content.splice(1, 0, {
                            margin: [0, 0, 0, 12],
                            alignment: 'left',
                            image: imagen,
                            width: 100,
                            height: 45
                        });
                    }
                }
            }
        ],
        "drawCallback": function() {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());

            if (typeof cerrarDropdownAcciones === "function") {
                cerrarDropdownAcciones();
            }
        }
    });

    table_nominas_detalles.search('').draw();
    $('#buscar').focus();

    editar_nominas_detalles_dataTable("#dataTableNominaDetalles tbody", table_nominas_detalles);
    eliminar_nominas_detalles_dataTable("#dataTableNominaDetalles tbody", table_nominas_detalles);
};

/* ============================
   EDITAR DETALLES (usa el FORM)
   ============================ */
var editar_nominas_detalles_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.nomina_detalles_editar");
    $(tbody).on("click", "button.nomina_detalles_editar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarNominasDetalles.php';
        $('#formNominaDetalles #nomina_detalles_id').val(data.nomina_detalles_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formNominaDetalles').serialize(),
            success: function(registro) {
                var valores = eval(registro);

                // Configurar FORM para UPDATE
                $('#formNominaDetalles').attr({ 'data-form': 'update' });
                $('#formNominaDetalles').attr({ 'action': '<?php echo SERVERURL;?>ajax/modificarNominaDetallesAjax.php' });

                $('#formNominaDetalles')[0].reset();
                $('#reg_nominaD').hide();
                $('#edi_nominaD').show();
                $('#delete_nominaD').hide();

                // Mapear valores
                $('#formNominaDetalles #nomina_id').val(valores[0]);
                $('#formNominaDetalles #nomina_detalles_id').val(valores[1]);
                $('#formNominaDetalles #pago_planificado_id').val(valores[2]);
                $('#formNominaDetalles #colaboradores_id').val(valores[31]);

                $('#formNominaDetalles #nominad_numero').val(valores[0]);
                $('#formNominaDetalles #nominad_empleados').val(valores[31]).selectpicker('refresh');
                $('#formNominaDetalles #nominad_puesto').val(valores[5]);
                $('#formNominaDetalles #nominad_identidad').val(valores[6]);
                $('#formNominaDetalles #nominad_contrato_id').val(valores[7]);
                $('#formNominaDetalles #nominad_fecha_ingreso').val(valores[8]);
                $('#formNominaDetalles #nominad_salario').val(parseFloat(valores[9]).toFixed(2));

                let salario_diario = (valores[9] / 30).toFixed(2);
                var salario_hora = (valores[37] == 1) ? salario_diario / 8 : salario_diario / 6;

                $('#formNominaDetalles #nominad_sueldo_diario').val(salario_diario);
                $('#formNominaDetalles #nominad_sueldo_hora').val(parseFloat(salario_hora).toFixed(2));

                $('#formNominaDetalles #nominad_diast').val(valores[10]);
                $('#formNominaDetalles #nominad_retroactivo').val(valores[11]);
                $('#formNominaDetalles #nominad_bono').val(valores[12]);
                $('#formNominaDetalles #nominad_otros_ingresos').val(valores[13]);
                $('#formNominaDetalles #nominad_horas25').val(valores[14]);
                $('#formNominaDetalles #nominad_horas50').val(valores[15]);
                $('#formNominaDetalles #nominad_horas75').val(valores[16]);
                $('#formNominaDetalles #nominad_horas100').val(valores[17]);
                $('#formNominaDetalles #nominad_deducciones').val(valores[18]);
                $('#formNominaDetalles #nominad_prestamo').val(valores[19]);
                $('#formNominaDetalles #nominad_ihss').val(valores[20]);
                $('#formNominaDetalles #nominad_rap').val(valores[21]);
                $('#formNominaDetalles #nominad_isr').val(valores[22]);
                $('#formNominaDetalles #nominad_vales').val(valores[30]);
                $('#formNominaDetalles #nominad_incapacidad_ihss').val(valores[23]);
                $('#formNominaDetalles #nominad_neto_ingreso').val(valores[24]);
                $('#formNominaDetalles #nominad_neto_egreso').val(valores[25]);
                $('#formNominaDetalles #nominad_neto').val(valores[26]);
                $('#formNominaDetalles #nominad_detalle').val(valores[36]);
                $('#formNominaDetalles #nomina_detalles_notas').val(valores[28]);
                $('#formNominaDetalles #nominad_vale').val(valores[30]);

                $('#formNominaDetalles #hrse25_valor').val(valores[32]);
                $('#formNominaDetalles #hrse50_valor').val(valores[33]);
                $('#formNominaDetalles #hrse75_valor').val(valores[34]);
                $('#formNominaDetalles #hrse100_valor').val(valores[35]);

                calculoNomina();

                if (valores[29] == 1) {
                    $('#formNominaDetalles #nomina_detalles_activo').prop('checked', true);
                    $('#edi_nominaD').attr('disabled', true);
                } else {
                    $('#formNominaDetalles #nomina_detalles_activo').prop('checked', false);
                    $('#edi_nominaD').attr('disabled', false);
                }

                // Habilitar/Deshabilitar
                $('#formNominaDetalles #nominad_retroactivo').prop('readonly', false);
                $('#formNominaDetalles #nominad_bono').prop('readonly', false);
                $('#formNominaDetalles #nominad_otros_ingresos').prop('readonly', false);
                $('#formNominaDetalles #nominad_horas25').prop('readonly', false);
                $('#formNominaDetalles #nominad_horas50').prop('readonly', false);
                $('#formNominaDetalles #nominad_horas75').prop('readonly', false);
                $('#formNominaDetalles #nominad_horas100').prop('readonly', false);
                $('#formNominaDetalles #nominad_deducciones').prop('readonly', false);
                $('#formNominaDetalles #nominad_prestamo').prop('readonly', false);
                $('#formNominaDetalles #nominad_ihss').prop('readonly', false);
                $('#formNominaDetalles #nominad_rap').prop('readonly', false);
                $('#formNominaDetalles #nominad_isr').prop('readonly', false);
                $('#formNominaDetalles #nominad_incapacidad_ihss').prop('readonly', false);
                $('#formNominaDetalles #nomina_detalles_notas').prop('readonly', false);
                $('#formNominaDetalles #estado_nomina_detalles').show();

                $('#formNominaDetalles #nominad_empleados').prop('disabled', true);
                $('#formNominaDetalles #nominad_neto_ingreso').prop('readonly', true);
                $('#formNominaDetalles #nominad_neto_egreso').prop('readonly', true);
                $('#formNominaDetalles #nominad_neto').prop('readonly', true);
                $('#formNominaDetalles #nomina_detalles_activo').prop('disabled', true);

                $('#formNominaDetalles #proceso_nomina_detalles').val("Editar");

                $('#modal_registrar_nomina_detalles').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
    });
};

var eliminar_nominas_detalles_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.nomina_detalles_eliminar");
    $(tbody).on("click", "button.nomina_detalles_eliminar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarNominasDetalles.php';
        $('#formNominaDetalles #nomina_detalles_id').val(data.nomina_detalles_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formNominaDetalles').serialize(),
            success: function(registro) {                
                var valores = eval(registro);

                // Configurar FORM para DELETE
                $('#formNominaDetalles').attr({ 'data-form': 'delete' });
                $('#formNominaDetalles').attr({ 'action': '<?php echo SERVERURL;?>ajax/eliminarNominaDetallesAjax.php' });

                $('#formNominaDetalles')[0].reset();
                $('#reg_nominaD').hide();
                $('#edi_nominaD').hide();
                $('#delete_nominaD').show();

                $('#formNominaDetalles #nomina_id').val(valores[0]);
                $('#formNominaDetalles #nomina_detalles_id').val(valores[1]);
                $('#formNominaDetalles #pago_planificado_id').val(valores[2]);
                $('#formNominaDetalles #colaboradores_id').val(valores[3]).selectpicker('refresh');
                $('#formNominaDetalles #nominad_numero').val(valores[0]);
                $('#formNominaDetalles #nominad_empleados').val(valores[4]);
                $('#formNominaDetalles #nominad_puesto').val(valores[5]);
                $('#formNominaDetalles #nominad_identidad').val(valores[6]);
                $('#formNominaDetalles #nominad_contrato_id').val(valores[7]);
                $('#formNominaDetalles #nominad_fecha_ingreso').val(valores[8]);
                $('#formNominaDetalles #nominad_salario').val(parseFloat(valores[9]).toFixed(2));

                let salario_diario = (valores[9] / 30).toFixed(2);
                let salario_hora = (parseFloat(salario_diario) / 8).toFixed(2);

                $('#formNominaDetalles #nominad_sueldo_diario').val(salario_diario);
                $('#formNominaDetalles #nominad_sueldo_hora').val(salario_hora);

                $('#formNominaDetalles #nominad_diast').val(valores[10]);
                $('#formNominaDetalles #nominad_retroactivo').val(valores[11]);
                $('#formNominaDetalles #nominad_bono').val(valores[12]);
                $('#formNominaDetalles #nominad_otros_ingresos').val(valores[13]);
                $('#formNominaDetalles #nominad_horas25').val(valores[14]);
                $('#formNominaDetalles #nominad_horas50').val(valores[15]);
                $('#formNominaDetalles #nominad_horas75').val(valores[16]);
                $('#formNominaDetalles #nominad_horas100').val(valores[17]);
                $('#formNominaDetalles #nominad_deducciones').val(valores[18]);
                $('#formNominaDetalles #nominad_prestamo').val(valores[19]);
                $('#formNominaDetalles #nominad_ihss').val(valores[20]);
                $('#formNominaDetalles #nominad_rap').val(valores[21]);
                $('#formNominaDetalles #nominad_isr').val(valores[22]);
                $('#formNominaDetalles #nominad_vales').val(valores[30]);
                $('#formNominaDetalles #nominad_incapacidad_ihss').val(valores[23]);
                $('#formNominaDetalles #nominad_neto_ingreso').val(parseFloat(valores[24]).toFixed(2));
                $('#formNominaDetalles #nominad_neto_egreso').val(parseFloat(valores[25]).toFixed(2));
                $('#formNominaDetalles #nominad_neto').val(parseFloat(valores[26]).toFixed(2));
                $('#formNominaDetalles #nominad_detalle').val(valores[27]);
                $('#formNominaDetalles #nomina_detalles_notas').val(valores[28]);

                calculoNomina();

                if (valores[29] == 1) {
                    $('#formNominaDetalles #nomina_detalles_activo').prop('checked', true);
                    $('#delete_nominaD').attr('disabled', true);
                } else {
                    $('#formNominaDetalles #nomina_detalles_activo').prop('checked', false);
                    $('#delete_nominaD').attr('disabled', false);
                }

                $('#formNominaDetalles #estado_nomina_detalles').show();

                // Deshabilitar campos en vista de eliminación
                $('#formNominaDetalles input, #formNominaDetalles textarea, #formNominaDetalles select').prop('readonly', true).prop('disabled', true);
                $('#delete_nominaD').prop('disabled', false);

                $('#formNominaDetalles #proceso_nomina_detalles').val("Eliminar");

                $('#modal_registrar_nomina_detalles').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
    });
};

/* ============================
   UTILIDADES DE CÁLCULO
   ============================ */
function pagoPlanificado(pago_planificado) {
    var diasTrabajadosMap = { 1: 7, 2: 15, 3: 30 };
    return diasTrabajadosMap[pago_planificado] || 0;
}

function obtenerDatosEmpleado(colaboradores_id) {
    var url = '<?php echo SERVERURL;?>core/getDatosEmpleado.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        data: 'colaboradores_id=' + colaboradores_id,
        success: function(data) {
            var valores = JSON.parse(data);
            var validar_semanal = valores[9];

            var valor_dividir = pagoPlanificado(valores[6]);
            var salario = parseFloat(valores[3]);
            var salario_diario = salario / 30;
            var salario_hora = (valores[5] == 1) ? salario_diario / 8 : salario_diario / 6;

            // Asignar valores
            $('#formNominaDetalles #nominad_puesto').val(valores[0]);
            $('#formNominaDetalles #nominad_identidad').val(valores[1]);
            $('#formNominaDetalles #nominad_contrato_id').val(valores[2]);
            $('#formNominaDetalles #nominad_salario').val(parseFloat(salario).toFixed(2));
            $('#formNominaDetalles #nominad_fecha_ingreso').val(valores[4]);
            $('#formNominaDetalles #nominad_sueldo_diario').val(salario_diario.toFixed(2));
            $('#formNominaDetalles #nominad_sueldo_hora').val(parseFloat(salario_hora).toFixed(2));
            $('#formNominaDetalles #nominad_vale').val(valores[7]);
            $('#formNominaDetalles #salario').val(parseFloat(valores[8]).toFixed(2));
            $('#formNominaDetalles #validar_semanal').val(valores[9]);
            $('#formNominaDetalles #pago_planificado_id').val(valor_dividir);

            var fecha_inicio = $('#formNominaDetalles #fecha_inicio').val();
            var fecha_fin = $('#formNominaDetalles #fecha_fin').val();

            $('#formNominaDetalles #nominad_diast').val(ObtenerDiasTrabajados(colaboradores_id, fecha_inicio, fecha_fin));
            calculoNomina();
        }
    });
}

$("#formNominaDetalles #nominad_empleados").on("change", function() {
    var colaboradores_id = $(this).val();
    obtenerDatosEmpleado(colaboradores_id);
});

function calculoHorasExtras(hora_valor, salario_hora, horas) {
    var porcentaje = parseFloat(horas) / 100;
    if (porcentaje >= 0.25 && porcentaje <= 1) {
        return parseFloat((salario_hora * porcentaje + salario_hora) * (parseFloat(hora_valor) || 0));
    }
    return 0;
}

function calculoNomina() {
    var neto_ingresos = 0;
    var neto_egresos = 0;
    var neto = 0;

    var validar_semanal = parseFloat($('#formNominaDetalles #validar_semanal').val() || 0);

    // INGRESOS
    var dias_trabajadas = parseFloat($('#formNominaDetalles #nominad_diast').val()) || 0;
    var salario_hora = parseFloat($('#formNominaDetalles #nominad_sueldo_hora').val()) || 0;
    var salario_mensual = parseFloat($('#formNominaDetalles #nominad_salario').val()) || 0;

    var hora25  = calculoHorasExtras($('#formNominaDetalles #nominad_horas25').val(),  salario_hora, "25");
    var hora50  = calculoHorasExtras($('#formNominaDetalles #nominad_horas50').val(),  salario_hora, "50");
    var hora75  = calculoHorasExtras($('#formNominaDetalles #nominad_horas75').val(),  salario_hora, "75");
    var hora100 = calculoHorasExtras($('#formNominaDetalles #nominad_horas100').val(), salario_hora, "100");

    $('#formNominaDetalles #hrse25_valor').val(hora25.toFixed(2));
    $('#formNominaDetalles #hrse50_valor').val(hora50.toFixed(2));
    $('#formNominaDetalles #hrse75_valor').val(hora75.toFixed(2));
    $('#formNominaDetalles #hrse100_valor').val(hora100.toFixed(2));

    var retroactivo    = parseFloat($('#formNominaDetalles #nominad_retroactivo').val()) || 0;
    var bono           = parseFloat($('#formNominaDetalles #nominad_bono').val()) || 0;
    var otros_ingresos = parseFloat($('#formNominaDetalles #nominad_otros_ingresos').val()) || 0;

    if (validar_semanal === 1) {
        neto_ingresos = dias_trabajadas * ((salario_mensual / 4) / 7) + retroactivo + bono + otros_ingresos + hora25 + hora50 + hora75 + hora100;
    } else {
        neto_ingresos = dias_trabajadas * (salario_mensual / 30) + retroactivo + bono + otros_ingresos + hora25 + hora50 + hora75 + hora100;
    }

    // EGRESOS
    var deducciones      = parseFloat($('#formNominaDetalles #nominad_deducciones').val()) || 0;
    var prestamo         = parseFloat($('#formNominaDetalles #nominad_prestamo').val()) || 0;
    var ihss             = parseFloat($('#formNominaDetalles #nominad_ihss').val()) || 0;
    var rap              = parseFloat($('#formNominaDetalles #nominad_rap').val()) || 0;
    var isr              = parseFloat($('#formNominaDetalles #nominad_isr').val()) || 0;
    var vales            = parseFloat($('#formNominaDetalles #nominad_vale').val()) || 0;
    var incapacidad_ihss = parseFloat($('#formNominaDetalles #nominad_incapacidad_ihss').val()) || 0;

    neto_egresos = deducciones + prestamo + ihss + rap + isr + incapacidad_ihss + vales;
    neto = neto_ingresos - neto_egresos;

    $('#formNominaDetalles #nominad_neto_ingreso').val(neto_ingresos.toFixed(2));
    $('#formNominaDetalles #nominad_neto_egreso').val(neto_egresos.toFixed(2));
    $('#formNominaDetalles #nominad_neto').val(neto.toFixed(2));

    $('#nominad_neto_ingreso1').val(neto_ingresos.toFixed(2));
    $('#nominad_neto_egreso1').val(neto_egresos.toFixed(2));
    $('#nominad_neto1').val(neto.toFixed(2));
}

function actualizarCampo(selector, valor) { $(selector).val(valor); }

$("#formNominaDetalles #nominad_diast, \
#formNominaDetalles #nominad_retroactivo, \
#formNominaDetalles #nominad_bono, \
#formNominaDetalles #nominad_otros_ingresos, \
#formNominaDetalles #nominad_horas25, \
#formNominaDetalles #nominad_horas50, \
#formNominaDetalles #nominad_horas75, \
#formNominaDetalles #nominad_horas100, \
#formNominaDetalles #nominad_deducciones, \
#formNominaDetalles #nominad_prestamo, \
#formNominaDetalles #nominad_ihss, \
#formNominaDetalles #nominad_rap, \
#formNominaDetalles #nominad_isr, \
#formNominaDetalles #nominad_incapacidad_ihss, \
#formNominaDetalles #nominad_vale").on("keyup change", function() {
    calculoNomina();
});

function ObtenerDiasTrabajados(colaboradores_id, fechaiNomina, fechafNomina) {
    var url = '<?php echo SERVERURL;?>core/getDiasTrabajados.php';
    var dt;

    $.ajax({
        type: 'POST',
        url: url,
        data: { colaboradores_id: colaboradores_id, fechaiNomina: fechaiNomina, fechafNomina: fechafNomina },
        success: function(registro) {
            try {
                var valores = JSON.parse(registro);
                dt = valores[0];
            } catch (error) {
                console.error("Error al procesar la respuesta JSON:", error);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("Error en la solicitud AJAX:", textStatus, errorThrown);
        },
        async: false // (mantienes sync)
    });

    return dt;
}

function getCuentaNominas() {
    var url = '<?php echo SERVERURL;?>core/getCuenta.php';
    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formNomina #pago_nomina').html("").html(data).selectpicker('refresh');
        }
    });
}

/* ============================
   VALES (tabla + anular)
   ============================ */
var listar_vales = function() {
    var table_vales = $("#DatatableVale").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableVales.php"
        },
        "columns": [
            {"data": "empleado"},
            {
                "data": "monto",
                render: function(data, type) {
                    var number = $.fn.dataTable.render.number(',', '.', 2, 'L ').display(data);
                    if (type === 'display') {
                        let color = (data < 0) ? 'red' : 'green';
                        return '<span style="color:' + color + '">' + number + '</span>';
                    }
                    return number;
                },
            },
            {"data": "nota"},
            {"defaultContent": "<button class='btn btn-danger anular_vale ocultar'><span class='fas fa-ban fa-lg'></span>Anular</button>"}
        ],
        "lengthMenu": lengthMenu10,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [
            { width: "45%", targets: 0 },
            { width: "10%", targets: 1 },
            { width: "35%", targets: 2 },
            { width: "2%",  targets: 3 }
        ],
        "fnRowCallback": function(nRow, aData) {
            var number = $.fn.dataTable.render.number(',', '.', 2, 'L ').display(aData['neto_importe']);
            $('#neto_importe').html(number);
        },
        "buttons": [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Vales',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() { listar_vales(); }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Nomina Empleados',
                messageTop: 'Fecha: ' + convertDateFormat(today()),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: { columns: [0,1,2,3] }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                orientation: 'landscape',
                title: 'Nomina Empleados',
                messageTop: 'Fecha: ' + convertDateFormat(today()),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: { columns: [0,1,2,3] },
                customize: function(doc) {
                    if (imagen) {
                        doc.content.splice(1, 0, {
                            margin: [0, 0, 0, 12],
                            alignment: 'left',
                            image: imagen,
                            width: 100,
                            height: 45
                        });
                    }
                }
            }
        ],
        "drawCallback": function() {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
        }
    });
    table_vales.search('').draw();
    $('#buscar').focus();

    anular_vale_nominas_dataTable("#DatatableVale tbody", table_vales);
};

var anular_vale_nominas_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.anular_vale");
    $(tbody).on("click", "button.anular_vale", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();

        // CONFIRMACIÓN → swal
        swal({
            title: "¿Estás seguro?",
            content: { element: "span", attributes: { innerHTML: "¿Desea anular este vale de <strong>" + data.empleado + "</strong>?" } },
            icon: "warning",
            buttons: {
                cancel: { text: "Cancelar", visible: true },
                confirm: { text: "¡Sí, anular el vale!" }
            },
            dangerMode: true,
            closeOnEsc: false,
            closeOnClickOutside: false
        }).then((willConfirm) => {
            if (willConfirm) {
                anularVale(data.vale_id);
            }
        });
    });
};

function anularVale(vale_id) {
    var url = '<?php echo SERVERURL;?>core/anularVale.php';
    $.ajax({
        type: "POST",
        url: url,
        data: { vale_id: vale_id },
        dataType: "json",
        cache: false
    })
    .done(function(res){
        if(res.status === "success"){
            showNotify('success', res.title || 'Éxito', res.message || 'El vale ha sido anulado correctamente');
            if (typeof listar_vales === 'function') listar_vales();
            if (res.run) { try { eval(res.run); } catch(e){} }
        }else if(res.status === "unauthorized"){
            showNotify('error', res.title || 'Sesión expirada', res.message || 'Debes iniciar sesión nuevamente.');
            if (res.redirect) { setTimeout(function(){ window.location.href = res.redirect; }, 1200); }
        }else{
            showNotify('error', res.title || 'Error', res.message || 'Lo sentimos, no se puede anular el vale');
        }
    })
    .fail(function(){
        showNotify('error','Error','Error de conexión al anular el vale');
    });
}

/* ============================
   Texto + Voz (contadores / speech)
   ============================ */
function inicializarContadores(limites) {
    Object.keys(limites).forEach(function(campo) {
        $(document).on('input', '#' + campo, function() {
            actualizarCaracteres(campo, 'charNum_' + campo, limites[campo]);
        });
        if ($('#' + campo).length) {
            actualizarCaracteres(campo, 'charNum_' + campo, limites[campo]);
        }
    });
}

function actualizarCaracteres(campo, contadorId, max_chars) {
    var $campo = $('#' + campo);
    if ($campo.length === 0) return;

    var texto = $campo.val() || '';
    var longitudTexto = texto.length;

    if (longitudTexto > max_chars) {
        $campo.val(texto.substring(0, max_chars));
        longitudTexto = max_chars;
    }

    $('#' + contadorId).text(longitudTexto + '/' + max_chars);
}

function inicializarSpeechRecognition(limites) {
    Object.keys(limites).forEach(function(campo) {
        $('#search_' + campo + '_stop').hide();

        var recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();
        recognition.continuous = true;
        recognition.lang = "es-ES";
        recognition.interimResults = false;

        $(document).on('click', '#search_' + campo + '_start', function(event) {
            $(this).hide();
            $('#search_' + campo + '_stop').show();
            recognition.start();
            event.preventDefault();
        });

        $(document).on('click', '#search_' + campo + '_stop', function(event) {
            recognition.stop();
            $(this).hide();
            $('#search_' + campo + '_start').show();
            event.preventDefault();
        });

        recognition.onresult = function(event) {
            var finalResult = '';
            var $campo = $('#' + campo);
            var valorAnterior = $campo.val() || '';

            for (var i = event.resultIndex; i < event.results.length; ++i) {
                if (event.results[i].isFinal) {
                    finalResult = event.results[i][0].transcript;
                    var nuevoTexto = (valorAnterior + ' ' + finalResult).trim();

                    if (nuevoTexto.length > limites[campo]) {
                        nuevoTexto = nuevoTexto.substring(0, limites[campo]);
                    }

                    $campo.val(nuevoTexto);
                    actualizarCaracteres(campo, 'charNum_' + campo, limites[campo]);
                }
            }
        };

        recognition.onerror = function(event) {
            console.error('Error en reconocimiento de voz:', event.error);
            $('#search_' + campo + '_stop').hide();
            $('#search_' + campo + '_start').show();
        };
    });
}

$(() => {
    var limites = {
        'nomina_notas': 254,
        'nomina_detalles_notas': 254,
        'vale_notas': 254,
        'nominad_neto': 254
    };

    inicializarContadores(limites);
    inicializarSpeechRecognition(limites);

    $('.modal').on('shown.bs.modal', function() {
        inicializarContadores(limites);
        inicializarSpeechRecognition(limites);
    });
});
</script>