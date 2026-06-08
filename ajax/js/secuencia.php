<script>
$(document).ready(function() {
    listar_secuencia_facturacion();
    getEmpresaSecuencia();
    getDocumentoSecuencia();

	$('#form_main_secuencia #search').on("click", function (e) {
		e.preventDefault();
		listar_secuencia_facturacion();
	});

	$('#form_main_secuencia').on('reset', function () {
        var form = this;

        setTimeout(function () {
            $(form).find('.selectpicker').val('').selectpicker('refresh');
            $('#form_main_secuencia #filtro_secuencia_general').val('');

            listar_secuencia_facturacion();
        }, 100);
	});

    $('#form_main_secuencia #filtro_secuencia_general').on('keyup', function () {
        if ($.fn.DataTable.isDataTable("#dataTableSecuencia")) {
            $("#dataTableSecuencia").DataTable().ajax.reload(null, false);
        }
    });

    $('#form_main_secuencia #documento_secuencia_main, #form_main_secuencia #vencimiento_secuencia_main').on('changed.bs.select change', function () {
        if ($.fn.DataTable.isDataTable("#dataTableSecuencia")) {
            $("#dataTableSecuencia").DataTable().ajax.reload(null, false);
        }
    });
});

/* =========================================================
   HEADER DINÁMICO - SECUENCIA FACTURACIÓN
   ========================================================= */
function construirHeaderDataTableSecuencia() {
    var $tabla = $("#dataTableSecuencia");

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Secuencia</th>' +
                '<th>Autorización / CAI</th>' +
                '<th>Numeración</th>' +
                '<th>Vigencia</th>' +
            '</tr>' +
        '</thead>'
    );
}

/* =========================================================
   HELPERS SECUENCIA
   ========================================================= */
function secuenciaValor(valor, textoDefault) {
    if (valor === null || valor === undefined || String(valor).trim() === '') {
        if (textoDefault !== undefined) {
            return textoDefault;
        }

        return 'No registrado';
    }

    return String(valor).trim();
}

function secuenciaEscape(valor) {
    return secuenciaValor(valor, '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function secuenciaNumero(valor) {
    var numero = parseInt(String(valor || '0').replace(/[^0-9]/g, ''), 10);

    if (isNaN(numero)) {
        return 0;
    }

    return numero;
}

function secuenciaFechaToDate(fecha) {
    fecha = secuenciaValor(fecha, '');

    if (fecha === '') {
        return null;
    }

    var partes = fecha.split('/');

    if (partes.length !== 3) {
        return null;
    }

    return new Date(parseInt(partes[2], 10), parseInt(partes[1], 10) - 1, parseInt(partes[0], 10));
}

function secuenciaDiasRestantes(fechaLimite) {
    var fecha = secuenciaFechaToDate(fechaLimite);

    if (!fecha) {
        return null;
    }

    var hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    fecha.setHours(0, 0, 0, 0);

    var diff = fecha.getTime() - hoy.getTime();

    return Math.ceil(diff / (1000 * 60 * 60 * 24));
}

function secuenciaEstadoBadge(estado) {
    if (parseInt(estado) === 1) {
        return '' +
            '<span class="secuencia-status-badge secuencia-status-active">' +
                '<i class="fas fa-check-circle"></i> Activo' +
            '</span>';
    }

    return '' +
        '<span class="secuencia-status-badge secuencia-status-inactive">' +
            '<i class="fas fa-times-circle"></i> Inactivo' +
        '</span>';
}

function secuenciaDocumentoBadge(documento) {
    var doc = secuenciaValor(documento, 'Documento');
    var docLower = doc.toLowerCase();

    if (docLower.indexOf('factura') !== -1) {
        return '' +
            '<span class="secuencia-doc-badge secuencia-doc-factura">' +
                '<i class="fas fa-file-invoice-dollar"></i> ' + secuenciaEscape(doc) +
            '</span>';
    }

    if (docLower.indexOf('proforma') !== -1) {
        return '' +
            '<span class="secuencia-doc-badge secuencia-doc-proforma">' +
                '<i class="fas fa-file-alt"></i> ' + secuenciaEscape(doc) +
            '</span>';
    }

    return '' +
        '<span class="secuencia-doc-badge secuencia-doc-normal">' +
            '<i class="fas fa-file"></i> ' + secuenciaEscape(doc) +
        '</span>';
}

function secuenciaVencimientoBadge(fechaLimite) {
    var dias = secuenciaDiasRestantes(fechaLimite);

    if (dias === null) {
        return '' +
            '<span class="secuencia-vencimiento-badge secuencia-vencimiento-normal">' +
                '<i class="fas fa-calendar-alt"></i> Sin fecha' +
            '</span>';
    }

    if (dias < 0) {
        return '' +
            '<span class="secuencia-vencimiento-badge secuencia-vencimiento-vencida">' +
                '<i class="fas fa-times-circle"></i> Vencida' +
            '</span>';
    }

    if (dias <= 30) {
        return '' +
            '<span class="secuencia-vencimiento-badge secuencia-vencimiento-alerta">' +
                '<i class="fas fa-exclamation-triangle"></i> ' + dias + ' días' +
            '</span>';
    }

    return '' +
        '<span class="secuencia-vencimiento-badge secuencia-vencimiento-ok">' +
            '<i class="fas fa-check-circle"></i> Vigente' +
        '</span>';
}

function secuenciaDisponibles(row) {
    var rangoFinal = secuenciaNumero(row.rango_final);
    var siguiente = secuenciaNumero(row.siguiente);
    var disponibles = rangoFinal - siguiente + 1;

    if (disponibles < 0) {
        disponibles = 0;
    }

    return disponibles;
}

function secuenciaPorcentajeUsado(row) {
    var rangoInicial = secuenciaNumero(row.rango_inicial);
    var rangoFinal = secuenciaNumero(row.rango_final);
    var siguiente = secuenciaNumero(row.siguiente);
    var total = rangoFinal - rangoInicial + 1;
    var usado = siguiente - rangoInicial;

    if (total <= 0) {
        return 0;
    }

    if (usado < 0) {
        usado = 0;
    }

    var porcentaje = (usado / total) * 100;

    if (porcentaje > 100) {
        porcentaje = 100;
    }

    return porcentaje.toFixed(0);
}

function secuenciaFiltrarRows(rows) {
    rows = rows || [];

    var texto = $('#form_main_secuencia #filtro_secuencia_general').val();
    var documentoFiltro = $('#form_main_secuencia #documento_secuencia_main').val();
    var vencimientoFiltro = $('#form_main_secuencia #vencimiento_secuencia_main').val();

    texto = texto === null || texto === undefined ? '' : String(texto).trim().toLowerCase();
    documentoFiltro = documentoFiltro === null || documentoFiltro === undefined ? '' : String(documentoFiltro).trim().toLowerCase();
    vencimientoFiltro = vencimientoFiltro === null || vencimientoFiltro === undefined ? '' : String(vencimientoFiltro).trim().toLowerCase();

    return rows.filter(function (item) {
        var documento = item.documento === null || item.documento === undefined ? '' : String(item.documento).trim().toLowerCase();
        var dias = secuenciaDiasRestantes(item.fecha_limite);

        var textoBase = [
            item.empresa,
            item.documento,
            item.cai,
            item.prefijo,
            item.siguiente,
            item.rango_inicial,
            item.rango_final,
            item.fecha_activacion,
            item.fecha_limite,
            item.estado == 1 ? 'activo' : 'inactivo'
        ].join(' ').toLowerCase();

        if (texto !== '' && textoBase.indexOf(texto) === -1) {
            return false;
        }

        if (documentoFiltro !== '' && documento.indexOf(documentoFiltro) === -1) {
            return false;
        }

        if (vencimientoFiltro !== '') {
            if (vencimientoFiltro === 'vencida' && !(dias !== null && dias < 0)) {
                return false;
            }

            if (vencimientoFiltro === 'por_vencer' && !(dias !== null && dias >= 0 && dias <= 30)) {
                return false;
            }

            if (vencimientoFiltro === 'vigente' && !(dias !== null && dias > 30)) {
                return false;
            }
        }

        return true;
    });
}

function actualizarResumenSecuencia(rows) {
    rows = rows || [];

    var totalActivas = 0;
    var totalCai = 0;
    var totalDisponibles = 0;
    var totalPorVencer = 0;

    rows.forEach(function (item) {
        var dias = secuenciaDiasRestantes(item.fecha_limite);

        if (parseInt(item.estado) === 1) {
            totalActivas++;
        }

        if (secuenciaValor(item.cai, '') !== '') {
            totalCai++;
        }

        totalDisponibles += secuenciaDisponibles(item);

        if (dias !== null && dias >= 0 && dias <= 30) {
            totalPorVencer++;
        }
    });

    $('#secuencia_total_activas').text(totalActivas);
    $('#secuencia_total_cai').text(totalCai);
    $('#secuencia_total_disponibles').text(totalDisponibles);
    $('#secuencia_total_por_vencer').text(totalPorVencer);
}

//INICIO ACCIONES FROMULARIO SECUENCIA FACTURACION
var listar_secuencia_facturacion = function() {
    var estado = $('#form_main_secuencia #estado_secuencia_main').val();

    if ($.fn.DataTable.isDataTable("#dataTableSecuencia")) {
        $("#dataTableSecuencia").DataTable().clear().destroy();
    }

    construirHeaderDataTableSecuencia();

    var table_secuencia_facturacion = $("#dataTableSecuencia").DataTable({
        "destroy": true,
        "autoWidth": false,
        "scrollX": false,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableSecuenciaFacturacion.php",
            "data": {
                "estado": estado
            },
            "dataSrc": function (json) {
                var rows = [];

                if (json && json.data) {
                    rows = json.data;
                }

                rows = secuenciaFiltrarRows(rows);

                actualizarResumenSecuencia(rows);

                return rows;
            }
        },
        "columns": [
            {
                "data": null,
                "orderable": false,
                "searchable": false,
                "className": "text-center align-middle secuencia-acciones-cell",
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

                                '<button type="button" class="dropdown-item accion-item accion-editar table_editar ocultar">' +
                                    '<span class="accion-icon accion-icon-editar">' +
                                        '<i class="fas fa-edit"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Editar</span>' +
                                '</button>' +

                                '<button type="button" class="dropdown-item accion-item accion-eliminar table_eliminar ocultar">' +
                                    '<span class="accion-icon accion-icon-eliminar">' +
                                        '<i class="fas fa-trash-alt"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Eliminar</span>' +
                                '</button>' +

                            '</div>' +

                        '</div>';
                }
            },
            {
                "data": null,
                "className": "align-middle secuencia-info-cell",
                "render": function(data, type, row) {
                    var empresa = secuenciaEscape(row.empresa);
                    var estadoBadge = secuenciaEstadoBadge(row.estado);
                    var documentoBadge = secuenciaDocumentoBadge(row.documento);

                    if (type !== "display") {
                        return empresa + ' ' + row.documento + ' ' + (parseInt(row.estado) === 1 ? 'Activo' : 'Inactivo');
                    }

                    return '' +
                        '<div class="secuencia-main-box">' +
                            '<div class="secuencia-main-icon">' +
                                '<i class="fas fa-file-invoice"></i>' +
                            '</div>' +
                            '<div class="secuencia-main-info">' +
                                '<div class="secuencia-title-row">' +
                                    '<h6 class="secuencia-empresa">' + empresa + '</h6>' +
                                    estadoBadge +
                                '</div>' +
                                '<div class="secuencia-documento-row">' +
                                    documentoBadge +
                                '</div>' +
                                '<div class="secuencia-id-text">' +
                                    '<i class="fas fa-hashtag mr-1"></i> ID: ' + secuenciaEscape(row.secuencia_facturacion_id) +
                                '</div>' +
                            '</div>' +
                        '</div>';
                }
            },
            {
                "data": null,
                "className": "align-middle secuencia-cai-cell",
                "render": function(data, type, row) {
                    var cai = secuenciaEscape(secuenciaValor(row.cai, 'Sin CAI'));
                    var prefijo = secuenciaEscape(secuenciaValor(row.prefijo, 'Sin prefijo'));
                    var relleno = secuenciaEscape(secuenciaValor(row.relleno, '0'));
                    var incremento = secuenciaEscape(secuenciaValor(row.incremento, '0'));

                    if (type !== "display") {
                        return cai + ' ' + prefijo + ' ' + relleno + ' ' + incremento;
                    }

                    return '' +
                        '<div class="secuencia-detail-list">' +
                            '<div class="secuencia-detail-item">' +
                                '<span class="secuencia-detail-icon secuencia-icon-cai"><i class="fas fa-key"></i></span>' +
                                '<span><strong>CAI:</strong> <span class="secuencia-cai-text">' + cai + '</span></span>' +
                            '</div>' +
                            '<div class="secuencia-detail-item">' +
                                '<span class="secuencia-detail-icon secuencia-icon-prefijo"><i class="fas fa-barcode"></i></span>' +
                                '<span><strong>Prefijo:</strong> ' + prefijo + '</span>' +
                            '</div>' +
                            '<div class="secuencia-mini-row">' +
                                '<span><i class="fas fa-fill-drip mr-1"></i> Relleno: <strong>' + relleno + '</strong></span>' +
                                '<span><i class="fas fa-plus mr-1"></i> Incremento: <strong>' + incremento + '</strong></span>' +
                            '</div>' +
                        '</div>';
                }
            },
            {
                "data": null,
                "className": "align-middle secuencia-numero-cell",
                "render": function(data, type, row) {
                    var siguiente = secuenciaEscape(secuenciaValor(row.siguiente, '0'));
                    var rangoInicial = secuenciaEscape(secuenciaValor(row.rango_inicial, '0'));
                    var rangoFinal = secuenciaEscape(secuenciaValor(row.rango_final, '0'));
                    var disponibles = secuenciaDisponibles(row);
                    var porcentaje = secuenciaPorcentajeUsado(row);

                    if (type !== "display") {
                        return siguiente + ' ' + rangoInicial + ' ' + rangoFinal + ' ' + disponibles;
                    }

                    return '' +
                        '<div class="secuencia-number-box">' +
                            '<div class="secuencia-next-number">' +
                                '<span>Siguiente</span>' +
                                '<strong>' + siguiente + '</strong>' +
                            '</div>' +
                            '<div class="secuencia-range-text">' +
                                '<i class="fas fa-arrows-alt-h mr-1"></i>' +
                                rangoInicial + ' - ' + rangoFinal +
                            '</div>' +
                            '<div class="secuencia-progress-box">' +
                                '<div class="secuencia-progress-line">' +
                                    '<span style="width:' + porcentaje + '%"></span>' +
                                '</div>' +
                                '<div class="secuencia-progress-meta">' +
                                    '<span>' + porcentaje + '% usado</span>' +
                                    '<strong>' + disponibles + ' disponibles</strong>' +
                                '</div>' +
                            '</div>' +
                        '</div>';
                }
            },
            {
                "data": null,
                "className": "align-middle secuencia-vigencia-cell",
                "render": function(data, type, row) {
                    var fechaActivacion = secuenciaEscape(secuenciaValor(row.fecha_activacion, 'No registrada'));
                    var fechaLimite = secuenciaEscape(secuenciaValor(row.fecha_limite, 'No registrada'));
                    var fechaRegistro = secuenciaEscape(secuenciaValor(row.fecha_registro, 'No registrada'));
                    var badgeVencimiento = secuenciaVencimientoBadge(row.fecha_limite);
                    var dias = secuenciaDiasRestantes(row.fecha_limite);

                    var diasTexto = 'Sin información';

                    if (dias !== null) {
                        if (dias < 0) {
                            diasTexto = 'Venció hace ' + Math.abs(dias) + ' días';
                        } else {
                            diasTexto = 'Faltan ' + dias + ' días';
                        }
                    }

                    if (type !== "display") {
                        return fechaActivacion + ' ' + fechaLimite + ' ' + fechaRegistro + ' ' + diasTexto;
                    }

                    return '' +
                        '<div class="secuencia-detail-list">' +
                            '<div class="secuencia-detail-item">' +
                                '<span class="secuencia-detail-icon secuencia-icon-date"><i class="fas fa-calendar-check"></i></span>' +
                                '<span><strong>Activación:</strong> ' + fechaActivacion + '</span>' +
                            '</div>' +
                            '<div class="secuencia-detail-item">' +
                                '<span class="secuencia-detail-icon secuencia-icon-date"><i class="fas fa-calendar-times"></i></span>' +
                                '<span><strong>Límite:</strong> ' + fechaLimite + '</span>' +
                            '</div>' +
                            '<div class="secuencia-detail-item">' +
                                '<span class="secuencia-detail-icon secuencia-icon-date"><i class="fas fa-clock"></i></span>' +
                                '<span><strong>Registro:</strong> ' + fechaRegistro + '</span>' +
                            '</div>' +
                            '<div class="secuencia-vencimiento-row">' +
                                badgeVencimiento +
                                '<small>' + diasTexto + '</small>' +
                            '</div>' +
                        '</div>';
                }
            }
        ],
        "lengthMenu": lengthMenu,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "order": [[1, "asc"]],
        "columnDefs": [
            {
                width: "8%",
                targets: 0,
                orderable: false,
                searchable: false,
                className: "text-center text-nowrap align-middle secuencia-acciones-cell"
            },
            {
                width: "24%",
                targets: 1,
                className: "align-middle secuencia-info-cell"
            },
            {
                width: "26%",
                targets: 2,
                className: "align-middle secuencia-cai-cell"
            },
            {
                width: "22%",
                targets: 3,
                className: "align-middle secuencia-numero-cell"
            },
            {
                width: "20%",
                targets: 4,
                className: "align-middle secuencia-vigencia-cell"
            }
        ],
        "buttons": [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Secuencia de Facturación',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_secuencia_facturacion();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg"></i> Ingresar',
                titleAttr: 'Agregar Secuencia de Facturación',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modal_secuencia_facturacion();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte de Secuencia de Facturación',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                exportOptions: {
                    columns: [1, 2, 3, 4]
                },
                className: 'table_reportes btn btn-success ocultar'
            },
            {
                extend: 'pdf',
                orientation: 'landscape',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                title: 'Reporte de Secuencia de Facturación',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [1, 2, 3, 4]
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

    table_secuencia_facturacion.search('').draw();

    editar_secuencia_facturacion_dataTable("#dataTableSecuencia tbody", table_secuencia_facturacion);
    eliminar_secuencia_facturacion_dataTable("#dataTableSecuencia tbody", table_secuencia_facturacion);
}

var editar_secuencia_facturacion_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_editar");
    $(tbody).on("click", "button.table_editar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarSecuenciaFacturacion.php';
        $('#formSecuencia #secuencia_facturacion_id').val(data.secuencia_facturacion_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formSecuencia').serialize(),
            success: function(registro) {
                var valores = eval(registro);

                $('#formSecuencia').attr({'data-form': 'update'});
                $('#formSecuencia').attr({'action': '<?php echo SERVERURL;?>ajax/modificarSecuenciaFacturacionAjax.php'});
                $('#formSecuencia')[0].reset();

                $('#reg_secuencia').hide();
                $('#edi_secuencia').show();
                $('#delete_secuencia').hide();

                $('#formSecuencia #empresa_secuencia').val(valores[0]).prop('disabled', true).selectpicker('refresh');
                $('#formSecuencia #cai_secuencia').val(valores[1]);
                $('#formSecuencia #prefijo_secuencia').val(valores[2]);
                $('#formSecuencia #relleno_secuencia').val(valores[3]);
                $('#formSecuencia #incremento_secuencia').val(valores[4]);
                $('#formSecuencia #siguiente_secuencia').val(valores[5]);
                $('#formSecuencia #rango_inicial_secuencia').val(valores[6]);
                $('#formSecuencia #rango_final_secuencia').val(valores[7]);
                $('#formSecuencia #fecha_activacion_secuencia').val(valores[8]);
                $('#formSecuencia #fecha_limite_secuencia').val(valores[9]);
                $('#formSecuencia #documento_secuencia').val(valores[11]).prop('disabled', true).selectpicker('refresh');

                if (valores[10] == 1) {
                    $('#formSecuencia #estado_secuencia').prop('checked', true);
                } else {
                    $('#formSecuencia #estado_secuencia').prop('checked', false);
                }

                $('#formSecuencia #cai_secuencia').prop('readonly', true);
                $('#formSecuencia #prefijo_secuencia').prop('readonly', true);
                $('#formSecuencia #relleno_secuencia').prop('readonly', true);
                $('#formSecuencia #incremento_secuencia').prop('readonly', true);
                $('#formSecuencia #rango_inicial_secuencia').prop('readonly', true);
                $('#formSecuencia #rango_final_secuencia').prop('readonly', true);
                $('#formSecuencia #fecha_activacion_secuencia').prop('readonly', true);
                $('#formSecuencia #fecha_limite_secuencia').prop('readonly', true);

                $('#formSecuencia #estado_secuencia_container').show();

                $('#formSecuencia #siguiente_secuencia').prop('readonly', false);
                $('#formSecuencia #estado_secuencia').prop('disabled', false);

                $('#modal_registrar_secuencias').off('shown.bs.modal.setFocus').on('shown.bs.modal.setFocus', function () {
                    var $sig = $('#formSecuencia #siguiente_secuencia');
                    $sig.trigger('focus');
                    try { $sig[0].select(); } catch(e){}
                });

                $('#modal_registrar_secuencias').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
    });
}

var eliminar_secuencia_facturacion_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_eliminar");
    $(tbody).on("click", "button.table_eliminar", function() {
        var data = table.row($(this).parents("tr")).data();

        var secuencia_id = data.secuencia_facturacion_id;
        var nombreSecuencia = data.empresa; 
        
        var mensajeHTML = `¿Desea eliminar permanentemente la secuencia de facturación?<br><br>
                        <strong>Empresa:</strong> ${nombreSecuencia}`;
        
        swal({
            title: "Confirmar eliminación",
            content: {
                element: "span",
                attributes: {
                    innerHTML: mensajeHTML
                }
            },
            icon: "warning",
            buttons: {
                cancel: {
                    text: "Cancelar",
                    value: null,
                    visible: true,
                    className: "btn-light"
                },
                confirm: {
                    text: "Sí, eliminar",
                    value: true,
                    className: "btn-danger",
                    closeModal: false
                }
            },
            dangerMode: true,
            closeOnEsc: false,
            closeOnClickOutside: false
        }).then((confirmar) => {
            if (confirmar) {
                $.ajax({
                    type: 'POST',
                    url: '<?php echo SERVERURL;?>ajax/eliminarSecuenciaFacturacionAjax.php',
                    data: {
                        secuencia_id: secuencia_id
                    },
                    dataType: 'json',
                    beforeSend: function(){
                        showLoading("Eliminando registro...");
                    },
                    success: function(response) {
                        swal.close();
                        
                        if(response.status === "success") {
                            showNotify("success", response.title, response.message);
                            table.ajax.reload(null, false);
                            table.search('').draw();                    
                        } else {
                            showNotify("error", response.title, response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        swal.close();
                        showNotify("error", "Error", "Ocurrió un error al procesar la solicitud");
                    }
                });
            }
        });
    });
}
//FIN ACCIONES FROMULARIO SECUENCIA FACTURACION

/*INICIO FORMULARIO SECUENCIA DE FACTURACION*/
function modal_secuencia_facturacion() {
    getEmpresaSecuencia();
    getDocumentoSecuencia();

    $('#formSecuencia').attr({'data-form': 'save'});
    $('#formSecuencia').attr({'action': '<?php echo SERVERURL;?>ajax/agregarSecuenciaFacturacionAjax.php'});
    $('#formSecuencia')[0].reset();

    $('#reg_secuencia').show();
    $('#edi_secuencia').hide();
    $('#delete_secuencia').hide();

    $('#formSecuencia #empresa_secuencia').prop('disabled', false).selectpicker('refresh');
    $('#formSecuencia #documento_secuencia').prop('disabled', false).selectpicker('refresh');

    $('#formSecuencia #cai_secuencia').prop('readonly', false);
    $('#formSecuencia #prefijo_secuencia').prop('readonly', false);
    $('#formSecuencia #relleno_secuencia').prop('readonly', false);
    $('#formSecuencia #incremento_secuencia').prop('readonly', false);
    $('#formSecuencia #siguiente_secuencia').prop('readonly', false);
    $('#formSecuencia #rango_inicial_secuencia').prop('readonly', false);
    $('#formSecuencia #rango_final_secuencia').prop('readonly', false);
    $('#formSecuencia #fecha_activacion_secuencia').prop('readonly', false);
    $('#formSecuencia #fecha_limite_secuencia').prop('readonly', false);
    $('#formSecuencia #estado_secuencia').prop('disabled', false);

    $('#formSecuencia #proceso_secuencia_facturacion').val("Registro");

    $('#modal_registrar_secuencias').off('shown.bs.modal.setFocus').on('shown.bs.modal.setFocus', function () {
        $('#formSecuencia #empresa_secuencia').trigger('focus');
    });

    $('#modal_registrar_secuencias').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}
/*FIN FORMULARIO SECUENCIA DE FACTURACION*/

function getEmpresaSecuencia() {
    $.ajax({
        url: "<?php echo SERVERURL; ?>core/getEmpresa.php",
        type: "POST",
        dataType: "json",
        success: function(response) {
            const select = $('#formSecuencia #empresa_secuencia');
            select.empty();
            
            if(response.success) {
                response.data.forEach(empresa => {
                    select.append(`
                        <option value="${empresa.empresa_id}">
                            ${empresa.nombre}
                        </option>
                    `);
                });
                
                if(response.data.length > 0) {
                    select.val(1);
                    select.selectpicker('refresh');
                }
            } else {
                select.append('<option value="">No hay empresas disponibles</option>');
                showNotify("warning", "Advertencia", response.message || "No se encontraron empresas");
            }
            
            select.selectpicker('refresh');

            $('#formSecuencia #empresa_secuencia').val(1);
            $('#formSecuencia #empresa_secuencia').selectpicker('refresh');
        },
        error: function(xhr) {
            showNotify("error", "Error", "Error de conexión al cargar empresas");
            $('#formSecuencia #empresa_secuencia').html('<option value="">Error al cargar</option>');
            $('#formSecuencia #empresa_secuencia').selectpicker('refresh');
        }
    });
}

function getDocumentoSecuencia() {
    var url = '<?php echo SERVERURL;?>core/getDocumentoSecuencia.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formSecuencia #documento_secuencia').html("");
            $('#formSecuencia #documento_secuencia').html(data);
            $('#formSecuencia #documento_secuencia').selectpicker('refresh');
        }
    });
}

$(document).ready(function() {
    $("#modal_registrar_secuencias").on('shown.bs.modal', function() {
        $(this).find('#formSecuencia #empresa_secuencia').focus();
    });
});

$('#formSecuencia #label_estado_secuencia').html("Activo");

$('#formSecuencia .switch').change(function() {
    if ($('input[name=estado_secuencia]').is(':checked')) {
        $('#formSecuencia #label_estado_secuencia').html("Activo");
        return true;
    } else {
        $('#formSecuencia #label_estado_secuencia').html("Inactivo");
        return false;
    }
});
</script>