<script>
$(function() {
    listar_programa_puntos();
});


$('#form_main_programa_puntos #estado_programa_puntos').on('change', () => {
    listar_programa_puntos();
});

$("#tipo_calculo").on("change", function() {
    $("#calculo_monto, #calculo_porcentaje, #calculo_personalizado").hide();
    $("#ejemplo_calculo").hide();

    if (this.value === "monto") {
        $("#calculo_monto").show();
        $("#ejemplo_calculo").show();
        // Verificamos si ya hay un valor en el input monto
        let montoActual = parseFloat($("#monto").val());
        if (montoActual) {
            let montoDoble = montoActual * 2;
            $("#ejemploTexto").html(`Si se define que por cada ${montoActual} Lempiras se acumula 1 punto, entonces por consumir ${montoDoble} Lempiras se acumulan 2 puntos.`);
        } else {
            $("#ejemploTexto").html("Si se define que por cada 25 Lempiras se acumula 1 punto, entonces por 50 Lempiras se acumulan 2 puntos.");
        }
    } else if (this.value === "porcentaje") {
        $("#calculo_porcentaje").show();
        $("#ejemplo_calculo").show();
        // Verificamos si ya hay un valor en el input porcentaje
        let porcentajeActual = parseFloat($("#porcentaje").val());
        if (porcentajeActual) {
            let montoConsumido = 5000;
            let puntos = (montoConsumido * porcentajeActual) / 100;
            $("#ejemploTexto").html(`Si se define un porcentaje de ${porcentajeActual}%, entonces por consumir ${montoConsumido} Lempiras se acumulan ${puntos} puntos.`);
        } else {
            $("#ejemploTexto").html("Si se define un porcentaje de 10%, entonces por consumir 5000 Lempiras se acumulan 500 puntos.");
        }
    }
});

$("#monto").on("input", function() {
    let monto = parseFloat($(this).val());
    if (monto && $("#tipo_calculo").val() === "monto") {
        let montoDoble = monto * 2;
        $("#ejemploTexto").html(`Si se define que por cada ${monto} Lempiras se acumula 1 punto, entonces por consumir ${montoDoble} Lempiras se acumulan 2 puntos.`);
        $("#ejemplo_calculo").show();
    } else if (!monto && $("#tipo_calculo").val() === "monto") {
        $("#ejemploTexto").html("Si se define que por cada 25 Lempiras se acumula 1 punto, entonces por 50 Lempiras se acumulan 2 puntos.");
    }
});

$("#porcentaje").on("input", function() {
    let porcentaje = parseFloat($(this).val());
    let montoConsumido = 5000;
    if (porcentaje && $("#tipo_calculo").val() === "porcentaje") {
        let puntos = (montoConsumido * porcentaje) / 100;
        $("#ejemploTexto").html(`Si se define un porcentaje de ${porcentaje}%, entonces por consumir ${montoConsumido} Lempiras se acumulan ${puntos} puntos.`);
        $("#ejemplo_calculo").show();
    } else if (!porcentaje && $("#tipo_calculo").val() === "porcentaje") {
        $("#ejemploTexto").html("Si se define un porcentaje de 10%, entonces por consumir 5000 Lempiras se acumulan 500 puntos.");
    }
});

//INICIO ACCIONES FROMULARIO PUESTOS
var listar_programa_puntos = function(){
	let estado = $('#form_main_programa_puntos #estado_programa_puntos').val();

	var table_programa_puntos  = $("#dataTableProgramaPuntos").DataTable({
		"destroy":true,
		"ajax":{
			"method":"POST",
			"url":"<?php echo SERVERURL;?>core/llenarDataTableProgramaPuntos.php",
			"data": {
				estado: estado
			},
		},
		"columns":[
			{
				"data": "nombre",
				"render": function(data, type, row) {
					return `<a href="#" 
							class="ver-historico" 
							data-id="${row.id}" 
							data-toggle="tooltip" 
							data-placement="top" 
							title="Ver histórico de puntos"
							style="color: #3498db !important; background-color: transparent !important; text-decoration: none !important;">${data}</a>`;
				}
			},
			{"data":"tipo_calculo"},
			{"data":"monto"},
			{"data":"porcentaje"},	
			{
				"data": "activo",
				"render": function(data, type, row) {
					const iconSize = "1.25em"; // Tamaño consistente
					if (data == 1) {
					return '<span class="status-badge status-active">' +
								`<i class="fas fa-check-circle" style="font-size: ${iconSize}"></i>` +
								'ACTIVO' +
							'</span>';
					} else {
					return '<span class="status-badge status-inactive">' +
								`<i class="fas fa-times-circle" style="font-size: ${iconSize}"></i>` +
								'INACTIVO' +
							'</span>';
					}
				}
			},
			{
				"data": "fecha_creacion",
				"render": function(data, type, row) {
					// Configurar moment para que use el idioma español
					moment.locale('es');
					
					// Formatear la fecha en el formato deseado
					return moment(data).format('dddd D [de] MMMM [de] YYYY');
				}
			},		
			{"defaultContent":"<button class='table_editar btn btn-dark ocultar'><span class='fas fa-edit fa-lg'></span></button>"},
			{"defaultContent":"<button class='table_eliminar btn btn-dark ocultar'><span class='fa fa-trash fa-lg'></span></button>"}
		],
        "lengthMenu": lengthMenu,
		"stateSave": true,
		"bDestroy": true,
		"language": idioma_español,
		"dom": dom,
		"columnDefs": [
			{ width: "35%", targets: 0 },  // Columna 1: 'nombre' (aumento el ancho a 25%)
			{ width: "10%", targets: 1 },  // Columna 2: 'tipo_calculo'
			{ width: "8%", targets: 2 },  // Columna 3: 'monto'
			{ width: "8%", targets: 3 },  // Columna 4: 'porcentaje'
			{ width: "14%", targets: 4 },  // Columna 5: 'estado'
			{ width: "20%", targets: 5 },  // Columna 6: 'fecha_creacion'
			{ width: "5%", targets: 6 },  // Columna 7: botón de editar
			{ width: "5%", targets: 7 }   // Columna 8: botón de eliminar
		],
		"buttons":[
			{
				text:      '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
				titleAttr: 'Actualizar Programa de Puntos',
				className: 'table_actualizar btn btn-secondary ocultar',
				action: 	function(){
					listar_programa_puntos();
				}
			},
			{
				text:      '<i class="fas fas fa-plus fa-lg"></i> Ingresar',
				titleAttr: 'Agregar Programa de Puntos',
				className: 'table_crear btn btn-primary ocultar',
				action: 	function(){
					modal_puestos();
				}
			},
			{
				extend:    'excelHtml5',
				text:      '<i class="fas fa-file-excel fa-lg"></i> Excel',
				titleAttr: 'Excel',
				title: 'Reporte de Programa de Puntos',
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
				title: 'Reporte de Programa de Puntos',
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

	// Inicializar tooltips después de cada redibujado de la tabla
	$('#dataTableProgramaPuntos').on('draw.dt', function() {
		$('[data-toggle="tooltip"]').tooltip();
	});

	table_programa_puntos.search('').draw();
	$('#buscar').focus();

	editar_puestos_dataTable("#dataTableProgramaPuntos tbody", table_programa_puntos);
	eliminar_puestos_dataTable("#dataTableProgramaPuntos tbody", table_programa_puntos);
}

var editar_puestos_dataTable = function(tbody, table){
	$(tbody).off("click", "button.table_editar");
	$(tbody).on("click", "button.table_editar", function(){
		var data = table.row( $(this).parents("tr") ).data();
		var url = '<?php echo SERVERURL;?>core/editarPuestos.php';
		$('#formProgramaPuntos #programa_puntos_id').val(data.id);

		$.ajax({
			type:'POST',
			url:url,
			data:$('#formProgramaPuntos').serialize(),
			success: function(registro){
				var valores = eval(registro);
				$('#formProgramaPuntos').attr({ 'data-form': 'update' });
				$('#formProgramaPuntos').attr({ 'action': '<?php echo SERVERURL;?>ajax/modificarPuestosAjax.php' });
				$('#formProgramaPuntos')[0].reset();
				$('#reg_ProgramaPuntos').hide();
				$('#edi_ProgramaPuntos').show();
				$('#delete_ProgramaPuntos').hide();
				$('#formProgramaPuntos #puesto').val(valores[0]);

				if(valores[1] == 1){
					$('#formProgramaPuntos #ProgramaPuntos_activo').attr('checked', true);
				}else{
					$('#formProgramaPuntos #ProgramaPuntos_activo').attr('checked', false);
				}

				//HABILITAR OBJETOS
				$('#formProgramaPuntos #puesto').attr('readonly', false);
				$('#formProgramaPuntos #ProgramaPuntos_activo').attr('disabled', false);
				$('#formProgramaPuntos #estadoProgramaPuntos').show();

				$('#modalProgramaPuntos').modal({
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
		$('#formProgramaPuntos #programa_puntos_id').val(data.id);

		$.ajax({
			type:'POST',
			url:url,
			data:$('#formProgramaPuntos').serialize(),
			success: function(registro){
				var valores = eval(registro);
				$('#formProgramaPuntos').attr({ 'data-form': 'delete' });
				$('#formProgramaPuntos').attr({ 'action': '<?php echo SERVERURL;?>ajax/eliminarPuestosAjax.php' });
				$('#formProgramaPuntos')[0].reset();
				$('#reg_ProgramaPuntos').hide();
				$('#edi_ProgramaPuntos').hide();
				$('#delete_ProgramaPuntos').show();
				$('#formProgramaPuntos #nombre').val(valores[0]);

				if(valores[1] == 1){
					$('#formProgramaPuntos #ProgramaPuntos_activo').attr('checked', true);
				}else{
					$('#formProgramaPuntos #ProgramaPuntos_activo').attr('checked', false);
				}

				//DESHABILITAR OBJETOS
				$('#formProgramaPuntos #nombre').attr('readonly', true);
				$('#formProgramaPuntos #ProgramaPuntos_activo').attr('disabled', true);
				$('#formProgramaPuntos #estadoProgramaPuntos').hide();

				$('#modalProgramaPuntos').modal({
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
	  $('#formProgramaPuntos').attr({ 'data-form': 'save' });
	  $('#formProgramaPuntos').attr({ 'action': '<?php echo SERVERURL;?>ajax/agregarPuestosAjax.php' });
	  $('#formProgramaPuntos')[0].reset();
	  $('#reg_ProgramaPuntos').show();
	  $('#edi_ProgramaPuntos').hide();
	  $('#delete_ProgramaPuntos').hide();

	  //HABILITAR OBJETOS
	  $('#formProgramaPuntos #puesto').attr('readonly', false);
	  $('#formProgramaPuntos #ProgramaPuntos_activo').attr('disabled', false);
	  $('#formProgramaPuntos #estadoProgramaPuntos').hide();

	  $('#formProgramaPuntos #ejemplo_calculo').hide();

	  $('#modalProgramaPuntos').modal({
		show:true,
		keyboard: false,
		backdrop:'static'
	  });
}
/*FIN FORMULARIO PUESTO DE COLABORADORES*/

$(document).ready(function(){
    $("#modalProgramaPuntos").on('shown.bs.modal', function(){
        $(this).find('#formProgramaPuntos #nombre').focus();
    });
});

$('#formProgramaPuntos #label_ProgramaPuntos_activo').html("Activo");
	
$('#formProgramaPuntos .switch').change(function(){    
    if($('input[name=ProgramaPuntos_activo]').is(':checked')){
        $('#formProgramaPuntos #label_ProgramaPuntos_activo').html("Activo");
        return true;
    }
    else{
        $('#formProgramaPuntos #label_ProgramaPuntos_activo').html("Inactivo");
        return false;
    }
});	

// Agrega esto en tu listar_programa_puntos() después de inicializar la DataTable

// Evento para mostrar el modal cuando se hace clic en el nombre
$('#dataTableProgramaPuntos').on('click', '.ver-historico', function(e) {
    e.preventDefault();
    const programaPuntosId = $(this).data('id');
    const programaNombre = $(this).text();
    
    // Mostrar el modal
    $('#modalHistoricoPuntosLabel').text(`Historial de Puntos - ${programaNombre}`);
	$('#modalHistoricoPuntos').modal({
		show: true,
		keyboard: false // Esto desactiva el cierre con ESC
	});
    
    // Limpiar tabla anterior
    $('#tablaHistoricoPuntos tbody').empty();
    $('#fecha-actualizacion').text('cargando...');
    
    // Obtener datos del historial
    $.ajax({
        url: '<?php echo SERVERURL;?>core/llenarDataTableHistoricoProgramaPuntos.php',
        type: 'POST',
        data: { programa_puntos_id: programaPuntosId },
        dataType: 'json',
        success: function(response) {
            if(response.data && response.data.length > 0) {
                // Llenar la tabla
                response.data.forEach(function(item) {
                    $('#tablaHistoricoPuntos tbody').append(`
                        <tr>
                            <td>${item.cliente}</td>
                            <td><span class="badge ${item.tipo_movimiento === 'Acumulación' ? 'badge-success' : 'badge-danger'}">${item.tipo_movimiento}</span></td>
                            <td>${item.puntos}</td>
                            <td>${item.descripcion}</td>
                            <td>${item.fecha}</td>
                        </tr>
                    `);
                });
                
                // Actualizar fecha
                $('#fecha-actualizacion').text(response.ultima_actualizacion);
            } else {
                $('#tablaHistoricoPuntos tbody').append(`
                    <tr>
                        <td colspan="5" class="text-center">No hay registros de historial para este programa</td>
                    </tr>
                `);
                $('#fecha-actualizacion').text('No disponible');
            }
        },
        error: function() {
            $('#tablaHistoricoPuntos tbody').append(`
                <tr>
                    <td colspan="5" class="text-center text-danger">Error al cargar el historial</td>
                </tr>
            `);
            $('#fecha-actualizacion').text('Error');
        }
    });
});

// Inicializar DataTable del modal
$('#modalHistoricoPuntos').on('shown.bs.modal', function () {
    $('#tablaHistoricoPuntos').DataTable({
        "language": idioma_español,
        "dom": '<"top"f>rt<"bottom"lip><"clear">',
        "pageLength": 10,
        "order": [[4, "desc"]]
    });
});

// Destruir DataTable al cerrar el modal
$('#modalHistoricoPuntos').on('hidden.bs.modal', function () {
    if ($.fn.DataTable.isDataTable('#tablaHistoricoPuntos')) {
        $('#tablaHistoricoPuntos').DataTable().destroy();
    }
});
</script>