<script>
    $(() => {
        getReporteFactura();
        getFacturador();
        getVendedores();
        GetProductos();
        listar_reporte_ventas();
        $('#form_main_ventas #tipo_factura_reporte').val(1);
        $('#form_main_ventas #tipo_factura_reporte').selectpicker('refresh');

        $('#form_main_ventas #search').on("click", function (e) {
            e.preventDefault();
            console.log("Botón de búsqueda clickeado"); // Verifica en la consola (F12)
            listar_reporte_ventas();
        });

        // Evento para el botón de Limpiar (reset)
        $('#form_main_ventas').on('reset', function () {
            // Limpia y refresca los selects
            $(this).find('.selectpicker') // Usa `this` para referenciar el formulario actual
                .val('')
                .selectpicker('refresh');

            listar_reporte_ventas();
        });
    });

    // Función para redondear números de manera personalizada
    function customRound(number) {
        var truncated = Math.floor(number * 100) / 100;
        var secondDecimal = Math.floor((number * 100) % 10);

        if (secondDecimal >= 5) {
            return parseFloat((truncated + 0.01).toFixed(2));
        } else {
            return parseFloat(truncated.toFixed(2));
        }
    }

    var listar_reporte_ventas = function () {
        // --- Limpia un estado guardado que esté forzando otro orden en ese navegador ---
        try {
            var _dtKey = 'DataTables_' + 'dataTablaReporteVentas' + '_' + window.location.pathname;
            localStorage.removeItem(_dtKey);
        } catch(e){ /* ignore */ }

        let tipo_factura_reporte = $("#form_main_ventas #tipo_factura_reporte").val();
        tipo_factura_reporte = tipo_factura_reporte ? tipo_factura_reporte : 1;

        let factura = $("#form_main_ventas #factura_reporte").val();
        factura = factura ? factura : 1;

        var fechai = $("#form_main_ventas #fechai").val();
        var fechaf = $("#form_main_ventas #fechaf").val();
        var facturador = $("#form_main_ventas #facturador").val();
        var vendedor = $("#form_main_ventas #vendedor").val();

        var table_reporteVentas = $("#dataTablaReporteVentas").DataTable({
            "destroy": true,
            "footer": true,
            // Desactiva guardado de estado para que no vuelva a cambiarte el order
            "stateSave": false,
            "orderMulti": false,

            "ajax": {
                "method": "POST",
                "url": "<?php echo SERVERURL;?>core/llenarDataTableReporteVentas.php",
                "data": {
                    "tipo_factura_reporte": tipo_factura_reporte,
                    "facturador": facturador,
                    "vendedor": vendedor,
                    "fechai": fechai,
                    "fechaf": fechaf,
                    "factura": factura
                }
            },

            "columns": [
                { "data": "fecha" },
                {
                    "data": "tipo_documento",
                    "render": function (data, type) {
                        if (type === 'display') {
                            var icon = data === 'Crédito'
                                ? '<i class="fas fa-clock mr-1"></i>'
                                : '<i class="fas fa-check-circle mr-1"></i>';
                            var badgeClass = data === 'Crédito'
                                ? 'badge badge-pill badge-warning'
                                : 'badge badge-pill badge-success';
                            return '<span class="' + badgeClass + '" style="font-size: 0.95rem; padding: 0.5em 0.8em; font-weight: 600;">' +
                                icon + data + '</span>';
                        }
                        return data;
                    }
                },
                { "data": "cliente" },

                // FACTURA: muestra 'numero' pero ORDENA por 'numero_sort' (si no, por 'number')
                {
                    data: {
                        display: 'numero',  // lo que se ve
                        _: 'numero',        // fallback
                        filter: 'numero',   // búsqueda
                        sort: 'numero_sort' // clave de orden (viene en tu JSON)
                    },
                    render: function (data, type, row) {
                        // Para sort / filter devolvemos lo que DataTables ya resolvió (numero_sort/numero)
                        if (type !== 'display') return data;

                        // --- DISPLAY (tu UI actual) ---
                        if (parseInt(row.documento_id, 10) === 4) {
                            const est = parseInt(row.proforma_estado, 10); // 0 Abierta, 1 Cerrada
                            const badge = (est === 1)
                                ? '<span class="badge badge-secondary ml-2">Cerrada</span>'
                                : '<span class="badge badge-info ml-2">Abierta</span>';

                            const cerrarBtn = (est === 0)
                                ? `<button class="btn btn-sm btn-danger ml-2 cerrar_proforma"
                                        data-toggle="tooltip" data-placement="top" title="Cerrar proforma">
                                        <i class="fas fa-times-circle"></i>
                                </button>`
                                : '';

                            return `
                                <div class="d-flex align-items-center justify-content-between" style="gap:.5rem;">
                                    <span>${row.numero}</span>
                                    <span>${badge}</span>
                                    ${cerrarBtn}
                                </div>`;
                        }

                        // FACTURAS normales
                        return row.numero;
                    }
                },

                { // subtotal
                    "data": "subtotal",
                    render: function (data, type) {
                        var number = $.fn.dataTable.render.number(',', '.', 2, 'L ').display(data);
                        if (type === 'display') {
                            let color = (data < 0) ? 'red' : 'green';
                            return '<span style="color:' + color + '">' + number + '</span>';
                        }
                        return number;
                    }
                },
                { // isv
                    "data": "isv",
                    render: function (data, type) {
                        var number = $.fn.dataTable.render.number(',', '.', 2, 'L ').display(data);
                        if (type === 'display') {
                            let color = (data < 0) ? 'red' : 'green';
                            return '<span style="color:' + color + '">' + number + '</span>';
                        }
                        return number;
                    }
                },
                { // descuento
                    "data": "descuento",
                    render: function (data, type) {
                        var number = $.fn.dataTable.render.number(',', '.', 2, 'L ').display(data);
                        if (type === 'display') {
                            let color = (data < 0) ? 'red' : 'green';
                            return '<span style="color:' + color + '">' + number + '</span>';
                        }
                        return number;
                    }
                },
                { // total
                    "data": "total",
                    render: function (data, type, row) {
                        const numberFormatted = 'L ' + parseFloat(data).toLocaleString('es-HN', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                        if (type !== 'display') return numberFormatted;

                        let estado, estadoClass, borderColor, badgeColor, icon;
                        if (parseInt(row.documento_id, 10) === 4) {
                            const proformaEstado = parseInt(row.proforma_estado, 10);
                            if (proformaEstado === 1) {
                                estado = 'Cerrada'; estadoClass = 'text-white';
                                badgeColor = 'bg-secondary'; borderColor = '#343a40';
                                icon = '<i class="fas fa-lock mr-1"></i>';
                            } else {
                                estado = 'Abierta'; estadoClass = 'text-white';
                                badgeColor = 'bg-info'; borderColor = '#17a2b8';
                                icon = '<i class="fas fa-folder-open mr-1"></i>';
                            }
                        } else {
                            if (row.tipo_documento === 'Contado') {
                                estado = 'Pagado'; estadoClass = 'text-white';
                                badgeColor = 'bg-success'; borderColor = '#28a745';
                                icon = '<i class="fas fa-check-circle mr-1"></i>';
                            } else {
                                const pago = row.estado_pago || 'Pendiente';
                                if (pago === 'Pagado') {
                                    estado = 'Pagado'; estadoClass = 'text-white';
                                    badgeColor = 'bg-secondary'; borderColor = '#343a40';
                                    icon = '<i class="fas fa-check-double mr-1"></i>';
                                } else {
                                    estado = 'Pendiente'; estadoClass = 'text-dark';
                                    badgeColor = 'bg-warning'; borderColor = '#ffc107';
                                    icon = '<i class="fas fa-clock mr-1"></i>';
                                }
                            }
                        }

                        return `
                            <div class="total-container" style="display:flex;flex-direction:column;align-items:flex-end;min-width:0;max-width:200px;">
                                <div style="background:#fff;border-left:6px solid ${borderColor};padding:8px 12px;border-radius:.5rem;box-shadow:0 1px 5px rgba(0,0,0,.08);font-size:1.1em;font-weight:bold;color:#212529;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    ${numberFormatted}
                                </div>
                                <div class="status-badge ${badgeColor} ${estadoClass}"
                                    style="font-size:.75em!important;padding:.3em 1em!important;border-radius:999px!important;display:inline-block;line-height:1.3;margin-top:5px;white-space:nowrap;">
                                    ${icon}${estado}
                                </div>
                            </div>`;
                    }
                },
                { // ganancia
                    "data": "ganancia",
                    render: function (data, type) {
                        var number = $.fn.dataTable.render.number(',', '.', 2, 'L ').display(data);
                        if (type === 'display') {
                            let color = (data < 0) ? 'red' : 'green';
                            return '<span style="color:' + color + '">' + number + '</span>';
                        }
                        return number;
                    }
                },
                { "data": "vendedor" },
                { "data": "facturador" },
                { "defaultContent": "<button class='table_reportes detalle_factura btn btn-primary'><span class='fas fa-search fa-lg'></span> Detalle</button>" },
                { "defaultContent": "<button class='table_reportes print_factura btn btn-success'><span class='fas fa-file-download fa-lg'></span> Factura</button>" },
                { "defaultContent": "<button class='table_reportes print_comprobante btn btn-success'><span class='far fa-file-pdf fa-lg'></span> Comprobante</button>" },
                { "defaultContent": "<button class='table_reportes email_factura btn btn-secondary'><span class='fas fa-paper-plane fa-lg'></span> Enviar</button>" },
                { "defaultContent": "<button class='table_cancelar cancelar_factura btn btn-danger'><span class='fas fa-ban fa-lg'></span> Anular</button>" }
            ],

            // ¡Clave! Orden inicial por FACTURA (col 3) DESC.
            "order": [[3, "desc"]],
            "columnDefs": [
                { targets: 3, type: 'num' } // ayúdale a tratarla como numérica
            ],

            "lengthMenu": lengthMenu10,
            "bDestroy": true,
            "language": idioma_español,
            "dom": dom,

            "footerCallback": function (row, data, start, end, display) {
                var totalSubtotal = data.reduce((acc, r) => acc + (parseFloat(r.subtotal) || 0), 0);
                var totalIsv      = data.reduce((acc, r) => acc + (parseFloat(r.isv) || 0), 0);
                var totalDescuento= data.reduce((acc, r) => acc + (parseFloat(r.descuento) || 0), 0);
                var totalVentas   = data.reduce((acc, r) => acc + (parseFloat(r.total) || 0), 0);
                var totalGanancia = data.reduce((acc, r) => acc + (parseFloat(r.ganancia) || 0), 0);

                var formatter = new Intl.NumberFormat('es-HN', {
                    style: 'currency', currency: 'HNL', minimumFractionDigits: 2,
                });

                $('#subtotal-i').html(formatter.format(totalSubtotal));
                $('#impuesto-i').html(formatter.format(totalIsv));
                $('#descuento-i').html(formatter.format(totalDescuento));
                $('#total-footer-ingreso').html(formatter.format(totalVentas));
                $('#ganancia').html(formatter.format(totalGanancia));
            },

            "buttons": [
                {
                    text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                    titleAttr: 'Actualizar Reporte de Ventas',
                    className: 'table_actualizar btn btn-secondary ocultar',
                    action: function () { listar_reporte_ventas(); }
                },
                {
                    text: '<i class="fas fa-search fa-lg crear"></i> Detalle Ventas',
                    titleAttr: 'Detalle Ventas',
                    className: 'table_crear btn btn-primary ocultar',
                    action: function () { modal_detalles(); }
                },
                {
                    extend: 'excelHtml5',
                    footer: true,
                    text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                    titleAttr: 'Excel',
                    title: 'Reporte de Ventas',
                    messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' + convertDateFormat(fechaf),
                    messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                    exportOptions: { columns: [0,1,2,3,4,5,6,7,8] },
                    className: 'table_reportes btn btn-success ocultar'
                },
                {
                    extend: 'pdf',
                    footer: true,
                    orientation: 'landscape',
                    text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                    titleAttr: 'PDF',
                    pageSize: 'LETTER',
                    title: 'Reporte de Ventas',
                    messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' + convertDateFormat(fechaf),
                    messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                    className: 'table_reportes btn btn-danger ocultar',
                    exportOptions: { columns: [0,1,2,3,4,5,6,7,8] },
                    customize: function (doc) {
                        if (imagen) {
                            doc.content.splice(0, 0, { image: imagen, width: 100, height: 45, margin: [0,0,0,12] });
                        }
                    }
                }
            ],

            "drawCallback": function () {
                getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
            }
        });

        table_reporteVentas.search('').draw();
        $('#buscar').focus();

        view_detalle_factura_dataTable("#dataTablaReporteVentas tbody", table_reporteVentas);
        view_correo_facturas_dataTable("#dataTablaReporteVentas tbody", table_reporteVentas);
        view_reporte_facturas_dataTable("#dataTablaReporteVentas tbody", table_reporteVentas);
        view_reporte_comprobante_dataTable("#dataTablaReporteVentas tbody", table_reporteVentas);
        view_anular_facturas_dataTable("#dataTablaReporteVentas tbody", table_reporteVentas);
        view_cerrar_proforma_dataTable("#dataTablaReporteVentas tbody", table_reporteVentas);
    };

    var view_cerrar_proforma_dataTable = function (tbody, table) {
        $(tbody).off("click", "button.cerrar_proforma");
        $(tbody).on("click", "button.cerrar_proforma", function (e) {
            e.preventDefault();
            const data = table.row($(this).parents("tr")).data();
            // ENVIAMOS AMBOS IDs
            cerrarProforma(data.facturas_proforma_id, data.facturas_id, data.numero);
        });
    };

    function cerrarProforma(facturas_proforma_id, facturas_id, numero) {
        swal({
            title: "Cerrar proforma",
            text: `¿Desea cerrar la proforma ${numero}?`,
            icon: "warning",
            buttons: {
                cancel: "Cancelar",
                confirm: { text: "Sí, cerrar", closeModal: false }
            },
            dangerMode: true, closeOnEsc: false, closeOnClickOutside: false
        }).then((ok) => {
            if (!ok) return;

            $.ajax({
                url: '<?php echo SERVERURL; ?>core/facturas/cerrarProforma.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    facturas_proforma_id: facturas_proforma_id,
                    facturas_id: facturas_id
                },
                success: function (r) {
                    swal.close();
                    if (r && r.success) {
                        showNotify('success', r.title || 'Éxito', r.message || 'Proforma cerrada.');
                        listar_reporte_ventas();
                    } else {
                        showNotify('error', r.title || 'Error', (r && r.message) ? r.message : 'No se pudo cerrar la proforma.');
                    }
                },
                error: function (xhr) {
                    swal.close();
                    showNotify('error', 'Error', xhr.responseText || 'Error de red.');
                }
            });
        });
    }

    var view_detalle_factura_dataTable = function (tbody, table) {
        $(tbody).off("click", "button.detalle_factura");
        $(tbody).on("click", "button.detalle_factura", function (e) {
            e.preventDefault();
            var data = table.row($(this).parents("tr")).data();
            mostrarDetalleFactura(data.facturas_id);
        });
    }
    
    function mostrarDetalleFactura(facturas_id) {
        // Mostrar el modal
        var $modal = $('#modalDetalleFactura');
        $modal.modal('show');

        // Obtener datos
        $.ajax({
            url: '<?php echo SERVERURL; ?>core/getDetalleFacturaReporteVentas.php',
            type: 'POST',
            data: {
                facturas_id: facturas_id
            },
            dataType: 'json',
            success: function (response) {

                if (response.success && response.data) {
                    var factura = response.data.cabecera;
                    var detalles = response.data.detalle;
                    // Llenar cabecera
                    $modal.find('#numero-factura-modal').text(factura.numero_factura || 'N/A');
                    $modal.find('#fecha-factura').text(factura.fecha || 'N/A');
                    $modal.find('#cliente-factura').text(factura.cliente || 'N/A');
                    $modal.find('#tipo-factura').text(factura.tipo_factura || 'N/A');

                    // Estado
                    var estadoNum = parseInt(factura.estado) || 0;
                    var estadoBadge = '';
                    switch (estadoNum) {
                        case 2:
                            estadoBadge = 'badge-success">Pagada';
                            break;
                        case 3:
                            estadoBadge = 'badge-warning text-dark">Crédito';
                            break;
                        case 4:
                            estadoBadge = 'badge-danger">Anulada';
                            break;
                        default:
                            estadoBadge = 'badge-secondary">Pendiente';
                    }
                    $modal.find('#estado-factura').html('<span class="badge badge-pill ' + estadoBadge +
                        '</span>');

                    // Totales
                    $modal.find('#subtotal-factura').text(formatMoney(factura.subtotal || 0));
                    $modal.find('#total-factura').text(formatMoney(factura.total || 0));
                    $modal.find('#notas-factura').text(factura.notas || 'No hay notas');

                    // Llenar detalle
                    var detalleHtml = '';
                    if (detalles && detalles.length > 0) {
                        detalles.forEach(function (item) {
                            detalleHtml += `
                            <tr>
                                <td>${item.producto || 'Producto no especificado'}</td>
                                <td class="text-center">${item.cantidad || 0} ${item.medida || ''}</td>
                                <td class="text-right">${formatMoney(item.precio || 0)}</td>
                                <td class="text-right">${formatMoney(item.isv_valor || 0)}</td>
                                <td class="text-right">${formatMoney(item.descuento || 0)}</td>
                                <td class="text-right">${formatMoney(item.subtotal || 0)}</td>
                            </tr>`;
                        });
                    } else {
                        detalleHtml = `
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No se encontraron detalles para esta factura
                            </td>
                        </tr>`;
                    }

                    $modal.find('#detalle-factura-body').html(detalleHtml);

                    // Configurar botón de imprimir
                    $modal.find('#btn-imprimir-factura').off('click').on('click', function () {
                        if (typeof printBillReporteVentas === 'function') {
                            printBillReporteVentas(facturas_id);
                        }
                    });

                } else {
                    $modal.find('.modal-body').html(`
                    <div class="alert alert-danger">
                        ${response.message || 'Error al cargar los detalles'}
                    </div>
                `);
                }
            },
            error: function (xhr, status, error) {
                console.error('Error en AJAX:', error, xhr.responseText);
                $modal.find('.modal-body').html(`
                <div class="alert alert-danger">
                    Error al cargar los datos: ${error}
                    <button class="btn btn-sm btn-outline-primary mt-2" onclick="mostrarDetalleFactura(${facturas_id})">
                        <i class="fas fa-sync-alt"></i> Reintentar
                    </button>
                </div>
            `);
            }
        });
    }

    // Función de formato de dinero segura
    function formatMoney(amount) {
        try {
            var number = parseFloat(amount) || 0;
            return 'L ' + number.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        } catch (e) {
            console.error('Error formateando dinero:', e);
            return 'L 0.00';
        }
    }

    // Funciones para manejar los eventos de los botones en la tabla
    var view_correo_facturas_dataTable = function (tbody, table) {
        $(tbody).off("click", "button.email_factura");
        $(tbody).on("click", "button.email_factura", function (e) {
            e.preventDefault();
            var data = table.row($(this).parents("tr")).data();
            mailBill(data.facturas_id);
        });
    }

    var view_reporte_facturas_dataTable = function (tbody, table) {
        $(tbody).off("click", "button.print_factura");
        $(tbody).on("click", "button.print_factura", function (e) {
            e.preventDefault();
            var data = table.row($(this).parents("tr")).data();
            printBillReporteVentas(data.facturas_id);
        });
    }

    var view_reporte_comprobante_dataTable = function (tbody, table) {
        $(tbody).off("click", "button.print_comprobante");
        $(tbody).on("click", "button.print_comprobante", function (e) {
            e.preventDefault();
            var data = table.row($(this).parents("tr")).data();
            printBillComprobanteReporteVentas(data.facturas_id);
        });
    }

    var view_anular_facturas_dataTable = function (tbody, table) {
        $(tbody).off("click", "button.cancelar_factura");
        $(tbody).on("click", "button.cancelar_factura", function (e) {
            e.preventDefault();
            var data = table.row($(this).parents("tr")).data();
            anularFacturas(data.facturas_id);
        });
    }

    function anularFacturas(facturas_id) {
        swal({
            title: "¿Esta seguro?",
            text: "¿Desea anular la factura: # " + getNumeroFactura(facturas_id) + "?",
            content: {
                element: "input",
                attributes: {
                    placeholder: "Comentario",
                    type: "text",
                },
            },
            icon: "warning",
            buttons: {
                cancel: "Cancelar",
                confirm: {
                    text: "¡Sí, anular la factura!",
                    closeModal: false,
                },
            },
            dangerMode: true,
            closeOnEsc: false,
            closeOnClickOutside: false
        }).then((value) => {
            if (value === null || value.trim() === "") {
                showNotify('error', 'Error', '¡Necesita escribir algo!');
                return false;
            }
            anular(facturas_id, value);
        });
    }

    function anular(facturas_id, comentario) {
        var url = '<?php echo SERVERURL; ?>core/anularFactura.php';

        $.ajax({
            type: 'POST',
            url: url,
            async: false,
            data: 'facturas_id=' + facturas_id + '&comentario=' + comentario,
            success: function (data) {
                if (data == 1) {
                    swal.close(); // Cierra el modal de SweetAlert
                    showNotify('success', 'Success', 'La factura ha sido anulada con éxito');
                    listar_reporte_ventas();
                } else {
                    swal.close(); // Cierra el modal de SweetAlert
                    showNotify('error', 'Error', 'La factura no se puede anular');
                }
            }
        });
    }

    function getReporteFactura() {
        var url = '<?php echo SERVERURL;?>core/getTipoFacturaReporte.php';

        $.ajax({
            type: "POST",
            url: url,
            async: true,
            success: function (data) {
                $('#form_main_ventas #tipo_factura_reporte').html("");
                $('#form_main_ventas #tipo_factura_reporte').html(data);
                $('#form_main_ventas #tipo_factura_reporte').selectpicker('refresh');
            }
        });
    }

    function getFacturador() {
        var url = '<?php echo SERVERURL;?>core/getFacturador.php';

        $.ajax({
            type: "POST",
            url: url,
            async: true,
            success: function (data) {
                $('#form_main_ventas #facturador').html("");
                $('#form_main_ventas #facturador').html(data);
                $('#form_main_ventas #facturador').selectpicker('refresh');
            }
        });
    }

    function getVendedores() {
        var url = '<?php echo SERVERURL;?>core/getColaboradores.php';

        $.ajax({
            type: "POST",
            url: url,
            async: true,
            success: function (data) {
                $('#form_main_ventas #vendedor').html("");
                $('#form_main_ventas #vendedor').html(data);
                $('#form_main_ventas #vendedor').selectpicker('refresh');

                $('#FormDetalleVentas #DetalleVendedores').html("");
                $('#FormDetalleVentas #DetalleVendedores').html(data);
                $('#FormDetalleVentas #DetalleVendedores').selectpicker('refresh');
            }
        });
    }

    function GetProductos() {
        var url = '<?php echo SERVERURL;?>core/getProductos.php';

        $.ajax({
            type: "POST",
            url: url,
            async: true,
            success: function (data) {
                $('#FormDetalleVentas #DetallesProductos').html("");
                $('#FormDetalleVentas #DetallesProductos').html(data);
                $('#FormDetalleVentas #DetallesProductos').selectpicker('refresh');
            }
        });
    }

    function modal_detalles() {
        getVendedores();
        GetProductos();
        ListarDetalleVenas();
        $('#ModalDetalleVentas').modal({
            show: true,
            keyboard: false,
            backdrop: 'static'
        });
    }

    var ListarDetalleVenas = function () {
        var fechai = $("#FormDetalleVentas #DetallesFechai").val();
        var fechaf = $("#FormDetalleVentas #DetallesFechaf").val();
        var productos_id = $("#FormDetalleVentas #DetallesProductos").val();
        var colaboradores_id = $("#FormDetalleVentas #DetalleVendedores").val();

        var table_puestos = $("#DatatableDetalleVentas").DataTable({
            "destroy": true,
            "ajax": {
                "method": "POST",
                "url": "<?php echo SERVERURL;?>core/llenarDataTableDetalleVentas.php",
                "data": {
                    "fechai": fechai,
                    "fechaf": fechaf,
                    "productos_id": productos_id,
                    "colaboradores_id": colaboradores_id
                }
            },
            "columns": [{
                    "data": "Producto"
                },
                {
                    "data": "numero"
                },
                {
                    "data": "Cliente"
                },                
                {
                    "data": "Precio",
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
                {
                    "data": "Cantidad"
                },
                {
                    "data": "ISV",
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
                {
                    "data": "Descuento",
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
                {
                    "data": "Total",
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
                {
                    "data": "Vendedor"
                }
            ],
            "lengthMenu": lengthMenu,
            "stateSave": true,
            "bDestroy": true,
            "language": idioma_español,
            "dom": dom,
            "footerCallback": function (row, data, start, end, display) {
                var totalPrecio = 0;
                var totalCantidad = 0;
                var totalISV = 0;
                var totalDescuento = 0;
                var totalTotal = 0;

                data.forEach(function (row) {
                    totalPrecio += parseFloat(row.Precio) || 0;
                    totalCantidad += parseFloat(row.Cantidad) || 0;
                    totalISV += parseFloat(row.ISV) || 0;
                    totalDescuento += parseFloat(row.Descuento) || 0;
                    totalTotal += parseFloat(row.Total) || 0;
                });

                var formatter = new Intl.NumberFormat('es-HN', {
                    style: 'currency',
                    currency: 'HNL',
                    minimumFractionDigits: 2,
                });

                $('#total-precio').html(formatter.format(totalPrecio));
                $('#total-cantidad').html(formatter.format(totalCantidad));
                $('#total-isv').html(formatter.format(totalISV));
                $('#total-descuento').html(formatter.format(totalDescuento));
                $('#total-total').html(formatter.format(totalTotal));
            },
            "columnDefs": [{
                    width: "5%",
                    targets: 0
                },
                {
                    width: "85%",
                    targets: 1
                },
                {
                    width: "5%",
                    targets: 2
                },
                {
                    width: "5%",
                    targets: 3
                }
            ],
            "buttons": [{
                    text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                    titleAttr: 'Actualizar Puestos',
                    className: 'table_actualizar btn btn-secondary ocultar',
                    action: function () {
                        ListarDetalleVenas();
                    }
                },
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                    titleAttr: 'Excel',
                    title: 'Reporte Detalle de Ventas',
                    messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                    className: 'table_reportes btn btn-success ocultar',
                },
                {
                    extend: 'pdf',
                    orientation: 'landscape',
                    text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                    titleAttr: 'PDF',
                    title: 'Reporte Detalle de Ventas',
                    messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                    className: 'table_reportes btn btn-danger ocultar',
                    customize: function (doc) {
                        doc.content.splice(1, 0, {
                            margin: [0, 0, 0, 12],
                            alignment: 'left',
                            image: imagen,
                            width: 100,
                            height: 45
                        });
                    }
                }
            ],
            "drawCallback": function (settings) {
                getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
            }
        });
        table_puestos.search('').draw();
        $('#buscar').focus();
    }
</script>