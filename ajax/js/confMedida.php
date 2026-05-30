<script>
$(document).ready(function() {
    listar_medidas(); 

	$('#form_main_medidas #search').on("click", function (e) {
		e.preventDefault();
		listar_medidas();
	});

	// Evento para el botón de Limpiar (reset)
	$('#form_main_medidas').on('reset', function () {
		// Limpia y refresca los selects
		$(this).find('.selectpicker') // Usa `this` para referenciar el formulario actual
			.val('')
			.selectpicker('refresh');

			listar_medidas();
	});	
});

/* =========================================================
   HEADER DINÁMICO - MEDIDAS
   ========================================================= */
   function construirHeaderDataTableConfMedidas() {
    var $tabla = $("#dataTableConfMedidas");

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Medida</th>' +
                '<th>Descripción</th>' +
                '<th>Estado</th>' +
            '</tr>' +
        '</thead>'
    );
}

//INICIO MEDIDAS
var listar_medidas = function() {
    var estado = $('#form_main_medidas #estado_medidas').val();

    if ($.fn.DataTable.isDataTable("#dataTableConfMedidas")) {
        $("#dataTableConfMedidas").DataTable().clear().destroy();
    }

    construirHeaderDataTableConfMedidas();

    var table_medidas = $("#dataTableConfMedidas").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL; ?>core/llenarDataTableMedida.php",
            "data": {
                "estado": estado
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
                                '<button type="button" class="dropdown-item accion-item accion-editar table_editar ocultar">' +
                                    '<span class="accion-icon accion-icon-editar">' +
                                        '<i class="fas fa-edit"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Editar</span>' +
                                '</button>' +
                                '<button type="button" class="dropdown-item accion-item accion-eliminar table_eliminar ocultar">' +
                                    '<span class="accion-icon accion-icon-eliminar">' +
                                        '<i class="fas fa-trash-alt"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Eliminar</span>' +
                                '</button>' +
                            '</div>' +
                        '</div>';
                }
            },
            {"data": "nombre"},
            {"data": "descripcion"},
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
                width: "20%",
                targets: 1
            },
            {
                width: "60%",
                targets: 2
            },
            {
                width: "10%",
                targets: 3,
                className: "text-center text-nowrap align-middle"
            }
        ],
        "buttons": [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Medidas',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_medidas();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg"></i> Ingresar',
                titleAttr: 'Agregar Medidas',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modalMedidas();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte Medidas',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: {
                    columns: [1, 2]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                title: 'Reporte Medidas',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [1, 2]
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

            if (typeof cerrarDropdownAcciones === "function") {
                cerrarDropdownAcciones();
            }
        }
    });

    table_medidas.search('').draw();
    $('#buscar').focus();

    edit_medidas_dataTable("#dataTableConfMedidas tbody", table_medidas);
    delete_medidas_dataTable("#dataTableConfMedidas tbody", table_medidas);
}

var edit_medidas_dataTable = function(tbody, table){
	$(tbody).off("click", "button.table_editar");
	$(tbody).on("click", "button.table_editar", function(){
		var data = table.row( $(this).parents("tr") ).data();
		var url = '<?php echo SERVERURL;?>core/editarMedidas.php';
		$('#formMedidas #medida_id').val(data.medida_id);

		$.ajax({
			type:'POST',
			url:url,
			data:$('#formMedidas').serialize(),
			success: function(registro){
				var valores = eval(registro);
				$('#formMedidas').attr({ 'data-form': 'update' });
				$('#formMedidas').attr({ 'action': '<?php echo SERVERURL;?>ajax/modificarMedidasAjax.php' });
				$('#formMedidas')[0].reset();
				$('#reg_medidas').hide();
				$('#edi_medidas').show();
				$('#delete_medidas').hide();
				$('#formMedidas #pro_medidas').val("Editar");
				$('#formMedidas #medidas_medidas').val(valores[0]);
				$('#formMedidas #descripcion_medidas').val(valores[1]);

				if(valores[2] == 1){
					$('#formMedidas #medidas_activo').attr('checked', true);
				}else{
					$('#formMedidas #medidas_activo').attr('checked', false);
				}

				//HABILITAR OBJETOS
				$('#formMedidas #medidas_medidas').attr('readonly', false);
				$('#formMedidas #descripcion_medidas').attr('readonly', false);
				$('#formMedidas #medidas_activo').attr('disabled', false);
				$('#formMedidas #estado_medidas').show();

				//DESHABIITAR OBJETOS
				$('#formMedidas #medidas_medidas').attr('readonly', true);

				$('#modal_medidas').modal({
					show:true,
					keyboard: false,
					backdrop:'static'
				});
			}
		});
	});
}

var delete_medidas_dataTable = function(tbody, table){
	$(tbody).off("click", "button.table_eliminar");
	$(tbody).on("click", "button.table_eliminar", function(){
		var data = table.row( $(this).parents("tr") ).data();

		var medida_id = data.medida_id;
        var nombreMedida = data.nombre; 
        
        // Construir el mensaje de confirmación con HTML
        var mensajeHTML = `¿Desea eliminar permanentemente la medida?<br><br>
                        <strong>Nombre:</strong> ${nombreMedida}`;
        
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
                    url: '<?php echo SERVERURL;?>ajax/eliminarMedidasAjax.php',
                    data: {
                        medida_id: medida_id
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
//FIN MEDIDAS

//INICIO FORMULARIO MEDIDAS
function modalMedidas(){
	$('#formMedidas').attr({ 'data-form': 'save' });
	$('#formMedidas').attr({ 'action': '<?php echo SERVERURL; ?>ajax/agregarMedidasAjax.php' });
	$('#formMedidas')[0].reset();
	$('#formMedidas #pro_medidas').val("Registro");
	$('#reg_medidas').show();
	$('#edi_medidas').hide();
	$('#delete_medidas').hide();

	//HABILITAR OBJETOS
	$('#formMedidas #medidas_medidas').attr('readonly', false);
	$('#formMedidas #descripcion_medidas').attr('readonly', false);
	$('#formMedidas #medidas_activo').attr('disabled', false);
	$('#formMedidas #estado_medidas').hide();

	$('#modal_medidas').modal({
		show:true,
		keyboard: false,
		backdrop:'static'
	});
}
//FIN FORMULARIO MEDIDAS

$(document).ready(function(){
    $("#modal_medidas").on('shown.bs.modal', function(){
        $(this).find('#formMedidas #medidas_medidas').focus();
    });
});

$('#formMedidas #label_medidas_activo').html("Activo");
	
$('#formMedidas .switch').change(function(){    
    if($('input[name=medidas_activo]').is(':checked')){
        $('#formMedidas #label_medidas_activo').html("Activo");
        return true;
    }
    else{
        $('#formMedidas #label_medidas_activo').html("Inactivo");
        return false;
    }
});	
</script>