<script>
$(() => {
	listar_movimientos_contabilidad();

	// Buscar / Filtrar
	$('#formMainMovimientosContabilidad').on("submit", function (e) {
		e.preventDefault();
		listar_movimientos_contabilidad();
	});

	// Limpiar
	$('#formMainMovimientosContabilidad').on('reset', function () {
		var form = $(this);

		setTimeout(function () {
			form.find('.selectpicker')
				.val('')
				.selectpicker('refresh');

			listar_movimientos_contabilidad();
		}, 0);
	});

	// Filtrar con Enter en los inputs
	$('#formMainMovimientosContabilidad input').on('keypress', function (e) {
		if (e.which === 13) {
			e.preventDefault();
			listar_movimientos_contabilidad();
		}
	});
});

// ===============================
//  Construir header/footer desde JS
// ===============================
function construirEstructuraTablaMovimientosContabilidad() {
	var tabla = $("#dataTableMovimientosContabilidad");

	if ($.fn.DataTable.isDataTable("#dataTableMovimientosContabilidad")) {
		tabla.DataTable().clear().destroy();
	}

	tabla.empty();

	tabla.append(
		'<thead>' +
			'<tr>' +
				'<th>Fecha</th>' +
				'<th>Cuenta</th>' +
				'<th>Nombre</th>' +
				'<th>Tipo</th>' +
				'<th>Ingreso</th>' +
				'<th>Egreso</th>' +
				'<th>Saldo</th>' +
			'</tr>' +
		'</thead>' +
		'<tfoot class="table-footer-total">' +
			'<tr>' +
				'<th colspan="4" class="text-right">Totales filtrados</th>' +
				'<th class="text-right" id="footer_total_ingreso">L 0.00</th>' +
				'<th class="text-right" id="footer_total_egreso">L 0.00</th>' +
				'<th class="text-right" id="footer_saldo_final">L 0.00</th>' +
			'</tr>' +
		'</tfoot>'
	);
}

// ===============================
//  DataTable Movimientos Contables
// ===============================
var listar_movimientos_contabilidad = function () {
	// Limpia estado guardado
	try {
		var _dtKey = 'DataTables_' + 'dataTableMovimientosContabilidad' + '_' + window.location.pathname;
		localStorage.removeItem(_dtKey);
	} catch (e) { /* ignore */ }

	construirEstructuraTablaMovimientosContabilidad();

	var fechai = $("#formMainMovimientosContabilidad #fechai").val();
	var fechaf = $("#formMainMovimientosContabilidad #fechaf").val();
	var cuenta_busqueda = $("#formMainMovimientosContabilidad #cuenta_busqueda").val();
	var tipo_movimiento = $("#formMainMovimientosContabilidad #tipo_movimiento").val();
	var monto_desde = $("#formMainMovimientosContabilidad #monto_desde").val();
	var monto_hasta = $("#formMainMovimientosContabilidad #monto_hasta").val();

	function toNumber(val) {
		if (typeof val === "number") return val;
		return parseFloat(String(val).replace(/[^\d.-]/g, "")) || 0;
	}

	function formatMoney(n) {
		try {
			return Number(n).toLocaleString('es-HN', {
				minimumFractionDigits: 2,
				maximumFractionDigits: 2
			});
		} catch (e) {
			var s = (Number(n) || 0).toFixed(2);
			return s.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
		}
	}

	function moneyText(n) {
		return 'L ' + formatMoney(n);
	}

	function moneyRender(data, type) {
		var n = toNumber(data);

		if (type === 'display') {
			var color = n < 0 ? '#dc2626' : '#16a34a';

			if (n === 0) {
				color = '#64748b';
			}

			return '<span style="color:' + color + ';font-size:inherit;font-weight:800;line-height:inherit">' + moneyText(n) + '</span>';
		}

		return n;
	}

	function tipoMovimientoRender(data, type, row) {
		var ingreso = toNumber(row.ingreso);
		var egreso = toNumber(row.egreso);

		if (type !== 'display') {
			if (ingreso > 0) return 'Ingreso';
			if (egreso > 0) return 'Egreso';
			return 'Sin movimiento';
		}

		if (ingreso > 0) {
			return '<span class="badge-movimiento badge-movimiento-ingreso"><i class="fas fa-arrow-down"></i> Ingreso</span>';
		}

		if (egreso > 0) {
			return '<span class="badge-movimiento badge-movimiento-egreso"><i class="fas fa-arrow-up"></i> Egreso</span>';
		}

		return '<span class="badge-movimiento badge-movimiento-neutro"><i class="fas fa-minus-circle"></i> Sin movimiento</span>';
	}

	function cuentaRender(data, type, row) {
		var codigo = data || '';

		if (type !== 'display') {
			return codigo;
		}

		return '<strong>' + codigo + '</strong>';
	}

	function nombreCuentaRender(data, type, row) {
		var nombre = data || '';

		if (type !== 'display') {
			return nombre;
		}

		var html = '<span style="font-weight:700;color:#0f172a;">' + nombre + '</span>';

		if (parseInt(row.es_inversion || 0) === 1) {
			html += '<span class="badge-cuenta-inversion"><i class="fas fa-seedling"></i> Inversión</span>';
		}

		return html;
	}

	function actualizarResumenMovimientos(api) {
		var data = api.rows({ search: 'applied' }).data();

		var totalIngreso = 0;
		var totalEgreso = 0;
		var cuentas = {};
		var ultimoSaldoPorCuenta = {};
		var totalMovimientos = data.length;

		for (var i = 0; i < data.length; i++) {
			var row = data[i];

			var codigo = row.codigo || '';
			var ingreso = toNumber(row.ingreso);
			var egreso = toNumber(row.egreso);
			var saldo = toNumber(row.saldo);
			var fecha = row.fecha || '';

			totalIngreso += ingreso;
			totalEgreso += egreso;

			if (codigo !== '') {
				cuentas[codigo] = true;

				if (
					typeof ultimoSaldoPorCuenta[codigo] === 'undefined' ||
					String(fecha) > String(ultimoSaldoPorCuenta[codigo].fecha)
				) {
					ultimoSaldoPorCuenta[codigo] = {
						fecha: fecha,
						saldo: saldo
					};
				}
			}
		}

		var totalCuentas = Object.keys(cuentas).length;
		var balancePeriodo = totalIngreso - totalEgreso;
		var saldoFinalConsultado = 0;

		Object.keys(ultimoSaldoPorCuenta).forEach(function (codigo) {
			saldoFinalConsultado += toNumber(ultimoSaldoPorCuenta[codigo].saldo);
		});

		$('#resumen_total_ingresos').html(moneyText(totalIngreso));
		$('#resumen_total_egresos').html(moneyText(totalEgreso));
		$('#resumen_balance_periodo').html(moneyText(balancePeriodo));
		$('#resumen_saldo_final').html(moneyText(saldoFinalConsultado));
		$('#resumen_movimientos').html(totalMovimientos + (totalMovimientos === 1 ? ' movimiento' : ' movimientos'));
		$('#resumen_cuentas').html(totalCuentas + (totalCuentas === 1 ? ' cuenta' : ' cuentas'));

		$('#footer_total_ingreso').html(moneyText(totalIngreso));
		$('#footer_total_egreso').html(moneyText(totalEgreso));
		$('#footer_saldo_final').html(moneyText(saldoFinalConsultado));
	}

	var table_movimientos_contabilidad = $("#dataTableMovimientosContabilidad").DataTable({
		destroy: true,
		stateSave: false,
		orderMulti: false,
		autoWidth: false,

		ajax: {
			method: "POST",
			url: "<?php echo SERVERURL;?>core/llenarDataTableMovimientosCuentasContabilidad.php",
			data: {
				fechai: fechai,
				fechaf: fechaf,
				cuenta_busqueda: cuenta_busqueda,
				tipo_movimiento: tipo_movimiento,
				monto_desde: monto_desde,
				monto_hasta: monto_hasta
			}
		},

		columns: [
			{
				data: "fecha",
				className: "align-middle"
			},
			{
				data: "codigo",
				className: "align-middle",
				render: cuentaRender
			},
			{
				data: "nombre",
				className: "align-middle",
				render: nombreCuentaRender
			},
			{
				data: null,
				className: "text-center align-middle",
				render: tipoMovimientoRender
			},
			{
				data: "ingreso",
				className: "dt-body-right align-middle",
				render: moneyRender
			},
			{
				data: "egreso",
				className: "dt-body-right align-middle",
				render: moneyRender
			},
			{
				data: "saldo",
				className: "dt-body-right align-middle",
				render: moneyRender
			}
		],

		lengthMenu: lengthMenu10,
		language: idioma_español,
		dom: dom,
		order: [[0, "desc"]],

		columnDefs: [
			{ width: "15%", targets: 0 },
			{ width: "12%", targets: 1 },
			{ width: "23%", targets: 2 },
			{ width: "12%", targets: 3 },
			{ width: "13%", targets: 4 },
			{ width: "13%", targets: 5 },
			{ width: "12%", targets: 6 }
		],

		buttons: [
			{
				text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
				titleAttr: 'Actualizar Registro Movimientos Contables',
				className: 'table_actualizar btn btn-secondary ocultar',
				action: function () {
					listar_movimientos_contabilidad();
				}
			},
			{
				extend: 'excelHtml5',
				footer: true,
				text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
				titleAttr: 'Excel',
				title: 'Reporte Registro Movimientos Contables',
				messageTop: function () {
					return 'Fecha desde: ' + convertDateFormat(fechai) +
						'  Fecha hasta: ' + convertDateFormat(fechaf) +
						'  Cuenta: ' + (cuenta_busqueda || 'Todas') +
						'  Tipo: ' + (tipo_movimiento || 'Todos');
				},
				messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
				className: 'table_reportes btn btn-success ocultar',
				exportOptions: {
					columns: [0, 1, 2, 3, 4, 5, 6],
					format: {
						body: function (data, row, column, node) {
							return String(data)
								.replace(/<[^>]*>/g, '')
								.replace('L ', '')
								.trim();
						},
						footer: function (data, row, column, node) {
							return String(data)
								.replace(/<[^>]*>/g, '')
								.replace('L ', '')
								.trim();
						}
					}
				}
			},
			{
				extend: 'pdf',
				footer: true,
				text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
				titleAttr: 'PDF',
				title: 'Reporte Registro Movimientos Contables',
				messageTop: function () {
					return 'Fecha desde: ' + convertDateFormat(fechai) +
						'  Fecha hasta: ' + convertDateFormat(fechaf) +
						'  Cuenta: ' + (cuenta_busqueda || 'Todas') +
						'  Tipo: ' + (tipo_movimiento || 'Todos');
				},
				messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
				className: 'table_reportes btn btn-danger ocultar',
				exportOptions: {
					columns: [0, 1, 2, 3, 4, 5, 6],
					format: {
						body: function (data, row, column, node) {
							return String(data)
								.replace(/<[^>]*>/g, '')
								.replace('L ', '')
								.trim();
						},
						footer: function (data, row, column, node) {
							return String(data)
								.replace(/<[^>]*>/g, '')
								.replace('L ', '')
								.trim();
						}
					}
				},
				customize: function (doc) {
					if (typeof imagen !== 'undefined' && imagen) {
						doc.content.splice(0, 0, {
							image: imagen,
							width: 100,
							height: 45,
							margin: [0, 0, 0, 12]
						});
					}

					doc.styles.tableHeader.alignment = 'left';
					doc.defaultStyle.fontSize = 8;
					doc.styles.tableHeader.fontSize = 8;
				}
			}
		],

		initComplete: function () {
			this.api().order([0, 'desc']).draw();
			$('#cuenta_busqueda').focus();
		},

		drawCallback: function () {
			var api = this.api();

			actualizarResumenMovimientos(api);

			getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
		}
	});

	table_movimientos_contabilidad.search('').draw();
};
// FIN ACCIONES FORMULARIO MOVIMIENTOS CONTABLES
</script>