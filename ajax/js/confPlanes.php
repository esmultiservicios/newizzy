<script>
$(document).ready(function() {
    listar_planes();
});
//INICIO ACCIONES FROMULARIO PLANES
var listar_planes = function(){
	var table_planes  = $("#dataTablePlanes").DataTable({
		"destroy":true,
		"ajax":{
			"method":"POST",
			"url":"<?php echo SERVERURL;?>core/llenarDataTablePlanes.php"
		},
		"columns":[
			{"data":"planes_id"},
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
				titleAttr: 'Actualizar Planes',
				className: 'table_actualizar btn btn-secondary ocultar',
				action: 	function(){
					listar_planes();
				}
			},
			{
				text:      '<i class="fas fas fa-plus fa-lg"></i> Ingresar',
				titleAttr: 'Agregar Planes',
				className: 'table_crear btn btn-primary ocultar',
				action: 	function(){
					modal_planes();
				}
			},
			{
				extend:    'excelHtml5',
				text:      '<i class="fas fa-file-excel fa-lg"></i> Excel',
				titleAttr: 'Excel',
				title: 'Reporte de Planes',
				messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
				className: 'table_reportes btn btn-success ocultar'
			},
			{
				extend:    'pdf',
				text:      '<i class="fas fa-file-pdf fa-lg"></i> PDF',
				titleAttr: 'PDF',
				title: 'Reporte de Planes',
				messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
				className: 'table_reportes btn btn-danger ocultar',
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
	table_planes.search('').draw();
	$('#buscar').focus();

	editar_planes_dataTable("#dataTablePlanes tbody", table_planes);
	eliminar_planes_dataTable("#dataTablePlanes tbody", table_planes);
}

var editar_planes_dataTable = function(tbody, table){
	$(tbody).off("click", "button.table_editar");
	$(tbody).on("click", "button.table_editar", function(){
		var data = table.row( $(this).parents("tr") ).data();
		var url = '<?php echo SERVERURL;?>core/editarPlanes.php';
		$('#formPlanes #planes_id').val(data.planes_id);

		$.ajax({
			type:'POST',
			url:url,
			data:$('#formPlanes').serialize(),
			success: function(registro){
				var valores = eval(registro);
				$('#formPlanes').attr({ 'data-form': 'update' });
				$('#formPlanes').attr({ 'action': '<?php echo SERVERURL;?>ajax/modificarPuestosAjax.php' });
				$('#formPlanes')[0].reset();
				$('#reg_planes').hide();
				$('#edi_planes').show();
				$('#delete_planes').hide();
				$('#formPlanes #plan').val(valores[0]);

				if(valores[1] == 1){
					$('#formPlanes #puestplan_activoos_activo').attr('checked', true);
				}else{
					$('#formPlanes #plan_activo').attr('checked', false);
				}

				//HABILITAR OBJETOS
				$('#formPlanes #plan').attr('readonly', false);
				$('#formPlanes #plan_activo').attr('disabled', false);
				$('#formPlanes #estado_planes').show();

				$('#formPlanes #proceso_planes').val("Editar");
				$('#modal_registrar_planes').modal({
					show:true,
					keyboard: false,
					backdrop:'static'
				});
			}
		});
	});
}

var eliminar_planes_dataTable = function(tbody, table){
	$(tbody).off("click", "button.table_eliminar");
	$(tbody).on("click", "button.table_eliminar", function(){
		var data = table.row( $(this).parents("tr") ).data();
		var url = '<?php echo SERVERURL;?>core/editarPlanes.php';
		$('#formPlanes #planes_id').val(data.planes_id);

		$.ajax({
			type:'POST',
			url:url,
			data:$('#formPlanes').serialize(),
			success: function(registro){
				var valores = eval(registro);
				$('#formPlanes').attr({ 'data-form': 'delete' });
				$('#formPlanes').attr({ 'action': '<?php echo SERVERURL;?>ajax/eliminarPuestosAjax.php' });
				$('#formPlanes')[0].reset();
				$('#reg_planes').hide();
				$('#edi_planes').hide();
				$('#delete_planes').show();
				$('#formPlanes #plan').val(valores[0]);

				if(valores[1] == 1){
					$('#formPlanes #plan_activo').attr('checked', true);
				}else{
					$('#formPlanes #plan_activo').attr('checked', false);
				}

				//DESHABILITAR OBJETOS
				$('#formPlanes #plan').attr('readonly', true);
				$('#formPlanes #plan_activo').attr('disabled', true);
				$('#formPlanes #estado_planes').hide();

				$('#formPlanes #proceso_planes').val("Eliminar");
				$('#modal_registrar_planes').modal({
					show:true,
					keyboard: false,
					backdrop:'static'
				});
			}
		});
	});
}
//FIN ACCIONES FROMULARIO PLANES

/*INICIO FORMULARIO PLANES*/
function modal_planes(){
	  $('#formPlanes').attr({ 'data-form': 'save' });
	  $('#formPlanes').attr({ 'action': '<?php echo SERVERURL;?>ajax/agregarPuestosAjax.php' });
	  $('#formPlanes')[0].reset();
	  $('#reg_planes').show();
	  $('#edi_planes').hide();
	  $('#delete_planes').hide();

	  //HABILITAR OBJETOS
	  $('#formPlanes #plan').attr('readonly', false);
	  $('#formPlanes #puestos_activo').attr('disabled', false);
	  $('#formPlanes #estado_planes').hide();

	  $('#formPlanes #proceso_planes').val("Registro");
	  $('#modal_registrar_planes').modal({
		show:true,
		keyboard: false,
		backdrop:'static'
	  });
}
/*FIN FORMULARIO PLANES*/

$(document).ready(function(){
    $("#modal_registrar_planes").on('shown.bs.modal', function(){
        $(this).find('#formPlanes #plan').focus();
    });
});

$('#formPlanes #label_plan_activo').html("Activo");
	
$('#formPlanes .switch').change(function(){    
    if($('input[name=plan_activo]').is(':checked')){
        $('#formPlanes #label_plan_activo').html("Activo");
        return true;
    }
    else{
        $('#formPlanes #label_plan_activo').html("Inactivo");
        return false;
    }
});	
</script>