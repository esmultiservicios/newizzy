<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>IZZY | Reporte de Cotizaciones</title>
<link rel="stylesheet" href="<?php echo SERVERURL; ?>vistas/plantilla/css/reporte_cotizacion.css">
</head>
<body>
<div class="container-fluid rv-page">
  <div class="breadcrumb-container">
    <ol class="breadcrumb-harmony">
      <li class="breadcrumb-item"><a class="breadcrumb-link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>dashboard/"><i class="fas fa-home breadcrumb-icon"></i><span>Dashboard</span></a></li>
      <li class="breadcrumb-separator">/</li>
      <li class="breadcrumb-item active"><i class="fas fa-file-signature breadcrumb-icon"></i><span>Reporte de Cotizaciones</span></li>
    </ol>
  </div>

  <section class="rv-section-card" id="rcFiltrosSection">
    <div class="rv-section-header"><div><h5><i class="fas fa-filter mr-1"></i> Filtros de cotizaciones</h5><small>Refine el reporte por tipo de documento y período.</small></div><button type="button" class="btn rv-toggle-section" data-target="#rcFiltrosBody"><i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span></button></div>
    <div class="rv-section-body" id="rcFiltrosBody"><form id="form_main_cotizaciones">
                <div class="row">
                    <div class="col-md-4 col-sm-6 mb-3">
                        <div class="form-group">
                            <label class="small mb-1">Tipo Factura</label>
                            <select id="tipo_cotizacion_reporte" name="tipo_cotizacion_reporte" 
                                class="form-control selectpicker" title="Tipo de Factura" data-live-search="true">
                            </select>
                        </div>
                    </div>
                    
					<div class="col-md-4 col-sm-6 mb-3">
						<div class="form-group">
							<label class="small mb-1">Fecha Inicio</label>
							<div class="input-group">
								<div class="input-group-prepend">
									<span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
								</div>
								<input type="date" class="form-control" id="fechai" name="fechai" value="<?php 
									$fecha = date ("Y-m-d");
									
									$año = date("Y", strtotime($fecha));
									$mes = date("m", strtotime($fecha));
									$dia = date("d", mktime(0,0,0, $mes+1, 0, $año));

									$dia1 = date('d', mktime(0,0,0, $mes, 1, $año));
									$dia2 = date('d', mktime(0,0,0, $mes, $dia, $año));

									$fecha_inicial = date("Y-m-d", strtotime($año."-".$mes."-".$dia1));
									echo $fecha_inicial;
								?>">
							</div>
						</div>
					</div>
                    
					<div class="col-md-4 col-sm-6 mb-3">
						<div class="form-group">
							<label class="small mb-1">Fecha Fin</label>
							<div class="input-group">
								<div class="input-group-prepend">
									<span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
								</div>
								<input type="date" class="form-control" id="fechaf" name="fechaf" value="<?php echo date('Y-m-d');?>">
							</div>
						</div>
					</div>
                </div>
                
                <div class="row">
                    <div class="col-12 text-right">
                        <button type="submit" class="btn btn-primary mr-2" id="search">
                            <i class="fas fa-filter fa-lg mr-1"></i> Filtrar
                        </button>
                        <button type="reset" id="btn-limpiar-filtros" class="btn btn-secondary">
							<i class="fas fa-broom fa-lg mr-1"></i> Limpiar
						</button>                        
                    </div>
                </div>
            </form></div>
  </section>

  <section class="rv-section-card" id="rcKpisSection">
    <div class="rv-section-header"><div><h5><i class="fas fa-chart-pie mr-1"></i> Indicadores</h5><small>Resumen ejecutivo de las cotizaciones filtradas.</small></div><button type="button" class="btn rv-toggle-section" data-target="#rcKpisBody"><i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span></button></div>
    <div class="rv-section-body" id="rcKpisBody">
      <div class="rv-kpi-grid rc-kpi-grid">
        <div class="rv-kpi rv-kpi-registros"><div class="rv-kpi-copy"><span class="rv-kpi-label"><i class="fas fa-file-signature"></i> Registros</span><strong id="rcKpiRegistros">0</strong><small>Cotizaciones filtradas</small></div><div class="rv-kpi-icon"><i class="fas fa-file-signature"></i></div></div>
        <div class="rv-kpi rv-kpi-subtotal"><div class="rv-kpi-copy"><span class="rv-kpi-label"><i class="fas fa-coins"></i> Subtotal</span><strong id="rcKpiSubtotal">L. 0.00</strong><small>Antes de impuestos</small></div><div class="rv-kpi-icon"><i class="fas fa-coins"></i></div></div>
        <div class="rv-kpi rv-kpi-isv"><div class="rv-kpi-copy"><span class="rv-kpi-label"><i class="fas fa-percentage"></i> ISV</span><strong id="rcKpiIsv">L. 0.00</strong><small>Impuesto calculado</small></div><div class="rv-kpi-icon"><i class="fas fa-percentage"></i></div></div>
        <div class="rv-kpi rv-kpi-descuento"><div class="rv-kpi-copy"><span class="rv-kpi-label"><i class="fas fa-tags"></i> Descuento</span><strong id="rcKpiDescuento">L. 0.00</strong><small>Descuentos aplicados</small></div><div class="rv-kpi-icon"><i class="fas fa-tags"></i></div></div>
        <div class="rv-kpi rv-kpi-primary rv-kpi-total"><div class="rv-kpi-copy"><span class="rv-kpi-label"><i class="fas fa-money-bill-wave"></i> Total cotizado</span><strong id="rcKpiTotal">L. 0.00</strong><small>Valor total del período</small></div><div class="rv-kpi-icon"><i class="fas fa-money-bill-wave"></i></div></div>
        <div class="rv-kpi rv-kpi-ganancia rc-kpi-promedio"><div class="rv-kpi-copy"><span class="rv-kpi-label"><i class="fas fa-chart-line"></i> Promedio</span><strong id="rcKpiPromedio">L. 0.00</strong><small>Promedio por cotización</small></div><div class="rv-kpi-icon"><i class="fas fa-chart-line"></i></div></div>
      </div>
    </div>
  </section>

  <section class="rv-section-card" id="rcListadoSection">
    <div class="rv-section-header"><div><h5><i class="fas fa-file-signature mr-1"></i> Reporte de Cotizaciones</h5><small>Cotizaciones según los filtros aplicados.</small></div><button type="button" class="btn rv-toggle-section" data-target="#rcListadoBody"><i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span></button></div>
    <div class="rv-section-body" id="rcListadoBody">
      <div class="rv-list-toolbar">
        <div class="rv-toolbar-left">
          <button type="button" id="rcBtnActualizar" class="btn btn-info table_actualizar ocultar"><i class="fas fa-sync-alt"></i> Actualizar</button>
          <button type="button" id="rcBtnExcel" class="btn btn-success table_reportes ocultar"><i class="fas fa-file-excel"></i> Excel</button>
          <button type="button" id="rcBtnPdf" class="btn btn-danger table_reportes ocultar"><i class="fas fa-file-pdf"></i> PDF</button>
        </div>
        <div class="rv-toolbar-right">
          <label class="rv-page-size">Mostrar <select id="rcPageSize" class="form-control form-control-sm"><option value="10" selected>10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select> registros</label>
          <div class="rv-view-switch"><button type="button" class="rv-view-btn active" data-rc-view="detalle"><i class="fas fa-list"></i> Detalle</button><button type="button" class="rv-view-btn" data-rc-view="miniatura"><i class="fas fa-th-large"></i> Miniatura</button></div>
          <div class="rv-search-wrap"><i class="fas fa-search"></i><input type="search" id="rcSearch" class="form-control form-control-sm" placeholder="Buscar..."><button type="button" id="rcSearchClear" aria-label="Limpiar búsqueda"><i class="fas fa-times"></i></button></div>
        </div>
      </div>
      <div id="rcListado" class="rv-list"></div>
      <div id="rcTotales" class="rv-total-row"></div>
      <div class="rv-list-footer"><span id="rcInfo">0 registros</span><div id="rcPagination" class="rv-pagination"></div></div>
    </div>
    <div class="card-footer small text-muted rv-last-update">
      <?php
        require_once "./core/mainModel.php";
        $insMainModel = new mainModel();
        $entidad = "compras";
        if($insMainModel->getlastUpdate($entidad)->num_rows > 0){
          $consulta_last_update = $insMainModel->getlastUpdate($entidad)->fetch_assoc();
          $fecha_registro = htmlspecialchars($consulta_last_update['fecha_registro'], ENT_QUOTES, 'UTF-8');
          $hora = htmlspecialchars(date('g:i:s a', strtotime($fecha_registro)), ENT_QUOTES, 'UTF-8');
          echo "Última Actualización ".htmlspecialchars($insMainModel->getTheDay($fecha_registro, $hora), ENT_QUOTES, 'UTF-8');
        } else { echo "No se encontraron registros "; }
      ?>
    </div>
  </section>
</div>
<?php $insMainModel->guardar_historial_accesos("Ingreso al modulo Reporte de Cotizaciones"); ?>
</body>
</html>
