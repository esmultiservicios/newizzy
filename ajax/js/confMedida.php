<script>
$(document).ready(function() {
    listar_medidas(); 
});
//INICIO MEDIDAS
var listar_medidas = function(){
	var table_medidas  = $("#dataTableConfMedidas").DataTable({
		"destroy":true,
		"ajax":{
			"method":"POST",
			"url":"<?php echo SERVERURL; ?>core/llenarDataTableMedida.php"
		},
		"columns":[
			{"data":"nombre"},
			{"data":"descripcion"},
			{"defaultContent":"<button class='table_editar btn btn-dark ocultar'><span class='fas fa-edit'></span></button>"},
			{"defaultContent":"<button class='table_eliminar btn btn-dark ocultar'><span class='fa fa-trash'></span></button>"}
		],
        "lengthMenu": lengthMenu,
		"stateSave": true,
		"bDestroy": true,
		"language": idioma_español,//esta se encuenta en el archivo main.js
		"dom": dom,
		"columnDefs": [
		  { width: "15%", targets: 0 },
		  { width: "75%", targets: 1 },
		  { width: "5%", targets: 2 },
		  { width: "5%", targets: 3 }		  		  
		],
		"buttons":[
			{
				text:      '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
				titleAttr: 'Actualizar Medidas',
				className: 'table_actualizar btn btn-secondary ocultar',
				action: 	function(){
					listar_medidas();
				}
			},
			{
				text:      '<i class="fas fas fa-plus fa-lg"></i> Ingresar',
				titleAttr: 'Agregar Medidas',
				className: 'table_crear btn btn-primary ocultar',
				action: 	function(){
					modalMedidas();
				}
			},
			{
				extend:    'excelHtml5',
				text:      '<i class="fas fa-file-excel fa-lg"></i> Excel',
				titleAttr: 'Excel',
				title: 'Reporte Medidas',
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
				title: 'Reporte Medidas',
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
		var url = '<?php echo SERVERURL;?>core/editarMedidas.php';
		$('#formMedidas #medida_id').val(data.medida_id);

		$.ajax({
			type:'POST',
			url:url,
			data:$('#formMedidas').serialize(),
			success: function(registro){
				var valores = eval(registro);
				$('#formMedidas').attr({ 'data-form': 'update' });
				$('#formMedidas').attr({ 'action': '<?php echo SERVERURL;?>ajax/eliminarMedidasAjax.php' });
				$('#formMedidas')[0].reset();
				$('#reg_medidas').hide();
				$('#edi_medidas').hide();
				$('#delete_medidas').show();
				$('#formMedidas #pro_medidas').val("Eliminar");
				$('#formMedidas #medidas_medidas').val(valores[0]);
				$('#formMedidas #descripcion_medidas').val(valores[1]);

				if(valores[2] == 1){
					$('#formMedidas #medidas_activo').attr('checked', true);
				}else{
					$('#formMedidas #medidas_activo').attr('checked', false);
				}

				//DESHABILITAR OBJETOS
				$('#formMedidas #ubicacion_ubicacion').attr('readonly', true);
				$('#formMedidas #descripcion_medidas').attr('readonly', true);
				$('#formMedidas #medidas_activo').attr('disabled', true);
				$('#formMedidas #estado_medidas').hide();

				$('#modal_medidas').modal({
					show:true,
					keyboard: false,
					backdrop:'static'
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