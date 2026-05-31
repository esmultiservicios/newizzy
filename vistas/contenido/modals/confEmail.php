<!--INICIO MODAL PARA EL INGRESO DE CORREOS-->
<div class="modal fade" id="modalConfEmails">
    <div class="modal-dialog modal-correo-premium modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title">
                    <i class="fas fa-envelope mr-2"></i>Configuración de Correos
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form class="FormularioAjax" id="formConfEmails" action="" method="POST" data-form="" enctype="multipart/form-data">
                    <input type="hidden" required id="correo_id" name="correo_id" class="form-control">

                    <!-- CONFIGURACIÓN GENERAL -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-server mr-2"></i>Configuración General
                            </h5>
                        </div>

                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-lg-4 col-md-6 mb-3">
                                    <label for="tipo_correo_confEmail">
                                        <i class="fas fa-inbox mr-1"></i>Tipo correo <span class="priority">*</span>
                                    </label>
                                    <select id="tipo_correo_confEmail" name="tipo_correo_confEmail"
                                        class="selectpicker form-control" data-live-search="true" title="Seleccione tipo" required>
                                    </select>
                                    <small class="form-text text-muted">Tipo de cuenta de correo</small>
                                </div>

                                <div class="col-lg-4 col-md-6 mb-3">
                                    <label for="metodoEnvioConfEmail">
                                        <i class="fas fa-route mr-1"></i>Método de envío <span class="priority">*</span>
                                    </label>
                                    <select id="metodoEnvioConfEmail" name="metodoEnvioConfEmail"
                                        class="selectpicker form-control" title="Seleccione método" required>
                                        <option value="SMTP">SMTP / PHPMailer</option>
                                        <option value="GRAPH">Microsoft Graph API</option>
                                    </select>
                                    <small class="form-text text-muted">Define si el correo sale por SMTP o Graph</small>
                                </div>

                                <div class="col-lg-4 col-md-12 mb-3">
                                    <label for="correoConfEmail">
                                        <i class="fas fa-at mr-1"></i>Correo emisor <span class="priority">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="email" required id="correoConfEmail" name="correoConfEmail"
                                            class="form-control" placeholder="Ej: administracion@empresa.com" maxlength="100">
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <i class="fas fa-envelope"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Correo que se mostrará como remitente</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- AYUDA MICROSOFT GRAPH -->
                    <div class="ayuda-correo ayuda-graph">
                        <div class="ayuda-header">
                            <i class="fab fa-microsoft fa-lg"></i>
                            <h5>Guía rápida para configurar Microsoft Graph API</h5>
                        </div>
                        <div class="ayuda-body">
                            <p>
                                Use esta opción cuando el correo pertenece a <strong>Microsoft 365 / Exchange Online</strong>.
                                Graph no usa contraseña del correo, usa OAuth con una aplicación registrada en Microsoft Entra.
                            </p>

                            <ol class="ayuda-lista">
                                <li>Entrar a <strong>Microsoft Entra admin center</strong>: <span class="ayuda-pill">https://entra.microsoft.com</span></li>
                                <li>Ir a <strong>Entra ID → App registrations → New registration</strong>.</li>
                                <li>Nombre sugerido: <strong>ES MULTISERVICIOS Mail Sender</strong>.</li>
                                <li>Seleccionar <strong>Single tenant only</strong> y dejar <strong>Redirect URI vacío</strong>.</li>
                                <li>Copiar <strong>Application (client) ID</strong> y colocarlo en el campo <strong>Client ID</strong>.</li>
                                <li>Copiar <strong>Directory (tenant) ID</strong> y colocarlo en el campo <strong>Tenant ID</strong>.</li>
                                <li>Ir a <strong>Certificates & secrets → New client secret</strong> y copiar el campo <strong>Value</strong>, no el Secret ID.</li>
                                <li>Ir a <strong>API permissions → Add a permission → Microsoft Graph → Application permissions</strong>.</li>
                                <li>Buscar y agregar <strong>Mail.Send</strong>.</li>
                                <li>Presionar <strong>Grant admin consent</strong> para autorizar el envío.</li>
                            </ol>

                            <div class="ayuda-nota">
                                <strong>Importante:</strong> el <strong>Graph User</strong> debe ser un buzón real dentro del tenant,
                                por ejemplo <strong>administracion@esmultiservicios.com</strong>. El campo <strong>Client Secret VALUE</strong>
                                queda vacío al editar por seguridad; si no escribe uno nuevo, se conserva el guardado.
                            </div>

                            <a class="ayuda-link" href="https://entra.microsoft.com" target="_blank" rel="noopener noreferrer">
                                <i class="fas fa-external-link-alt"></i> Abrir Microsoft Entra admin center
                            </a>
                        </div>
                    </div>

                    <!-- AYUDA SMTP -->
                    <div class="ayuda-correo ayuda-smtp">
                        <div class="ayuda-header">
                            <i class="fas fa-network-wired fa-lg"></i>
                            <h5>Guía rápida para configurar SMTP / PHPMailer</h5>
                        </div>
                        <div class="ayuda-body">
                            <p>
                                Use esta opción cuando el correo se envía mediante servidor SMTP tradicional,
                                por ejemplo hosting/cPanel, servidor propio o algún proveedor externo.
                            </p>

                            <ol class="ayuda-lista">
                                <li><strong>Servidor SMTP:</strong> normalmente algo como <strong>smtp.dominio.com</strong>, <strong>mail.dominio.com</strong> o <strong>smtp.office365.com</strong>.</li>
                                <li><strong>Correo emisor:</strong> la cuenta que enviará el correo.</li>
                                <li><strong>Contraseña SMTP:</strong> contraseña de la cuenta o contraseña de aplicación si el proveedor lo requiere.</li>
                                <li><strong>Puerto:</strong> normalmente <strong>587</strong> para TLS o <strong>465</strong> para SSL.</li>
                                <li><strong>SMTP Secure:</strong> use <strong>tls</strong> para puerto 587 o <strong>ssl</strong> para puerto 465.</li>
                                <li>Presione <strong>Probar Conexión</strong> antes de guardar para validar credenciales.</li>
                            </ol>

                            <div class="ayuda-nota">
                                <strong>Nota:</strong> si usa Microsoft 365 con SMTP, puede requerir que SMTP AUTH esté habilitado.
                                Para Microsoft 365 empresarial recomendamos Graph API porque evita depender de contraseña SMTP.
                            </div>
                        </div>
                    </div>

                    <!-- CONFIGURACIÓN SMTP -->
                    <div class="card border-info mb-4 seccion-smtp">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-network-wired mr-2"></i>Configuración SMTP
                            </h5>
                        </div>

                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-lg-3 col-md-6 mb-3">
                                    <label for="serverConfEmail">
                                        <i class="fas fa-server mr-1"></i>Servidor SMTP
                                    </label>
                                    <input type="text" id="serverConfEmail" name="serverConfEmail"
                                        class="form-control campo-smtp" placeholder="Ej: smtp.office365.com" maxlength="100">
                                    <small class="form-text text-muted">Servidor SMTP del correo</small>
                                </div>

                                <div class="col-lg-3 col-md-6 mb-3">
                                    <label for="passConfEmail">
                                        <i class="fas fa-key mr-1"></i>Contraseña SMTP
                                    </label>
                                    <input type="password" id="passConfEmail" name="passConfEmail"
                                        class="form-control campo-smtp" placeholder="Dejar vacío para conservar" maxlength="255">
                                    <small class="form-text text-muted">Solo se actualiza si escribe una nueva</small>
                                </div>

                                <div class="col-lg-3 col-md-6 mb-3">
                                    <label for="puertoConfEmail">
                                        <i class="fas fa-plug mr-1"></i>Puerto SMTP
                                    </label>
                                    <input type="number" id="puertoConfEmail" name="puertoConfEmail"
                                        class="form-control campo-smtp" placeholder="Ej: 587" maxlength="10">
                                    <small class="form-text text-muted">Normalmente 587 TLS o 465 SSL</small>
                                </div>

                                <div class="col-lg-3 col-md-6 mb-3">
                                    <label for="smtpSecureConfEmail">
                                        <i class="fas fa-shield-alt mr-1"></i>SMTP Secure
                                    </label>
                                    <select id="smtpSecureConfEmail" name="smtpSecureConfEmail"
                                        class="selectpicker form-control campo-smtp" data-live-search="true" title="Seleccione seguridad">
                                    </select>
                                    <small class="form-text text-muted">TLS o SSL</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CONFIGURACIÓN GRAPH -->
                    <div class="card border-success mb-4 seccion-graph">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fab fa-microsoft mr-2"></i>Configuración Microsoft Graph API
                            </h5>
                        </div>

                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-lg-4 col-md-12 mb-3">
                                    <label for="tenantIdConfEmail">
                                        <i class="fas fa-building mr-1"></i>Tenant ID
                                    </label>
                                    <input type="text" id="tenantIdConfEmail" name="tenantIdConfEmail"
                                        class="form-control campo-graph" placeholder="Directory (tenant) ID" maxlength="100">
                                    <small class="form-text text-muted">Directory (tenant) ID de Microsoft Entra</small>
                                </div>

                                <div class="col-lg-4 col-md-12 mb-3">
                                    <label for="clientIdConfEmail">
                                        <i class="fas fa-id-card mr-1"></i>Client ID
                                    </label>
                                    <input type="text" id="clientIdConfEmail" name="clientIdConfEmail"
                                        class="form-control campo-graph" placeholder="Application (client) ID" maxlength="100">
                                    <small class="form-text text-muted">Application (client) ID de la app registrada</small>
                                </div>

                                <div class="col-lg-4 col-md-12 mb-3">
                                    <label for="clientSecretConfEmail">
                                        <i class="fas fa-user-secret mr-1"></i>Client Secret VALUE
                                    </label>
                                    <input type="password" id="clientSecretConfEmail" name="clientSecretConfEmail"
                                        class="form-control campo-graph" placeholder="Dejar vacío para conservar" maxlength="500">
                                    <small class="form-text text-muted">Por seguridad no se muestra. Solo se actualiza si escribe un nuevo VALUE</small>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="col-lg-8 col-md-12 mb-3">
                                    <label for="graphUserConfEmail">
                                        <i class="fas fa-mail-bulk mr-1"></i>Graph User / Buzón real
                                    </label>
                                    <input type="email" id="graphUserConfEmail" name="graphUserConfEmail"
                                        class="form-control campo-graph" placeholder="Ej: administracion@empresa.com" maxlength="150">
                                    <small class="form-text text-muted">Buzón real de Microsoft 365 que enviará el correo</small>
                                </div>

                                <div class="col-lg-4 col-md-12 mb-3">
                                    <label for="saveToSentItemsConfEmail">
                                        <i class="fas fa-paper-plane mr-1"></i>Guardar en enviados
                                    </label>
                                    <select id="saveToSentItemsConfEmail" name="saveToSentItemsConfEmail"
                                        class="selectpicker form-control campo-graph" title="Seleccione">
                                        <option value="1">Sí, guardar copia</option>
                                        <option value="0">No guardar copia</option>
                                    </select>
                                    <small class="form-text text-muted">Si está en “Sí”, Graph deja copia en Elementos enviados</small>
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

                <button class="btn btn-info" type="button" style="display: none;" id="test_confEmails">
                    <i class="fas fa-mail-bulk fa-lg mr-1"></i> Probar Conexión
                </button>

                <button class="btn btn-success" type="submit" style="display: none;" id="edi_confEmails" form="formConfEmails">
                    <i class="fas fa-edit fa-lg mr-1"></i> Actualizar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PARA EL INGRESO DE CORREOS-->