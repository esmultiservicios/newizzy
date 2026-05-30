<script>
$(document).ready(function() {
    listar_diarios_configuracion();
	getCuentaDiarios(); 
});

function construirHeaderDataTableConfDiarios() {
    var $tabla = $("#dataTableConfDiarios");

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Entidad</th>' +
                '<th>Cuenta</th>' +
            '</tr>' +
        '</thead>'
    );
}


//INICIO CONFIGURACION CUENTAS CONTABLES EN DIARIOS
var listar_diarios_configuracion = function () {

    if ($.fn.DataTable.isDataTable("#dataTableConfDiarios")) {
        $("#dataTableConfDiarios").DataTable().clear().destroy();
    }

    construirHeaderDataTableConfDiarios();

    var table_diarios_configuracion = $("#dataTableConfDiarios").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL; ?>core/llenarDataTableDiarios.php"
        },
        "columns": [
            {
                "data": null,
                "orderable": false,
                "searchable": false,
                "className": "text-center align-middle",
                "render": function (data, type, row) {
                    if (type !== "display") {
                        return "";
                    }

                    return '' +
                        '<div class="dropdown acciones-dropdown">' +

                            '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                                '<i class="fas fa-cog"></i>' +
                                '<span>Acciones</span>' +
                            '</button>' +

                            '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +

                                '<button type="button" class="dropdown-item accion-item accion-editar table_editar ocultar">' +
                                    '<span class="accion-icon accion-icon-editar">' +
                                        '<i class="fas fa-edit"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Editar</span>' +
                                '</button>' +

                            '</div>' +

                        '</div>';
                }
            },
            { "data": "diario" },
            { "data": "cuenta" }
        ],
        "lengthMenu": lengthMenu10,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [
            { width: "12%", targets: 0 },
            { width: "44%", targets: 1 },
            { width: "44%", targets: 2 }
        ],
        "buttons": [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Diarios',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function () {
                    listar_diarios_configuracion();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte Diarios',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: {
                    columns: [1, 2]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                title: 'Reporte Diarios',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [1, 2]
                },
                customize: function (doc) {
                    if (imagen) {
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
        "drawCallback": function (settings) {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());

            if (typeof cerrarDropdownAcciones === "function") {
                cerrarDropdownAcciones();
            }
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