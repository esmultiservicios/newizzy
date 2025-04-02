<script>
$(document).ready(function() {
    listar_host();
	getClientes();
	getPlanes();	
});
//INICIO ACCIONES FROMULARIO HOST
var listar_host = function(){
	var table_host  = $("#dataTableHost").DataTable({
		"destroy":true,
		"ajax":{
			"method":"POST",
			"url":"<?php echo SERVERURL;?>core/llenarDataTableHost.php"
		},
		"columns":[
			{"data":"cliente"},
			{"data":"plan"},
			{"data":"server"},
			{"data":"db"},
			{"data":"user"},
			{"defaultContent":"<button class='table_editar btn btn-dark ocultar'><span class='fas fa-edit fa-lg'></span></button>"},
			{"defaultContent":"<button class='table_eliminar btn btn-dark ocultar'><span class='fa fa-trash fa-lg'></span></button>"}
		],
        "lengthMenu": lengthMenu,
		"stateSave": true,
		"bDestroy": true,
		"language": idioma_español,
		"dom": dom,
		"columnDefs": [
		  { width: "35.28%", targets: 0 },
		  { width: "13.28%", targets: 1 },
		  { width: "16.28%", targets: 2 },
		  { width: "16.28%", targets: 3 },
		  { width: "16.28%", targets: 4 },
		  { width: "1.28%", targets: 5 },
		  { width: "1.28%", targets: 6 }
		],
		"buttons":[
			{
				text:      '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
				titleAttr: 'Actualizar Host',
				className: 'table_actualizar btn btn-secondary ocultar',
				action: 	function(){
					listar_host();
				}
			},
			{
				text:      '<i class="fas fas fa-plus fa-lg"></i> Ingresar',
				titleAttr: 'Agregar Host',
				className: 'table_crear btn btn-primary ocultar',
				action: 	function(){
					modal_host();
				}
			},
			{
				extend:    'excelHtml5',
				text:      '<i class="fas fa-file-excel fa-lg"></i> Excel',
				titleAttr: 'Excel',
				title: 'Reporte de Host',
				messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
				className: 'table_reportes btn btn-success ocultar'
			},
			{
				extend:    'pdf',
				text:      '<i class="fas fa-file-pdf fa-lg"></i> PDF',
				titleAttr: 'PDF',
				title: 'Reporte de Host',
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
	table_host.search('').draw();
	$('#buscar').focus();

	editar_host_dataTable("#dataTableHost tbody", table_host);
	eliminar_host_dataTable("#dataTableHost tbody", table_host);
}

var editar_host_dataTable = function(tbody, table){
	$(tbody).off("click", "button.table_editar");
	$(tbody).on("click", "button.table_editar", function(){
		var data = table.row( $(this).parents("tr") ).data();
		var url = '<?php echo SERVERURL;?>core/editarHost.php';
		$('#formHost #host_id ').val(data.host_id );

		$.ajax({
			type:'POST',
			url:url,
			data:$('#formHost').serialize(),
			success: function(registro){
				var valores = eval(registro);
				$('#formHost').attr({ 'data-form': 'update' });
				$('#formHost').attr({ 'action': '<?php echo SERVERURL;?>ajax/modificarPuestosAjax.php' });
				$('#formHost')[0].reset();
				$('#reg_host').hide();
				$('#edi_host').show();
				$('#delete_host').hide();
				$('#formHost #server').val(valores[2]);
				$('#formHost #db').val(valores[3]);
				$('#formHost #user').val(valores[4]);
				$('#formHost #pass').val(valores[5]);
				$('#formHost #cliente').val(valores[0]);
				$('#formHost #planes').val(valores[1]);																				

				if(valores[6] == 1){
					$('#formHost #host_activo').attr('checked', true);
				}else{
					$('#formHost #host_activo').attr('checked', false);
				}

				//HABILITAR OBJETOS
				$('#formHost #server').attr('readonly', false);
				$('#formHost #db').attr('readonly', false);
				$('#formHost #user').attr('readonly', false);
				$('#formHost #pass').attr('readonly', false);
				$('#formHost #cliente').attr('disabled', false);
				$('#formHost #planes').attr('disabled', false);
				$('#formHost #host_activo').attr('disabled', false);
				$('#formHost #estado_host').show();
				$('#formHost #buscar_clientes_host').show();
	  			$('#formHost #buscar_planes_host').show();

				$('#formHost #proceso_host').val("Editar");
				$('#modal_registrar_host').modal({
					show:true,
					keyboard: false,
					backdrop:'static'
				});
			}
		});
	});
}

var eliminar_host_dataTable = function(tbody, table){
	$(tbody).off("click", "button.table_eliminar");
	$(tbody).on("click", "button.table_eliminar", function(){
		var data = table.row( $(this).parents("tr") ).data();
		var url = '<?php echo SERVERURL;?>core/editarHost.php';
		$('#formHost #host_id ').val(data.host_id );

		$.ajax({
			type:'POST',
			url:url,
			data:$('#formHost').serialize(),
			success: function(registro){
				var valores = eval(registro);
				$('#formHost').attr({ 'data-form': 'delete' });
				$('#formHost').attr({ 'action': '<?php echo SERVERURL;?>ajax/eliminarPuestosAjax.php' });
				$('#formHost')[0].reset();
				$('#reg_host').hide();
				$('#edi_host').hide();
				$('#delete_host').show();
				$('#formHost #server').val(valores[2]);
				$('#formHost #db').val(valores[3]);
				$('#formHost #user').val(valores[4]);
				$('#formHost #pass').val(valores[5]);
				$('#formHost #cliente').val(valores[0]);
				$('#formHost #planes').val(valores[1]);

				if(valores[6] == 1){
					$('#formHost #host_activo').attr('checked', true);
				}else{
					$('#formHost #host_activo').attr('checked', false);
				}

				//DESHABILITAR OBJETOS
				$('#formHost #server').attr('readonly', true);
				$('#formHost #db').attr('readonly', true);
				$('#formHost #user').attr('readonly', true);
				$('#formHost #pass').attr('readonly', true);
				$('#formHost #cliente').attr('disabled', true);
				$('#formHost #planes').attr('disabled', true);
				$('#formHost #buscar_clientes_host').hide();
				$('#formHost #buscar_planes_host').hide();

				$('#formHost #host_activo').attr('disabled', true);
				$('#formHost #estado_host').hide();

				$('#formHost #proceso_host').val("Eliminar");
				$('#modal_registrar_host').modal({
					show:true,
					keyboard: false,
					backdrop:'static'
				});
			}
		});
	});
}
//FIN ACCIONES FROMULARIO HOST

/*INICIO FORMULARIO HOST*/
function modal_host(){
	  $('#formHost').attr({ 'data-form': 'save' });
	  $('#formHost').attr({ 'action': '<?php echo SERVERURL;?>ajax/agregarPuestosAjax.php' });
	  $('#formHost')[0].reset();
	  $('#reg_host').show();
	  $('#edi_host').hide();
	  $('#delete_host').hide();

	  //HABILITAR OBJETOS
	  $('#formHost #server').attr('readonly', false);
	  $('#formHost #db').attr('readonly', false);
	  $('#formHost #user').attr('readonly', false);
	  $('#formHost #pass').attr('readonly', false);
	  $('#formHost #cliente').attr('disabled', false);
	  $('#formHost #planes').attr('disabled', false);
	  $('#formHost #host_activo').attr('disabled', false);
	  $('#formHost #estado_host').hide();
	  $('#formHost #buscar_clientes_host').show();
	  $('#formHost #buscar_planes_host').show();	  

	  $('#formHost #proceso_host').val("Registro");
	  $('#modal_registrar_host').modal({
		show:true,
		keyboard: false,
		backdrop:'static'
	  });
}
/*FIN FORMULARIO HOST*/

$(document).ready(function(){
    $("#modal_registrar_host").on('shown.bs.modal', function(){
        $(this).find('#formHost #server').focus();
    });
});

$('#formHost #label_host_activo').html("Activo");
	
$('#formHost .switch').change(function(){    
    if($('input[name=host_activo]').is(':checked')){
        $('#formHost #label_host_activo').html("Activo");
        return true;
    }
    else{
        $('#formHost #label_host_activo').html("Inactivo");
        return false;
    }
});	

function getClientes(){
    var url = '<?php echo SERVERURL;?>core/getClientes.php';

	$.ajax({
        type: "POST",
        url: url,
	    async: true,
        success: function(data){
		    $('#formHost #cliente').html("");
			$('#formHost #cliente').html(data);		
		}
     });
}

function getPlanes(){
    var url = '<?php echo SERVERURL;?>core/getPlanes.php';

	$.ajax({
        type: "POST",
        url: url,
	    async: true,
        success: function(data){
		    $('#formHost #planes').html("");
			$('#formHost #planes').html(data);		
		}
     });
}
</script>