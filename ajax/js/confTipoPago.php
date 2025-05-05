<script>
$(document).ready(function() {
    listar_tipo_pago_contabilidad();
    getCuentaTipoPago();
	getTipoCuenta();
});

//INICIO TIPO DE PAGO
var listar_tipo_pago_contabilidad = function(){
	var table_tipo_pago_contabilidad = $("#dataTableConfTipoPago").DataTable({
		"destroy":true,
		"ajax":{
			"method":"POST",
			"url":"<?php echo SERVERURL; ?>core/llenarDataTableConfTipoPago.php"
		},
		"columns":[
			{"data":"nombre"},
			{"data":"codigo"},
			{"data":"cuenta"},						
			{"defaultContent":"<button class='table_editar btn ocultar'><span class='fas fa-edit'></span>Editar</button>"},
			{"defaultContent":"<button class='table_eliminar btn ocultar'><span class='fa fa-trash'></span>Eliminar</button>"}
		],
        "lengthMenu": lengthMenu,
		"stateSave": true,
		"bDestroy": true,
		"language": idioma_español,//esta se encuenta en el archivo main.js
		"dom": dom,
		"columnDefs": [
		  { width: "30%", targets: 0 },
		  { width: "30%", targets: 1 },
		  { width: "30%", targets: 2 },
		  { width: "5%", targets: 3 },
		  { width: "5%", targets: 4 }			  
		],		
		"buttons":[
			{
				text:      '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
				titleAttr: 'Actualizar Tipo de Pago',
				className: 'table_actualizar btn btn-secondary ocultar',
				action: 	function(){
					listar_tipo_pago_contabilidad();
				}
			},
			{
				text:      '<i class="fab fa-bitcoin fa-lg"></i> Ingresar',
				titleAttr: 'Agregar Tipo de Pago',
				className: 'table_crear btn btn-primary ocultar',
				action: 	function(){
					modalTipoPago();
				}
			},
			{
				extend:    'excelHtml5',
				text:      '<i class="fas fa-file-excel fa-lg"></i> Excel',
				titleAttr: 'Excel',
				title: 'Reporte Tipo de Pago',
				messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
				className: 'table_reportes btn btn-success ocultar',
				exportOptions: {
						columns: [0,1,2]
				}				
			},
			{
				extend:    'pdf',
				text:      '<i class="fas fa-file-pdf fa-lg"></i> PDF',
				titleAttr: 'PDF',
				title: 'Reporte Tipo de Pago',
				messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
				className: 'table_reportes btn btn-danger ocultar',
				exportOptions: {
						columns: [0,1,2]
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
	table_tipo_pago_contabilidad.search('').draw();
	$('#buscar').focus();

	edit_tipo_pago_contabilidad_dataTable("#dataTableConfTipoPago tbody", table_tipo_pago_contabilidad);
	delete_tipo_pago_contabilidad_dataTable("#dataTableConfTipoPago tbody", table_tipo_pago_contabilidad);
}

var edit_tipo_pago_contabilidad_dataTable = function(tbody, table){
	$(tbody).off("click", "button.table_editar");
	$(tbody).on("click", "button.table_editar", function(){
		var data = table.row( $(this).parents("tr") ).data();
		var url = '<?php echo SERVERURL;?>core/editarTipoPago.php';
		$('#formConfTipoPago')[0].reset();
		$('#formConfTipoPago #tipo_pago_id').val(data.tipo_pago_id);

		$.ajax({
			type:'POST',
			url:url,
			data:$('#formConfTipoPago').serialize(),
			success: function(registro){
				var valores = eval(registro);
				$('#formConfTipoPago').attr({ 'data-form': 'update' });
				$('#formConfTipoPago').attr({ 'action': '<?php echo SERVERURL;?>ajax/modificarTipoPagoAjax.php' });
				$('#reg_formTipoPago').hide();
				$('#edi_formTipoPago').show();
				$('#delete_formTipoPago').hide();
				$('#formConfTipoPago #pro_tipoPago').val("Editar");
				$('#formConfTipoPago #confTipoPago').val(valores[0]);
				$('#formConfTipoPago #confCuentaTipoPago').val(valores[1]);	
				$('#formConfTipoPago #confCuentaTipoPago').selectpicker('refresh');				
				$('#formConfTipoPago #confTipoCuenta').val(valores[3]);		
				$('#formConfTipoPago #confTipoCuenta').selectpicker('refresh');	

				if(valores[2] == 1){
					$('#formConfTipoPago #confTipoPago_activo').attr('checked', true);
				}else{
					$('#formConfTipoPago #confTipoPago_activo').attr('checked', false);
				}

				//DESHABILITAR OBJETOS				
				$('#formConfTipoPago #confCuentaTipoPago').attr('disabled', true);
				$('#formConfTipoPago #confTipoPago_activo').attr('disabled', true);	
				$('#formConfTipoPago #confTipoCuenta').attr('disabled', true);

				//HABILITAR OBJETOS
				$('#formConfTipoPago #confTipoPago_activo').attr('disabled', false);
				$('#formConfTipoPago #confTipoPago').attr('readonly', false);
				$('#formConfTipoPago #buscar_confCuentaTipoPago').show();
				$('#formConfTipoPago #estado_tipo_pago').show();

				//DESHABILITAR OBJETOS

				$('#modalConfTipoPago').modal({
					show:true,
					keyboard: false,
					backdrop:'static'
				});
			}
		});
	});
}

var delete_tipo_pago_contabilidad_dataTable = function(tbody, table){
	$(tbody).off("click", "button.table_eliminar");
	$(tbody).on("click", "button.table_eliminar", function(){
		var data = table.row( $(this).parents("tr") ).data();

		var tipo_pago_id = data.tipo_pago_id;
        var nombreTipoPago = data.nombre; 
        
        // Construir el mensaje de confirmación con HTML
        var mensajeHTML = `¿Desea eliminar permanentemente al tipo de pago?<br><br>
                        <strong>Nombre:</strong> ${nombreTipoPago}`;
        
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
                    url: '<?php echo SERVERURL;?>ajax/eliminarTipoPagoAjax.php',
                    data: {
                        tipo_pago_id: tipo_pago_id
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
//FIN TIPO DE PAGO

//INICIO FORMULARIO TIPO DE PAGO
function modalTipoPago(){
	$('#formConfTipoPago').attr({ 'data-form': 'save' });
	$('#formConfTipoPago').attr({ 'action': '<?php echo SERVERURL; ?>ajax/addTipoPagoAjax.php' });
	$('#formConfTipoPago')[0].reset();
	$('#formConfTipoPago #pro_tipoPago').val("Registro");
	$('#reg_formTipoPago').show();
	$('#edi_formTipoPago').hide();
	$('#delete_formTipoPago').hide();

	//HABILITAR OBJETOS
	$('#formConfTipoPago #confTipoPago').attr('readonly', false);
	$('#formConfTipoPago #confCuentaTipoPago').attr('disabled', false);
	$('#formConfTipoPago #confTipoPago_activo').attr('disabled', false);
	$('#formConfTipoPago #confTipoCuenta').attr('disabled', false);	
	$('#formConfTipoPago #buscar_confCuentaTipoPago').show();
	$('#formConfTipoPago #estado_tipo_pago').hide();

	$('#modalConfTipoPago').modal({
		show:true,
		keyboard: false,
		backdrop:'static'
	});
}
//FIN FORMULARIO TIPO DE PAGO

function getCuentaTipoPago(){
    var url = '<?php echo SERVERURL;?>core/getCuenta.php';

	$.ajax({
        type: "POST",
        url: url,
	    async: true,
        success: function(data){
		    $('#formConfTipoPago #confCuentaTipoPago').html("");
			$('#formConfTipoPago #confCuentaTipoPago').html(data);
			$('#formConfTipoPago #confCuentaTipoPago').selectpicker('refresh');
		}
     });
}

function getTipoCuenta(){
    var url = '<?php echo SERVERURL;?>core/getTipoCuenta.php';

	$.ajax({
        type: "POST",
        url: url,
	    async: true,
        success: function(data){
		    $('#formConfTipoPago #confTipoCuenta').html("");
			$('#formConfTipoPago #confTipoCuenta').html(data);
			$('#formConfTipoPago #confTipoCuenta').selectpicker('refresh');			
		}
     });
}

$(document).ready(function(){
    $("#modalCuentascontables").on('shown.bs.modal', function(){
        $(this).find('#formCuentasContables #cuenta_codigo').focus();
    });
});

$('#formConfTipoPago #label_confTipoPago_activo').html("Activo");
	
$('#formConfTipoPago .switch').change(function(){    
    if($('input[name=confTipoPago_activo]').is(':checked')){
        $('#formConfTipoPago #label_confTipoPago_activo').html("Activo");
        return true;
    }
    else{
        $('#formConfTipoPago #label_confTipoPago_activo').html("Inactivo");
        return false;
    }
});	
</script>