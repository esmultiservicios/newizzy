<!--INICIO MODAL PROGRAMA PUNTOS-->
<div class="modal fade" id="modalProgramaPuntos">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title">Programa de Puntos</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formProgramaPuntos" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
                    <input type="hidden" id="programa_puntos_id" name="programa_puntos_id">
                    
                    <div class="form-group">
                        <label for="nombre">Nombre del Programa <span class="priority">*</span></label>
                        <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Nombre del Programa" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="tipo_calculo">Tipo de Cálculo <span class="priority">*</span></label>
						<select id="tipo_calculo" name="tipo_calculo" required class="selectpicker" data-width="100%" data-live-search="true" title="Tipo de Cálculo">
                            <option value="" disabled >Seleccione una opción</option>
                            <option value="monto">Por Monto</option>
                            <option value="porcentaje">Por Porcentaje</option>
						</select>
                    </div>
                    
                    <div class="form-group" id="calculo_monto" style="display: none;">
                        <label for="monto">Monto en Lempiras para 1 punto</label>
                        <input type="number" class="form-control" id="monto" name="monto" placeholder="Ejemplo: 25">
                    </div>
                    
                    <div class="form-group" id="calculo_porcentaje" style="display: none;">
                        <label for="porcentaje">Porcentaje del Consumo</label>
                        <input type="number" class="form-control" id="porcentaje" name="porcentaje" placeholder="Ejemplo: 10" min="0" max="100">
                    </div>                
                    
                    <div id="ejemplo_calculo" class="form-group" style="display: none;">
                        <div class="ejemplo-contenedor">
                            <p class="ejemplo-texto"><strong>Ejemplo:</strong> <span id="ejemploTexto"></span></p>
                        </div>
                    </div>

                    <div class="form-group" id="estadoProgramaPuntos">
                        <label>Estado:</label>
                        <div>
                            <label class="switch">
                                <input type="checkbox" id="ProgramaPuntos_activo" name="ProgramaPuntos_activo" value="1" checked>
                                <div class="slider round"></div>
                            </label>
                            <span id="label_ProgramaPuntos_activo"></span>
                        </div>
                    </div>
                    
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-primary" type="submit" id="reg_ProgramaPuntos" form="formProgramaPuntos">
                    <i class="far fa-save fa-lg"></i> Registrar
                </button>
                <button class="editar btn btn-warning" type="submit" id="edi_ProgramaPuntos" form="formProgramaPuntos" style="display: none;">
                    <i class="fas fa-edit fa-lg"></i> Editar
                </button>
                <button class="eliminar btn btn-danger" type="submit" id="delete_ProgramaPuntos" form="formProgramaPuntos" style="display: none;">
                    <i class="fa fa-trash fa-lg"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PROGRAMA PUNTOS-->

<!--INICIO MODAL HISTORICO PROGRAMA PUNTOS-->
<div class="modal fade" id="modalHistoricoPuntos" tabindex="-1" role="dialog" aria-labelledby="modalHistoricoPuntosLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalHistoricoPuntosLabel">Historial de Puntos</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-history mr-1"></i>
                        Detalle de acumulación y redención de puntos
                    </div>
                    <div class="card-body"> 
                        <div class="table-responsive">
                            <table id="tablaHistoricoPuntos" class="table table-striped table-condensed table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Cliente</th>
                                        <th>Tipo Movimiento</th>
                                        <th>Puntos</th>
                                        <th>Descripción</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Los datos se cargarán aquí -->
                                </tbody>
                            </table>  
                        </div>                   
                    </div>
                    <div class="card-footer small text-muted">
                        Última actualización: <span id="fecha-actualizacion"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL HISTORICO PROGRAMA PUNTOS-->