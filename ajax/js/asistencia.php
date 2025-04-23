<script>
(function() {
    listar_asistencia();
    getColaboradores();
})();

//INICIO ASISTENCIA
var listar_asistencia = function(){
    var table_asistencia = $("#dataTableAsistencia").DataTable({
        "destroy":true,
        "ajax":{
            "method":"POST",
            "url":"<?php echo SERVERURL; ?>core/llenarDataTableAsistencia.php"
        },
        "columns":[
            {"data":"colaborador"},
            {"data":"fecha"},
            {"data":"horai"},
            {"data":"horaf"},
            {"data":"comentario"},
            {"defaultContent":"<button class='table_editar btn btn-dark ocultar'><span class='fas fa-edit'></span>Editar</button>"},
            {"defaultContent":"<button class='table_eliminar btn btn-dark ocultar'><span class='fa fa-trash'></span>Eliminar</button>"}
        ],
        "lengthMenu": lengthMenu,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [
            { width: "20%", targets: 0 },
            { width: "15%", targets: 1 },
            { width: "10%", targets: 2 },
            { width: "10%", targets: 3 },
            { width: "30%", targets: 4 },
            { width: "7.5%", targets: 5 },
            { width: "7.5%", targets: 6 }
        ],
        "buttons":[
            {
                text:      '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Asistencia',
                className: 'table_actualizar btn btn-secondary ocultar',
                action:    function(){
                    listar_asistencia();
                }
            },
            {
                text:      '<i class="fas fa-user-clock fa-lg"></i> Registrar',
                titleAttr: 'Registrar Asistencia',
                className: 'table_crear btn btn-primary ocultar',
                action:    function(){
                    modalAsistencia();
                }
            },
            {
                extend:    'excelHtml5',
                text:      '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte de Asistencia',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4]
                }
            },
            {
                extend:    'pdf',
                text:      '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                title: 'Reporte de Asistencia',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4]
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
        "drawCallback": function(settings) {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
        }
    });
    
    table_asistencia.search('').draw();
    $('#buscar').focus();

    edit_asistencia_dataTable("#dataTableAsistencia tbody", table_asistencia);
    delete_asistencia_dataTable("#dataTableAsistencia tbody", table_asistencia);
}

var edit_asistencia_dataTable = function(tbody, table){
    $(tbody).off("click", "button.table_editar");
    $(tbody).on("click", "button.table_editar", function(){
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarAsistencia.php';
        $('#formAsistencia #asistencia_id').val(data.asistencia_id);

        $.ajax({
            type:'POST',
            url:url,
            data:$('#formAsistencia').serialize(),
            success: function(registro){
                var valores = eval(registro);
                $('#formAsistencia').attr({ 'data-form': 'update' });
                $('#formAsistencia').attr({ 'action': '<?php echo SERVERURL;?>ajax/modificarAsistenciaAjax.php' });
                $('#formAsistencia')[0].reset();
                $('#reg_asistencia').hide();
                $('#edi_asistencia').show();
                $('#delete_asistencia').hide();
                $('#formAsistencia #pro_asistencia').val("Editar");
                
                // Llenar los campos del formulario
                $('#formAsistencia #asistencia_empleado').val(valores[0]);
                $('#formAsistencia #fecha').val(valores[1]);
                $('#formAsistencia #hora').val(valores[2]);
                $('#formAsistencia #horaf').val(valores[3]);
                $('#formAsistencia #comentario').val(valores[4]);

                $('#modalAsistencia').modal({
                    show:true,
                    keyboard: false,
                    backdrop:'static'
                });
            }
        });
    });
}

var delete_asistencia_dataTable = function(tbody, table){
    $(tbody).off("click", "button.table_eliminar");
    $(tbody).on("click", "button.table_eliminar", function(){
        var data = table.row($(this).parents("tr")).data();
        var asistencia_id = data.asistencia_id;
        var nombreColaborador = data.colaborador;
        var fecha = data.fecha;

        var mensajeHTML = `¿Desea eliminar permanentemente el registro de asistencia?<br><br>
                        <strong>Colaborador:</strong> ${nombreColaborador}<br>
                        <strong>Fecha:</strong> ${fecha}`;

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
                    url: '<?php echo SERVERURL;?>ajax/eliminarAsistenciaAjax.php',
                    data: {
                        asistencia_id: asistencia_id
                    },
                    dataType: 'json',
                    beforeSend: function(){
                        showLoading("Eliminando registro...");
                    },
                    success: function(response) {
                        swal.close();
                        
                        if(response.status === "success") {
                            showNotify("success", response.title, response.message);
                            table.ajax.reload(null, false);
                            table.search('').draw();
                        } else {
                            showNotify("error", response.title, response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        swal.close();
                        showNotify("error", "Error", "Ocurrió un error al procesar la solicitud");
                    }
                });
            }
        });
    });
}
//FIN ASISTENCIA

//INICIO FORMULARIO ASISTENCIA
function modalAsistencia(){
    $('#formAsistencia').attr({ 'data-form': 'save' });
    $('#formAsistencia').attr({ 'action': '<?php echo SERVERURL; ?>ajax/addAsistenciaAjax.php' });
    $('#formAsistencia')[0].reset();
    $('#formAsistencia #pro_asistencia').val("Registro");
    $('#reg_asistencia').show();
    $('#edi_asistencia').hide();
    $('#delete_asistencia').hide();

    // Establecer fecha y hora actual por defecto
    var now = new Date();
    var fecha = now.toISOString().substring(0, 10);
    var hora = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
    
    $('#formAsistencia #fecha').val(fecha);
    $('#formAsistencia #hora').val(hora);

    $('#modalAsistencia').modal({
        show:true,
        keyboard: false,
        backdrop:'static'
    });
}
//FIN FORMULARIO ASISTENCIA

// Función para cargar colaboradores en el select
function getColaboradores(){
    $.ajax({
        url: "<?php echo SERVERURL; ?>core/getColaboradoresAsistencia.php",
        type: "POST",
        dataType: "json",
        success: function(response) {
            const select = $('#formAsistencia #asistencia_empleado');
            select.empty();
            
            if(response.success) {
                response.data.forEach(colaborador => {
                    select.append(`
                        <option value="${colaborador.colaboradores_id}">
                            ${colaborador.nombre}
                        </option>
                    `);
                });
            } else {
                select.append('<option value="">No hay colaboradores disponibles</option>');
            }
            
            select.selectpicker('refresh');
        },
        error: function(xhr) {
            showNotify("error", "Error", "Error de conexión al cargar colaboradores");
            $('#formAsistencia #asistencia_empleado').html('<option value="">Error al cargar</option>');
            $('#formAsistencia #asistencia_empleado').selectpicker('refresh');
        }
    });
}

(function() {
    $("#modalAsistencia").on('shown.bs.modal', function(){
        $(this).find('#formAsistencia #asistencia_empleado').focus();
    });
})();
</script>