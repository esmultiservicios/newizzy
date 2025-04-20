<!--INICIO MODAL PROGRAMA PUNTOS-->
<div class="modal fade" id="modalProgramaPuntos">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-star mr-2"></i>Programa de Puntos</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formProgramaPuntos" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
                    <input type="hidden" id="programa_puntos_id" name="programa_puntos_id">
                    
                    <!-- Sección de Configuración del Programa -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-cog mr-2"></i>Configuración del Programa</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="nombre">Nombre del Programa <span class="priority">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                    </div>
                                    <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Nombre del Programa" required>
                                </div>
                                <small class="form-text text-muted">Ingrese un nombre descriptivo para el programa de puntos</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="tipo_calculo">Tipo de Cálculo <span class="priority">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-calculator"></i></span>
                                    </div>
                                    <select id="tipo_calculo" name="tipo_calculo" required class="selectpicker form-control" data-live-search="true" title="Seleccione un tipo de cálculo">
                                        <option value="" disabled>Seleccione una opción</option>
                                        <option value="monto">Por Monto</option>
                                        <option value="porcentaje">Por Porcentaje</option>
                                    </select>
                                </div>
                                <small class="form-text text-muted">Seleccione cómo se calcularán los puntos para los clientes</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Cálculo de Puntos -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-calculator mr-2"></i>Cálculo de Puntos</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group" id="calculo_monto" style="display: none;">
                                <label for="monto">Monto en Lempiras para 1 punto</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-money-bill-wave"></i></span>
                                    </div>
                                    <input type="number" class="form-control" id="monto" name="monto" placeholder="Ejemplo: 25">
                                    <div class="input-group-append">
                                        <span class="input-group-text">L.</span>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Ingrese el monto en Lempiras equivalente a 1 punto</small>
                            </div>
                            
                            <div class="form-group" id="calculo_porcentaje" style="display: none;">
                                <label for="porcentaje">Porcentaje del Consumo</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-percent"></i></span>
                                    </div>
                                    <input type="number" class="form-control" id="porcentaje" name="porcentaje" placeholder="Ejemplo: 10" min="0" max="100">
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Ingrese el porcentaje del consumo que se convertirá en puntos</small>
                            </div>                
                            
                            <div id="ejemplo_calculo" class="form-group" style="display: none;">
                                <div class="alert alert-info">
                                    <p class="mb-0"><i class="fas fa-info-circle mr-2"></i><strong>Ejemplo:</strong> <span id="ejemploTexto"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Estado -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-power-off mr-2"></i>Estado del Programa</h5>
                        </div>
                        <div class="card-body">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="ProgramaPuntos_activo" name="ProgramaPuntos_activo" checked>
                                <label class="custom-control-label" for="ProgramaPuntos_activo"><i class="fas fa-check-circle mr-1"></i>Programa Activo</label>
                            </div>
                            <small class="form-text text-muted">Active o desactive el programa de puntos en el sistema</small>
                        </div>
                    </div>

                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cancelar
                </button>
                <button class="btn btn-primary" type="submit" id="reg_ProgramaPuntos" form="formProgramaPuntos">
                    <i class="far fa-save mr-1"></i> Registrar
                </button>
                <button class="btn btn-warning" type="submit" style="display: none;" id="edi_ProgramaPuntos" form="formProgramaPuntos">
                    <i class="fas fa-edit mr-1"></i> Editar
                </button>
                <button class="btn btn-danger" type="submit" style="display: none;" id="delete_ProgramaPuntos" form="formProgramaPuntos">
                    <i class="fas fa-trash mr-1"></i> Eliminar
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
                <h5 class="modal-title" id="modalHistoricoPuntosLabel"><i class="fas fa-history mr-2"></i>Historial de Puntos</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="card border-primary mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-table mr-2"></i>Detalle de acumulación y redención de puntos</h5>
                    </div>
                    <div class="card-body"> 
                        <div class="table-responsive">
                            <table id="tablaHistoricoPuntos" class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-user mr-1"></i> Cliente</th>
                                        <th><i class="fas fa-exchange-alt mr-1"></i> Tipo Movimiento</th>
                                        <th><i class="fas fa-star mr-1"></i> Puntos</th>
                                        <th><i class="fas fa-align-left mr-1"></i> Descripción</th>
                                        <th><i class="fas fa-calendar-alt mr-1"></i> Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Los datos se cargarán aquí -->
                                </tbody>
                            </table>  
                        </div>                   
                    </div>
                    <div class="card-footer small text-muted">
                        <i class="fas fa-clock mr-1"></i> Última actualización: <span id="fecha-actualizacion"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL HISTORICO PROGRAMA PUNTOS-->