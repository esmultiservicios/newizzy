<!--INICIO MODAL PARA EL INGRESO DE CORREOS-->
<div class="modal fade" id="modalConfEmails">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-envelope mr-2"></i>Configuración de Correos</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formConfEmails" action="" method="POST" data-form="" enctype="multipart/form-data">
				
					<input type="hidden" required id="correo_id" name="correo_id" class="form-control">			
                    
                    <!-- Sección Configuración Servidor -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-server mr-2"></i>Configuración del Servidor</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-4 mb-3">
                                    <label for="tipo_correo_confEmail"><i class="fas fa-inbox mr-1"></i>Tipo correo <span class="priority">*</span></label>
                                    <select id="tipo_correo_confEmail" name="tipo_correo_confEmail" 
                                        class="selectpicker form-control" data-live-search="true" title="Seleccione tipo">
                                    </select>
                                    <small class="form-text text-muted">Tipo de cuenta de correo</small>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="serverConfEmail"><i class="fas fa-network-wired mr-1"></i>Servidor <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <input type="text" required id="serverConfEmail" name="serverConfEmail" 
                                            class="form-control" placeholder="Ej: smtp.gmail.com" maxlength="30">
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <i class="fas fa-server"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Servidor SMTP del correo</small>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="correoConfEmail"><i class="fas fa-at mr-1"></i>Correo <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <input type="text" required id="correoConfEmail" name="correoConfEmail" 
                                            class="form-control" placeholder="Ej: contacto@empresa.com" maxlength="100">
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <i class="fas fa-envelope"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Dirección de correo electrónico</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-4 mb-3">
                                    <label for="passConfEmail"><i class="fas fa-key mr-1"></i>Contraseña <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <input type="password" required id="passConfEmail" name="passConfEmail" 
                                            class="form-control" placeholder="Contraseña" maxlength="100">
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Contraseña de la cuenta</small>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="puertoConfEmail"><i class="fas fa-plug mr-1"></i>Puerto <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <input type="text" required id="puertoConfEmail" name="puertoConfEmail" 
                                            class="form-control" placeholder="Ej: 465" maxlength="30">
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <i class="fas fa-ethernet"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Puerto SMTP del servidor</small>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="smtpSecureConfEmail"><i class="fas fa-shield-alt mr-1"></i>SMTP Secure <span class="priority">*</span></label>
                                    <select id="smtpSecureConfEmail" name="smtpSecureConfEmail" 
                                        class="selectpicker form-control" data-live-search="true" title="Seleccione seguridad">
                                    </select>
                                    <small class="form-text text-muted">Tipo de seguridad SMTP</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger" data-dismiss="modal">
                    <i class="fas fa-times fa-lg mr-1"></i> Cancelar
                </button>
                <button class="btn btn-info" type="submit" style="display: none;" id="test_confEmails" form="formConfEmails">
                    <i class="fas fa-mail-bulk fa-lg mr-1"></i> Probar Conexión
                </button>
                <button class="btn btn-warning" type="submit" style="display: none;" id="edi_confEmails" form="formConfEmails">
                    <i class="fas fa-edit fa-lg mr-1"></i> Actualizar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PARA EL INGRESO DE CORREOS-->

<!--INICIO MODAL DESTINATARIOS-->
<div class="modal fade" id="modalRegistrarDestinatarios">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-users mr-2"></i>Destinatarios</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formDestinatarios" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
                    <input type="hidden" id="notificaciones_id" name="notificaciones_id" class="form-control">
                                        
                    <!-- Sección de Datos del Destinatario -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-user-plus mr-2"></i>Datos del Destinatario</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="correo">Correo <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        </div>
                                        <input type="email" class="form-control" id="correo" name="correo" placeholder="Correo electrónico" required>
                                    </div>
                                    <small class="form-text text-muted">Correo electrónico del destinatario</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="nombre">Nombre <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        </div>
                                        <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Nombre completo" required>
                                    </div>
                                    <small class="form-text text-muted">Nombre completo del destinatario</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Lista de Destinatarios -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-list-ul mr-2"></i>Lista de Destinatarios</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <div class="overflow-auto">
                                        <table id="DatatableDestinatarios" class="table table-striped table-condensed table-hover" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th><i class="fas fa-envelope mr-1"></i>Correo</th>
                                                    <th><i class="fas fa-user mr-1"></i>Nombre</th>
                                                    <th><i class="fas fa-trash-alt mr-1"></i>Eliminar</th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger" data-dismiss="modal">
                    <i class="fas fa-times fa-lg mr-1"></i> Cancelar
                </button>
                <button class="btn btn-success" type="submit" style="display: none;" id="reg_destinatarios" form="formDestinatarios">
                    <i class="far fa-save fa-lg mr-1"></i> Registrar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL DESTINATARIOS-->