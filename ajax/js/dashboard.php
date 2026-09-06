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
        timeout: 15000,
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
        timeout: 15000,
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
        timeout: 15000,
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
        timeout: 15000,
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
        timeout: 15000,
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
        timeout: 15000,
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
        timeout: 15000,
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
        timeout: 15000,
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
// DOCUMENTOS FISCALES DASHBOARD - LISTADO POR DIVs
/****************************************************************************************************************************************************************/

var dashboardFiscalesRows = [];
var dashboardFiscalesFiltradas = [];
var dashboardFiscalesVista = 'detalle';
var DASHBOARD_FISCALES_STORAGE_VISTA = 'izzy_dashboard_fiscales_tipo_vista';
var dashboardFiscalesPagina = 1;
var dashboardFiscalesPorPagina = 3;
var dashboardFiscalesPorPaginaDetalle = 3;
var dashboardFiscalesPorPaginaMiniatura = 6;

function secuenciaDashboardValor(valor, textoDefault) {
    if (valor === null || valor === undefined || String(valor).trim() === '') {
        return textoDefault !== undefined ? textoDefault : 'No registrado';
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
    return isNaN(numero) ? 0 : numero;
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

    return Math.ceil((fecha.getTime() - hoy.getTime()) / (1000 * 60 * 60 * 24));
}

function secuenciaDashboardEstadoBadge(estado) {
    if (parseInt(estado, 10) === 1) {
        return '<span class="dashboard-fiscal-badge dashboard-fiscal-badge-active"><i class="fas fa-check-circle"></i> Activo</span>';
    }

    return '<span class="dashboard-fiscal-badge dashboard-fiscal-badge-inactive"><i class="fas fa-times-circle"></i> Inactivo</span>';
}

function secuenciaDashboardDocumentoBadge(documento) {
    var doc = secuenciaDashboardValor(documento, 'Documento');
    var docLower = doc.toLowerCase();
    var clase = 'dashboard-fiscal-doc-default';
    var icono = 'fa-file-alt';

    if (docLower.indexOf('proforma') !== -1) {
        clase = 'dashboard-fiscal-doc-proforma';
        icono = 'fa-file-contract';
    } else if (docLower.indexOf('credito') !== -1 || docLower.indexOf('crédito') !== -1) {
        clase = 'dashboard-fiscal-doc-credito';
        icono = 'fa-file-invoice';
    } else if (docLower.indexOf('debito') !== -1 || docLower.indexOf('débito') !== -1) {
        clase = 'dashboard-fiscal-doc-debito';
        icono = 'fa-file-invoice-dollar';
    } else if (docLower.indexOf('factura') !== -1) {
        clase = 'dashboard-fiscal-doc-factura';
        icono = 'fa-file-invoice-dollar';
    }

    return '<span class="dashboard-fiscal-doc-badge ' + clase + '"><i class="fas ' + icono + '"></i> ' + secuenciaDashboardEscape(doc) + '</span>';
}

function secuenciaDashboardVencimientoBadge(fechaLimite) {
    var dias = secuenciaDashboardDiasRestantes(fechaLimite);

    if (dias === null) {
        return '<span class="dashboard-fiscal-vigencia dashboard-fiscal-vigencia-neutral"><i class="fas fa-calendar-alt"></i> Sin fecha</span>';
    }

    if (dias < 0) {
        return '<span class="dashboard-fiscal-vigencia dashboard-fiscal-vigencia-danger"><i class="fas fa-times-circle"></i> Vencida</span>';
    }

    if (dias <= 30) {
        return '<span class="dashboard-fiscal-vigencia dashboard-fiscal-vigencia-warning"><i class="fas fa-exclamation-triangle"></i> ' + dias + ' días</span>';
    }

    return '<span class="dashboard-fiscal-vigencia dashboard-fiscal-vigencia-success"><i class="fas fa-check-circle"></i> Vigente</span>';
}

function secuenciaDashboardDisponibles(row) {
    var rangoFinal = secuenciaDashboardNumero(row.fin || row.rango_final);
    var siguiente = secuenciaDashboardNumero(row.siguiente);
    var disponibles = rangoFinal - siguiente + 1;
    return disponibles < 0 ? 0 : disponibles;
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

    usado = Math.max(0, usado);
    return Math.min(100, Math.round((usado / total) * 100));
}

function dashboardFiscalesNormalizarTexto(valor) {
    return String(valor || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');
}

function dashboardFiscalesInicializarVista() {
    var vista = 'detalle';

    try {
        vista = localStorage.getItem(DASHBOARD_FISCALES_STORAGE_VISTA) || 'detalle';
    } catch (e) {
        vista = 'detalle';
    }

    dashboardFiscalesVista = vista === 'miniatura' ? 'miniatura' : 'detalle';
    dashboardFiscalesActualizarBotonesVista();
    dashboardFiscalesSincronizarTamanoPagina();
}

function dashboardFiscalesCambiarVista(vista) {
    dashboardFiscalesVista = vista === 'miniatura' ? 'miniatura' : 'detalle';

    try {
        localStorage.setItem(DASHBOARD_FISCALES_STORAGE_VISTA, dashboardFiscalesVista);
    } catch (e) {
        // La interfaz continúa funcionando aunque localStorage esté bloqueado.
    }

    dashboardFiscalesActualizarBotonesVista();
    dashboardFiscalesSincronizarTamanoPagina();
    dashboardFiscalesPagina = 1;
    dashboardFiscalesRender();
}

function dashboardFiscalesSincronizarTamanoPagina() {
    var $select = $('#dashboard_fiscales_page_size');
    var esMiniatura = dashboardFiscalesVista === 'miniatura';
    var opciones = esMiniatura ? [6, 12, 18, 30] : [3, 5, 10, 20];
    var seleccionado = esMiniatura
        ? dashboardFiscalesPorPaginaMiniatura
        : dashboardFiscalesPorPaginaDetalle;

    $select.empty();

    opciones.forEach(function(valor) {
        $select.append('<option value="' + valor + '">' + valor + '</option>');
    });

    dashboardFiscalesPorPagina = opciones.indexOf(seleccionado) !== -1
        ? seleccionado
        : opciones[0];

    $select.val(String(dashboardFiscalesPorPagina));
}

function dashboardFiscalesActualizarBotonesVista() {
    $('.dashboard-fiscales-view-btn')
        .removeClass('active')
        .attr('aria-pressed', 'false');

    $('.dashboard-fiscales-view-btn[data-view="' + dashboardFiscalesVista + '"]')
        .addClass('active')
        .attr('aria-pressed', 'true');
}

function dashboardFiscalesFiltrarRows() {
    var termino = dashboardFiscalesNormalizarTexto($('#dashboard_fiscales_buscar').val());

    if (!termino) {
        dashboardFiscalesFiltradas = dashboardFiscalesRows.slice();
        return;
    }

    dashboardFiscalesFiltradas = dashboardFiscalesRows.filter(function(row) {
        var texto = dashboardFiscalesNormalizarTexto([
            row.secuencia_facturacion_id || row.id || row.secuencia_id,
            row.empresa,
            row.documento,
            row.cai,
            row.prefijo,
            row.relleno,
            row.incremento,
            row.siguiente,
            row.inicio || row.rango_inicial,
            row.fin || row.rango_final,
            row.fecha_activacion || row.activacion,
            row.fecha_limite || row.fecha,
            row.fecha_registro || row.registro
        ].join(' '));

        return texto.indexOf(termino) !== -1;
    });
}

function dashboardFiscalesRenderHeaderDetalle() {
    return '' +
        '<div class="dashboard-fiscales-detail-header" role="row">' +
            '<div class="dashboard-fiscales-detail-header-cell" role="columnheader"><i class="fas fa-file-invoice"></i><span>Documento</span></div>' +
            '<div class="dashboard-fiscales-detail-header-cell" role="columnheader"><i class="fas fa-key"></i><span>Autorización / CAI</span></div>' +
            '<div class="dashboard-fiscales-detail-header-cell" role="columnheader"><i class="fas fa-list-ol"></i><span>Numeración</span></div>' +
            '<div class="dashboard-fiscales-detail-header-cell" role="columnheader"><i class="fas fa-calendar-alt"></i><span>Vigencia</span></div>' +
        '</div>';
}

function dashboardFiscalesRenderCard(row) {
    var empresa = secuenciaDashboardEscape(secuenciaDashboardValor(row.empresa, 'Empresa'));
    var documento = secuenciaDashboardDocumentoBadge(row.documento);
    var estado = secuenciaDashboardEstadoBadge(row.estado !== undefined ? row.estado : 1);
    var idSecuencia = secuenciaDashboardEscape(secuenciaDashboardValor(row.secuencia_facturacion_id || row.id || row.secuencia_id, 'N/D'));
    var cai = secuenciaDashboardEscape(secuenciaDashboardValor(row.cai, 'Sin CAI'));
    var prefijo = secuenciaDashboardEscape(secuenciaDashboardValor(row.prefijo, 'Sin prefijo'));
    var relleno = secuenciaDashboardEscape(secuenciaDashboardValor(row.relleno, '0'));
    var incremento = secuenciaDashboardEscape(secuenciaDashboardValor(row.incremento, '0'));
    var siguiente = secuenciaDashboardEscape(secuenciaDashboardValor(row.siguiente, '0'));
    var rangoInicial = secuenciaDashboardEscape(secuenciaDashboardValor(row.inicio || row.rango_inicial, '0'));
    var rangoFinal = secuenciaDashboardEscape(secuenciaDashboardValor(row.fin || row.rango_final, '0'));
    var disponibles = secuenciaDashboardDisponibles(row);
    var porcentaje = secuenciaDashboardPorcentajeUsado(row);
    var fechaActivacionRaw = row.fecha_activacion || row.activacion || '';
    var fechaLimiteRaw = row.fecha_limite || row.fecha || '';
    var fechaRegistroRaw = row.fecha_registro || row.registro || '';
    var fechaActivacion = secuenciaDashboardEscape(secuenciaDashboardValor(fechaActivacionRaw, 'No registrada'));
    var fechaLimite = secuenciaDashboardEscape(secuenciaDashboardValor(fechaLimiteRaw, 'No registrada'));
    var fechaRegistro = secuenciaDashboardEscape(secuenciaDashboardValor(fechaRegistroRaw, 'No registrada'));
    var vigencia = secuenciaDashboardVencimientoBadge(fechaLimiteRaw);
    var dias = secuenciaDashboardDiasRestantes(fechaLimiteRaw);
    var diasTexto = 'Sin información';

    if (dias !== null) {
        diasTexto = dias < 0 ? 'Venció hace ' + Math.abs(dias) + ' días' : 'Faltan ' + dias + ' días';
    }

    return '' +
        '<article class="dashboard-fiscal-item">' +
            '<div class="dashboard-fiscal-item-head">' +
                '<div class="dashboard-fiscal-identidad">' +
                    '<div class="dashboard-fiscal-icon"><i class="fas fa-file-invoice"></i></div>' +
                    '<div class="dashboard-fiscal-identidad-content">' +
                        '<div class="dashboard-fiscal-title-row">' +
                            '<h4>' + empresa + '</h4>' + estado +
                        '</div>' +
                        '<div class="dashboard-fiscal-doc-row">' + documento + '</div>' +
                        '<small><i class="fas fa-hashtag"></i> ID: ' + idSecuencia + '</small>' +
                    '</div>' +
                '</div>' +
            '</div>' +

            '<div class="dashboard-fiscal-grid">' +
                '<section class="dashboard-fiscal-section">' +
                    '<div class="dashboard-fiscal-section-title"><i class="fas fa-key"></i><span>Autorización / CAI</span></div>' +
                    '<div class="dashboard-fiscal-detail"><span class="dashboard-fiscal-detail-icon icon-cai"><i class="fas fa-key"></i></span><div><strong>CAI</strong><span class="dashboard-fiscal-wrap">' + cai + '</span></div></div>' +
                    '<div class="dashboard-fiscal-detail"><span class="dashboard-fiscal-detail-icon icon-prefix"><i class="fas fa-barcode"></i></span><div><strong>Prefijo</strong><span>' + prefijo + '</span></div></div>' +
                    '<div class="dashboard-fiscal-mini"><span>Relleno <strong>' + relleno + '</strong></span><span>Incremento <strong>' + incremento + '</strong></span></div>' +
                '</section>' +

                '<section class="dashboard-fiscal-section">' +
                    '<div class="dashboard-fiscal-section-title"><i class="fas fa-list-ol"></i><span>Numeración</span></div>' +
                    '<div class="dashboard-fiscal-next"><span>Siguiente</span><strong>' + siguiente + '</strong></div>' +
                    '<div class="dashboard-fiscal-range"><i class="fas fa-arrows-alt-h"></i><span>' + rangoInicial + ' - ' + rangoFinal + '</span></div>' +
                    '<div class="dashboard-fiscal-progress"><div class="dashboard-fiscal-progress-track"><span style="width:' + porcentaje + '%"></span></div><div class="dashboard-fiscal-progress-meta"><span>' + porcentaje + '% usado</span><strong>' + disponibles + ' disponibles</strong></div></div>' +
                '</section>' +

                '<section class="dashboard-fiscal-section">' +
                    '<div class="dashboard-fiscal-section-title"><i class="fas fa-calendar-alt"></i><span>Vigencia</span></div>' +
                    '<div class="dashboard-fiscal-detail"><span class="dashboard-fiscal-detail-icon icon-date"><i class="fas fa-calendar-check"></i></span><div><strong>Activación</strong><span>' + fechaActivacion + '</span></div></div>' +
                    '<div class="dashboard-fiscal-detail"><span class="dashboard-fiscal-detail-icon icon-date"><i class="fas fa-calendar-times"></i></span><div><strong>Límite</strong><span>' + fechaLimite + '</span></div></div>' +
                    '<div class="dashboard-fiscal-detail"><span class="dashboard-fiscal-detail-icon icon-date"><i class="fas fa-clock"></i></span><div><strong>Registro</strong><span>' + fechaRegistro + '</span></div></div>' +
                    '<div class="dashboard-fiscal-vigencia-row">' + vigencia + '<small>' + secuenciaDashboardEscape(diasTexto) + '</small></div>' +
                '</section>' +
            '</div>' +
        '</article>';
}

function dashboardFiscalesRenderMiniatura(row) {
    var empresa = secuenciaDashboardEscape(secuenciaDashboardValor(row.empresa, 'Empresa'));
    var documento = secuenciaDashboardDocumentoBadge(row.documento);
    var estado = secuenciaDashboardEstadoBadge(row.estado !== undefined ? row.estado : 1);
    var idSecuencia = secuenciaDashboardEscape(secuenciaDashboardValor(row.secuencia_facturacion_id || row.id || row.secuencia_id, 'N/D'));
    var cai = secuenciaDashboardEscape(secuenciaDashboardValor(row.cai, 'Sin CAI'));
    var prefijo = secuenciaDashboardEscape(secuenciaDashboardValor(row.prefijo, 'Sin prefijo'));
    var siguiente = secuenciaDashboardEscape(secuenciaDashboardValor(row.siguiente, '0'));
    var disponibles = secuenciaDashboardDisponibles(row);
    var porcentaje = secuenciaDashboardPorcentajeUsado(row);
    var fechaLimiteRaw = row.fecha_limite || row.fecha || '';
    var fechaLimite = secuenciaDashboardEscape(secuenciaDashboardValor(fechaLimiteRaw, 'No registrada'));
    var vigencia = secuenciaDashboardVencimientoBadge(fechaLimiteRaw);

    return '' +
        '<article class="dashboard-fiscal-mini-card">' +
            '<div class="dashboard-fiscal-mini-topline"></div>' +

            '<div class="dashboard-fiscal-mini-head">' +
                '<div class="dashboard-fiscal-icon"><i class="fas fa-file-invoice"></i></div>' +
                '<div class="dashboard-fiscal-mini-title">' +
                    '<div class="dashboard-fiscal-title-row"><h4>' + empresa + '</h4>' + estado + '</div>' +
                    '<div class="dashboard-fiscal-doc-row">' + documento + '</div>' +
                '</div>' +
            '</div>' +

            '<div class="dashboard-fiscal-mini-body">' +
                '<div class="dashboard-fiscal-mini-field"><span><i class="fas fa-key"></i> CAI</span><strong class="dashboard-fiscal-wrap">' + cai + '</strong></div>' +
                '<div class="dashboard-fiscal-mini-field"><span><i class="fas fa-barcode"></i> Prefijo</span><strong>' + prefijo + '</strong></div>' +
                '<div class="dashboard-fiscal-mini-field"><span><i class="fas fa-list-ol"></i> Siguiente</span><strong class="dashboard-fiscal-mini-number">' + siguiente + '</strong></div>' +
                '<div class="dashboard-fiscal-mini-field"><span><i class="fas fa-calendar-times"></i> Límite</span><strong>' + fechaLimite + '</strong></div>' +
            '</div>' +

            '<div class="dashboard-fiscal-mini-progress">' +
                '<div class="dashboard-fiscal-progress-track"><span style="width:' + porcentaje + '%"></span></div>' +
                '<div class="dashboard-fiscal-progress-meta"><span>' + porcentaje + '% usado</span><strong>' + disponibles + ' disponibles</strong></div>' +
            '</div>' +

            '<div class="dashboard-fiscal-mini-footer">' +
                '<small><i class="fas fa-hashtag"></i> ID: ' + idSecuencia + '</small>' +
                vigencia +
            '</div>' +
        '</article>';
}

function dashboardFiscalesRender() {
    dashboardFiscalesFiltrarRows();

    var total = dashboardFiscalesFiltradas.length;
    dashboardFiscalesPorPagina = parseInt($('#dashboard_fiscales_page_size').val(), 10) || 3;

    var totalPaginas = Math.max(1, Math.ceil(total / dashboardFiscalesPorPagina));

    if (dashboardFiscalesPagina > totalPaginas) {
        dashboardFiscalesPagina = totalPaginas;
    }

    var inicio = (dashboardFiscalesPagina - 1) * dashboardFiscalesPorPagina;
    var fin = Math.min(inicio + dashboardFiscalesPorPagina, total);
    var filasPagina = dashboardFiscalesFiltradas.slice(inicio, fin);
    var $listado = $('#dashboard_fiscales_listado');
    var html = '';

    $listado
        .toggleClass('vista-detalle', dashboardFiscalesVista === 'detalle')
        .toggleClass('vista-miniatura', dashboardFiscalesVista === 'miniatura');

    if (dashboardFiscalesVista === 'detalle' && total > 0) {
        html += dashboardFiscalesRenderHeaderDetalle();
        html += filasPagina.map(dashboardFiscalesRenderCard).join('');
    } else {
        html += filasPagina.map(dashboardFiscalesRenderMiniatura).join('');
    }

    $listado.html(html);

    $('#dashboard_fiscales_empty').toggleClass('d-none', total !== 0);
    $('#dashboard_fiscales_info').text(
        total === 0
            ? 'Mostrando 0 registros'
            : 'Mostrando ' + (inicio + 1) + ' a ' + fin + ' de ' + total + ' registros'
    );

    dashboardFiscalesRenderPaginacion(totalPaginas);

    if (typeof getPermisosTipoUsuarioAccesosTable === 'function' &&
        typeof getPrivilegioTipoUsuario === 'function') {
        getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
    }
}

function dashboardFiscalesRenderPaginacion(totalPaginas) {
    var $nav = $('#dashboard_fiscales_paginacion');
    $nav.empty();

    if (totalPaginas <= 1) {
        return;
    }

    function boton(texto, pagina, disabled, active, icono) {
        var clase = 'dashboard-page-btn';
        if (active) clase += ' active';
        if (disabled) clase += ' disabled';

        return '<button type="button" class="' + clase + '" data-page="' + pagina + '" ' + (disabled ? 'disabled' : '') + '>' +
            (icono ? '<i class="fas ' + icono + '"></i>' : '') + '<span>' + texto + '</span></button>';
    }

    var html = '';
    html += boton('Inicio', 1, dashboardFiscalesPagina === 1, false, 'fa-angle-double-left');
    html += boton('Anterior', dashboardFiscalesPagina - 1, dashboardFiscalesPagina === 1, false, 'fa-angle-left');

    var desde = Math.max(1, dashboardFiscalesPagina - 2);
    var hasta = Math.min(totalPaginas, desde + 4);
    desde = Math.max(1, hasta - 4);

    for (var i = desde; i <= hasta; i++) {
        html += boton(String(i), i, false, i === dashboardFiscalesPagina, '');
    }

    html += boton('Siguiente', dashboardFiscalesPagina + 1, dashboardFiscalesPagina === totalPaginas, false, 'fa-angle-right');
    html += boton('Final', totalPaginas, dashboardFiscalesPagina === totalPaginas, false, 'fa-angle-double-right');

    $nav.html(html);
}

function listar_secuencia_fiscales_dashboard() {
    $('#dashboard_fiscales_loading').removeClass('d-none');
    $('#dashboard_fiscales_empty').addClass('d-none');
    $('#dashboard_fiscales_listado').empty();

    $.ajax({
        method: 'POST',
        url: '<?php echo SERVERURL; ?>core/llenarDataTableSecuenciaFacturacion.php',
        dataType: 'json',
        timeout: 15000,
        success: function(json) {
            dashboardFiscalesRows = json && Array.isArray(json.data) ? json.data : [];
            dashboardFiscalesPagina = 1;
            dashboardFiscalesRender();
        },
        error: function(xhr) {
            dashboardFiscalesRows = [];
            dashboardFiscalesRender();
            if (typeof showNotify === 'function') {
                showNotify('error', 'Error', 'No se pudieron cargar los documentos fiscales del dashboard.');
            }
            console.error('Error al cargar documentos fiscales:', xhr.status, xhr.responseText);
        },
        complete: function() {
            $('#dashboard_fiscales_loading').addClass('d-none');
        }
    });
}

function dashboardFiscalesDatosExportacion() {
    return dashboardFiscalesRows.map(function(row) {
        var estado = parseInt(row.estado !== undefined ? row.estado : 1, 10) === 1 ? 'Activo' : 'Inactivo';
        var fechaLimite = secuenciaDashboardValor(row.fecha_limite || row.fecha, '');
        var dias = secuenciaDashboardDiasRestantes(fechaLimite);
        var vigencia = 'Sin información';

        if (dias !== null) {
            if (dias < 0) {
                vigencia = 'Vencida hace ' + Math.abs(dias) + ' días';
            } else if (dias === 0) {
                vigencia = 'Vence hoy';
            } else {
                vigencia = 'Faltan ' + dias + ' días';
            }
        }

        return {
            empresa: secuenciaDashboardValor(row.empresa, ''),
            documento: secuenciaDashboardValor(row.documento, ''),
            estado: estado,
            cai: secuenciaDashboardValor(row.cai, 'Sin CAI'),
            prefijo: secuenciaDashboardValor(row.prefijo, 'Sin prefijo'),
            relleno: secuenciaDashboardValor(row.relleno, '0'),
            incremento: secuenciaDashboardValor(row.incremento, '1'),
            siguiente: secuenciaDashboardValor(row.siguiente, '0'),
            rango_inicial: secuenciaDashboardValor(row.inicio || row.rango_inicial, ''),
            rango_final: secuenciaDashboardValor(row.fin || row.rango_final, ''),
            disponibles: secuenciaDashboardDisponibles(row),
            porcentaje: secuenciaDashboardPorcentajeUsado(row),
            activacion: secuenciaDashboardValor(row.fecha_activacion || row.activacion, ''),
            limite: fechaLimite,
            vigencia: vigencia
        };
    });
}

function dashboardFiscalesFechaReporte() {
    if (typeof convertDateFormat === 'function' && typeof today === 'function') {
        return convertDateFormat(today());
    }

    return new Date().toLocaleDateString('es-HN');
}

function dashboardFiscalesNombreArchivo(extension) {
    var fecha = new Date();
    var yyyy = fecha.getFullYear();
    var mm = String(fecha.getMonth() + 1).padStart(2, '0');
    var dd = String(fecha.getDate()).padStart(2, '0');
    return 'Documentos_Fiscales_' + yyyy + '-' + mm + '-' + dd + '.' + extension;
}

function dashboardFiscalesXmlEscape(value) {
    return String(value === null || value === undefined ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&apos;');
}

function dashboardFiscalesExcelColName(index) {
    var name = '';
    var n = index + 1;

    while (n > 0) {
        var mod = (n - 1) % 26;
        name = String.fromCharCode(65 + mod) + name;
        n = Math.floor((n - 1) / 26);
    }

    return name;
}

function dashboardFiscalesExcelCell(ref, value, styleId, numeric) {
    if (numeric) {
        var numero = Number(value);
        if (!isNaN(numero)) {
            return '<c r="' + ref + '" s="' + styleId + '"><v>' + numero + '</v></c>';
        }
    }

    var raw = String(value === null || value === undefined ? '' : value);
    var preserve = /^\s|\s$/.test(raw) ? ' xml:space="preserve"' : '';

    return '<c r="' + ref + '" s="' + styleId + '" t="inlineStr"><is><t' + preserve + '>' + dashboardFiscalesXmlEscape(raw) + '</t></is></c>';
}

function dashboardFiscalesGenerarXlsx(rows) {
    if (typeof JSZip === 'undefined') {
        return null;
    }

    var headers = [
        'Empresa', 'Documento', 'Estado', 'CAI', 'Prefijo', 'Relleno',
        'Incremento', 'Siguiente', 'Rango Inicial', 'Rango Final',
        'Disponibles', '% Usado', 'Activación', 'Límite', 'Vigencia'
    ];

    var totalActivas = rows.filter(function(row) {
        return String(row.estado || '').toLowerCase() === 'activo';
    }).length;

    var totalCai = rows.filter(function(row) {
        return row.cai && String(row.cai).trim() !== '' && String(row.cai).toLowerCase() !== 'sin cai';
    }).length;

    var totalDisponibles = rows.reduce(function(total, row) {
        return total + (parseInt(row.disponibles, 10) || 0);
    }, 0);

    var totalPorVencer = rows.filter(function(row) {
        var texto = String(row.vigencia || '').toLowerCase();
        var match = texto.match(/faltan\s+(\d+)/);
        return match && parseInt(match[1], 10) <= 30;
    }).length;

    var dataRows = rows.map(function(row) {
        return [
            row.empresa,
            row.documento,
            row.estado,
            row.cai,
            row.prefijo,
            row.relleno,
            row.incremento,
            row.siguiente,
            row.rango_inicial,
            row.rango_final,
            row.disponibles,
            row.porcentaje,
            row.activacion,
            row.limite,
            row.vigencia
        ];
    });

    var lastCol = dashboardFiscalesExcelColName(headers.length - 1);
    var headerRow = 7;
    var lastRow = Math.max(headerRow, headerRow + dataRows.length);
    var sheetRows = [];

    sheetRows.push('<row r="1" ht="30" customHeight="1">' +
        dashboardFiscalesExcelCell('A1', 'IZZY • DOCUMENTOS FISCALES', 1, false) +
    '</row>');

    sheetRows.push('<row r="2" ht="20" customHeight="1">' +
        dashboardFiscalesExcelCell('A2', 'Control de secuencias fiscales, correlativos y vigencia • Generado: ' + dashboardFiscalesFechaReporte(), 2, false) +
    '</row>');

    sheetRows.push('<row r="3" ht="18" customHeight="1">' +
        dashboardFiscalesExcelCell('A3', 'REGISTROS', 6, false) +
        dashboardFiscalesExcelCell('D3', 'ACTIVAS', 6, false) +
        dashboardFiscalesExcelCell('G3', 'CON CAI', 6, false) +
        dashboardFiscalesExcelCell('J3', 'DISPONIBLES', 6, false) +
        dashboardFiscalesExcelCell('M3', 'POR VENCER', 6, false) +
    '</row>');

    sheetRows.push('<row r="4" ht="26" customHeight="1">' +
        dashboardFiscalesExcelCell('A4', rows.length, 7, true) +
        dashboardFiscalesExcelCell('D4', totalActivas, 7, true) +
        dashboardFiscalesExcelCell('G4', totalCai, 7, true) +
        dashboardFiscalesExcelCell('J4', totalDisponibles, 7, true) +
        dashboardFiscalesExcelCell('M4', totalPorVencer, 7, true) +
    '</row>');

    sheetRows.push('<row r="5"></row>');
    sheetRows.push('<row r="6" ht="18" customHeight="1">' +
        dashboardFiscalesExcelCell('A6', 'Detalle de documentos fiscales', 8, false) +
    '</row>');

    var headerCells = headers.map(function(header, index) {
        return dashboardFiscalesExcelCell(dashboardFiscalesExcelColName(index) + headerRow, header, 3, false);
    }).join('');

    sheetRows.push('<row r="' + headerRow + '" ht="28" customHeight="1">' + headerCells + '</row>');

    dataRows.forEach(function(row, rowIndex) {
        var excelRow = headerRow + rowIndex + 1;
        var cells = row.map(function(value, colIndex) {
            var numeric = colIndex === 5 || colIndex === 6 || colIndex === 7 || colIndex === 10 || colIndex === 11;
            var style = 4;

            if (colIndex === 2) {
                style = String(value || '').toLowerCase() === 'activo' ? 9 : 10;
            } else if (numeric) {
                style = 5;
            }

            return dashboardFiscalesExcelCell(
                dashboardFiscalesExcelColName(colIndex) + excelRow,
                value,
                style,
                numeric
            );
        }).join('');

        sheetRows.push('<row r="' + excelRow + '" ht="22" customHeight="1">' + cells + '</row>');
    });

    var sheetXml =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<dimension ref="A1:' + lastCol + lastRow + '"/>' +
            '<sheetViews><sheetView workbookViewId="0" showGridLines="0">' +
                '<pane ySplit="7" topLeftCell="A8" activePane="bottomLeft" state="frozen"/>' +
                '<selection pane="bottomLeft" activeCell="A8" sqref="A8"/>' +
            '</sheetView></sheetViews>' +
            '<sheetFormatPr defaultRowHeight="15"/>' +
            '<cols>' +
                '<col min="1" max="1" width="24" customWidth="1"/>' +
                '<col min="2" max="2" width="24" customWidth="1"/>' +
                '<col min="3" max="3" width="12" customWidth="1"/>' +
                '<col min="4" max="4" width="42" customWidth="1"/>' +
                '<col min="5" max="5" width="20" customWidth="1"/>' +
                '<col min="6" max="8" width="12" customWidth="1"/>' +
                '<col min="9" max="10" width="17" customWidth="1"/>' +
                '<col min="11" max="12" width="14" customWidth="1"/>' +
                '<col min="13" max="14" width="16" customWidth="1"/>' +
                '<col min="15" max="15" width="24" customWidth="1"/>' +
            '</cols>' +
            '<sheetData>' + sheetRows.join('') + '</sheetData>' +
            '<autoFilter ref="A' + headerRow + ':' + lastCol + lastRow + '"/>' +
            '<mergeCells count="12">' +
                '<mergeCell ref="A1:' + lastCol + '1"/>' +
                '<mergeCell ref="A2:' + lastCol + '2"/>' +
                '<mergeCell ref="A3:C3"/><mergeCell ref="A4:C4"/>' +
                '<mergeCell ref="D3:F3"/><mergeCell ref="D4:F4"/>' +
                '<mergeCell ref="G3:I3"/><mergeCell ref="G4:I4"/>' +
                '<mergeCell ref="J3:L3"/><mergeCell ref="J4:L4"/>' +
                '<mergeCell ref="M3:O3"/><mergeCell ref="M4:O4"/>' +
            '</mergeCells>' +
            '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>' +
            '<pageSetup orientation="landscape" paperSize="1" fitToWidth="1" fitToHeight="0"/>' +
        '</worksheet>';

    var stylesXml =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<fonts count="7">' +
                '<font><sz val="10"/><name val="Calibri"/><family val="2"/></font>' +
                '<font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
                '<font><sz val="9"/><color rgb="FF5E6C84"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
                '<font><sz val="10"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="8"/><color rgb="FF6B778C"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="15"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
            '</fonts>' +
            '<fills count="7">' +
                '<fill><patternFill patternType="none"/></fill>' +
                '<fill><patternFill patternType="gray125"/></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FF172B4D"/><bgColor indexed="64"/></patternFill></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FF0EA5A8"/><bgColor indexed="64"/></patternFill></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FFF7F9FC"/><bgColor indexed="64"/></patternFill></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FFE3FCEF"/><bgColor indexed="64"/></patternFill></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FFFFEBE6"/><bgColor indexed="64"/></patternFill></fill>' +
            '</fills>' +
            '<borders count="2">' +
                '<border><left/><right/><top/><bottom/><diagonal/></border>' +
                '<border><left style="thin"><color rgb="FFDDE3EA"/></left><right style="thin"><color rgb="FFDDE3EA"/></right><top style="thin"><color rgb="FFDDE3EA"/></top><bottom style="thin"><color rgb="FFDDE3EA"/></bottom><diagonal/></border>' +
            '</borders>' +
            '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' +
            '<cellXfs count="11">' +
                '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' +
                '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="2" fillId="4" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="6" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="5" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="5" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="6" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
            '</cellXfs>' +
            '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>' +
        '</styleSheet>';

    var workbookXml =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
            '<bookViews><workbookView activeTab="0"/></bookViews>' +
            '<sheets><sheet name="Documentos Fiscales" sheetId="1" r:id="rId1"/></sheets>' +
        '</workbook>';

    var workbookRels =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' +
            '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' +
        '</Relationships>';

    var rootRels =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' +
        '</Relationships>';

    var contentTypes =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' +
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' +
            '<Default Extension="xml" ContentType="application/xml"/>' +
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' +
            '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' +
            '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' +
        '</Types>';

    var zip = new JSZip();
    zip.file('[Content_Types].xml', contentTypes);
    zip.folder('_rels').file('.rels', rootRels);
    zip.folder('xl').file('workbook.xml', workbookXml);
    zip.folder('xl').file('styles.xml', stylesXml);
    zip.folder('xl').folder('_rels').file('workbook.xml.rels', workbookRels);
    zip.folder('xl').folder('worksheets').file('sheet1.xml', sheetXml);

    var opcionesZip = {
        type: 'blob',
        mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        compression: 'DEFLATE'
    };

    if (typeof zip.generateAsync === 'function') {
        return zip.generateAsync(opcionesZip);
    }

    if (typeof zip.generate === 'function') {
        try {
            return Promise.resolve(zip.generate(opcionesZip));
        } catch (error) {
            return Promise.reject(error);
        }
    }

    return Promise.reject(new Error('La versión de JSZip cargada no soporta generateAsync() ni generate().'));
}

function dashboardFiscalesDescargarBlob(blob, nombreArchivo) {
    var url = URL.createObjectURL(blob);
    var link = document.createElement('a');

    link.href = url;
    link.download = nombreArchivo;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    setTimeout(function() {
        URL.revokeObjectURL(url);
    }, 1000);
}

function dashboardFiscalesExportarExcel() {
    var rows = dashboardFiscalesDatosExportacion();

    if (!rows.length) {
        if (typeof showNotify === 'function') {
            showNotify('warning', 'Advertencia', 'No hay registros para exportar.');
        }
        return;
    }

    var promesa = dashboardFiscalesGenerarXlsx(rows);

    if (!promesa) {
        if (typeof showNotify === 'function') {
            showNotify('error', 'Excel no disponible', 'No se encontró JSZip. Verifique que la librería esté cargada antes de dashboard.php.');
        }
        return;
    }

    promesa.then(function(blob) {
        dashboardFiscalesDescargarBlob(blob, dashboardFiscalesNombreArchivo('xlsx'));

        if (typeof showNotify === 'function') {
            showNotify('success', 'Excel generado', 'El reporte de documentos fiscales se generó correctamente.');
        }
    }).catch(function(error) {
        console.error('Error generando Excel de documentos fiscales:', error);

        if (typeof showNotify === 'function') {
            showNotify('error', 'Error', 'No se pudo generar el archivo Excel.');
        }
    });
}

function dashboardFiscalesAbrirPdfEnModal(pdfGenerator, tituloModal, nombreArchivo) {
    if (typeof abrirModalPdfPublico !== 'function') {
        if (typeof showNotify === 'function') {
            showNotify(
                'error',
                'Vista previa no disponible',
                'No se encontró el modal público de PDF.'
            );
        }
        return;
    }

    var abrir = function(url) {
        abrirModalPdfPublico(url, tituloModal, nombreArchivo);

        if (typeof showNotify === 'function') {
            showNotify(
                'success',
                'PDF generado',
                'El reporte está listo para visualizarse.'
            );
        }
    };

    if (pdfGenerator && typeof pdfGenerator.getBlob === 'function') {
        pdfGenerator.getBlob(function(blob) {
            var url = URL.createObjectURL(blob);
            abrir(url);
        });
        return;
    }

    if (pdfGenerator && typeof pdfGenerator.getDataUrl === 'function') {
        pdfGenerator.getDataUrl(function(dataUrl) {
            abrir(dataUrl);
        });
        return;
    }

    if (pdfGenerator && typeof pdfGenerator.getBase64 === 'function') {
        pdfGenerator.getBase64(function(base64) {
            abrir('data:application/pdf;base64,' + base64);
        });
        return;
    }

    if (typeof showNotify === 'function') {
        showNotify(
            'error',
            'PDF no disponible',
            'La versión actual del componente PDF no permite generar la vista previa.'
        );
    }
}


function dashboardFiscalesExportarPDF() {
    var rows = dashboardFiscalesDatosExportacion();

    if (!rows.length) {
        if (typeof showNotify === 'function') {
            showNotify('warning', 'Sin información', 'No hay documentos fiscales para exportar.');
        }
        return;
    }

    if (typeof pdfMake === 'undefined') {
        if (typeof showNotify === 'function') {
            showNotify('error', 'PDF no disponible', 'No se encontró pdfMake.');
        }
        return;
    }

    var activos = rows.filter(function(row){return String(row.estado).toLowerCase()==='activo';}).length;
    var conCai = rows.filter(function(row){return row.cai && row.cai !== 'Sin CAI';}).length;
    var disponibles = rows.reduce(function(acc,row){return acc + (parseInt(row.disponibles,10)||0);},0);
    var logo=(typeof imagen!=='undefined'&&imagen)?{image:imagen,width:50,height:24,alignment:'center',margin:[0,1,0,0]}:{text:'IZZY',fontSize:16,bold:true,color:'#FFFFFF',alignment:'center',margin:[0,4,0,0]};

    var encabezado={table:{widths:[70,'*',150],body:[[
        {border:[false,false,false,false],fillColor:'#17324D',margin:[12,10,0,10],stack:[logo]},
        {border:[false,false,false,false],fillColor:'#17324D',margin:[0,10,0,10],stack:[
            {text:'DOCUMENTOS FISCALES',fontSize:16,bold:true,color:'#FFFFFF'},
            {text:'Control de secuencias, vigencia y disponibilidad fiscal',fontSize:7.5,color:'#D8E5F0',margin:[0,2,0,0]}
        ]},
        {border:[false,false,false,false],fillColor:'#17324D',margin:[0,10,12,10],stack:[
            {text:'REPORTE EJECUTIVO',fontSize:6.5,bold:true,color:'#72E2E5',alignment:'right'},
            {text:new Date().toLocaleDateString('es-HN'),fontSize:9,bold:true,color:'#FFFFFF',alignment:'right',margin:[0,3,0,0]},
            {text:rows.length+' registro(s) filtrado(s)',fontSize:6.5,color:'#D8E5F0',alignment:'right',margin:[0,2,0,0]}
        ]}
    ]]},layout:{hLineWidth:function(){return 0;},vLineWidth:function(){return 0;}},margin:[0,0,0,10]};

    var filtros={table:{widths:['*'],body:[[{text:'Filtros aplicados: se respetan los filtros actuales del panel de Documentos Fiscales.',fontSize:6.8,color:'#52627A',margin:[10,7,10,7],fillColor:'#F7F9FC'}]]},
        layout:{hLineColor:function(){return '#DDE3EA';},vLineColor:function(){return '#DDE3EA';},hLineWidth:function(){return 0.6;},vLineWidth:function(){return 0.6;}},margin:[0,0,0,10]};

    var resumen={table:{widths:['*','*','*','*'],body:[[
        {fillColor:'#F7F9FC',margin:[8,7,8,7],stack:[{text:'REGISTROS',fontSize:6.3,bold:true,color:'#6B778C'},{text:String(rows.length),fontSize:13,bold:true,color:'#172B4D',margin:[0,2,0,0]}]},
        {fillColor:'#F7F9FC',margin:[8,7,8,7],stack:[{text:'ACTIVAS',fontSize:6.3,bold:true,color:'#6B778C'},{text:String(activos),fontSize:13,bold:true,color:'#172B4D',margin:[0,2,0,0]}]},
        {fillColor:'#F7F9FC',margin:[8,7,8,7],stack:[{text:'CON CAI',fontSize:6.3,bold:true,color:'#6B778C'},{text:String(conCai),fontSize:13,bold:true,color:'#172B4D',margin:[0,2,0,0]}]},
        {fillColor:'#F7F9FC',margin:[8,7,8,7],stack:[{text:'DISPONIBLES',fontSize:6.3,bold:true,color:'#6B778C'},{text:String(disponibles),fontSize:13,bold:true,color:'#14804A',margin:[0,2,0,0]}]}
    ]]},layout:{hLineColor:function(){return '#DDE3EA';},vLineColor:function(){return '#DDE3EA';},hLineWidth:function(){return 0.6;},vLineWidth:function(){return 0.6;}},margin:[0,0,0,12]};

    var vistaPdfFiscales = dashboardFiscalesState && dashboardFiscalesState.view ? dashboardFiscalesState.view : 'detalle';
    var contenido=[];

    if (vistaPdfFiscales === 'miniatura') {
        function card(row) {
            var estadoColor=String(row.estado||'').toLowerCase()==='activo'?'#14804A':'#C9372C';
            return {table:{widths:['*'],body:[[{margin:[10,9,10,9],stack:[
                {columns:[{width:'*',stack:[{text:row.empresa||'Empresa',fontSize:10,bold:true,color:'#172B4D'},{text:row.documento||'Documento',fontSize:7,color:'#6B778C',margin:[0,2,0,0]}]},{width:'auto',text:row.estado||'',fontSize:7,bold:true,color:estadoColor}]},
                {canvas:[{type:'line',x1:0,y1:0,x2:250,y2:0,lineWidth:0.6,lineColor:'#DDE3EA'}],margin:[0,7,0,7]},
                {columns:[
                    {width:'50%',stack:[{text:'CAI',fontSize:6.2,bold:true,color:'#6B778C'},{text:row.cai||'Sin CAI',fontSize:7.5,bold:true,color:'#172B4D',margin:[0,2,0,0]},{text:'PREFIJO',fontSize:6.2,bold:true,color:'#6B778C',margin:[0,8,0,0]},{text:row.prefijo||'—',fontSize:7.5,bold:true,color:'#172B4D',margin:[0,2,0,0]}]},
                    {width:'50%',stack:[{text:'SIGUIENTE',fontSize:6.2,bold:true,color:'#6B778C'},{text:String(row.siguiente||'0'),fontSize:9,bold:true,color:'#172B4D',margin:[0,2,0,0]},{text:'DISPONIBLES',fontSize:6.2,bold:true,color:'#6B778C',margin:[0,8,0,0]},{text:String(row.disponibles||'0'),fontSize:9,bold:true,color:'#14804A',margin:[0,2,0,0]}]}
                ]},
                {text:'Rango: '+String(row.rango_inicial||'')+' - '+String(row.rango_final||''),fontSize:6.5,color:'#6B778C',margin:[0,8,0,0]},
                {text:'Vigencia: '+(row.vigencia||'—'),fontSize:7,bold:true,color:estadoColor,margin:[0,3,0,0]}
            ]}]]},layout:{hLineColor:function(){return '#DDE3EA';},vLineColor:function(){return '#DDE3EA';},hLineWidth:function(){return 0.7;},vLineWidth:function(){return 0.7;}}};
        }
        for(var i=0;i<rows.length;i+=2){
            contenido.push({columns:[{width:'*',stack:[card(rows[i])]},{width:10,text:''},rows[i+1]?{width:'*',stack:[card(rows[i+1])]}:{width:'*',text:''}],margin:[0,0,0,9]});
        }
    } else {
        var body=[[{text:'EMPRESA',style:'th',fillColor:'#17324D'},{text:'DOCUMENTO',style:'th',fillColor:'#17324D'},{text:'ESTADO',style:'th',fillColor:'#17324D'},{text:'CAI',style:'th',fillColor:'#17324D'},{text:'PREFIJO',style:'th',fillColor:'#17324D'},{text:'SIGUIENTE',style:'th',fillColor:'#17324D'},{text:'RANGO',style:'th',fillColor:'#17324D'},{text:'DISPONIBLES',style:'th',fillColor:'#17324D'},{text:'VIGENCIA',style:'th',fillColor:'#17324D'}]];
        rows.forEach(function(row,index){
            var fill=index%2===0?'#FFFFFF':'#F7F9FC';
            var estadoColor=String(row.estado||'').toLowerCase()==='activo'?'#14804A':'#C9372C';
            body.push([
                {text:row.empresa||'Empresa',fillColor:fill,bold:true},
                {text:row.documento||'Documento',fillColor:fill},
                {text:row.estado||'',fillColor:fill,color:estadoColor,bold:true},
                {text:row.cai||'Sin CAI',fillColor:fill},
                {text:row.prefijo||'—',fillColor:fill},
                {text:String(row.siguiente||'0'),fillColor:fill,alignment:'center',bold:true},
                {text:String(row.rango_inicial||'')+' - '+String(row.rango_final||''),fillColor:fill},
                {text:String(row.disponibles||'0'),fillColor:fill,alignment:'center',color:'#14804A',bold:true},
                {text:row.vigencia||'—',fillColor:fill,color:estadoColor,bold:true}
            ]);
        });
        contenido=[{table:{headerRows:1,widths:[82,74,48,112,62,46,92,52,'*'],body:body},layout:{
            hLineColor:function(){return '#DDE3EA';},vLineColor:function(){return '#DDE3EA';},hLineWidth:function(){return 0.55;},vLineWidth:function(){return 0.55;},
            paddingLeft:function(){return 4;},paddingRight:function(){return 4;},paddingTop:function(){return 5;},paddingBottom:function(){return 5;}
        }}];
    }

    var doc={pageSize:'LETTER',pageOrientation:'landscape',pageMargins:[28,28,28,34],
        header:function(){return{margin:[28,12,28,0],canvas:[{type:'line',x1:0,y1:0,x2:736,y2:0,lineWidth:2,lineColor:'#0EA5A8'}]};},
        footer:function(currentPage,pageCount){return{margin:[28,8,28,0],columns:[{text:'IZZY • Documentos Fiscales',fontSize:7,color:'#7A869A'},{text:'Página '+currentPage+' de '+pageCount,fontSize:7,color:'#7A869A',alignment:'right'}]};},
        content:[encabezado,filtros,resumen,{text:vistaPdfFiscales==='miniatura'?'VISTA MINIATURA':'VISTA DETALLE',fontSize:7,bold:true,color:'#17324D',margin:[0,1,0,7]}].concat(contenido),
        styles:{th:{fontSize:6.2,bold:true,color:'#FFFFFF',alignment:'center'}},
        defaultStyle:{fontSize:8,color:'#253858'}
    };

    try {
        var pdfGenerator=pdfMake.createPdf(doc);
        dashboardFiscalesAbrirPdfEnModal(pdfGenerator,'Reporte de Documentos Fiscales',dashboardFiscalesNombreArchivo('pdf'));
    } catch(error) {
        console.error('Error generando PDF de documentos fiscales:',error);
        if(typeof showNotify==='function'){showNotify('error','Error','No se pudo generar el reporte PDF.');}
    }
}

function setupDashboardFiscales() {
    dashboardFiscalesInicializarVista();

    $('#btn_dashboard_fiscales_actualizar').off('click').on('click', listar_secuencia_fiscales_dashboard);
    $('#btn_dashboard_fiscales_excel').off('click').on('click', dashboardFiscalesExportarExcel);
    $('#btn_dashboard_fiscales_pdf').off('click').on('click', dashboardFiscalesExportarPDF);

    $('#dashboard_fiscales_page_size').off('change').on('change', function() {
        var valor = parseInt($(this).val(), 10);

        dashboardFiscalesPorPagina = isNaN(valor) || valor <= 0
            ? (dashboardFiscalesVista === 'miniatura' ? 6 : 3)
            : valor;

        if (dashboardFiscalesVista === 'miniatura') {
            dashboardFiscalesPorPaginaMiniatura = dashboardFiscalesPorPagina;
        } else {
            dashboardFiscalesPorPaginaDetalle = dashboardFiscalesPorPagina;
        }

        dashboardFiscalesPagina = 1;
        dashboardFiscalesRender();
    });

    $('#dashboard_fiscales_buscar').off('input.dashboardFiscales').on('input.dashboardFiscales', function() {
        dashboardFiscalesPagina = 1;
        dashboardFiscalesRender();
    });

    $('.dashboard-fiscales-view-btn').off('click.dashboardFiscalesVista').on('click.dashboardFiscalesVista', function() {
        dashboardFiscalesCambiarVista($(this).data('view'));
    });

    $('#dashboard_fiscales_paginacion').off('click', '.dashboard-page-btn').on('click', '.dashboard-page-btn', function() {
        if ($(this).prop('disabled')) return;
        dashboardFiscalesPagina = parseInt($(this).data('page'), 10) || 1;
        dashboardFiscalesRender();
    });
}


/****************************************************************************************************************************************************************/
// MOSTRAR / OCULTAR SECCIONES DEL DASHBOARD + PERSISTENCIA EN LOCALSTORAGE
/****************************************************************************************************************************************************************/

function dashboardStorageGet(key, defaultValue) {
    try {
        var value = localStorage.getItem(key);

        if (value === null) {
            return defaultValue;
        }

        return value === '1';
    } catch (error) {
        console.warn('No se pudo leer el estado del dashboard:', key, error);
        return defaultValue;
    }
}

function dashboardStorageSet(key, visible) {
    try {
        localStorage.setItem(key, visible ? '1' : '0');
    } catch (error) {
        console.warn('No se pudo guardar el estado del dashboard:', key, error);
    }
}

function dashboardActualizarBotonSeccion($button, visible) {
    var $icon = $button.find('i').first();
    var $text = $button.find('span').first();

    $icon
        .removeClass('fa-chevron-down fa-chevron-up')
        .addClass(visible ? 'fa-chevron-up' : 'fa-chevron-down');

    if ($text.length) {
        $text.text(visible ? 'Ocultar' : 'Mostrar');
    }

    $button.attr('aria-expanded', visible ? 'true' : 'false');
}

function dashboardActualizarBotonGrafico($button, visible) {
    var $icon = $button.find('i').first();

    $icon
        .removeClass('fa-chevron-down fa-chevron-up')
        .addClass(visible ? 'fa-chevron-up' : 'fa-chevron-down');

    $button.attr({
        'aria-expanded': visible ? 'true' : 'false',
        'title': visible ? 'Ocultar gráfico' : 'Mostrar gráfico',
        'data-original-title': visible ? 'Ocultar gráfico' : 'Mostrar gráfico'
    });

    if ($button.tooltip) {
        $button.tooltip('dispose').tooltip();
    }
}

function dashboardRedimensionarGraficos() {
    var charts = [
        window.chartVentas,
        window.chartCompras,
        window.chartTopProductosAnoActual
    ];

    charts.forEach(function(chart) {
        if (chart && typeof chart.resize === 'function') {
            try {
                chart.resize();
            } catch (error) {
                console.warn('No se pudo redimensionar un gráfico del dashboard:', error);
            }
        }
    });
}

function dashboardAplicarEstadoPersistente($button, tipoBoton) {
    var selector = $button.data('target');
    var storageKey = $button.data('storage-key');

    if (!selector || !storageKey) {
        return;
    }

    var $target = $(selector);

    if (!$target.length) {
        return;
    }

    var visible = dashboardStorageGet(storageKey, true);

    $target.toggle(visible);

    if (tipoBoton === 'grafico') {
        dashboardActualizarBotonGrafico($button, visible);
    } else {
        dashboardActualizarBotonSeccion($button, visible);
    }
}

function dashboardConfigurarBotonesMostrarOcultar() {
    $('.dashboard-toggle-btn').each(function() {
        dashboardAplicarEstadoPersistente($(this), 'seccion');
    });

    $('.dashboard-chart-toggle').each(function() {
        dashboardAplicarEstadoPersistente($(this), 'grafico');
    });

    $('.dashboard-toggle-btn')
        .off('click.dashboardToggle')
        .on('click.dashboardToggle', function() {
            var $button = $(this);
            var selector = $button.data('target');
            var storageKey = $button.data('storage-key');
            var $target = $(selector);

            if (!$target.length) {
                return;
            }

            var visible = !$target.is(':visible');

            $target.stop(true, true).slideToggle(180, function() {
                if (visible) {
                    dashboardRedimensionarGraficos();
                }
            });

            dashboardStorageSet(storageKey, visible);
            dashboardActualizarBotonSeccion($button, visible);
        });

    $('.dashboard-chart-toggle')
        .off('click.dashboardChartToggle')
        .on('click.dashboardChartToggle', function() {
            var $button = $(this);
            var selector = $button.data('target');
            var storageKey = $button.data('storage-key');
            var $target = $(selector);

            if (!$target.length) {
                return;
            }

            var visible = !$target.is(':visible');

            $target.stop(true, true).slideToggle(180, function() {
                if (visible) {
                    dashboardRedimensionarGraficos();
                }
            });

            dashboardStorageSet(storageKey, visible);
            dashboardActualizarBotonGrafico($button, visible);
        });
}

/****************************************************************************************************************************************************************/
// INICIALIZACIÓN DASHBOARD
/****************************************************************************************************************************************************************/

$(document).ready(function() {
    dashboardConfigurarBotonesMostrarOcultar();

    setTotalCustomers();
    setTotalSuppliers();
    setTotalBills();
    setTotalPurchases();
    getMesFacturaCompra();

    setupDashboardFiscales();
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