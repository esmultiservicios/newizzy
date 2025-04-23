<script>
$(document).ready(function() {
    listar_colaboradores();
    getEmpresaColaboradores();
});
//INICIO ACCIONES FROMULARIO COLABORADORES
var listar_colaboradores = function() {
    var table_colaboradores = $("#dataTableColaboradores").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableColaboradores.php"
        },
        "columns": [{
                "data": "empresa"
            },
            {
                "data": "colaborador"
            },
            {
                "data": "identidad"
            },
            {
                "data": "estado"
            },
            {
                "data": "telefono"
            },
            {
                "data": "puesto"
            },
            {
                "defaultContent": "<button class='table_editar btn btn-dark ocultar'><span class='fas fa-edit fa-lg'></span></button>"
            },
            {
                "defaultContent": "<button class='table_eliminar btn btn-dark ocultar'><span class='fa fa-trash fa-lg'></span></button>"
            }
        ],
        "lengthMenu": lengthMenu,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [{
                width: "22.5%",
                targets: 0
            },
            {
                width: "22.5%",
                targets: 1
            },
            {
                width: "12.5%",
                targets: 2
            },
            {
                width: "12.5%",
                targets: 3
            },
            {
                width: "12.5%",
                targets: 4
            },
            {
                width: "12.5%",
                targets: 5
            },
            {
                width: "2.5%",
                targets: 6
            },
            {
                width: "2.5%",
                targets: 7
            }
        ],
        "buttons": [{
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Colaboradores',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_colaboradores();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg"></i> Ingresar',
                titleAttr: 'Agregar Colaboradores',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modal_colaboradores();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte de Colaboradores',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                title: 'Reporte de Colaboradores',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4]
                },
				customize: function(doc) {
					if (imagen) { // Solo agrega la imagen si 'imagen' tiene contenido válido
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
    table_colaboradores.search('').draw();
    $('#buscar').focus();

    editar_colaboradores_dataTable("#dataTableColaboradores tbody", table_colaboradores);
    eliminar_colaboradores_dataTable("#dataTableColaboradores tbody", table_colaboradores);
}

var editar_colaboradores_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_editar");
    $(tbody).on("click", "button.table_editar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarColaboradores.php';
        $('#formColaboradores')[0].reset();
        $('#formColaboradores #colaborador_id').val(data.colaborador_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formColaboradores').serialize(),
            success: function(registro) {
                var valores = eval(registro);
                $('#formColaboradores').attr({
                    'data-form': 'update'
                });
                $('#formColaboradores').attr({
                    'action': '<?php echo SERVERURL;?>ajax/modificarColaboradorAjax.php'
                });
                $('#reg_colaborador').hide();
                $('#edi_colaborador').show();
                $('#delete_colaborador').hide();
                $('#formColaboradores #nombre_colaborador').val(valores[0]);
                $('#formColaboradores #identidad_colaborador').val(valores[1]);
                $('#formColaboradores #telefono_colaborador').val(valores[2]);
                $('#formColaboradores #puesto_colaborador').val(valores[3]);
                $('#formColaboradores #puesto_colaborador').selectpicker('refresh');
                $('#formColaboradores #colaborador_empresa_id').val(valores[4]);
                $('#formColaboradores #colaborador_empresa_id').selectpicker('refresh');
                $('#formColaboradores #fecha_ingreso_colaborador').val(valores[7]);
                $('#formColaboradores #fecha_egreso_colaborador').val(valores[8]);

                if (valores[5] == 1) {
                    $('#formColaboradores #colaboradores_activo').attr('checked', true);
                } else {
                    $('#formColaboradores #colaboradores_activo').attr('checked', false);
                }

                //HABILITAR OBJETOS
                $('#formColaboradores #nombre_colaborador').attr('readonly', false);
                $('#formColaboradores #identidad_colaborador').attr('readonly', false);
                $('#formColaboradores #telefono_colaborador').attr('readonly', false);
                $('#formColaboradores #estado_colaborador').attr('disabled', false);
                $('#formColaboradores #colaboradores_activo').attr('disabled', false);
                $('#formColaboradores #fecha_ingreso_colaborador').attr('disabled', false);
                $('#formColaboradores #fecha_egreso_colaborador').attr('disabled', false);

                $('#formColaboradores #puesto_colaborador').attr('disabled', false);
                $('#formColaboradores #colaborador_empresa_id').attr('disabled', false);
                $('#formColaboradores #estado_colaboradores').show();

                $('#datosClientes').hide();
                $('#formColaboradores #estado_colaborador').show();

                $('#formColaboradores #proceso_colaboradores').val("Editar");
                $('#modal_registrar_colaboradores').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
    });
}

var eliminar_colaboradores_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_eliminar");
    $(tbody).on("click", "button.table_eliminar", function() {
        var data = table.row($(this).parents("tr")).data();

        var colaborador_id = data.colaborador_id;
        var nombre = data.colaborador; 
        var empresa = data.empresa;

        // Construir el mensaje de confirmación con HTML
        var mensajeHTML = `¿Desea eliminar permanentemente el usuario?<br><br>
                        <strong>Nombre:</strong> ${nombre}<br>
                        <strong>Empresa:</strong> ${empresa}`;
                                                
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
                    url: '<?php echo SERVERURL;?>ajax/eliminarColaboradorAjax.php',
                    data: {
                        users_id: users_id
                    },
                    dataType: 'json', // Esperamos respuesta JSON
                    before: function(){
                        // Mostrar carga mientras se procesa
                        showLoading("Eliminando registro...");
                    },                    
                    success: function(response) {
                        swal.close();
                        
                        if(response.status === "success") {
                            showNotify("success", response.title, response.message);
                            table.ajax.reload(null, false); // Recargar tabla sin resetear paginación
                            table.search('').draw();                    
                        } else {
                            showNotify("error", response.title, response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        swal.close();
                        showNotify("error", "Error", "Ocurrió un error al procesar la solicitud");
                        console.error("Error en la solicitud AJAX:", error);
                    }
                });
            }
        });

    });
}

$(document).ready(function() {
    $("#modal_registrar_colaboradores").on('shown.bs.modal', function() {
        $(this).find('#formColaboradores #nombre_colaborador').focus();
    });
});

$('#formColaboradores #label_colaboradores_activo').html("Activo");

$('#formColaboradores .switch').change(function() {
    if ($('input[name=colaboradores_activo]').is(':checked')) {
        $('#formColaboradores #label_colaboradores_activo').html("Activo");
        return true;
    } else {
        $('#formColaboradores #label_colaboradores_activo').html("Inactivo");
        return false;
    }
});
</script>