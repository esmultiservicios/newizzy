<script>
$(document).ready(function() {
    listar_diarios_configuracion();
	getCuentaDiarios(); 
});
//INICIO CONFIGURACION CUENTAS CONTABLES EN DIARIOS
var listar_diarios_configuracion = function(){
	var table_diarios_configuracion = $("#dataTableConfDiarios").DataTable({
		"destroy":true,
		"ajax":{
			"method":"POST",
			"url":"<?php echo SERVERURL; ?>core/llenarDataTableDiarios.php"
		},
		"columns":[
			{"data":"diario"},
			{"data":"cuenta"},											
			{"defaultContent":"<button class='table_editar btn btn-dark ocultar'><span class='fas fa-edit'></span></button>"}
		],
        "lengthMenu": lengthMenu10,
		"stateSave": true,
		"bDestroy": true,
		"language": idioma_español,//esta se encuenta en el archivo main.js
		"dom": dom,
		"columnDefs": [
		  { width: "47.33%", targets: 0 },
		  { width: "47.33%", targets: 1 },
		  { width: "33.33%", targets: 2 }		  		  		  		  
		],		
		"buttons":[
			{
				text:      '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
				titleAttr: 'Actualizar Diarios',
				className: 'table_actualizar btn btn-secondary ocultar',
				action: 	function(){
					listar_diarios_configuracion();
				}
			},
			{
				extend:    'excelHtml5',
				text:      '<i class="fas fa-file-excel fa-lg"></i> Excel',
				titleAttr: 'Excel',
				title: 'Reporte Diarios',
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
				title: 'Reporte Diarios',
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
	table_diarios_configuracion.search('').draw();
	$('#buscar').focus();

	edit_diarios_configuracion_dataTable("#dataTableConfDiarios tbody", table_diarios_configuracion);
}

var edit_diarios_configuracion_dataTable = function(tbody, table){
	$(tbody).off("click", "button.table_editar");
	$(tbody).on("click", "button.table_editar", function(){
		var data = table.row( $(this).parents("tr") ).data();
		var url = '<?php echo SERVERURL;?>core/editarDiarios.php';
		$('#formConfCuentasEntidades #diarios_id').val(data.diarios_id);

		$.ajax({
			type:'POST',
			url:url,
			data:$('#formConfCuentasEntidades').serialize(),
			success: function(registro){
				var valores = eval(registro);
				$('#formConfCuentasEntidades').attr({ 'data-form': 'update' });
				$('#formConfCuentasEntidades').attr({ 'action': '<?php echo SERVERURL;?>ajax/modificarDiariosAjax.php' });
				$('#formConfCuentasEntidades')[0].reset();
				$('#edi_confEntidades').show();
				$('#formConfCuentasEntidades #pro_ConfCuentasEntidades').val("Editar");
				$('#formConfCuentasEntidades #confEntidad').val(valores[1]);
				$('#formConfCuentasEntidades #confCuenta').val(valores[2]);
				$('#formConfCuentasEntidades #confCuenta').selectpicker('refresh');
				$('#formConfCuentasEntidades #buscar_confCuenta').hide();

				//DESHABILITAR OBJETOS
				$('#formConfCuentasEntidades #confEntidad').attr('disabled', true);

				$('#modalConfEntidades').modal({
					show:true,
					keyboard: false,
					backdrop:'static'
				});
			}
		});
	});
}

function getCuentaDiarios(){
    var url = '<?php echo SERVERURL;?>core/getCuenta.php';

	$.ajax({
        type: "POST",
        url: url,
	    async: true,
        success: function(data){
		    $('#formConfCuentasEntidades #confCuenta').html("");
			$('#formConfCuentasEntidades #confCuenta').html(data);
			$('#formConfCuentasEntidades #confCuenta').selectpicker('refresh');	
		}
     });
}

function getCuentaDiarios(){
    var url = '<?php echo SERVERURL;?>core/getCuenta.php';

	$.ajax({
        type: "POST",
        url: url,
	    async: true,
        success: function(data){
		    $('#formConfCuentasEntidades #confCuenta').html("");
			$('#formConfCuentasEntidades #confCuenta').html(data);
			$('#formConfCuentasEntidades #confCuenta').selectpicker('refresh');		
		}
     });
}
</script>