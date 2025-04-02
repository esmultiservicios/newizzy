<script>
$(document).ready(function() {
    listar_banco_contabilidad();
});
//INICIO BANCOS
var listar_banco_contabilidad = function(){
	var table_banco_contabilidad = $("#dataTableConfBancos").DataTable({
		"destroy":true,
		"ajax":{
			"method":"POST",
			"url":"<?php echo SERVERURL; ?>core/llenarDataTableConfBanco.php"
		},
		"columns":[
			{"data":"nombre"},						
			{"defaultContent":"<button class='table_editar btn btn-dark ocultar'><span class='fas fa-edit'></span></button>"},
			{"defaultContent":"<button class='table_eliminar btn btn-dark ocultar'><span class='fa fa-trash'></span></button>"}
		],
        "lengthMenu": lengthMenu,
		"stateSave": true,
		"bDestroy": true,
		"language": idioma_español,//esta se encuenta en el archivo main.js
		"dom": dom,
		"columnDefs": [
		  { width: "89.33%", targets: 0 },
		  { width: "5.33%", targets: 1 },
		  { width: "5.33%", targets: 2 }		  
		],		
		"buttons":[
			{
				text:      '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
				titleAttr: 'Actualizar Bancos',
				className: 'table_actualizar btn btn-secondary ocultar',
				action: 	function(){
					listar_banco_contabilidad();
				}
			},
			{
				text:      '<i class="fas fa-university fa-lg"></i> Ingresar',
				titleAttr: 'Agregar Bancos',
				className: 'table_crear btn btn-primary ocultar',
				action: 	function(){
					modalBancos();
				}
			},
			{
				extend:    'excelHtml5',
				text:      '<i class="fas fa-file-excel fa-lg"></i> Excel',
				titleAttr: 'Excel',
				title: 'Reporte Bancos',
				messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
				className: 'table_reportes btn btn-success ocultar',
				exportOptions: {
						columns: [0]
				},					
			},
			{
				extend:    'pdf',
				text:      '<i class="fas fa-file-pdf fa-lg"></i> PDF',
				titleAttr: 'PDF',
				title: 'Reporte Bancos',
				messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
				className: 'table_reportes btn btn-danger ocultar',
				exportOptions: {
						columns: [0]
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
	table_banco_contabilidad.search('').draw();
	$('#buscar').focus();

	edit_banco_contabilidad_dataTable("#dataTableConfBancos tbody", table_banco_contabilidad);
	delete_banco_contabilidad_dataTable("#dataTableConfBancos tbody", table_banco_contabilidad);
}

var edit_banco_contabilidad_dataTable = function(tbody, table){
	$(tbody).off("click", "button.table_editar");
	$(tbody).on("click", "button.table_editar", function(){
		var data = table.row( $(this).parents("tr") ).data();
		var url = '<?php echo SERVERURL;?>core/editarBancos.php';
		$('#formBancos #banco_id').val(data.banco_id);

		$.ajax({
			type:'POST',
			url:url,
			data:$('#formBancos').serialize(),
			success: function(registro){
				var valores = eval(registro);
				$('#formBancos').attr({ 'data-form': 'update' });
				$('#formBancos').attr({ 'action': '<?php echo SERVERURL;?>ajax/modificarBankAjax.php' });
				$('#formBancos')[0].reset();
				$('#reg_banco').hide();
				$('#edi_banco').show();
				$('#delete_banco').hide();
				$('#formBancos #pro_bancos').val("Editar");
				$('#formBancos #confbanco').val(valores[0]);

				if(valores[1] == 1){
					$('#formBancos #confbanco_activo').attr('checked', true);
				}else{
					$('#formBancos #confbanco_activo').attr('checked', false);
				}

				//HABILITAR OBJETOS
				$('#formBancos #confbanco').attr('disabled', false);
				$('#formBancos #confbanco_activo').attr('disabled', false);
				$('#formBancos #estado_bancos').show();

				$('#modalConfBancos').modal({
					show:true,
					keyboard: false,
					backdrop:'static'
				});
			}
		});
	});
}

var delete_banco_contabilidad_dataTable = function(tbody, table){
	$(tbody).off("click", "button.table_eliminar");
	$(tbody).on("click", "button.table_eliminar", function(){
		var data = table.row( $(this).parents("tr") ).data();
		var url = '<?php echo SERVERURL;?>core/editarBancos.php';
		$('#formBancos #banco_id').val(data.banco_id);

		$.ajax({
			type:'POST',
			url:url,
			data:$('#formBancos').serialize(),
			success: function(registro){
				var valores = eval(registro);
				$('#formBancos').attr({ 'data-form': 'delete' });
				$('#formBancos').attr({ 'action': '<?php echo SERVERURL;?>ajax/eliminarBankAjax.php' });
				$('#formBancos')[0].reset();
				$('#reg_banco').hide();
				$('#edi_banco').hide();
				$('#delete_banco').show();
				$('#formBancos #pro_bancos').val("Eliminar");
				$('#formBancos #confbanco').val(valores[0]);

				if(valores[1] == 1){
					$('#formBancos #confbanco_activo').attr('checked', true);
				}else{
					$('#formBancos #confbanco_activo').attr('checked', false);
				}

				//DESHABILITAR OBJETOS
				$('#formBancos #confbanco').attr('disabled', true);
				$('#formBancos #confbanco_activo').attr('disabled', true);
				$('#formBancos #estado_bancos').hide();

				$('#modalConfBancos').modal({
					show:true,
					keyboard: false,
					backdrop:'static'
				});
			}
		});
	});
}
//FIN BANCOS	

//INICIO FORMULARIO BANCOS
function modalBancos(){
	$('#formBancos').attr({ 'data-form': 'save' });
	$('#formBancos').attr({ 'action': '<?php echo SERVERURL; ?>ajax/addBankAjax.php' });
	$('#formBancos')[0].reset();
	$('#formBancos #pro_bancos').val("Registro");
	$('#reg_banco').show();
	$('#edi_banco').hide();
	$('#delete_banco').hide();

	//HABILITAR OBJETOS
	$('#formBancos #confbanco').attr('readonly', false);
	$('#formBancos #confbanco_activo').attr('disabled', false);
	$('#formBancos #estado_bancos').hide();

	$('#modalConfBancos').modal({
		show:true,
		keyboard: false,
		backdrop:'static'
	});
}
//FIN FORMULARIO BANCOS

$(document).ready(function(){
    $("#modalConfBancos").on('shown.bs.modal', function(){
        $(this).find('#formBancos #confbanco').focus();
    });
});

$('#formBancos #label_confbanco_activo').html("Activo");
	
$('#formBancos .switch').change(function(){    
    if($('input[name=confbanco_activo]').is(':checked')){
        $('#formBancos #label_confbanco_activo').html("Activo");
        return true;
    }
    else{
        $('#formBancos #label_confbanco_activo').html("Inactivo");
        return false;
    }
});	
</script>