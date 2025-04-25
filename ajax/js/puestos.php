<script>
$(document).ready(function() {
    listar_puestos();
});

//INICIO ACCIONES FROMULARIO PUESTOS
var listar_puestos = function(){
	var table_puestos  = $("#dataTablePuestos").DataTable({
		"destroy":true,
		"ajax":{
			"method":"POST",
			"url":"<?php echo SERVERURL;?>core/llenarDataTablePuestos.php"
		},
		"columns":[
			{"data":"puestos_id"},
			{"data":"nombre"},
			{"defaultContent":"<button class='table_editar btn btn-dark ocultar'><span class='fas fa-edit fa-lg'></span>Editar</button>"},
			{"defaultContent":"<button class='table_eliminar btn btn-dark ocultar'><span class='fa fa-trash fa-lg'></span>Eliminar</button>"}
		],
        "lengthMenu": lengthMenu,
		"stateSave": true,
		"language": idioma_español,
		"dom": dom,
		"columnDefs": [
		  { width: "5%", targets: 0 },
		  { width: "85%", targets: 1 },
		  { width: "5%", targets: 2 },
		  { width: "5%", targets: 3 }
		],
		"buttons":[
			{
				text:      '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
				titleAttr: 'Actualizar Puestos',
				className: 'table_actualizar btn btn-secondary ocultar',
				action: 	function(){
					listar_puestos();
				}
			},
			{
				text:      '<i class="fas fas fa-plus fa-lg"></i> Ingresar',
				titleAttr: 'Agregar Puestos',
				className: 'table_crear btn btn-primary ocultar',
				action: 	function(){
					modal_puestos();
				}
			},
			{
				extend:    'excelHtml5',
				text:      '<i class="fas fa-file-excel fa-lg"></i> Excel',
				titleAttr: 'Excel',
				title: 'Reporte de Puestos',
				messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
				className: 'table_reportes btn btn-success ocultar',
				exportOptions: {
						columns: [0,1]
				}					
			},
			{
				extend:    'pdf',
				text:      '<i class="fas fa-file-pdf fa-lg"></i> PDF',
				titleAttr: 'PDF',
				title: 'Reporte de Puestos',
				messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
				className: 'table_reportes btn btn-danger ocultar',
				exportOptions: {
						columns: [0,1]
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
		"drawCallback": function( settings ) {
        	getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
    	}
	});
	table_puestos.search('').draw();
	$('#buscar').focus();

	editar_puestos_dataTable("#dataTablePuestos tbody", table_puestos);
	eliminar_puestos_dataTable("#dataTablePuestos tbody", table_puestos);
}

var editar_puestos_dataTable = function(tbody, table){
	$(tbody).off("click", "button.table_editar");
	$(tbody).on("click", "button.table_editar", function(){
		var data = table.row( $(this).parents("tr") ).data();
		var url = '<?php echo SERVERURL;?>core/editarPuestos.php';
		$('#formPuestos #puestos_id').val(data.puestos_id);

		$.ajax({
			type:'POST',
			url:url,
			data:$('#formPuestos').serialize(),
			success: function(registro){
				var valores = eval(registro);
				$('#formPuestos').attr({ 'data-form': 'update' });
				$('#formPuestos').attr({ 'action': '<?php echo SERVERURL;?>ajax/modificarPuestosAjax.php' });
				$('#formPuestos')[0].reset();
				$('#reg_puestos').hide();
				$('#edi_puestos').show();
				$('#delete_puestos').hide();
				$('#formPuestos #puesto').val(valores[0]);

				if(valores[1] == 1){
					$('#formPuestos #puestos_activo').attr('checked', true);
				}else{
					$('#formPuestos #puestos_activo').attr('checked', false);
				}

				//HABILITAR OBJETOS
				$('#formPuestos #puesto').attr('readonly', false);
				$('#formPuestos #puestos_activo').attr('disabled', false);
				$('#formPuestos #estado_puestos').show();

				$('#formPuestos #proceso_puestos').val("Editar");
				$('#modal_registrar_puestos').modal({
					show:true,
					keyboard: false,
					backdrop:'static'
				});
			}
		});
	});
}

var eliminar_puestos_dataTable = function(tbody, table){
	$(tbody).off("click", "button.table_eliminar");
	$(tbody).on("click", "button.table_eliminar", function(){
		var data = table.row( $(this).parents("tr") ).data();

		var puestos_id = data.puestos_id;
        var nombrePuesto = data.nombre; 
        
        // Construir el mensaje de confirmación con HTML
        var mensajeHTML = `¿Desea eliminar permanentemente el puesto?<br><br>
                        <strong>Nombre:</strong> ${nombrePuesto}`;
        
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
                    url: '<?php echo SERVERURL;?>ajax/eliminarPuestosAjax.php',
                    data: {
                        puestos_id: puestos_id
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
//FIN ACCIONES FROMULARIO PUESTOS

$(document).ready(function(){
    $("#modal_registrar_puestos").on('shown.bs.modal', function(){
        $(this).find('#formPuestos #puesto').focus();
    });
});

$('#formPuestos #label_puestos_activo').html("Activo");
	
$('#formPuestos .switch').change(function(){    
    if($('input[name=puestos_activo]').is(':checked')){
        $('#formPuestos #label_puestos_activo').html("Activo");
        return true;
    }
    else{
        $('#formPuestos #label_puestos_activo').html("Inactivo");
        return false;
    }
});	

</script>