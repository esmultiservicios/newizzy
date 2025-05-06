<script>
$(document).ready(function() {
    listar_almacen();
    getEmpresaAlmacen();
    getUbicacionAlmacen();

	$('#form_main_almacen #search').on("click", function (e) {
		e.preventDefault();
		listar_almacen();
	});

	// Evento para el botón de Limpiar (reset)
	$('#form_main_almacen').on('reset', function () {
		// Limpia y refresca los selects
		$(this).find('.selectpicker') // Usa `this` para referenciar el formulario actual
			.val('')
			.selectpicker('refresh');

			listar_almacen();
	});    
});

//INICIO ALMACEN
var listar_almacen = function() {
    var estado = $('#form_main_almacen #estado_almacen').val();

    var table_almacen = $("#dataTableConfAlmacen").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL; ?>core/llenarDataTableAlmacen.php",
			"data": {
                "estado": estado
            }            
        },
        "columns": [{
                "data": "empresa"
            },
            {
                "data": "almacen"
            },
            {
                "data": "facturarCero"
            },
            {
                "data": "ubicacion"
            },
            {
                "data": "estado",
                "render": function(data, type, row) {
                    if (type === 'display') {
                        var estadoText = data == 1 ? 'Activo' : 'Inactivo';
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
            },            
            {
                "defaultContent": "<button class='table_editar btn ocultar'><span class='fas fa-edit'></span>Editar</button>"
            },
            {
                "defaultContent": "<button class='table_eliminar btn ocultar'><span class='fa fa-trash'></span>Eliminar</button>"
            }
        ],
        "lengthMenu": lengthMenu,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español, //esta se encuenta en el archivo main.js
        "dom": dom,
        "columnDefs": [{
                width: "30%",
                targets: 0
            },
            {
                width: "30%",
                targets: 1
            },
            {
                width: "20%",
                targets: 2
            },
            {
                width: "30%",
                targets: 3
            },
            {
                width: "5%",
                targets: 4
            },
            {
                width: "5%",
                targets: 5
            }

        ],
        "buttons": [{
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Almacén',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_almacen();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg"></i> Ingresar',
                titleAttr: 'Agregar Almacén',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modalAlmacen();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte Almacén',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: {
                    columns: [0, 1, 2, 3]
                },
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                title: 'Reporte Almacén',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [0, 1, 2, 3]
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
    table_almacen.search('').draw();
    $('#buscar').focus();

    edit_alamcen_dataTable("#dataTableConfAlmacen tbody", table_almacen);
    delete_almacen_dataTable("#dataTableConfAlmacen tbody", table_almacen);
}

var edit_alamcen_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_editar");
    $(tbody).on("click", "button.table_editar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarAlmacen.php';
        $('#formAlmacen #almacen_id').val(data.almacen_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formAlmacen').serialize(),
            success: function(registro) {
                var valores = eval(registro);
                $('#formAlmacen').attr({
                    'data-form': 'update'
                });
                $('#formAlmacen').attr({
                    'action': '<?php echo SERVERURL;?>ajax/modificarAlmacenAjax.php'
                });
                $('#formAlmacen')[0].reset();
                $('#reg_almacen').hide();
                $('#edi_almacen').show();
                $('#delete_almacen').hide();
                $('#formAlmacen #pro_almacen').val("Editar Almacén");
                $('#formAlmacen #ubicacion_almacen').val(valores[0]);
                $('#formAlmacen #ubicacion_almacen').selectpicker('refresh');
                $('#formAlmacen #almacen_almacen').val(valores[1]);
                $('#formAlmacen #almacen_empresa_id').val(valores[3]);
                $('#formAlmacen #almacen_empresa_id').selectpicker('refresh');

                if (valores[2] == 1) {
                    $('#formAlmacen #almacen_activo').attr('checked', true);
                } else {
                    $('#formAlmacen #almacen_activo').attr('checked', false);
                }

                if (valores[4] == 1) {
                    $('#formAlmacen #label_facturar_cero').html("Si");
                    $('#formAlmacen #facturar_cero').attr('checked', true);
                    $('#formAlmacen #cero').attr('checked', true);

                } else {
                    $('#formAlmacen #label_facturar_cero').html("No");
                    $('#formAlmacen #cero').attr('checked', false);
                    $('#formAlmacen #facturar_cero').attr('checked', false);

                }

                //HABILITAR OBJETOS			
                $('#formAlmacen #almacen_almacen').attr('readonly', false);
                $('#formAlmacen #ubicacion_almacen').attr('disabled', true);
                $('#formAlmacen #almacen_activo').attr('disabled', true);
                $('#formAlmacen #almacen_empresa_id').attr('disabled', true);

                //DESHABILITAR OBJETO
                $('#formAlmacen #almacen_empresa_id').attr('disabled', true);

                $('#modal_almacen').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
    });
}

var delete_almacen_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_eliminar");
    $(tbody).on("click", "button.table_eliminar", function() {
        var data = table.row($(this).parents("tr")).data();

        var almacen_id = data.almacen_id;
        var nombreAlmacen = data.nombre; 
        
        // Construir el mensaje de confirmación con HTML
        var mensajeHTML = `¿Desea eliminar permanentemente el almacén?<br><br>
                        <strong>Nombre:</strong> ${nombreAlmacen}`;
        
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
                    url: '<?php echo SERVERURL;?>ajax/eliminarAlmacenesAjax.php',
                    data: {
                        almacen_id: almacen_id
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
                    }
                });
            }
        });
    });
}

//INICIO FORMULARIO ALMACEN
function modalAlmacen() {
    $('#formAlmacen').attr({
        'data-form': 'save'
    });
    $('#formAlmacen').attr({
        'action': '<?php echo SERVERURL; ?>ajax/agregarAlmacenAjax.php'
    });
    $('#formAlmacen')[0].reset();
    $('#formAlmacen #pro_almacen').val("Registrar Almacén");
    $('#reg_almacen').show();
    $('#edi_almacen').hide();
    $('#delete_almacen').hide();
    getUbicacionAlmacen();
    getAlmacen();
    //HABILITAR OBJETOS
    $('#formAlmacen #almacen_almacen').attr('readonly', false);
    $('#formAlmacen #ubicacion_almacen').attr('disabled', false);
    $('#formAlmacen #almacen_activo').attr('disabled', false);
    $('#formAlmacen #almacen_empresa_id').attr('disabled', false);

    $('#modal_almacen').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}
//FIN FORMULARIO ALMACEN

function getUbicacionAlmacen() {
    var url = '<?php echo SERVERURL;?>core/getUbicacion.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formAlmacen #ubicacion_almacen').html("");
            $('#formAlmacen #ubicacion_almacen').html(data);
            $('#formAlmacen #ubicacion_almacen').selectpicker('refresh');
        }
    });
}

function getEmpresaAlmacen() {
    $.ajax({
        url: "<?php echo SERVERURL; ?>core/getEmpresa.php",
        type: "POST",
        dataType: "json",
        success: function(response) {
            const select = $('#formAlmacen #almacen_empresa_id');
            select.empty();
            
            if(response.success) {
                response.data.forEach(empresa => {
                    select.append(`
                        <option value="${empresa.empresa_id}">
                            ${empresa.nombre}
                        </option>
                    `);
                });
                
                // Establecer valor por defecto si existe
                if(response.data.length > 0) {
                    select.val(1); // O el valor que necesites por defecto
                    select.selectpicker('refresh');
                }
            } else {
                select.append('<option value="">No hay empresas disponibles</option>');
                showNotify("warning", "Advertencia", response.message || "No se encontraron empresas");
            }
            
            select.selectpicker('refresh');
        },
        error: function(xhr) {
            showNotify("error", "Error", "Error de conexión al cargar empresas");
            $('#formAlmacen #almacen_empresa_id').html('<option value="">Error al cargar</option>');
            $('#formAlmacen #almacen_empresa_id').selectpicker('refresh');
        }
    });
}

$(document).ready(function() {
    $("#modal_almacen").on('shown.bs.modal', function() {
        $(this).find('#formAlmacen #almacen_almacen').focus();
    });
});
//almacen activo
$('#formAlmacen #label_almacen_activo').html("Activo");

$('#formAlmacen .switch').change(function() {
    if ($('input[name=almacen_activo1]').is(':checked')) {

        $('#formAlmacen #label_almacen_activo').html("Activo");
        $("#almacen_activo").val(1);
        $("#val_almacen_activo").val(1);
        return true;
    } else {

        $('#formAlmacen #label_almacen_activo').html("Inactivo");
        $("#almacen_activo").val(0);
        $("#val_almacen_activo").val(0);
        return false;
    }
});

//facturar en cero switch
//$('#formAlmacen #label_facturar_cero').html("Si");

$('#formAlmacen .switch').change(function() {
    if ($('#formAlmacen #facturar_cero').is(':checked')) {

        $('#formAlmacen #label_facturar_cero').html("Si");
        $('#formAlmacen #facturar_cero').attr('checked', true);
        $('#formAlmacen #cero').attr('checked', true);


        $('#formAlmacen #label_facturar_cero').html("Si");
        $("#formAlmacen #facturar_cero").val(1)
        $("#formAlmacen #cero").val(1)
        return true;
    } else {
        $('#formAlmacen #label_facturar_cero').html("No");
        $("#facturar_cero").val(0)
        $("#cero").val(0)
        return false;
    }
});
</script>