<script>
//DASHBOARD
function setTotalCustomers() {
    var url = '<?php echo SERVERURL;?>core/getTotalCustomers.php';

    var isv;
    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        success: function(data) {
            $('#main_clientes').html(data);
        }
    });
    return isv;
}

function setTotalSuppliers() {
    var url = '<?php echo SERVERURL;?>core/getTotalSuppliers.php';

    var isv;
    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        success: function(data) {
            $('#main_proveedores').html(data);
        }
    });
    return isv;
}

function setTotalBills() {
    var url = '<?php echo SERVERURL;?>core/getTotalBills.php';

    var isv;
    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        success: function(data) {
            $('#main_facturas').html("L. " + data);
        }
    });
    return isv;
}

function setTotalPurchases() {
    var url = '<?php echo SERVERURL;?>core/getTotalPurchases.php';

    var isv;
    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        success: function(data) {
            $('#main_compras').html("L. " + data);
        }
    });
    return isv;
}

$(document).ready(function() {
    // DASHBOARD
    setTotalCustomers();
    setTotalSuppliers();
    setTotalBills();
    setTotalPurchases();
    getMesFacturaCompra();
    listar_secuencia_fiscales_dashboard();
    $(window).scrollTop(0);

    setInterval('setTotalCustomers()', 120000);
    setInterval('setTotalSuppliers()', 120000);
    setInterval('setTotalBills()', 120000);
    setInterval('setTotalPurchases()', 120000);

    // GRAPHICS
    showVentasAnuales();
    showComprasAnuales();

    setInterval('showVentasAnuales()', 120000);
    setInterval('showComprasAnuales()', 120000);
});

// Configurar selectores de meses para top productos
function setupMonthSelectors() {
    $('.btn-year-productos').on('click', function() {
        $('.btn-year-productos').removeClass('active');
        $(this).addClass('active');
        var months = $(this).data('months');
        showTopProductos(months);
    });
}

// Función principal para mostrar top productos (versión con barras agrupadas)
function showTopProductos(months = 3) {
    var url = '<?php echo SERVERURL; ?>core/getTopProductos.php?months=' + months;

    $.ajax({
        type: 'GET',
        url: url,
        success: function(data) {
            var datos = JSON.parse(data);
            var meses = [];
            var productos = {};

            // Procesar datos
            datos.forEach(function(item) {
                if (!meses.includes(item.mes)) {
                    meses.push(item.mes);
                }
                if (!productos[item.producto]) {
                    productos[item.producto] = {};
                }
                productos[item.producto][item.mes] = item.total_vendido || 0;
            });

            // Ordenar productos por cantidad total vendida
            var productosOrdenados = Object.keys(productos).sort(function(a, b) {
                var totalA = Object.values(productos[a]).reduce((sum, current) => sum + current, 0);
                var totalB = Object.values(productos[b]).reduce((sum, current) => sum + current, 0);
                return totalB - totalA;
            });

            // Tomar solo los top 5
            var top5Productos = productosOrdenados.slice(0, 5);

            // Preparar datasets para Chart.js (barras agrupadas)
            var datasets = top5Productos.map(function(producto, index) {
                var colores = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'];
                // Crear array de datos para cada mes
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

            var ctx = document.getElementById('graphTopProductosporAno').getContext('2d');

            // Destruir gráfico anterior si existe
            if (window.chartTopProductosAnoActual) {
                window.chartTopProductosAnoActual.destroy();
            }

            // Crear nuevo gráfico con opciones para barras agrupadas
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
                        // Añadido para mostrar valores en las barras
                        datalabels: {
                            anchor: 'center',
                            align: 'center',
                            formatter: function(value) {
                                // Formatear el valor con separadores de miles y decimales
                                return value.toLocaleString('es-HN', {
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
                                return context.dataset.data[context.dataIndex] > 0; // Solo mostrar si el valor es > 0
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

            // Generar leyenda dinámica
            generateDynamicLegend('top-products-legend', datasets);
        },
        error: function(error) {
            console.error('Error al cargar datos de top productos:', error);
        }
    });
}

// Función para oscurecer colores (para hover)
function darkenColor(color, amount = 20) {
    // Implementación de la función para oscurecer colores
    // (debes incluir tu implementación existente)
}

// Función para manejar el cambio de año en los gráficos
function setupYearSelectors() {
    // Selector de año para ventas
    $('.btn-year-ventas').on('click', function() {
        $('.btn-year-ventas').removeClass('active');
        $(this).addClass('active');
        var year = $(this).data('year');
        showVentasAnuales(year);
    });

    // Selector de año para compras
    $('.btn-year-compras').on('click', function() {
        $('.btn-year-compras').removeClass('active');
        $(this).addClass('active');
        var year = $(this).data('year');
        showComprasAnuales(year);
    });
}

// Función modificada para mostrar ventas anuales con parámetro de año
function showVentasAnuales(year = null) {
    if (year === null) {
        year = $('.btn-year-ventas.active').data('year');
    }
    
    var url = '<?php echo SERVERURL;?>core/getFacturaporAno.php?year=' + year;

    $.ajax({
        type: 'GET',
        url: url,
        success: function(data) {
            var datos = JSON.parse(data);
            var mes = [];
            var total = [];

            for (var fila = 0; fila < datos.length; fila++) {
                mes.push(datos[fila]["mes"]);
                total.push(datos[fila]["total"]);
            }

            var ctx = document.getElementById('graphVentas').getContext('2d');

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
                                    return ' L.' + context.parsed.y.toLocaleString(); // Cambiar $ por L.
                                }
                            }
                        },
                        datalabels: {
                            anchor: 'center',
                            align: 'center',
                            formatter: function(value) {
                                // Formatear el valor con separadores de miles y decimales
                                const formattedValue = new Intl.NumberFormat('es-HN', {
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
                                return context.dataset.data[context.dataIndex] > 0; // Solo mostrar si el valor es > 0
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
                                    return 'L.' + value.toLocaleString(); // Cambiar $ por L.
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
                // Necesario para el plugin datalabels
                plugins: [ChartDataLabels]
            });
        }
    });
}

// Función modificada para mostrar compras anuales con parámetro de año
function showComprasAnuales(year = null) {
    if (year === null) {
        year = $('.btn-year-compras.active').data('year');
    }
    
    var url = '<?php echo SERVERURL;?>core/getCompraporAno.php?year=' + year;

    $.ajax({
        type: 'GET',
        url: url,
        success: function(data) {
            var datos = JSON.parse(data);
            var mes = [];
            var total = [];

            for (var fila = 0; fila < datos.length; fila++) {
                mes.push(datos[fila]["mes"]);
                total.push(datos[fila]["total"]);
            }

            var ctx = document.getElementById('graphCompras').getContext('2d');

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
                                // Formatear el valor con separadores de miles y decimales
                                const formattedValue = new Intl.NumberFormat('es-HN', {
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
                                return context.dataset.data[context.dataIndex] > 0; // Solo mostrar si el valor es > 0
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
                                    return '$' + value.toLocaleString();
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
                // Necesario para el plugin datalabels
                plugins: [ChartDataLabels]
            });
        }
    });
}

// Función para descargar gráficos
function setupDownloadButtons() {
    // Descargar gráfico de ventas
    $('.download-ventas').on('click', function() {
        downloadChart('graphVentas', 'Reporte_Ventas_' + $('.btn-year-ventas.active').data('year'));
    });

    // Descargar gráfico de compras
    $('.download-compras').on('click', function() {
        downloadChart('graphCompras', 'Reporte_Compras_' + $('.btn-year-compras.active').data('year'));
    });

    // Descargar gráfico de top productos
    $('.download-top-productos').on('click', function() {
        downloadChart('graphTopProductosporAno', 'Top_Productos_' + new Date().toISOString().slice(0, 10));
    });
}

// Función genérica para descargar cualquier gráfico
function downloadChart(chartId, fileName) {
    // Obtener el elemento canvas
    const canvas = document.getElementById(chartId);
    
    // Crear un enlace temporal
    const link = document.createElement('a');
    
    // Establecer la imagen como href del enlace
    link.href = canvas.toDataURL('image/png');
    
    // Establecer el nombre del archivo
    link.download = fileName + '.png';
    
    // Simular clic en el enlace para iniciar la descarga
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Inicialización
$(function() {
    setupYearSelectors();
    setupMonthSelectors(); // <-- Nueva función para selectores de meses
    setupDownloadButtons();
    
    showVentasAnuales();
    showComprasAnuales();
    showTopProductos(3); // Mostrar últimos 3 meses por defecto
});
// FUNCIONES AUXILIARES PARA GRAFICOS

function getChartOptions(title, stacked = false) {
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
                        return ' $' + context.parsed.y.toLocaleString();
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
                        return '$' + value.toLocaleString();
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

function generateDynamicLegend(containerId, datasets) {
    const legendContainer = document.getElementById(containerId);
    if (!legendContainer) return;
    
    const items = datasets.map((dataset) => {
        return `
            <div class="legend-item">
                <span class="legend-color" style="background-color: ${dataset.backgroundColor}"></span>
                <span>${dataset.label}</span>
            </div>
        `;
    });
    
    legendContainer.innerHTML = items.join('');
}

function darkenColor(color, amount = 20) {
    // Función para oscurecer colores para efectos hover
    let usePound = false;
    if (color[0] === "#") {
        color = color.slice(1);
        usePound = true;
    }
    
    const num = parseInt(color, 16);
    let r = (num >> 16) - amount;
    if (r < 0) r = 0;
    
    let b = ((num >> 8) & 0x00FF) - amount;
    if (b < 0) b = 0;
    
    let g = (num & 0x0000FF) - amount;
    if (g < 0) g = 0;
    
    return (usePound ? "#" : "") + (g | (b << 8) | (r << 16)).toString(16).padStart(6, '0');
}

// Inicializar todos los gráficos al cargar la página
$(document).ready(function() {
    showVentasAnuales();
    showComprasAnuales();
});

var listar_secuencia_fiscales_dashboard = function() {
    var table_secuencia_fiscales_dashboard = $("#dataTableSecuenciaDashboard").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL; ?>core/llenarDataTableDocumentosFiscalesDashboard.php"
        },
        "columns": [{
                "data": "empresa"
            },
            {
                "data": "documento"
            },
            {
                "data": "inicio"
            },
            {
                "data": "fin"
            },
            {
                "data": "siguiente"
            },
            {
                "data": "fecha"
            }
        ],
        "lengthMenu": lengthMenu,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español, //esta se encuenta en el archivo main.js
        "dom": dom,
        "columnDefs": [{
                width: "40.66%",
                targets: 0
            },
            {
                width: "12.66%",
                targets: 1
            },
            {
                width: "12.66%",
                targets: 2
            },
            {
                width: "12.66%",
                targets: 3
            },
            {
                width: "8.66%",
                targets: 4
            },
            {
                width: "12.66%",
                targets: 5
            }
        ],
        "buttons": [{
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
                    columns: [0, 1, 2, 3, 4, 5]
                },
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
                    columns: [0, 1, 2, 3, 4, 5]
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
    table_secuencia_fiscales_dashboard.search('').draw();
    $('#buscar').focus();

}
//DASHBOARD

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
</script>