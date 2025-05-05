<script>
$(() => {
    listar_bitacora();

	$('#formMainBitacora #search').on("click", function(e) {
        e.preventDefault();
        listar_bitacora();
    });

    // Evento para el botón de Limpiar (reset)
    $('#formMainBitacora').on('reset', function() {
        // Limpia y refresca los selects
        $(this).find('.selectpicker')  // Usa `this` para referenciar el formulario actual
            .val('')
            .selectpicker('refresh');

			listar_bitacora();
    });		
});


//INICIO BITACORA
var listar_bitacora = function(){
	var fechai = $("#formMainBitacora #fechai").val();
	var fechaf = $("#formMainBitacora #fechaf").val();

	var table_bitacora = $("#dataTableBitacora").DataTable({
		"destroy":true,
		"ajax":{
			"method":"POST",
			"url":"<?php echo SERVERURL;?>core/llenarDataTableBitacora.php",
			"data":{
				"fechai":fechai,
				"fechaf":fechaf
			}
		},
		"columns":[
			{"data":"bitacoraFecha"},
			{"data":"bitacoraHoraInicio"},
			{"data":"bitacoraHoraFinal"},
			{"data":"bitacoraTipo"},
			{"data":"colaborador"}
		],
		"pageLength": 10,
        "lengthMenu": lengthMenu,
		"stateSave": true,
		"bDestroy": true,
		"language": idioma_español,
		"dom": dom,
		"buttons":[
			{
				text:      '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
				titleAttr: 'Actualizar de Historial de Accesos',
				className: 'table_actualizar btn btn-secondary ocultar',
				action: 	function(){
					listar_bitacora();
				}
			},
			{
				extend:    'excelHtml5',
				text:      '<i class="fas fa-file-excel fa-lg"></i> Excel',
				titleAttr: 'Excel',
				title: 'Reporte de Historial de Accesos',
				messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' + convertDateFormat(fechaf),
				messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
				className: 'table_reportes btn btn-success ocultar'
			},
			{
				extend:    'pdf',
				text:      '<i class="fas fa-file-pdf fa-lg"></i> PDF',
				titleAttr: 'PDF',
				title: 'Reporte de Historial de Accesos',
				messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' + convertDateFormat(fechaf),
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
	table_bitacora.search('').draw();
	$('#buscar').focus();
}
</script>