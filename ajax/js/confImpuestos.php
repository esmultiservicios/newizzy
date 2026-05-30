<script>
$(document).ready(function() {
    listar_impuestos_contabilidad();
});

/* =========================================================
   HEADER DINÁMICO - IMPUESTOS
   ========================================================= */
   function construirHeaderDataTableConfImpuestos() {
    var $tabla = $("#dataTableConfImpuestos");

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Impuesto</th>' +
                '<th>Valor</th>' +
            '</tr>' +
        '</thead>'
    );
}

//INICIO IMPUESTOS
var listar_impuestos_contabilidad = function () {

    if ($.fn.DataTable.isDataTable("#dataTableConfImpuestos")) {
        $("#dataTableConfImpuestos").DataTable().clear().destroy();
    }

    construirHeaderDataTableConfImpuestos();

    var table_impuestos_contabilidad = $("#dataTableConfImpuestos").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL; ?>core/llenarDataTableConfImpuestos.php"
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
            { "data": "tipo_isv_nombre" },
            { "data": "valor" }
        ],
        "lengthMenu": lengthMenu,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [
            {
                width: "12%",
                targets: 0,
                orderable: false,
                searchable: false,
                className: "text-center text-nowrap align-middle"
            },
            {
                width: "44%",
                targets: 1
            },
            {
                width: "44%",
                targets: 2
            }
        ],
        "buttons": [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Impuestos',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function () {
                    listar_impuestos_contabilidad();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte Impuestos',
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
                title: 'Reporte Impuestos',
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