(function(){
    'use strict';
    function init(){
        if(!window.jQuery)return;
        const $=window.jQuery,root=$('#izzy-account-api-overlay'),link=$('#izzy-mi-cuenta-api');
        if(!root.length||!link.length)return;
        const endpoint=root.data('url'),csrf=String(root.data('csrf')||'');
        const base=String(root.data('base-url')||'').replace(/\/?$/,'/');
        $('#izzy-account-api-token-url').text(base+'integrations/token.php');
        $('#izzy-account-api-me-url').text(base+'integrations/me.php');
        const esc=value=>$('<span>').text(value==null?'':String(value)).html();
        function notify(message,type){if(typeof window.showNotify==='function')window.showNotify(type||'success','API',message);else if(typeof window.swal==='function')window.swal({title:'API',text:message,icon:type==='error'?'error':'success',button:'Entendido'});}
        function post(data){return $.ajax({url:endpoint,method:'POST',dataType:'json',data:{...data,csrf},headers:{'X-CSRF-Token':csrf}});}
        function close(){root.removeClass('is-open').attr('aria-hidden','true');$('#izzy-account-client-secret').text('');$('#izzy-account-api-credentials').prop('hidden',true);$('body').css('overflow','');}
        let statusKnown=false,statusAllowed=false;
        function open(){root.addClass('is-open').attr('aria-hidden','false');$('body').css('overflow','hidden');if(statusKnown&&statusAllowed)load();setTimeout(()=>root.find('.izzy-account-api-tabs .is-active').trigger('focus'),30);}
        root.on('click','[data-izzy-api-tab]',function(){
            const selected=String($(this).data('izzy-api-tab'));
            root.find('[data-izzy-api-tab]').removeClass('is-active').attr('aria-selected','false');
            $(this).addClass('is-active').attr('aria-selected','true');
            root.find('[data-izzy-api-content]').removeClass('is-active').filter('[data-izzy-api-content="'+selected+'"]').addClass('is-active');
            root.find('.izzy-account-api-body').scrollTop(0);
            if(selected==='guide')$('#izzy-account-api-guide').prop('open',true);
        });
        function load(){
            $('#izzy-account-api-list').html('<p>Cargando integraciones…</p>');
            post({action:'list'}).done(res=>{
                const rows=res.datos.integraciones||[];
                $('#izzy-account-api-list').html(rows.length?rows.map(row=>'<div class="izzy-account-api-record"><div><strong>'+esc(row.nombre)+'</strong><small>Client ID: '+esc(row.client_id)+'</small><small>Permisos: '+esc(row.scopes)+' · '+(row.revoked_at?'Revocada':row.expires_at?'Vence '+esc(row.expires_at):'Sin vencimiento')+'</small><small>URL: '+esc(base+'integrations/token.php')+'</small></div>'+(row.revoked_at?'':'<button type="button" class="btn btn-danger btn-sm" data-izzy-account-revoke="'+Number(row.id)+'"><i class="fas fa-ban"></i> Revocar</button>')+'</div>').join(''):'<div class="izzy-account-api-empty"><i class="fas fa-plug"></i><strong>No hay accesos registrados</strong><span>Cuando IZZY autorice esta empresa, podrá crear aquí una credencial para conectar otros sistemas.</span></div>');
            }).fail(xhr=>$('#izzy-account-api-list').text(xhr.responseJSON?.mensaje||'No se pudieron cargar las integraciones.'));
        }
        post({action:'status'}).done(res=>{
            const allowed=!!res.datos?.habilitada;
            statusKnown=true;statusAllowed=allowed;
            if(allowed){link.show();$('#izzy-account-api-form,#izzy-account-api-list').show();$('#izzy-account-api-status').html('<div class="izzy-account-api-approved"><i class="fas fa-check-circle"></i> Integraciones autorizadas por IZZY para su empresa.</div>');load();}
            else{$('#izzy-account-api-status').html('<div class="izzy-account-api-denied"><i class="fas fa-lock"></i> Esta empresa no tiene autorización para crear integraciones. Solicite la habilitación a IZZY.</div>');$('#izzy-account-api-form').hide();$('#izzy-account-api-list').html('<div class="izzy-account-api-empty"><i class="fas fa-lock"></i><strong>No hay accesos disponibles</strong><span>La emisión de credenciales está bloqueada hasta que IZZY autorice su empresa.</span></div>');}
        }).fail(xhr=>{
            const message=xhr.responseJSON?.mensaje||'No hubo respuesta válida del servidor.';
            const detail=xhr.status===401?'Inicie sesión nuevamente.':xhr.status===419?'Actualice esta página.':xhr.status===403?'Verifique que está usando una cuenta administradora de una empresa activa.':'Informe este error a soporte IZZY.';
            $('#izzy-account-api-status').html('<div class="izzy-account-api-denied"><i class="fas fa-exclamation-triangle"></i><span><strong>No fue posible comprobar el permiso API.</strong><br>'+esc(message)+' '+esc(detail)+'</span></div>');
            statusKnown=true;statusAllowed=false;$('#izzy-account-api-form').hide();$('#izzy-account-api-list').html('<div class="izzy-account-api-empty"><i class="fas fa-shield-alt"></i><strong>No hay accesos disponibles</strong><span>No se pueden emitir credenciales sin comprobar primero la autorización. Consulte con soporte IZZY.</span></div>');
        });
        if($.fn.select2)$('#izzy-account-api-expiry').select2({width:'100%',minimumResultsForSearch:Infinity,dropdownParent:root});
        root.on('click','[data-izzy-copy]',function(){
            const value=$('#'+$(this).data('izzy-copy')).text();
            if(navigator.clipboard&&window.isSecureContext)navigator.clipboard.writeText(value).then(()=>notify('Copiado.','success')).catch(()=>notify('No se pudo copiar.','error'));
            else{const field=$('<textarea>').val(value).appendTo('body');field[0].select();document.execCommand('copy');field.remove();notify('Copiado.','success');}
        });
        link.on('click',e=>{e.preventDefault();open();});
        root.find('.izzy-account-api-close').on('click',close);
        $(document).on('keydown',e=>{if(e.key==='Escape'&&root.hasClass('is-open'))close();});
        $('#izzy-account-api-form').on('submit',function(e){
            e.preventDefault();
            const button=$(this).find('[type=submit]'),scopes=$(this).find('input[type=checkbox]:checked').map(function(){return this.value;}).get();
            if(!scopes.length){notify('Seleccione al menos un alcance.','error');return;}
            button.prop('disabled',true);
            post({action:'create',name:$('#izzy-account-api-name').val(),expires_in:$('#izzy-account-api-expiry').val(),scopes}).done(res=>{
                const item=res.datos.integracion||{};
                $('#izzy-account-client-id').text(item.client_id||'');$('#izzy-account-client-secret').text(item.client_secret||'');
                $('#izzy-account-api-credentials').prop('hidden',false);load();notify(res.mensaje,'success');
            }).fail(xhr=>notify(xhr.responseJSON?.mensaje||'No se pudo crear.','error')).always(()=>button.prop('disabled',false));
        });
        $('#izzy-account-api-credentials-close').on('click',()=>{$('#izzy-account-api-credentials').prop('hidden',true);$('#izzy-account-client-secret').text('');});
        $('#izzy-account-api-list').on('click','[data-izzy-account-revoke]',function(){
            const id=Number($(this).data('izzy-account-revoke'));
            if(typeof window.swal!=='function'){notify('No está disponible la confirmación. No se realizó ninguna acción.','error');return;}
            window.swal({title:'¿Revocar integración?',text:'Los tokens de esta integración dejarán de funcionar.',icon:'warning',buttons:{cancel:{text:'Cancelar',value:null,visible:true,className:'izzy-swal-cancel'},confirm:{text:'Sí, revocar',value:true,visible:true,className:'izzy-swal-danger'}},dangerMode:true,closeOnClickOutside:false}).then(confirmed=>{
                if(confirmed!==true)return;
                post({action:'revoke',id}).done(res=>{load();notify(res.mensaje,'success');}).fail(xhr=>notify(xhr.responseJSON?.mensaje||'No se pudo revocar.','error'));
            });
        });
    }
    if(document.readyState==='complete')init();else window.addEventListener('load',init,{once:true});
})();
