<script>
$(document).ready(function() {
    listar_host();
	getProductos();
	getClientes();
});
//INICIO ACCIONES FROMULARIO HOST
var listar_host = function(){
	var table_host  = $("#dataTableHost").DataTable({
		"destroy":true,
		"ajax":{
			"method":"POST",
			"url":"<?php echo SERVERURL;?>core/llenarDataTableHostProductos.php"
		},
		"columns":[
			{"data":"cliente"},
			{"data":"plan"},
			{"data":"producto"},
			{"data":"cantidad"},
			{"defaultContent":"<button class='table_editar btn btn-dark ocultar'><span class='fas fa-edit fa-lg'></span></button>"},
			{"defaultContent":"<button class='table_eliminar btn btn-dark ocultar'><span class='fa fa-trash fa-lg'></span></button>"}
		],
        "lengthMenu": lengthMenu,
		"stateSave": true,
		"bDestroy": true,
		"language": idioma_español,
		"dom": dom,
		"columnDefs": [
		  { width: "36.66%", targets: 0 },
		  { width: "24.66%", targets: 1 },
		  { width: "24.66%", targets: 2 },
		  { width: "16.66%", targets: 3 },
		  { width: "2.66%", targets: 4 },
		  { width: "2.66%", targets: 5 }
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
		var url = '<?php echo SERVERURL;?>core/editarHostProductos.php';
		$('#formHostProductos #host_detalles_id ').val(data.host_detalles_id );

		$.ajax({
			type:'POST',
			url:url,
			data:$('#formHostProductos').serialize(),
			success: function(registro){
				var valores = eval(registro);
				$('#formHostProductos').attr({ 'data-form': 'update' });
				$('#formHostProductos').attr({ 'action': '<?php echo SERVERURL;?>ajax/modificarPuestosAjax.php' });
				$('#formHostProductos')[0].reset();
				$('#reg_hostProductos').hide();
				$('#edi_hostProductos').show();
				$('#delete_hostProductos').hide();
				$('#formHostProductos #cliente').val(valores[0]);
				$('#formHostProductos #plan').val(valores[1]);
				$('#formHostProductos #productos').val(valores[2]);
				$('#formHostProductos #cantidad').val(valores[3]);;

				if(valores[6] == 1){
					$('#formHostProductos #host_activo').attr('checked', true);
				}else{
					$('#formHostProductos #host_activo').attr('checked', false);
				}

				//HABILITAR OBJETOS
				$('#formHostProductos #cantidad').attr('readonly', false);
				$('#formHostProductos #pass').attr('readonly', false);

				$('#formHostProductos #host_activo').attr('disabled', false);
				$('#formHostProductos #estado_hostProductos').show();

				//DESHABILITAR OBJETOS
				$('#formHostProductos #productos').attr('disabled', true);
				$('#formHostProductos #cliente').attr('disabled', true);
				$('#formHostProductos #plan').attr('readonly', true);	
				$('#formHostProductos #buscar_productos_host').hide();
				$('#formHostProductos #buscar_clientes_productos_host').hide();

				$('#formHostProductos #proceso_hostProductos').val("Editar");
				$('#modal_registrar_host_productos').modal({
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
		var url = '<?php echo SERVERURL;?>core/editarHostProductos.php';
		$('#formHostProductos #host_detalles_id ').val(data.host_detalles_id );

		$.ajax({
			type:'POST',
			url:url,
			data:$('#formHostProductos').serialize(),
			success: function(registro){
				var valores = eval(registro);
				$('#formHostProductos').attr({ 'data-form': 'delete' });
				$('#formHostProductos').attr({ 'action': '<?php echo SERVERURL;?>ajax/eliminarPuestosAjax.php' });
				$('#formHostProductos')[0].reset();
				$('#reg_hostProductos').hide();
				$('#edi_hostProductos').hide();
				$('#delete_hostProductos').show();
				$('#formHostProductos #cliente').val(valores[0]);
				$('#formHostProductos #plan').val(valores[1]);
				$('#formHostProductos #productos').val(valores[2]);
				$('#formHostProductos #cantidad').val(valores[3]);;

				if(valores[6] == 1){
					$('#formHostProductos #host_activo').attr('checked', true);
				}else{
					$('#formHostProductos #host_activo').attr('checked', false);
				}

				//DESHABILITAR OBJETOS
				$('#formHostProductos #cliente').attr('disabled', true);
				$('#formHostProductos #plan').attr('readonly', true);
				$('#formHostProductos #productos').attr('disabled', true);
				$('#formHostProductos #cantidad').attr('readonly', true);
				$('#formHostProductos #pass').attr('readonly', true);
				$('#formHostProductos #buscar_productos_host').hide();

				$('#formHostProductos #host_activo').attr('disabled', true);
				$('#formHostProductos #buscar_productos_host').hide();
				$('#formHostProductos #buscar_clientes_productos_host').hide();

				$('#formHostProductos #proceso_hostProductos').val("Eliminar");
				$('#modal_registrar_host_productos').modal({
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
	  $('#formHostProductos').attr({ 'data-form': 'save' });
	  $('#formHostProductos').attr({ 'action': '<?php echo SERVERURL;?>ajax/agregarPuestosAjax.php' });
	  $('#formHostProductos')[0].reset();
	  $('#reg_hostProductos').show();
	  $('#edi_hostProductos').hide();
	  $('#delete_hostProductos').hide();

	  //HABILITAR OBJETOS
	  $('#formHostProductos #cliente').attr('disabled', false);
	  $('#formHostProductos #productos').attr('disabled', false);
	  $('#formHostProductos #cantidad').attr('readonly', false);
	  $('#formHostProductos #pass').attr('readonly', false);

	  $('#formHostProductos #host_activo').attr('disabled', false);
	  $('#formHostProductos #estado_hostProductos').show();
	  $('#formHostProductos #buscar_productos_host').show();	  
	  $('#formHostProductos #buscar_clientes_productos_host').show();

	  //DESHABILITAR OBJETOS
	  $('#formHostProductos #plan').attr('readonly', true);

	  $('#formHostProductos #proceso_hostProductos').val("Registro");
	  $('#modal_registrar_host_productos').modal({
		show:true,
		keyboard: false,
		backdrop:'static'
	  });
}
/*FIN FORMULARIO HOST*/

$(document).ready(function(){
    $("#modal_registrar_host_productos").on('shown.bs.modal', function(){
        $(this).find('#formHostProductos #cliente').focus();
    });
});

$('#formHostProductos #label_hostProductos_activo').html("Activo");
	
$('#formHostProductos .switch').change(function(){    
    if($('input[name=hostProductos_activo]').is(':checked')){
        $('#formHostProductos #label_hostProductos_activo').html("Activo");
        return true;
    }
    else{
        $('#formHostProductos #label_hostProductos_activo').html("Inactivo");
        return false;
    }
});	

function getProductos(){
    var url = '<?php echo SERVERURL;?>core/getProductos.php';

	$.ajax({
        type: "POST",
        url: url,
	    async: true,
        success: function(data){
		    $('#formHostProductos #productos').html("");
			$('#formHostProductos #productos').html(data);		
		}
     });
}

function getClientes(){
    var url = '<?php echo SERVERURL;?>core/getClientesHostProductos.php';

	$.ajax({
        type: "POST",
        url: url,
	    async: true,
        success: function(data){
		    $('#formHostProductos #cliente').html("");
			$('#formHostProductos #cliente').html(data);		
		}
     });
}

$('#formHostProductos #cliente').on('change', function(){
		var url = '<?php echo SERVERURL;?>core/getPlanesHostProductos.php';

		var clientes_id = $('#formHostProductos #cliente').val();

	    $.ajax({
		   type:'POST',
		   url:url,
		   data:'clientes_id='+clientes_id,
		   success:function(data){
			  $('#formHostProductos #plan').val(data);
		  }
	  });
	  return false;
});
</script>