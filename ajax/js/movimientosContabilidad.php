<script>
$(document).ready(function() {
    listar_movimientos_contabilidad();
});

$('#formMainMovimientosContabilidad #search').on("click", function(e){
	e.preventDefault();
	listar_movimientos_contabilidad();
});

var listar_movimientos_contabilidad = function(){
	var fechai = $("#formMainMovimientosContabilidad #fechai").val();
	var fechaf = $("#formMainMovimientosContabilidad #fechaf").val();

	var table_movimientos_contabilidad  = $("#dataTableMovimientosContabilidad").DataTable({
		"destroy":true,
		"ajax":{
			"method":"POST",
			"url":"<?php echo SERVERURL;?>core/llenarDataTableMovimientosCuentasContabilidad.php",
			"data":{
				"fechai":fechai,
				"fechaf":fechaf
			}	
		},
		"columns":[
			{"data":"fecha"},
			{"data":"codigo"},
			{"data":"nombre"},			
			{"data":"ingreso",
				render: function (data, type) {
                    var number = $.fn.dataTable.render
                        .number(',', '.', 2, 'L ')
                        .display(data);
 
                    if (type === 'display') {
                        let color = 'green';
                        if (data < 0) {
                            color = 'red';
                        } 
 
                        return '<span style="color:' + color + '">' + number + '</span>';
                    }
 
                    return number;
                },			
			},
			{"data":"egreso",
				render: function (data, type) {
                    var number = $.fn.dataTable.render
                        .number(',', '.', 2, 'L ')
                        .display(data);
 
                    if (type === 'display') {
                        let color = 'green';
                        if (data < 0) {
                            color = 'red';
                        } 
 
                        return '<span style="color:' + color + '">' + number + '</span>';
                    }
 
                    return number;
                },			
			},
			{"data":"saldo",
				render: function (data, type) {
                    var number = $.fn.dataTable.render
                        .number(',', '.', 2, 'L ')
                        .display(data);
 
                    if (type === 'display') {
                        let color = 'green';
                        if (data < 0) {
                            color = 'red';
                        } 
 
                        return '<span style="color:' + color + '">' + number + '</span>';
                    }
 
                    return number;
                },			
			},		
		],	
        "lengthMenu": lengthMenu10,
		"stateSave": true,
		"bDestroy": true,
		"language": idioma_español,
		"dom": dom,
		"columnDefs": [
		  { width: "16.66%", targets: 0 },
		  { width: "10.66%", targets: 1 },
		  { width: "22.66%", targets: 2 },
		  { width: "16.66%", targets: 3 },
		  { width: "16.66%", targets: 4 },
		  { width: "16.66%", targets: 5 }			  
		],
		"buttons":[
			{
				text:      '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
			titleAttr: 'Actualizar Registro Movimientos Contables',
				className: 'table_actualizar btn btn-secondary ocultar',
				action: 	function(){
					listar_movimientos_contabilidad();
				}
			},
			{
				extend:    'excelHtml5',
				text:      '<i class="fas fa-file-excel fa-lg"></i> Excel',
				titleAttr: 'Excel',
				title: 'Reporte Registro Movimientos Contables',
				messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' + convertDateFormat(fechaf),
				messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
				className: 'table_reportes btn btn-success ocultar'
			},
			{
				extend:    'pdf',
				text:      '<i class="fas fa-file-pdf fa-lg"></i> PDF',
				titleAttr: 'PDF',
				//orientation: 'landscape',
				title: 'Reporte Registro Movimientos Contables',
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
	table_movimientos_contabilidad.search('').draw();
	$('#buscar').focus();
}
//FIN ACCIONES FORMULARIO MOVIMIENTOS CONTABLES	

</script>