<script>
$(document).ready(function() {
    listar_cheques_contabilidad();
});

$('#formMainChequesContabilidad #search').on("click", function(e){
	e.preventDefault();
	listar_cheques_contabilidad();
});
//INICIO ACCIONES FORMULARIO CHEQUES
var listar_cheques_contabilidad = function(){	
	var fechai = $("#formMainChequesContabilidad #fechai").val();
	var fechaf = $("#formMainChequesContabilidad #fechaf").val();
	
	var table_cheques_contabilidad  = $("#dataTableChequesContabilidad").DataTable({
		"destroy":true,
		"ajax":{
			"method":"POST",
			"url":"<?php echo SERVERURL;?>core/llenarDataTableChequesContabilidad.php",
			"data":{
				"fechai":fechai,
				"fechaf":fechaf
			}	
		},
		"columns":[
			{"data":"fecha"},
			{"data":"proveedor"},
			{"data":"factura"},
			{"data":"importe"},			
			{"data":"codigo"},
			{"data":"nombre"},
			{"data":"observacion"}		
		],	
        "lengthMenu": lengthMenu,
		"stateSave": true,
		"bDestroy": true,
		"language": idioma_español,
		"dom": dom,
		"columnDefs": [
		  { width: "10.28%", targets: 0 },
		  { width: "18.28%", targets: 1 },
		  { width: "14.28%", targets: 2 },
		  { width: "10.28%", targets: 3 },
		  { width: "10.28%", targets: 4 },
		  { width: "14.28%", targets: 5 },
		  { width: "22.28%", targets: 6 }	  
		],
		"buttons":[
			{
				text:      '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
			titleAttr: 'Actualizar Registro Cheques',
				className: 'table_actualizar btn btn-secondary ocultar',
				action: 	function(){
					listar_cheques_contabilidad();
				}
			},
			{
				text:      '<i class="fas fas fa-plus fa-lg crear"></i> Ingresar',
				titleAttr: 'Agregar Cheques',
				className: 'table_crear btn btn-primary ocultar',
				action: 	function(){
					//modal_echeques_contabilidad();
				}
			},			
			{
				extend:    'excelHtml5',
				text:      '<i class="fas fa-file-excel fa-lg"></i> Excel',
				titleAttr: 'Excel',
				title: 'Reporte Registro Cheques',
				messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' + convertDateFormat(fechaf),
				messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
				className: 'table_reportes btn btn-success ocultar'
			},
			{
				extend:    'pdf',
				text:      '<i class="fas fa-file-pdf fa-lg"></i> PDF',
				titleAttr: 'PDF',
				orientation: 'landscape',
				title: 'Reporte Registro Cheques',
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
	table_cheques_contabilidad.search('').draw();
	$('#buscar').focus();
}
//FIN ACCIONES FORMULARIO CHEQUES    
</script>