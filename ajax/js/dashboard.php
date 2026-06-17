<script>
/****************************************************************************************************************************************************************/
/* DASHBOARD - JS COMPLETO ORDENADO */
/****************************************************************************************************************************************************************/

/****************************************************************************************************************************************************************/
// TOTALES PRINCIPALES
/****************************************************************************************************************************************************************/

function setTotalCustomers() {
    var url = '<?php echo SERVERURL;?>core/getTotalCustomers.php';

    $.ajax({
        type: 'POST',
        url: url,
        async: true,
        success: function(data) {
            $('#main_clientes').html(data);
        }
    });
}

function setTotalSuppliers() {
    var url = '<?php echo SERVERURL;?>core/getTotalSuppliers.php';

    $.ajax({
        type: 'POST',
        url: url,
        async: true,
        success: function(data) {
            $('#main_proveedores').html(data);
        }
    });
}

function setTotalBills() {
    var url = '<?php echo SERVERURL;?>core/getTotalBills.php';

    $.ajax({
        type: 'POST',
        url: url,
        async: true,
        success: function(data) {
            $('#main_facturas').html("L. " + data);
        }
    });
}

function setTotalPurchases() {
    var url = '<?php echo SERVERURL;?>core/getTotalPurchases.php';

    $.ajax({
        type: 'POST',
        url: url,
        async: true,
        success: function(data) {
            $('#main_compras').html("L. " + data);
        }
    });
}

function getMesFacturaCompra() {
    var url = '<?php echo SERVERURL;?>core/getMes.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#mes_factura').html(data);
            $('#mes_compra').html(data);
        }
    });
}

/****************************************************************************************************************************************************************/
// SELECTORES DE GRÁFICOS
/****************************************************************************************************************************************************************/

function setupMonthSelectors() {
    $('.btn-year-productos').off('click').on('click', function() {
        $('.btn-year-productos').removeClass('active');
        $(this).addClass('active');

        var months = $(this).data('months');
        showTopProductos(months);
    });
}

function setupYearSelectors() {
    $('.btn-year-ventas').off('click').on('click', function() {
        $('.btn-year-ventas').removeClass('active');
        $(this).addClass('active');

        var year = $(this).data('year');
        showVentasAnuales(year);
    });

    $('.btn-year-compras').off('click').on('click', function() {
        $('.btn-year-compras').removeClass('active');
        $(this).addClass('active');

        var year = $(this).data('year');
        showComprasAnuales(year);
    });
}

/****************************************************************************************************************************************************************/
// GRÁFICO TOP PRODUCTOS
/****************************************************************************************************************************************************************/

function showTopProductos(months) {
    if (months === null || months === undefined || months === '') {
        months = 3;
    }

    var url = '<?php echo SERVERURL; ?>core/getTopProductos.php?months=' + months;

    $.ajax({
        type: 'GET',
        url: url,
        success: function(data) {
            var datos = [];

            try {
                datos = JSON.parse(data);
            } catch (e) {
                console.error('Error al procesar datos de top productos:', e);
                return;
            }

            var meses = [];
            var productos = {};

            datos.forEach(function(item) {
                if (!meses.includes(item.mes)) {
                    meses.push(item.mes);
                }

                if (!productos[item.producto]) {
                    productos[item.producto] = {};
                }

                productos[item.producto][item.mes] = item.total_vendido || 0;
            });

            var productosOrdenados = Object.keys(productos).sort(function(a, b) {
                var totalA = Object.values(productos[a]).reduce(function(sum, current) {
                    return sum + parseFloat(current || 0);
                }, 0);

                var totalB = Object.values(productos[b]).reduce(function(sum, current) {
                    return sum + parseFloat(current || 0);
                }, 0);

                return totalB - totalA;
            });

            var top5Productos = productosOrdenados.slice(0, 5);

            var datasets = top5Productos.map(function(producto, index) {
                var colores = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'];

                var productData = meses.map(function(mes) {
                    return productos[producto][mes] || 0;
                });

                return {
                    label: producto,
                    backgroundColor: colores[index % colores.length],
                    borderColor: colores[index % colores.length],
                    borderWidth: 1,
                    borderRadius: 6,
                    hoverBackgroundColor: darkenColor(colores[index % colores.length]),
                    hoverBorderColor: darkenColor(colores[index % colores.length]),
                    data: productData,
                    categoryPercentage: 0.8,
                    barPercentage: 0.9
                };
            });

            var canvas = document.getElementById('graphTopProductosporAno');

            if (!canvas) {
                return;
            }

            var ctx = canvas.getContext('2d');

            if (window.chartTopProductosAnoActual) {
                window.chartTopProductosAnoActual.destroy();
            }

            window.chartTopProductosAnoActual = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: meses,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgb(255,255,255)',
                            bodyColor: '#858796',
                            titleMarginBottom: 10,
                            titleColor: '#6e707e',
                            titleFontSize: 14,
                            borderColor: '#dddfeb',
                            borderWidth: 1,
                            xPadding: 15,
                            yPadding: 15,
                            displayColors: true,
                            intersect: false,
                            mode: 'index',
                            caretPadding: 10,
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y.toLocaleString();
                                }
                            }
                        },
                        datalabels: {
                            anchor: 'center',
                            align: 'center',
                            formatter: function(value) {
                                return parseFloat(value || 0).toLocaleString('es-HN', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                });
                            },
                            color: '#fff',
                            font: {
                                weight: 'bold',
                                size: 10
                            },
                            display: function(context) {
                                return context.dataset.data[context.dataIndex] > 0;
                            }
                        }
                    },
                    scales: {
                        x: {
                            stacked: false,
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#858796'
                            }
                        },
                        y: {
                            stacked: false,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                zeroLineColor: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                color: '#858796',
                                beginAtZero: true
                            }
                        }
                    },
                    animation: {
                        duration: 1000
                    }
                },
                plugins: [ChartDataLabels]
            });

            generateDynamicLegend('top-products-legend', datasets);
        },
        error: function(error) {
            console.error('Error al cargar datos de top productos:', error);
        }
    });
}

/****************************************************************************************************************************************************************/
// GRÁFICO VENTAS
/****************************************************************************************************************************************************************/

function showVentasAnuales(year) {
    if (year === null || year === undefined || year === '') {
        year = $('.btn-year-ventas.active').data('year');
    }

    var url = '<?php echo SERVERURL;?>core/getFacturaporAno.php?year=' + year;

    $.ajax({
        type: 'GET',
        url: url,
        success: function(data) {
            var datos = [];

            try {
                datos = JSON.parse(data);
            } catch (e) {
                console.error('Error al procesar datos de ventas:', e);
                return;
            }

            var mes = [];
            var total = [];

            for (var fila = 0; fila < datos.length; fila++) {
                mes.push(datos[fila]["mes"]);
                total.push(datos[fila]["total"]);
            }

            var canvas = document.getElementById('graphVentas');

            if (!canvas) {
                return;
            }

            var ctx = canvas.getContext('2d');

            if (window.chartVentas) {
                window.chartVentas.destroy();
            }

            window.chartVentas = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: mes,
                    datasets: [{
                        label: 'Ventas ' + year,
                        backgroundColor: '#4e73df',
                        borderColor: '#3a56b5',
                        hoverBackgroundColor: '#3a56b5',
                        hoverBorderColor: '#2a3f8a',
                        borderWidth: 1,
                        borderRadius: 6,
                        data: total
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: true,
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(255, 255, 255, 0.95)',
                            titleColor: '#2d3748',
                            bodyColor: '#4a5568',
                            borderColor: 'rgba(0, 0, 0, 0.08)',
                            borderWidth: 1,
                            padding: 12,
                            usePointStyle: true,
                            callbacks: {
                                label: function(context) {
                                    return ' L.' + context.parsed.y.toLocaleString();
                                }
                            }
                        },
                        datalabels: {
                            anchor: 'center',
                            align: 'center',
                            formatter: function(value) {
                                var formattedValue = new Intl.NumberFormat('es-HN', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                }).format(value);

                                return 'L.' + formattedValue;
                            },
                            color: '#fff',
                            font: {
                                weight: 'bold',
                                size: 10
                            },
                            display: function(context) {
                                return context.dataset.data[context.dataIndex] > 0;
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                color: '#718096'
                            }
                        },
                        y: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.03)',
                                drawBorder: false
                            },
                            ticks: {
                                color: '#718096',
                                callback: function(value) {
                                    return 'L.' + value.toLocaleString();
                                }
                            }
                        }
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    },
                    elements: {
                        bar: {
                            hoverBorderRadius: 8
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        }
    });
}

/****************************************************************************************************************************************************************/
// GRÁFICO COMPRAS
/****************************************************************************************************************************************************************/

function showComprasAnuales(year) {
    if (year === null || year === undefined || year === '') {
        year = $('.btn-year-compras.active').data('year');
    }

    var url = '<?php echo SERVERURL;?>core/getCompraporAno.php?year=' + year;

    $.ajax({
        type: 'GET',
        url: url,
        success: function(data) {
            var datos = [];

            try {
                datos = JSON.parse(data);
            } catch (e) {
                console.error('Error al procesar datos de compras:', e);
                return;
            }

            var mes = [];
            var total = [];

            for (var fila = 0; fila < datos.length; fila++) {
                mes.push(datos[fila]["mes"]);
                total.push(datos[fila]["total"]);
            }

            var canvas = document.getElementById('graphCompras');

            if (!canvas) {
                return;
            }

            var ctx = canvas.getContext('2d');

            if (window.chartCompras) {
                window.chartCompras.destroy();
            }

            window.chartCompras = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: mes,
                    datasets: [{
                        label: 'Compras ' + year,
                        backgroundColor: '#1abc9c',
                        borderColor: '#16a085',
                        hoverBackgroundColor: '#16a085',
                        hoverBorderColor: '#1abc9c',
                        borderWidth: 1,
                        borderRadius: 6,
                        data: total
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: true,
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(255, 255, 255, 0.95)',
                            titleColor: '#2d3748',
                            bodyColor: '#4a5568',
                            borderColor: 'rgba(0, 0, 0, 0.08)',
                            borderWidth: 1,
                            padding: 12,
                            usePointStyle: true,
                            callbacks: {
                                label: function(context) {
                                    return ' L.' + context.parsed.y.toLocaleString();
                                }
                            }
                        },
                        datalabels: {
                            anchor: 'center',
                            align: 'center',
                            formatter: function(value) {
                                var formattedValue = new Intl.NumberFormat('es-HN', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                }).format(value);

                                return 'L.' + formattedValue;
                            },
                            color: '#fff',
                            font: {
                                weight: 'bold',
                                size: 10
                            },
                            display: function(context) {
                                return context.dataset.data[context.dataIndex] > 0;
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                color: '#718096'
                            }
                        },
                        y: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.03)',
                                drawBorder: false
                            },
                            ticks: {
                                color: '#718096',
                                callback: function(value) {
                                    return 'L.' + value.toLocaleString();
                                }
                            }
                        }
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    },
                    elements: {
                        bar: {
                            hoverBorderRadius: 8
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        }
    });
}

/****************************************************************************************************************************************************************/
// DESCARGA DE GRÁFICOS
/****************************************************************************************************************************************************************/

function setupDownloadButtons() {
    $('.download-ventas').off('click').on('click', function() {
        downloadChart('graphVentas', 'Reporte_Ventas_' + $('.btn-year-ventas.active').data('year'));
    });

    $('.download-compras').off('click').on('click', function() {
        downloadChart('graphCompras', 'Reporte_Compras_' + $('.btn-year-compras.active').data('year'));
    });

    $('.download-top-productos').off('click').on('click', function() {
        downloadChart('graphTopProductosporAno', 'Top_Productos_' + new Date().toISOString().slice(0, 10));
    });
}

function downloadChart(chartId, fileName) {
    var canvas = document.getElementById(chartId);

    if (!canvas) {
        return;
    }

    var link = document.createElement('a');

    link.href = canvas.toDataURL('image/png');
    link.download = fileName + '.png';

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

/****************************************************************************************************************************************************************/
// FUNCIONES AUXILIARES GRÁFICOS
/****************************************************************************************************************************************************************/

function generateDynamicLegend(containerId, datasets) {
    var legendContainer = document.getElementById(containerId);

    if (!legendContainer) {
        return;
    }

    var items = datasets.map(function(dataset) {
        return '' +
            '<div class="legend-item">' +
                '<span class="legend-color" style="background-color: ' + dataset.backgroundColor + '"></span>' +
                '<span>' + dataset.label + '</span>' +
            '</div>';
    });

    legendContainer.innerHTML = items.join('');
}

function darkenColor(color, amount) {
    if (amount === null || amount === undefined) {
        amount = 20;
    }

    var usePound = false;

    if (color[0] === "#") {
        color = color.slice(1);
        usePound = true;
    }

    var num = parseInt(color, 16);

    var r = (num >> 16) - amount;
    var g = ((num >> 8) & 0x00FF) - amount;
    var b = (num & 0x0000FF) - amount;

    if (r < 0) {
        r = 0;
    }

    if (g < 0) {
        g = 0;
    }

    if (b < 0) {
        b = 0;
    }

    return (usePound ? "#" : "") + (r << 16 | g << 8 | b).toString(16).padStart(6, '0');
}

function getChartOptions(title, stacked) {
    if (stacked === null || stacked === undefined) {
        stacked = false;
    }

    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                enabled: true,
                mode: 'index',
                intersect: false,
                backgroundColor: 'rgba(255, 255, 255, 0.95)',
                titleColor: '#2d3748',
                bodyColor: '#4a5568',
                borderColor: 'rgba(0, 0, 0, 0.08)',
                borderWidth: 1,
                padding: 12,
                usePointStyle: true,
                callbacks: {
                    label: function(context) {
                        return ' L.' + context.parsed.y.toLocaleString();
                    }
                }
            }
        },
        scales: {
            x: {
                grid: {
                    display: false,
                    drawBorder: false
                },
                ticks: {
                    color: '#718096'
                },
                stacked: stacked
            },
            y: {
                grid: {
                    color: 'rgba(0, 0, 0, 0.03)',
                    drawBorder: false
                },
                ticks: {
                    color: '#718096',
                    callback: function(value) {
                        return 'L.' + value.toLocaleString();
                    }
                },
                stacked: stacked
            }
        },
        animation: {
            duration: 1000,
            easing: 'easeOutQuart'
        },
        elements: {
            bar: {
                hoverBorderRadius: 8
            }
        }
    };
}

/****************************************************************************************************************************************************************/
// HEADER DINÁMICO - SECUENCIA DASHBOARD
/****************************************************************************************************************************************************************/

function construirHeaderDataTableSecuenciaDashboard() {
    var $tabla = $("#dataTableSecuenciaDashboard");

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Secuencia</th>' +
                '<th>Autorización / CAI</th>' +
                '<th>Numeración</th>' +
                '<th>Vigencia</th>' +
            '</tr>' +
        '</thead>'
    );
}

/****************************************************************************************************************************************************************/
// HELPERS SECUENCIA DASHBOARD
/****************************************************************************************************************************************************************/

function secuenciaDashboardValor(valor, textoDefault) {
    if (valor === null || valor === undefined || String(valor).trim() === '') {
        if (textoDefault !== undefined) {
            return textoDefault;
        }

        return 'No registrado';
    }

    return String(valor).trim();
}

function secuenciaDashboardEscape(valor) {
    return secuenciaDashboardValor(valor, '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function secuenciaDashboardNumero(valor) {
    var numero = parseInt(String(valor || '0').replace(/[^0-9]/g, ''), 10);

    if (isNaN(numero)) {
        return 0;
    }

    return numero;
}

function secuenciaDashboardFechaToDate(fecha) {
    fecha = secuenciaDashboardValor(fecha, '');

    if (fecha === '') {
        return null;
    }

    var partes = fecha.split('/');

    if (partes.length !== 3) {
        return null;
    }

    return new Date(parseInt(partes[2], 10), parseInt(partes[1], 10) - 1, parseInt(partes[0], 10));
}

function secuenciaDashboardDiasRestantes(fechaLimite) {
    var fecha = secuenciaDashboardFechaToDate(fechaLimite);

    if (!fecha) {
        return null;
    }

    var hoy = new Date();

    hoy.setHours(0, 0, 0, 0);
    fecha.setHours(0, 0, 0, 0);

    var diff = fecha.getTime() - hoy.getTime();

    return Math.ceil(diff / (1000 * 60 * 60 * 24));
}

function secuenciaDashboardEstadoBadge(estado) {
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

function secuenciaDashboardDocumentoBadge(documento) {
    var doc = secuenciaDashboardValor(documento, 'Documento');
    var docLower = doc.toLowerCase();

    if (docLower.indexOf('factura') !== -1) {
        return '' +
            '<span class="secuencia-doc-badge secuencia-doc-factura">' +
                '<i class="fas fa-file-invoice-dollar"></i> ' + secuenciaDashboardEscape(doc) +
            '</span>';
    }

    if (docLower.indexOf('proforma') !== -1) {
        return '' +
            '<span class="secuencia-doc-badge secuencia-doc-proforma">' +
                '<i class="fas fa-file-alt"></i> ' + secuenciaDashboardEscape(doc) +
            '</span>';
    }

    return '' +
        '<span class="secuencia-doc-badge secuencia-doc-normal">' +
            '<i class="fas fa-file"></i> ' + secuenciaDashboardEscape(doc) +
        '</span>';
}

function secuenciaDashboardVencimientoBadge(fechaLimite) {
    var dias = secuenciaDashboardDiasRestantes(fechaLimite);

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

function secuenciaDashboardDisponibles(row) {
    var rangoFinal = secuenciaDashboardNumero(row.fin || row.rango_final);
    var siguiente = secuenciaDashboardNumero(row.siguiente);
    var disponibles = rangoFinal - siguiente + 1;

    if (disponibles < 0) {
        disponibles = 0;
    }

    return disponibles;
}

function secuenciaDashboardPorcentajeUsado(row) {
    var rangoInicial = secuenciaDashboardNumero(row.inicio || row.rango_inicial);
    var rangoFinal = secuenciaDashboardNumero(row.fin || row.rango_final);
    var siguiente = secuenciaDashboardNumero(row.siguiente);
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

/****************************************************************************************************************************************************************/
// DATATABLE DOCUMENTOS FISCALES DASHBOARD
/****************************************************************************************************************************************************************/

function listar_secuencia_fiscales_dashboard() {
    if ($.fn.DataTable.isDataTable("#dataTableSecuenciaDashboard")) {
        $("#dataTableSecuenciaDashboard").DataTable().clear().destroy();
    }

    construirHeaderDataTableSecuenciaDashboard();

    var table_secuencia_fiscales_dashboard = $("#dataTableSecuenciaDashboard").DataTable({
        "destroy": true,
        "autoWidth": false,
        "scrollX": false,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL; ?>core/llenarDataTableSecuenciaFacturacion.php",
            "dataSrc": function(json) {
                if (json && json.data) {
                    return json.data;
                }

                return [];
            }
        },
        "columns": [
            {
                "data": null,
                "className": "align-middle secuencia-info-cell",
                "render": function(data, type, row) {
                    var empresa = secuenciaDashboardEscape(row.empresa);
                    var estado = row.estado !== null && row.estado !== undefined ? row.estado : 1;
                    var estadoBadge = secuenciaDashboardEstadoBadge(estado);
                    var documentoBadge = secuenciaDashboardDocumentoBadge(row.documento);
                    var idSecuencia = secuenciaDashboardValor(row.secuencia_facturacion_id || row.id || row.secuencia_id, 'No disponible');

                    if (type !== "display") {
                        return empresa + ' ' + row.documento + ' ' + (parseInt(estado) === 1 ? 'Activo' : 'Inactivo');
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
                                    '<i class="fas fa-hashtag mr-1"></i> ID: ' + secuenciaDashboardEscape(idSecuencia) +
                                '</div>' +
                            '</div>' +
                        '</div>';
                }
            },
            {
                "data": null,
                "className": "align-middle secuencia-cai-cell",
                "render": function(data, type, row) {
                    var cai = secuenciaDashboardEscape(secuenciaDashboardValor(row.cai, 'Sin CAI'));
                    var prefijo = secuenciaDashboardEscape(secuenciaDashboardValor(row.prefijo, 'Sin prefijo'));
                    var relleno = secuenciaDashboardEscape(secuenciaDashboardValor(row.relleno, '0'));
                    var incremento = secuenciaDashboardEscape(secuenciaDashboardValor(row.incremento, '0'));

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
                    var siguiente = secuenciaDashboardEscape(secuenciaDashboardValor(row.siguiente, '0'));
                    var rangoInicial = secuenciaDashboardEscape(secuenciaDashboardValor(row.inicio || row.rango_inicial, '0'));
                    var rangoFinal = secuenciaDashboardEscape(secuenciaDashboardValor(row.fin || row.rango_final, '0'));
                    var disponibles = secuenciaDashboardDisponibles(row);
                    var porcentaje = secuenciaDashboardPorcentajeUsado(row);

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
                    var fechaActivacionRaw = row.fecha_activacion || row.activacion || '';
                    var fechaLimiteRaw = row.fecha_limite || row.fecha || '';
                    var fechaRegistroRaw = row.fecha_registro || row.registro || '';

                    var fechaActivacion = secuenciaDashboardEscape(secuenciaDashboardValor(fechaActivacionRaw, 'No registrada'));
                    var fechaLimite = secuenciaDashboardEscape(secuenciaDashboardValor(fechaLimiteRaw, 'No registrada'));
                    var fechaRegistro = secuenciaDashboardEscape(secuenciaDashboardValor(fechaRegistroRaw, 'No registrada'));

                    var badgeVencimiento = secuenciaDashboardVencimientoBadge(fechaLimiteRaw);
                    var dias = secuenciaDashboardDiasRestantes(fechaLimiteRaw);

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
        "order": [[0, "asc"]],
        "columnDefs": [
            {
                width: "30%",
                targets: 0,
                className: "align-middle secuencia-info-cell"
            },
            {
                width: "30%",
                targets: 1,
                className: "align-middle secuencia-cai-cell"
            },
            {
                width: "22%",
                targets: 2,
                className: "align-middle secuencia-numero-cell"
            },
            {
                width: "18%",
                targets: 3,
                className: "align-middle secuencia-vigencia-cell"
            }
        ],
        "buttons": [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Documentos Fiscales',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_secuencia_fiscales_dashboard();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                orientation: 'landscape',
                pageSize: 'LETTER',
                title: 'Reporte Documentos Fiscales',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: {
                    columns: [0, 1, 2, 3]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                orientation: 'landscape',
                pageSize: 'LETTER',
                title: 'Reporte Documentos Fiscales',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [0, 1, 2, 3]
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
        }
    });

    table_secuencia_fiscales_dashboard.search('').draw();
}

/****************************************************************************************************************************************************************/
// INICIALIZACIÓN DASHBOARD
/****************************************************************************************************************************************************************/

$(document).ready(function() {
    setTotalCustomers();
    setTotalSuppliers();
    setTotalBills();
    setTotalPurchases();
    getMesFacturaCompra();

    listar_secuencia_fiscales_dashboard();

    $(window).scrollTop(0);

    setupYearSelectors();
    setupMonthSelectors();
    setupDownloadButtons();

    showVentasAnuales();
    showComprasAnuales();
    showTopProductos(3);

    setInterval(function() {
        setTotalCustomers();
    }, 120000);

    setInterval(function() {
        setTotalSuppliers();
    }, 120000);

    setInterval(function() {
        setTotalBills();
    }, 120000);

    setInterval(function() {
        setTotalPurchases();
    }, 120000);

    setInterval(function() {
        showVentasAnuales();
    }, 120000);

    setInterval(function() {
        showComprasAnuales();
    }, 120000);
});
</script>