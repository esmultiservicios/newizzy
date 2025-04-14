<script>
$(document).ready(function() {
    listar_tipo_usuario();    
});

//INICIO ACCIONES FROMULARIO TIPO USUARIO
var listar_tipo_usuario = function(){
	var table_tipo_usuario  = $("#dataTableTipoUser").DataTable({
		"destroy":true,
		"ajax":{
			"method":"POST",
			"url":"<?php echo SERVERURL;?>core/llenarDataTableTipoUsuario.php"
		},
		"columns":[
			{"data":"nombre"},
			{"defaultContent":"<button class='table_permisos btn btn-dark'><span class='fas fa-users-cog fa-lg'></span></button>"},
			{"defaultContent":"<button class='table_editar1 table_editar btn btn-dark'><span class='fas fa-edit fa-lg'></span></button>"},
			{"defaultContent":"<button class='table_eliminar1 table_eliminar btn btn-dark'><span class='fa fa-trash fa-lg'></span></button>"}
		],
        "lengthMenu": lengthMenu,
		"stateSave": true,
		"bDestroy": true,
		"language": idioma_español,
		"dom": dom,
		"columnDefs": [
		  { width: "85%", targets: 0 },
		  { width: "5%", targets: 1 },
		  { width: "5%", targets: 2 },
		  { width: "5%", targets: 3 }
		],
		"buttons":[
			{
				text:      '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
				titleAttr: 'Actualizar Tipos de Usuario',
				className: 'btn btn-secondary',
				action: 	function(){
					listar_tipo_usuario();
				}
			},
			{
				text:      '<i class="fas fas fa-plus fa-lg"></i> Ingresar',
				titleAttr: 'Agregar Tipos de Usuario',
				className: 'btn btn-primary',
				action: 	function(){
					modal_tipo_usuarios();
				}
			},
			{
				extend:    'excelHtml5',
				text:      '<i class="fas fa-file-excel fa-lg"></i> Excel',
				titleAttr: 'Excel',
				title: 'Reporte Tipos de Usuario',
				messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
				className: 'btn btn-success',
				exportOptions: {
						columns: [0]
				},				
			},
			{
				extend:    'pdf',
				text:      '<i class="fas fa-file-pdf fa-lg"></i> PDF',
				titleAttr: 'PDF',
				title: 'Reporte Tipos de Usuario',
				messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
				className: 'btn btn-danger',
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
	table_tipo_usuario.search('').draw();
	$('#buscar').focus();

	permisos_tipo_usuario_dataTable("#dataTableTipoUser tbody", table_tipo_usuario);
	editar_tipo_usuario_dataTable("#dataTableTipoUser tbody", table_tipo_usuario);
	eliminar_tipo_usuario_dataTable("#dataTableTipoUser tbody", table_tipo_usuario);
}

var permisos_tipo_usuario_dataTable = function(tbody, table){
	$(tbody).off("click", "button.table_permisos");
	$(tbody).on("click", "button.table_permisos", function(){
		var data = table.row( $(this).parents("tr") ).data();
		getPermisosControl(data.tipo_user_id, data.nombre);
	});
}

function getPermisosControl(tipo_user_id, nombre){
	var url = '<?php echo SERVERURL;?>core/getTipoUsuarioAccesos.php';

	$('#formPermisos #permisos_tipo_user_id').val(tipo_user_id);
				
	$.ajax({
		type:'POST',
		url:url,
		data:$('#formPermisos').serialize(),
		success: function(registro){
			valoresTipoAcceso = JSON.parse(registro);

			$('#formPermisos').attr({ 'data-form': 'save' });
			$('#formPermisos').attr({ 'action': '<?php echo SERVERURL;?>ajax/addPermisosAccesosAjax.php' });
			$('#formPermisos')[0].reset();
			$('#formPermisos #permisos_tipo_user_id').val(tipo_user_id);
			$('#formPermisos #pro_permisos').val("Asignar Permisos: " + nombre);
			$('#formPermisos #permisos_nombre').val(nombre);
			
			$('#formPermisos #opcion_guardar').attr('checked', false);
			$('#formPermisos #opcion_editar').attr('checked', false);
			$('#formPermisos #opcion_eliminar').attr('checked', false);
			$('#formPermisos #opcion_consultar').attr('checked', false);
			$('#formPermisos #opcion_imprimir').attr('checked', false);				
			
			for(var i=0; i < valoresTipoAcceso.length; i++){
				if(valoresTipoAcceso[i].estado == 1){
					$('#formPermisos #opcion_' + valoresTipoAcceso[i].tipo_permiso).attr('checked', true);
				}else{
					$('#formPermisos #opcion_' + valoresTipoAcceso[i].tipo_permiso).attr('checked', false);
				}
			}
			
			$('#modal_permisos').modal({
				show:true,
				keyboard: false,
				backdrop:'static'
			});
		}
	});
}

var editar_tipo_usuario_dataTable = function(tbody, table){
	$(tbody).off("click", "button.table_editar1");
	$(tbody).on("click", "button.table_editar1", function(){
		var data = table.row( $(this).parents("tr") ).data();
		var url = '<?php echo SERVERURL;?>core/editarTipoUsuario.php';
		$('#formTipoUsuario #tipo_user_id').val(data.tipo_user_id);

		$.ajax({
			type:'POST',
			url:url,
			data:$('#formTipoUsuario').serialize(),
			success: function(registro){
				var valores = eval(registro);
				$('#formTipoUsuario').attr({ 'data-form': 'update' });
				$('#formTipoUsuario').attr({ 'action': '<?php echo SERVERURL;?>ajax/modificarTipoUsuarioAjax.php' });
				$('#formTipoUsuario')[0].reset();
				$('#reg_tipo_usuario').hide();
				$('#edi_tipo_usuario').show();
				$('#delete_tipo_usuario').hide();
				$('#formTipoUsuario #tipo_usuario_nombre').val(valores[0]);

				if(valores[1] == 1){
					$('#formTipoUsuario #tipo_usuario_activo').attr('checked', true);
				}else{
					$('#formTipoUsuario #tipo_usuario_activo').attr('checked', false);
				}

				//HABILITAR OBJETOS
				$('#formTipoUsuario #tipo_usuario_nombre').attr('readonly', false);
				$('#formTipoUsuario #tipo_usuario_activo').attr('disabled', false);
				$('#formTipoUsuario #estado_tipo_usuario').show();

				$('#formTipoUsuario #proceso_tipo_usuario').val("Editar");
				$('#modal_registrar_tipoUsuario').modal({
					show:true,
					keyboard: false,
					backdrop:'static'
				});
			}
		});
	});
}

var eliminar_tipo_usuario_dataTable = function(tbody, table){
	$(tbody).off("click", "button.table_eliminar1");
	$(tbody).on("click", "button.table_eliminar1", function(){
		var data = table.row( $(this).parents("tr") ).data();
		var url = '<?php echo SERVERURL;?>core/editarTipoUsuario.php';
		$('#formTipoUsuario #tipo_user_id').val(data.tipo_user_id);

		$.ajax({
			type:'POST',
			url:url,
			data:$('#formTipoUsuario').serialize(),
			success: function(registro){
				var valores = eval(registro);
				$('#formTipoUsuario').attr({ 'data-form': 'delete' });
				$('#formTipoUsuario').attr({ 'action': '<?php echo SERVERURL;?>ajax/eliminarTipoUsuarioAjax.php' });
				$('#formTipoUsuario')[0].reset();
				$('#reg_tipo_usuario').hide();
				$('#edi_tipo_usuario').hide();
				$('#delete_tipo_usuario').show();
				$('#formTipoUsuario #tipo_usuario_nombre').val(valores[0]);

				if(valores[1] == 1){
					$('#formTipoUsuario #tipo_usuario_activo').attr('checked', true);
				}else{
					$('#formTipoUsuario #tipo_usuario_activo').attr('checked', false);
				}

				//DESHABIITAR OBJETOS
				$('#formTipoUsuario #tipo_usuario_nombre').attr('readonly', true);
				$('#formTipoUsuario #tipo_usuario_activo').attr('disabled', true);
				$('#formTipoUsuario #estado_tipo_usuario').hide();

				$('#formTipoUsuario #proceso_tipo_usuario').val("Eliminar");
				$('#modal_registrar_tipoUsuario').modal({
					show:true,
					keyboard: false,
					backdrop:'static'
				});
			}
		});
	});
}
//FIN ACCIONES FROMULARIO TIPO USUARIO

/*INICIO FORMULARIO TIPO USUARIO*/
function modal_tipo_usuarios(){
	$('#formTipoUsuario').attr({ 'data-form': 'save' });
	$('#formTipoUsuario').attr({ 'action': '<?php echo SERVERURL;?>ajax/agregarTipoUsuarioAjax.php' });
	$('#formTipoUsuario')[0].reset();
	$('#reg_tipo_usuario').show();
	$('#edi_tipo_usuario').hide();
	$('#delete_tipo_usuario').hide();

	//HABILITAR OBJETOS
	$('#formTipoUsuario #tipo_usuario_nombre').attr('readonly', false);
	$('#formTipoUsuario #tipo_usuario_activo').attr('disabled', false);
	$('#formTipoUsuario #estado_tipo_usuario').hide();

	$('#formTipoUsuario #proceso_tipo_usuario').val("Registro");
	$('#modal_registrar_tipoUsuario').modal({
		show:true,
		keyboard: false,
		backdrop:'static'
	});
}
/*FIN FORMULARIO TIPO USAURIO*/

$(document).ready(function(){
    $("#modal_registrar_tipoUsuario").on('shown.bs.modal', function(){
        $(this).find('#formTipoUsuario #tipo_usuario_nombre').focus();
    });
});

$('#formTipoUsuario #label_tipo_usuario_activo').html("Activo");
	
$('#formTipoUsuario .switch').change(function(){    
    if($('input[name=tipo_usuario_activo]').is(':checked')){
        $('#formTipoUsuario #label_tipo_usuario_activo').html("Activo");
        return true;
    }
    else{
        $('#formTipoUsuario #label_tipo_usuario_activo').html("Inactivo");
        return false;
    }
});	

//INICIO PERMISOS
$('#formTipoUsuario #label_tipo_usuario_activo').html("Activo");
	
$('#formTipoUsuario .switch').change(function(){    
    if($('input[name=tipo_usuario_activo]').is(':checked')){
        $('#formTipoUsuario #label_tipo_usuario_activo').html("Activo");
        return true;
    }
    else{
        $('#formTipoUsuario #label_tipo_usuario_activo').html("Inactivo");
        return false;
    }
});	

$('#formPermisos #label_opcion_guardar').html("Desactivado");

$('#formPermisos .switch').change(function(){    
	if($('input[name=opcion_guardar]').is(':checked')){
		$('#formPermisos #label_opcion_guardar').html("Activado");
		return true;
	}
	else{
		$('#formPermisos #label_opcion_guardar').html("Desactivado");
		return false;
	}
});		

$('#formPermisos #label_opcion_editar').html("Desactivado");

$('#formPermisos .switch').change(function(){    
	if($('input[name=opcion_editar]').is(':checked')){
		$('#formPermisos #label_opcion_editar').html("Activado");
		return true;
	}
	else{
		$('#formPermisos #label_opcion_editar').html("Desactivado");
		return false;
	}
});		

$('#formPermisos #label_opcion_eliminar').html("Desactivado");

$('#formPermisos .switch').change(function(){    
	if($('input[name=opcion_eliminar]').is(':checked')){
		$('#formPermisos #label_opcion_eliminar').html("Activado");
		return true;
	}
	else{
		$('#formPermisos #label_opcion_eliminar').html("Desactivado");
		return false;
	}
});	

$('#formPermisos #label_opcion_consultar').html("Desactivado");

$('#formPermisos .switch').change(function(){    
	if($('input[name=opcion_consultar]').is(':checked')){
		$('#formPermisos #label_opcion_consultar').html("Activado");
		return true;
	}
	else{
		$('#formPermisos #label_opcion_consultar').html("Desactivado");
		return false;
	}
});	

$('#formPermisos #label_opcion_imprimir').html("Desactivado");

$('#formPermisos .switch').change(function(){    
	if($('input[name=opcion_imprimir]').is(':checked')){
		$('#formPermisos #label_opcion_imprimir').html("Activado");
		return true;
	}
	else{
		$('#formPermisos #label_opcion_imprimir').html("Desactivado");
		return false;
	}
});

$('#formPermisos #label_opcion_crear').html("Desactivado");

$('#formPermisos .switch').change(function(){    
	if($('input[name=opcion_crear]').is(':checked')){
		$('#formPermisos #label_opcion_crear').html("Activado");
		return true;
	}
	else{
		$('#formPermisos #label_opcion_crear').html("Desactivado");
		return false;
	}
});	

$('#formPermisos #label_opcion_reportes').html("Desactivado");

$('#formPermisos .switch').change(function(){    
	if($('input[name=opcion_reportes]').is(':checked')){
		$('#formPermisos #label_opcion_reportes').html("Activado");
		return true;
	}
	else{
		$('#formPermisos #label_opcion_reportes').html("Desactivado");
		return false;
	}
});		

$('#formPermisos #label_opcion_actualizar').html("Desactivado");

$('#formPermisos .switch').change(function(){    
	if($('input[name=opcion_actualizar]').is(':checked')){
		$('#formPermisos #label_opcion_actualizar').html("Activado");
		return true;
	}
	else{
		$('#formPermisos #label_opcion_actualizar').html("Desactivado");
		return false;
	}
});	

$('#formPermisos #label_opcion_view').html("Desactivado");

$('#formPermisos .switch').change(function(){    
	if($('input[name=opcion_view]').is(':checked')){
		$('#formPermisos #label_opcion_view').html("Activado");
		return true;
	}
	else{
		$('#formPermisos #label_opcion_view').html("Desactivado");
		return false;
	}
});	

$('#formPermisos #label_opcion_pay').html("Desactivado");

$('#formPermisos .switch').change(function(){    
	if($('input[name=opcion_pay]').is(':checked')){
		$('#formPermisos #label_opcion_pay').html("Activado");
		return true;
	}
	else{
		$('#formPermisos #label_opcion_pay').html("Desactivado");
		return false;
	}
});	

$('#formPermisos #label_opcion_cambiar').html("Desactivado");

$('#formPermisos .switch').change(function(){    
	if($('input[name=opcion_cambiar]').is(':checked')){
		$('#formPermisos #label_opcion_cambiar').html("Activado");
		return true;
	}
	else{
		$('#formPermisos #label_opcion_cambiar').html("Desactivado");
		return false;
	}
});	

$('#formPermisos #label_opcion_cancelar').html("Desactivado");

$('#formPermisos .switch').change(function(){    
	if($('input[name=opcion_cancelar]').is(':checked')){
		$('#formPermisos #label_opcion_cancelar').html("Activado");
		return true;
	}
	else{
		$('#formPermisos #label_opcion_cancelar').html("Desactivado");
		return false;
	}
});		

$('#formPermisos #label_opcion_generar').html("Desactivado");

$('#formPermisos .switch').change(function(){    
	if($('input[name=opcion_generar]').is(':checked')){
		$('#formPermisos #label_opcion_generar').html("Activado");
		return true;
	}
	else{
		$('#formPermisos #label_opcion_generar').html("Desactivado");
		return false;
	}
});	

$('#formPermisos #label_opcion_sistema').html("Desactivado");

$('#formPermisos .switch').change(function(){    
	if($('input[name=opcion_sistema]').is(':checked')){
		$('#formPermisos #label_opcion_sistema').html("Activado");
		return true;
	}
	else{
		$('#formPermisos #label_opcion_sistema').html("Desactivado");
		return false;
	}
});
//INICIO PERMISOS

</script>