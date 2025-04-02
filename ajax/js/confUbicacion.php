<script>
$(document).ready(function() {
    listar_ubicacion();
	getEmpresaUbicacion();
});
//INICIO UBUCACION
var listar_ubicacion = function(){
	var table_ubicacion  = $("#dataTableConfUbicacion").DataTable({
		"destroy":true,
		"ajax":{
			"method":"POST",
			"url":"<?php echo SERVERURL; ?>core/llenarDataTableUbicacion.php"
		},
		"columns":[
			{"data":"ubicacion"},
			{"data":"empresa"},
			{"defaultContent":"<button class='table_editar btn btn-dark ocultar'><span class='fas fa-edit'></span></button>"},
			{"defaultContent":"<button class='table_eliminar btn btn-dark ocultar'><span class='fa fa-trash'></span></button>"}
		],
        "lengthMenu": lengthMenu,
		"stateSave": true,
		"bDestroy": true,
		"language": idioma_español,//esta se encuenta en el archivo main.js
		"dom": dom,
		"columnDefs": [
		  { width: "45%", targets: 0 },
		  { width: "45%", targets: 1 },
		  { width: "5%", targets: 2 },
		  { width: "5%", targets: 3 }
		],		
		"buttons":[
			{
				text:      '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
				titleAttr: 'Actualizar Ubicación',
				className: 'table_actualizar btn btn-secondary ocultar',
				action: 	function(){
					listar_ubicacion();
				}
			},
			{
				text:      '<i class="fas fas fa-plus fa-lg"></i> Ingresar',
				titleAttr: 'Agregar Ubicación',
				className: 'table_crear btn btn-primary ocultar',
				action: 	function(){
					modalUbicacion();
				}
			},
			{
				extend:    'excelHtml5',
				text:      '<i class="fas fa-file-excel fa-lg"></i> Excel',
				titleAttr: 'Excel',
				title: 'Reporte Ubicación',
				messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
				className: 'table_reportes btn btn-success ocultar',
				exportOptions: {
						columns: [0,1]
				},				
			},
			{
				extend:    'pdf',
				text:      '<i class="fas fa-file-pdf fa-lg"></i> PDF',
				titleAttr: 'PDF',
				title: 'Reporte Ubicación',
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
	table_ubicacion.search('').draw();
	$('#buscar').focus();

	edit_ubicacion_dataTable("#dataTableConfUbicacion tbody", table_ubicacion);
	delete_ubicacion_dataTable("#dataTableConfUbicacion tbody", table_ubicacion);
}

var edit_ubicacion_dataTable = function(tbody, table){
	$(tbody).off("click", "button.table_editar");
	$(tbody).on("click", "button.table_editar", function(){
		var data = table.row( $(this).parents("tr") ).data();
		var url = '<?php echo SERVERURL;?>core/editarUbicacion.php';
		$('#formUbicacion #ubicacion_id').val(data.ubicacion_id);

		$.ajax({
			type:'POST',
			url:url,
			data:$('#formUbicacion').serialize(),
			success: function(registro){
				var valores = eval(registro);
				$('#formUbicacion').attr({ 'data-form': 'update' });
				$('#formUbicacion').attr({ 'action': '<?php echo SERVERURL;?>ajax/modificarUbicacionAjax.php' });
				$('#formUbicacion')[0].reset();
				$('#reg_ubicacion').hide();
				$('#edi_ubicacion').show();
				$('#delete_ubicacion').hide();
				$('#formUbicacion #pro_ubicacion').val("Editar");
				$('#formUbicacion #empresa_ubicacion').val(valores[0]);
				$('#formUbicacion #empresa_ubicacion').selectpicker('refresh');
				$('#formUbicacion #ubicacion_ubicacion').val(valores[1]);

				if(valores[2] == 1){
					$('#formUbicacion #ubicacion_activo').attr('checked', true);
				}else{
					$('#formUbicacion #ubicacion_activo').attr('checked', false);
				}

				//HABILITAR OBJETOS
				$('#formUbicacion #ubicacion_ubicacion').attr('readonly', false);
				$('#formUbicacion #ubicacion_activo').attr('disabled', false);
				$('#formUbicacion #estado_ubicacion').show();

				//DESHABIITAR OBJETOS
				$('#formUbicacion #empresa_ubicacion').attr('disabled', true);
				
				$('#formUbicacion #buscar_empresa_ubicacion').hide();

				$('#modal_ubicacion').modal({
					show:true,
					keyboard: false,
					backdrop:'static'
				});
			}
		});
	});
}

var delete_ubicacion_dataTable = function(tbody, table){
	$(tbody).off("click", "button.table_eliminar");
	$(tbody).on("click", "button.table_eliminar", function(){
		var data = table.row( $(this).parents("tr") ).data();
		var url = '<?php echo SERVERURL;?>core/editarUbicacion.php';
		$('#formUbicacion #ubicacion_id').val(data.ubicacion_id);

		$.ajax({
			type:'POST',
			url:url,
			data:$('#formUbicacion').serialize(),
			success: function(registro){
				var valores = eval(registro);
				$('#formUbicacion').attr({ 'data-form': 'update' });
				$('#formUbicacion').attr({ 'action': '<?php echo SERVERURL;?>ajax/eliminarUbicacionAjax.php' });
				$('#formUbicacion')[0].reset();
				$('#reg_ubicacion').hide();
				$('#edi_ubicacion').hide();
				$('#delete_ubicacion').show();
				$('#formUbicacion #pro_ubicacion').val("Eliminar");
				$('#formUbicacion #empresa_ubicacion').val(valores[0]);
				$('#formUbicacion #empresa_ubicacion').selectpicker('refresh');
				$('#formUbicacion #ubicacion_ubicacion').val(valores[1]);

				if(valores[2] == 1){
					$('#formUbicacion #ubicacion_activo').attr('checked', true);
				}else{
					$('#formUbicacion #ubicacion_activo').attr('checked', false);
				}

				//DESHABIITAR OBJETOS
				$('#formUbicacion #ubicacion_ubicacion').attr('readonly', true);
				$('#formUbicacion #ubicacion_activo').attr('disabled', true);
				$('#formUbicacion #empresa_ubicacion').attr('disabled', true);
				$('#formUbicacion #estado_ubicacion').hide();
				$('#formUbicacion #buscar_empresa_ubicacion').hide();
				
				$('#modal_ubicacion').modal({
					show:true,
					keyboard: false,
					backdrop:'static'
				});
			}
		});
	});
}

//INICIO FORMULARIO UBICACION
function modalUbicacion(){
	$('#formUbicacion').attr({ 'data-form': 'save' });
	$('#formUbicacion').attr({ 'action': '<?php echo SERVERURL; ?>ajax/agregarUbicacionAjax.php' });
	$('#formUbicacion')[0].reset();	
	$('#formUbicacion #pro_ubicacion').val("Registro");
	$('#reg_ubicacion').show();
	$('#edi_ubicacion').hide();
	$('#delete_ubicacion').hide();

	//HABILITAR OBJETOS
	$('#formUbicacion #ubicacion_ubicacion').attr('readonly', false);
	$('#formUbicacion #ubicacion_activo').attr('disabled', false);
	$('#formUbicacion #empresa_ubicacion').attr('disabled', false);
	$('#formUbicacion #estado_ubicacion').hide();
	
	$('#formUbicacion #buscar_empresa_ubicacion').show();

	 $('#modal_ubicacion').modal({
		show:true,
		keyboard: false,
		backdrop:'static'
	});
}
//FIN FORMULARIO UBICACION

function getEmpresaUbicacion(){
    var url = '<?php echo SERVERURL;?>core/getEmpresa.php';

	$.ajax({
        type: "POST",
        url: url,
	    async: true,
        success: function(data){
		    $('#formUbicacion #empresa_ubicacion').html("");
			$('#formUbicacion #empresa_ubicacion').html(data);
			$('#formUbicacion #empresa_ubicacion').selectpicker('refresh');
		}
     });
}

$(document).ready(function(){
    $("#modal_ubicacion").on('shown.bs.modal', function(){
        $(this).find('#formUbicacion #ubicacion_ubicacion').focus();
    });
});

$('#formUbicacion #label_ubicacion_activo').html("Activo");
	
$('#formUbicacion .switch').change(function(){    
    if($('input[name=ubicacion_activo]').is(':checked')){
        $('#formUbicacion #label_ubicacion_activo').html("Activo");
        return true;
    }
    else{
        $('#formUbicacion #label_ubicacion_activo').html("Inactivo");
        return false;
    }
});	
</script>