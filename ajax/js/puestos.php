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
			{"defaultContent":"<button class='table_editar btn btn-dark ocultar'><span class='fas fa-edit fa-lg'></span></button>"},
			{"defaultContent":"<button class='table_eliminar btn btn-dark ocultar'><span class='fa fa-trash fa-lg'></span></button>"}
		],
        "lengthMenu": lengthMenu,
		"stateSave": true,
		"bDestroy": true,
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
		var url = '<?php echo SERVERURL;?>core/editarPuestos.php';
		$('#formPuestos #puestos_id').val(data.puestos_id);

		$.ajax({
			type:'POST',
			url:url,
			data:$('#formPuestos').serialize(),
			success: function(registro){
				var valores = eval(registro);
				$('#formPuestos').attr({ 'data-form': 'delete' });
				$('#formPuestos').attr({ 'action': '<?php echo SERVERURL;?>ajax/eliminarPuestosAjax.php' });
				$('#formPuestos')[0].reset();
				$('#reg_puestos').hide();
				$('#edi_puestos').hide();
				$('#delete_puestos').show();
				$('#formPuestos #puesto').val(valores[0]);

				if(valores[1] == 1){
					$('#formPuestos #puestos_activo').attr('checked', true);
				}else{
					$('#formPuestos #puestos_activo').attr('checked', false);
				}

				//DESHABILITAR OBJETOS
				$('#formPuestos #puesto').attr('readonly', true);
				$('#formPuestos #puestos_activo').attr('disabled', true);
				$('#formPuestos #estado_puestos').hide();

				$('#formPuestos #proceso_puestos').val("Eliminar");
				$('#modal_registrar_puestos').modal({
					show:true,
					keyboard: false,
					backdrop:'static'
				});
			}
		});
	});
}
//FIN ACCIONES FROMULARIO PUESTOS

/*INICIO FORMULARIO PUESTO DE COLABORADORES*/
function modal_puestos(){
	  $('#formPuestos').attr({ 'data-form': 'save' });
	  $('#formPuestos').attr({ 'action': '<?php echo SERVERURL;?>ajax/agregarPuestosAjax.php' });
	  $('#formPuestos')[0].reset();
	  $('#reg_puestos').show();
	  $('#edi_puestos').hide();
	  $('#delete_puestos').hide();

	  //HABILITAR OBJETOS
	  $('#formPuestos #puesto').attr('readonly', false);
	  $('#formPuestos #puestos_activo').attr('disabled', false);
	  $('#formPuestos #estado_puestos').hide();

	  $('#formPuestos #proceso_puestos').val("Registro");
	  $('#modal_registrar_puestos').modal({
		show:true,
		keyboard: false,
		backdrop:'static'
	  });
}
/*FIN FORMULARIO PUESTO DE COLABORADORES*/

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