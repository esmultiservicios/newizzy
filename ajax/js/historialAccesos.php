<script>
$(() => {
    listar_historial_accesos();
});


//BUSQUEDA FECHAS HISTORIAL DE ACCESOS
$(() => {
    $('#formMainHistorialAcceso #fechai').on('change', function() {
        listar_historial_accesos();
    });

    $('#formMainHistorialAcceso #fechaf').on('change', function() {
        listar_historial_accesos();
    });

    // Evento para el botón de Generar Reporte
    $('#formMainHistorialAcceso').on('submit', function(e) {
        e.preventDefault();
        listar_historial_accesos();
    });

    // Evento para el botón de Limpiar Filtros
    $('#btn-limpiar-filtros').on('click', function() {
        $('#formMainHistorialAcceso')[0].reset();
        $('#formMainHistorialAcceso .selectpicker').selectpicker('refresh');
        listar_historial_accesos();
    });     
});

//DATA TABLE HISTORIAL ACCESOS
var listar_historial_accesos = function() {
    var fechai = $("#formMainHistorialAcceso #fechai").val();
    var fechaf = $("#formMainHistorialAcceso #fechaf").val();

    var table_historial_accesos = $("#dataTableHistorialAccesos").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableHistorialAccesos.php",
            "data": {
                "fechai": fechai,
                "fechaf": fechaf
            }
        },
        "columns": [{
                "data": "fecha"
            },
            {
                "data": "colaborador"
            },
            {
                "data": "ip"
            },
            {
                "data": "acceso"
            }
        ],
        "pageLength": 10,
        "lengthMenu": lengthMenu10,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "buttons": [{
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar de Historial de Accesos',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_historial_accesos();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte de Historial de Accesos',
                messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' +
                    convertDateFormat(fechaf),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar'
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                orientation: 'landscape',

                title: 'Reporte de Historial de Accesos',
                messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' +
                    convertDateFormat(fechaf),
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
        "drawCallback": function(settings) {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
        }
    });
    table_historial_accesos.search('').draw();
    $('#buscar').focus();
}
</script>