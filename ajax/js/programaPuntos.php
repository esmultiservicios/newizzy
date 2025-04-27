<script>
// Declarar la variable global para DataTable
var table_programa_puntos;

$(function() {
    listar_programa_puntos();
    initCalculoEventos();

    $("#modalProgramaPuntos").on('shown.bs.modal', function() {
        $(this).find('#formProgramaPuntos #nombre').focus();
    });
});

$('#form_main_programa_puntos').on('submit', function(e) {
    e.preventDefault();
    listar_programa_puntos();
});

$('#form_main_programa_puntos #estado_programa_puntos').on('change', function() {
    listar_programa_puntos();
});

var listar_programa_puntos = function(){
    let estado = $('#form_main_programa_puntos #estado_programa_puntos').val();

    table_programa_puntos = $("#dataTableProgramaPuntos").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/programaPuntos/llenarDataTableProgramaPuntos.php",
            "data": { estado: estado },
            "dataSrc": "data"
        },
        "columns": [
            {
                "data": "nombre",
                "render": function(data, type, row) {
                    return `<a href="#" class="ver-historico" data-id="${row.id}" data-toggle="tooltip" data-placement="top" title="Ver histórico de puntos" style="color: #3498db !important; background-color: transparent !important; text-decoration: none !important;">${data}</a>`;
                }
            },
            {"data": "tipo_calculo"},
            {"data": "monto", "className": "text-right"},
            {"data": "porcentaje", "className": "text-right"},    
            {
                "data": "activo",
                "render": function(data, type, row) {
                    const iconSize = "1.25em";
                    if (data == 1) {
                        return '<span class="status-badge status-active"><i class="fas fa-check-circle" style="font-size: '+iconSize+'"></i> ACTIVO</span>';
                    } else {
                        return '<span class="status-badge status-inactive"><i class="fas fa-times-circle" style="font-size: '+iconSize+'"></i> INACTIVO</span>';
                    }
                }
            },
            {
                "data": "fecha_creacion",
                "render": function(data, type, row) {
                    moment.locale('es');
                    return moment(data).format('dddd D [de] MMMM [de] YYYY');
                }
            },        
            {
                "defaultContent": "<button class='table_editar btn btn-dark ocultar'><span class='fas fa-edit fa-lg'></span></button>",
                "className": "text-center"
            },
            {
                "defaultContent": "<button class='table_eliminar btn btn-dark ocultar'><span class='fa fa-trash fa-lg'></span></button>",
                "className": "text-center"
            }
        ],
        "lengthMenu": lengthMenu,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [
            { width: "30%", targets: 0 },
            { width: "12%", targets: 1 },
            { width: "10%", targets: 2 },
            { width: "10%", targets: 3 },
            { width: "12%", targets: 4 },
            { width: "16%", targets: 5 },
            { width: "5%", targets: 6 },
            { width: "5%", targets: 7 }
        ],
        "buttons": [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Programa de Puntos',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_programa_puntos();
                }
            },
            {
                text: '<i class="fas fa-plus fa-lg"></i> Ingresar',
                titleAttr: 'Agregar Programa de Puntos',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modal_programa_puntos();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte de Programa de Puntos',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: { columns: [0,1,2,3,4,5] }                  
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                title: 'Reporte de Programa de Puntos',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: { columns: [0,1,2,3,4,5] },
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
        "drawCallback": function(settings) {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
        }
    });

    $('#dataTableProgramaPuntos').on('draw.dt', function() {
        $('[data-toggle="tooltip"]').tooltip();
    });

    table_programa_puntos.search('').draw();
    $('#buscar').focus();

    editar_programa_puntos_dataTable("#dataTableProgramaPuntos tbody", table_programa_puntos);
    eliminar_programa_puntos_dataTable("#dataTableProgramaPuntos tbody", table_programa_puntos);
};

function formatNumber(num) {
    if (num % 1 === 0) {
        return num.toLocaleString('es-HN');
    } else {
        return num.toLocaleString('es-HN', { 
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
}

function initCalculoEventos() {
    $("#calculo_monto, #calculo_porcentaje, #ejemplo_calculo").hide();
    
    $("#tipo_calculo").on("change", function() {
        $("#calculo_monto, #calculo_porcentaje, #ejemplo_calculo").hide();
        
        if (this.value === "monto") {
            $("#calculo_monto").show();
            $("#ejemplo_calculo").show();
            let montoActual = parseFloat($("#monto").val()) || 25;
            let montoDoble = montoActual * 2;
            $("#ejemploTexto").html(`Si se define que por cada <strong>${formatNumber(montoActual)}</strong> Lempiras se acumula 1 punto, entonces por consumir <strong>${formatNumber(montoDoble)}</strong> Lempiras se acumulan 2 puntos.`);
			$("#monto").focus();
        } else if (this.value === "porcentaje") {
            $("#calculo_porcentaje").show();
            $("#ejemplo_calculo").show();
            let porcentajeActual = parseFloat($("#porcentaje").val()) || 10;
            let montoConsumido = 5000;
            let puntos = (montoConsumido * porcentajeActual) / 100;
            $("#ejemploTexto").html(`Si se define un porcentaje de <strong>${porcentajeActual}%</strong>, entonces por consumir <strong>${formatNumber(montoConsumido)}</strong> Lempiras se acumulan <strong>${formatNumber(puntos)}</strong> puntos.`);
			$("#porcentaje").focus();
        }
    });

    $("#monto").on("input", function() {
        if ($("#tipo_calculo").val() === "monto") {
            let monto = parseFloat($(this).val()) || 25;
            let montoDoble = monto * 2;
            $("#ejemploTexto").html(`Si se define que por cada <strong>${formatNumber(monto)}</strong> Lempiras se acumula 1 punto, entonces por consumir <strong>${formatNumber(montoDoble)}</strong> Lempiras se acumulan 2 puntos.`);
            $("#ejemplo_calculo").show();
        }
    });

    $("#porcentaje").on("input", function() {
        if ($("#tipo_calculo").val() === "porcentaje") {
            let porcentaje = parseFloat($(this).val()) || 10;
            let montoConsumido = 5000;
            let puntos = (montoConsumido * porcentaje) / 100;
            $("#ejemploTexto").html(`Si se define un porcentaje de <strong>${porcentaje}%</strong>, entonces por consumir <strong>${formatNumber(montoConsumido)}</strong> Lempiras se acumulan <strong>${formatNumber(puntos)}</strong> puntos.`);
            $("#ejemplo_calculo").show();
        }
    });
}

function modal_programa_puntos() {
    $('#formProgramaPuntos')[0].reset();
    $("#calculo_monto, #calculo_porcentaje, #ejemplo_calculo").hide();
    $('#reg_ProgramaPuntos').show();
    $('#edi_ProgramaPuntos').hide();
    $('#delete_ProgramaPuntos').hide();
    
    $('#modalProgramaPuntos').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

$('#formProgramaPuntos').on('submit', function(e) {
    e.preventDefault();
    
    // Validación básica
    if ($('#nombre').val().trim() === '') {
        showNotify("error", "Error", "El nombre del programa es requerido");
        return;
    }
    
    // Determinar acción (registrar o editar)
    var isRegister = $('#reg_ProgramaPuntos').is(':visible');
    var url = isRegister 
        ? '<?php echo SERVERURL;?>core/programaPuntos/agregarProgramaPuntos.php'
        : '<?php echo SERVERURL;?>core/programaPuntos/editarProgramaPuntos.php';
    var actionText = isRegister ? 'crear' : 'actualizar';
    
    // Asegurar que el estado se envíe correctamente
    var estado = $('#ProgramaPuntos_activo').is(':checked') ? 1 : 0;
    $(this).append('<input type="hidden" name="estado" value="' + estado + '">');
    
    swal({
        title: "¿Estás seguro?",
        text: "¿Desea " + actionText + " este programa de puntos?",
        icon: "warning",
        buttons: {
            cancel: { text: "Cancelar", visible: true },
            confirm: { text: "¡Sí, continuar!" }
        },
        closeOnEsc: false,
        closeOnClickOutside: false
    }).then((willConfirm) => {
        if (willConfirm) {
            $.ajax({
                type: 'POST',
                url: url,
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if(response.estado === true) {
                        showNotify(response.type, response.title, response.message);
                        $('#formProgramaPuntos')[0].reset();
                        $('#modalProgramaPuntos').modal('hide');
                        table_programa_puntos.ajax.reload(null, false);
                        table_programa_puntos.search('').draw();
                    } else {
                        showNotify("error", response.title, response.message);
                    }
                },
                error: function(xhr, status, error) {
                    showNotify("error", "Error", "Ocurrió un error al procesar la solicitud");
                    console.error("Error en la solicitud AJAX:", error);
                }
            });
        }
    });
});

var editar_programa_puntos_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_editar");
    $(tbody).on("click", "button.table_editar", function() {
        var data = table.row($(this).parents("tr")).data();
        
        $('#formProgramaPuntos #programa_puntos_id').val(data.id);
        $('#formProgramaPuntos').attr('data-form', 'update');
        $('#formProgramaPuntos').attr('action', '<?php echo SERVERURL;?>core/programaPuntos/editarProgramaPuntos.php');
        
        $('#reg_ProgramaPuntos').hide();
        $('#edi_ProgramaPuntos').show();
        $('#delete_ProgramaPuntos').hide();
        
        $('#formProgramaPuntos #nombre').val(data.nombre);
        $('#formProgramaPuntos #tipo_calculo').val(data.tipo_calculo).selectpicker('refresh').trigger("change");
        $('#formProgramaPuntos #monto').val(data.monto).trigger("input");
        $('#formProgramaPuntos #porcentaje').val(data.porcentaje).trigger("input");
        $('#formProgramaPuntos #estado').val(data.activo).selectpicker('refresh');
        
        if(data.tipo_calculo === "monto") {
            $("#calculo_monto").show();
            $("#ejemplo_calculo").show();
            let montoDoble = parseFloat(data.monto) * 2 || 50;
            $("#ejemploTexto").html(`Si se define que por cada ${formatNumber(data.monto)} Lempiras se acumula 1 punto, entonces por consumir ${formatNumber(montoDoble)} Lempiras se acumulan 2 puntos.`);
        } else if(data.tipo_calculo === "porcentaje") {
            $("#calculo_porcentaje").show();
            $("#ejemplo_calculo").show();
            let montoConsumido = 5000;
            let puntos = (montoConsumido * parseFloat(data.porcentaje)) / 100;
            $("#ejemploTexto").html(`Si se define un porcentaje de ${data.porcentaje}%, entonces por consumir ${formatNumber(montoConsumido)} Lempiras se acumulan ${formatNumber(puntos)} puntos.`);
        }
        
        if(data.activo == 1) {
            $('#formProgramaPuntos #ProgramaPuntos_activo').prop('checked', true);
            $('#formProgramaPuntos #label_ProgramaPuntos_activo').html("Activo");
        } else {
            $('#formProgramaPuntos #ProgramaPuntos_activo').prop('checked', false);
            $('#formProgramaPuntos #label_ProgramaPuntos_activo').html("Inactivo");
        }
        
        $('#modalProgramaPuntos').modal({
            show: true,
            keyboard: false,
            backdrop: 'static'
        });
    });
};

var eliminar_programa_puntos_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_eliminar");
    $(tbody).on("click", "button.table_eliminar", function() {
        var data = table.row($(this).parents("tr")).data();
        
        var mensajeHTML = `¿Desea eliminar permanentemente el programa de puntos?<br><br>
                        <strong>Programa:</strong> ${data.nombre}<br>
                        <strong>Tipo Calculo:</strong> ${data.tipo_calculo}`;
        
        swal({
            title: "Confirmar eliminación",
            content: {
                element: "span",
                attributes: {
                    innerHTML: mensajeHTML
                }
            },
            icon: "warning",
            buttons: {
                cancel: {
                    text: "Cancelar",
                    value: null,
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
            if (confirmar) {                                
                $.ajax({
                    type: 'POST',
                    url: '<?php echo SERVERURL;?>core/programaPuntos/eliminarProgramaPuntos.php',
                    data: {
                        programa_puntos_id: data.id
                    },
                    dataType: 'json',
                    success: function(response) {                       
                        if(response.estado === true) {
                            showNotify("success", response.title || "Éxito", response.message);
                            table_programa_puntos.ajax.reload(null, false);
                            table_programa_puntos.search('').draw();                    
                        } else {
                            showNotify("error", response.title || "Error", response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        showNotify("error", "Error", "Ocurrió un error al procesar la solicitud");
                        console.error("Error en la solicitud AJAX:", error);
                    },
                    complete: function() {
                        swal.close();
                    }
                });
            }
        });
    });
};

$('#dataTableProgramaPuntos').on('click', '.ver-historico', function(e) {
    e.preventDefault();
    const programaPuntosId = $(this).data('id');
    const programaNombre = $(this).text();
    
    // Destruir DataTable si ya existe
    if ($.fn.DataTable.isDataTable('#tablaHistoricoPuntos')) {
        $('#tablaHistoricoPuntos').DataTable().destroy();
        $('#tablaHistoricoPuntos tbody').empty();
    }
    
    $('#modalHistoricoPuntosLabel').text(`Historial de Puntos - ${programaNombre}`);
    $('#modalHistoricoPuntos').modal({
        show: true,
        keyboard: false
    });
    
    $('#fecha-actualizacion').text('cargando...');
    
    $.ajax({
        url: '<?php echo SERVERURL;?>core/programaPuntos/llenarDataTableHistoricoPuntos.php',
        type: 'POST',
        data: { programa_puntos_id: programaPuntosId },
        dataType: 'json',
        success: function(response) {
            $('#tablaHistoricoPuntos tbody').empty();
            
            if(response.data && response.data.length > 0) {
                response.data.forEach(function(item) {
                    $('#tablaHistoricoPuntos tbody').append(`
                        <tr>
                            <td>${item.cliente}</td>
                            <td><span class="badge ${item.tipo_movimiento === 'Acumulación' ? 'badge-success' : 'badge-danger'}">${item.tipo_movimiento}</span></td>
                            <td class="text-right">${item.puntos}</td>
                            <td>${item.descripcion}</td>
                            <td>${item.fecha}</td>
                        </tr>
                    `);
                });
                
                $('#fecha-actualizacion').text(response.ultima_actualizacion);
                
                // Inicializar DataTable después de agregar los datos
                $('#tablaHistoricoPuntos').DataTable({
                    "language": idioma_español,
                    "dom": '<"top"f>rt<"bottom"lip><"clear">',
                    "pageLength": 10,
                    "order": [[4, "desc"]],
                    "columnDefs": [
                        { width: "25%", targets: 0 },
                        { width: "15%", targets: 1 },
                        { width: "10%", targets: 2 },
                        { width: "30%", targets: 3 },
                        { width: "20%", targets: 4 }
                    ],
                    "initComplete": function() {
                        // Asegurar que las tooltips funcionen
                        $('[data-toggle="tooltip"]').tooltip();
                    }
                });
            } else {
                $('#tablaHistoricoPuntos tbody').append(`
                    <tr>
                        <td colspan="5" class="text-center">No hay registros de historial para este programa</td>
                    </tr>
                `);
                $('#fecha-actualizacion').text('No disponible');
            }
        },
        error: function() {
            $('#tablaHistoricoPuntos tbody').append(`
                <tr>
                    <td colspan="5" class="text-center text-danger">Error al cargar el historial</td>
                </tr>
            `);
            $('#fecha-actualizacion').text('Error');
        }
    });
});

$('#modalHistoricoPuntos').on('hidden.bs.modal', function() {
    // Destruir DataTable al cerrar el modal
    if ($.fn.DataTable.isDataTable('#tablaHistoricoPuntos')) {
        $('#tablaHistoricoPuntos').DataTable().destroy();
    }
    $('#tablaHistoricoPuntos tbody').empty();
});
</script>