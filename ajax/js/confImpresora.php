<script>
$(document).ready(function() {
	getImpresora();
});

/* =========================================================
   HEADER DINÁMICO - IMPRESORA
   ========================================================= */
   function construirHeaderDataTableConfImpresora() {
    var $tabla = $("#dataTableConfImpresora");

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Descripción</th>' +
                '<th>Activo</th>' +
            '</tr>' +
        '</thead>'
    );
}

//CONFIGURACION DE IMPRESORA    
var getImpresora = function() {
    var impresora_id;
    var activo;
    var descripcion;

    if ($.fn.DataTable.isDataTable("#dataTableConfImpresora")) {
        $("#dataTableConfImpresora").DataTable().clear().destroy();
    }

    construirHeaderDataTableConfImpresora();

    var table_impresora = $("#dataTableConfImpresora").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableImpresora.php",
            "data": {
                "impresora_id": impresora_id,
                "descripcion": descripcion,
                "activo": activo
            }
        },
        "columns": [
            {
                "data": null,
                "orderable": false,
                "searchable": false,
                "className": "text-center align-middle",
                "render": function(data, type, row) {
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

                                '<button type="button" class="dropdown-item accion-item accion-editar table_impresora table_editar">' +
                                    '<span class="accion-icon accion-icon-editar">' +
                                        '<i class="fas fa-edit"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Editar</span>' +
                                '</button>' +

                            '</div>' +
                        '</div>';
                }
            },
            {
                "data": "descripcion"
            },
            {
                "data": "activo"
            }
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
                width: "68%",
                targets: 1,
                className: "text-center"
            },
            {
                width: "20%",
                targets: 2,
                className: "text-center"
            }
        ],
        "buttons": [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    getImpresora();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte',
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
                orientation: 'landscape',
                title: 'Reporte',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [1, 2]
                },
                customize: function(doc) {
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
        "drawCallback": function(settings) {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());

            if (typeof cerrarDropdownAcciones === "function") {
                cerrarDropdownAcciones();
            }
        }
    });

    table_impresora.search('').draw();
    $('#buscar').focus();

    updateStatus("#dataTableConfImpresora tbody", table_impresora);
}
//FIN 

//CAMBIAR EL ESTATUS DE CONFIGURACION
var updateStatus = function(tbody, table){
	$(tbody).off("click", "button.table_impresora");
	$(tbody).on("click", "button.table_impresora", function(){
		var data = table.row( $(this).parents("tr") ).data();	
		swal({
			title: "¿Desea cambiar el estado?",
			icon: "info",
			buttons: {
				confirm: {
					text: "Activado!",
					value: true,
					visible: true
				},
				cancel: {
					text: "Desactivado!",
					value: false,
					visible: true
				}
			},
			closeOnEsc: false, // Desactiva el cierre con la tecla Esc
			closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera 
		}).then((isConfirm) => {
			if (isConfirm) {
				showNotify('success', 'Estado de Impresora', 'Activado');
				editarImpresora(data.impresora_id, 1);
			} else {
				showNotify('success', 'Estado de Impresora', 'Desactivado');
				editarImpresora(data.impresora_id, 0);
			}
		});
	})
};

function editarImpresora(id, estado) {
    var url = '<?php echo SERVERURL; ?>core/editarImpresora.php';

    $.ajax({
        type: 'POST',
        url: url,
        data: {
            id: id,
            estado: estado
        },
        success: function (response) {
            // Convertir la respuesta en un objeto JSON si no está ya parseada
            var data = typeof response === 'object' ? response : JSON.parse(response);

            if (data.success) {
                showNotify('success', 'Éxito', data.message); // Mensaje del backend
                getImpresora(); // Actualizar la lista de impresoras
            } else {
                showNotify('error', 'Error', data.message); // Mensaje del backend
            }
        },
        error: function () {
            showNotify('error', 'Error', 'Hubo un problema con la conexión al servidor. Por favor, inténtelo de nuevo.');
        }
    });
}
</script>
