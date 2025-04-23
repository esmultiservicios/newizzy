<script>
$(() => {
    $("#formMainCajas #estado_cajas").val(1);
    $('#formMainCajas #estado_cajas').selectpicker('refresh');
    listar_registro_cajas();

    // Evento para el botón de Buscar (submit)
    $('#formMainCajas').on('submit', function(e) {
        e.preventDefault();
        listar_registro_cajas(); 
    });

    // Evento para el botón de Limpiar (reset)
    $('#formMainCajas').on('reset', function() {
        // Limpia y refresca los selects
        $('#formMainCajas .selectpicker')
            .val('')
            .selectpicker('refresh');
            listar_registro_cajas();
    });
});

$('#formMainCajas #estado_cajas').on("change", function(e) {
    listar_registro_cajas();
});

$('#formMainCajas #fecha_cajas').on("change", function(e) {
    listar_registro_cajas();
});

$('#formMainCajas #fecha_cajas_f').on("change", function(e) {
    listar_registro_cajas();
});

//INICIO ACCIONES FORMULARIO REGISTRO DE CAJA
var listar_registro_cajas = function() {
    var fechai = $("#formMainCajas #fecha_cajas").val();
    var fechaf = $("#formMainCajas #fecha_cajas_f").val();
    var estado = $("#formMainCajas #estado_cajas").val();

    var table_registro_cajas = $("#dataTableCajas").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableCajaDisponibles.php",
            "data": {
                "fechai": fechai,
                "fechaf": fechaf,
                "estado": estado,
            }
        },
        "columns": [{
                "defaultContent": "<button class='table_crear btn btn-dark'><span class='fas fa-times-circle fa-lg'></span></button>"
            },
            {
                "defaultContent": "<button class='table_reportes btn btn-dark'><span class='far fa-file-pdf fa-lg'></span></button>"
            },
            {
                "data": "fecha"
            },
            {
                "data": "usuario"
            },
            {
                "data": "factura_inicial"
            },
            {
                "data": "factura_final"
            },
            {
                "data": "monto_apertura",
                render: function(data, type) {
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
            {
                "data": "importe_venta",
                render: function(data, type) {
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
            {
                "data": "neto",
                render: function(data, type) {
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
            {
                "data": "caja"
            },
        ],
        "columnDefs": [{
            "targets": 9,
            "createdCell": function(td, cellData, rowData, row, col) {
                if (cellData == "Activa") {
                    $(td).addClass("btn btn-success customButtom");
                } else {
                    $(td).addClass("btn btn-danger customButtom");
                }
            }
        }],
        "lengthMenu": lengthMenu,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "buttons": [{
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Registro de Cajas',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_registro_cajas();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte Registro de Cajas',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7]
                },
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                orientation: 'landscape',
                title: 'Reporte Registro de Cajas',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7]
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
        "drawCallback": function(settings) {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
        }
    });
    table_registro_cajas.search('').draw();
    $('#buscar').focus();

    cerrar_registro_cajas_dataTable("#dataTableCajas tbody", table_registro_cajas);
    comprobante_cajas_dataTable("#dataTableCajas tbody", table_registro_cajas);
}

var comprobante_cajas_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_crear");
    $(tbody).on("click", "button.table_crear", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarCajas.php';
        $('#formAperturaCaja #apertura_id').val(data.apertura_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formAperturaCaja').serialize(),
            success: function(registro) {
                var valores = eval(registro);
                $('#formAperturaCaja').attr({
                    'data-form': 'update'
                });
                $('#formAperturaCaja').attr({
                    'action': '<?php echo SERVERURL;?>ajax/addCierreCajaAjax.php'
                });
                $('#formAperturaCaja')[0].reset();
                $('#open_caja').hide();
                $('#close_caja').show();
                $('#formAperturaCaja #usuario_apertura').val(valores[0]);
                $('#formAperturaCaja #monto_apertura').val(valores[1]);
                $('#formAperturaCaja #fecha_apertura').val(valores[2]);
                $('#formAperturaCaja #colaboradores_id_apertura').val(valores[3]);

                //DESHBILITAR OBJETOS
                $('#formAperturaCaja #usuario_apertura').attr('readonly', true);
                $('#formAperturaCaja #monto_apertura').attr('readonly', true);
                $('#formAperturaCaja #fecha_apertura').attr('readonly', true);

                $('#formAperturaCaja #proceso_aperturaCaja').val("Cerrar Caja");
                $('#modal_apertura_caja').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
    });
}

var cerrar_registro_cajas_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_reportes");
    $(tbody).on("click", "button.table_reportes", function() {
        var data = table.row($(this).parents("tr")).data();
        printComprobanteCajas(data.apertura_id);
    });
}
//FIN ACCIONES FORMULARIO REGISTRO DE CAJA
</script>