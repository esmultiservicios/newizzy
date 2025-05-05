<script>
$(document).ready(function() {
    listar_impuestos_contabilidad();
});
//INICIO IMPUESTOS
var listar_impuestos_contabilidad = function(){
	var table_impuestos_contabilidad = $("#dataTableConfImpuestos").DataTable({
		"destroy":true,
		"ajax":{
			"method":"POST",
			"url":"<?php echo SERVERURL; ?>core/llenarDataTableConfImpuestos.php"
		},
		"columns":[
			{"data":"tipo_isv_nombre"},	
			{"data":"valor"},								
			{"defaultContent":"<button class='table_editar btn ocultar'><span class='fas fa-edit'></span>Editar</button>"}
		],
        "lengthMenu": lengthMenu,
		"stateSave": true,
		"bDestroy": true,
		"language": idioma_español,//esta se encuenta en el archivo main.js
		"dom": dom,
		"columnDefs": [
		  { width: "43.33%", targets: 0 },
		  { width: "43.33%", targets: 1 },
		  { width: "13.33%", targets: 2 }	  		  
		],		
		"buttons":[
			{
				text:      '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
				titleAttr: 'Actualizar Impuestos',
				className: 'table_actualizar btn btn-secondary ocultar',
				action: 	function(){
					listar_impuestos_contabilidad();
				}
			},
			{
				extend:    'excelHtml5',
				text:      '<i class="fas fa-file-excel fa-lg"></i> Excel',
				titleAttr: 'Excel',
				title: 'Reporte Impuestos',
				messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
				className: 'table_reportes btn btn-success ocultar',
				exportOptions: {
						columns: [0,1]
				},					
			},
			{
				extend:    'pdf',
				text:      '<i class="fas fa-file-pdf fa-lg"></i> PDF',
				titleAttr: 'PDF',
				title: 'Reporte Impuestos',
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
	table_impuestos_contabilidad.search('').draw();
	$('#buscar').focus();

	edit_impuestos_contabilidad_dataTable("#dataTableConfImpuestos tbody", table_impuestos_contabilidad);
}

var edit_impuestos_contabilidad_dataTable = function(tbody, table){
	$(tbody).off("click", "button.table_editar");
	$(tbody).on("click", "button.table_editar", function(){
		var data = table.row( $(this).parents("tr") ).data();
		var url = '<?php echo SERVERURL;?>core/editarImpuestos.php';
		$('#formImpuestos #isv_id').val(data.isv_id);

		$.ajax({
			type:'POST',
			url:url,
			data:$('#formImpuestos').serialize(),
			success: function(registro){
				var valores = eval(registro);
				$('#formImpuestos').attr({ 'data-form': 'update' });
				$('#formImpuestos').attr({ 'action': '<?php echo SERVERURL;?>ajax/modificarImpuestos.php' });
				$('#formImpuestos')[0].reset();
				$('#reg_catProd').hide();
				$('#edi_catProd').show();
				$('#delete_catProd').hide();
				$('#formImpuestos #pro_impuestos').val("Editar");
				$('#formImpuestos #tipo_isv').val(valores[1]);				
				$('#formImpuestos #valor').val(valores[3]);

				$('#modalImpuestos').modal({
					show:true,
					keyboard: false,
					backdrop:'static'
				});
			}
		});
	});
}
//FIN IMPUESTOS

$(document).ready(function(){
    $("#modalImpuestos").on('shown.bs.modal', function(){
        $(this).find('#formImpuestos #valor').focus();
    });
});
</script>