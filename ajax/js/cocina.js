// Pantalla de Cocina - IZZY
// Acceso independiente + vinculación de TV por código temporal.
if (typeof SERVERURL === 'undefined') var SERVERURL = window.location.origin + '/';

document.addEventListener('DOMContentLoaded', function () {
    window.COCINA_APP_BOOTED = true;
    const container = document.getElementById('comandas-container');
    const refreshBtn = document.getElementById('btn-refresh');
    const pairing = document.getElementById('cocina-pairing');
    const pairCodeEl = document.getElementById('cocina-pair-code');
    const pairStatusEl = document.getElementById('cocina-pair-status');
    const pairExpireEl = document.getElementById('cocina-pair-expire');
    const newCodeBtn = document.getElementById('btn-nuevo-codigo-cocina');
    const fullscreenBtn = document.getElementById('btn-fullscreen-cocina');
    document.body.classList.add('vista-cocina-active');

    const TOKEN_KEY = 'izzy_cocina_token_v1';
    const DEVICE_KEY = 'izzy_cocina_device_secret_v1';
    const MODE_KEY = 'izzy_cocina_access_mode_v1';
    const COCINA_ENDPOINT = `${SERVERURL}core/cocina/cocinaPublicaAjax.php`;
    const PAIR_ENDPOINT = `${SERVERURL}core/cocina/cocinaVinculacionAjax.php`;
    let COCINA_TOKEN = '';
    let pairCode = '';
    let pairPoll = null;
    let pairCountdown = null;
    let pairSeconds = 0;
    let cargaComandasEnCurso = false;
    let ultimaFirmaComandas = null;
    let controladorCarga = null;
    let flujoCocina = 'pasos';

    function notificar(tipo,titulo,mensaje){ if(typeof showNotify==='function') showNotify(tipo,titulo,mensaje); }
    function escapeHtml(valor){ return String(valor==null?'':valor).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }
    function normalizarCantidad(valor){ const n=Number(valor); if(!Number.isFinite(n)) return '1'; return Number.isInteger(n)?String(n):n.toFixed(2).replace(/0+$/,'').replace(/\.$/,''); }
    function actualizarHora(){ const a=new Date(); const el=document.getElementById('hora-actual'); if(el) el.textContent=[a.getHours(),a.getMinutes(),a.getSeconds()].map(v=>String(v).padStart(2,'0')).join(':'); }
    actualizarHora(); window.setInterval(actualizarHora,1000);


    function fullscreenElementActual(){
        return document.fullscreenElement
            || document.webkitFullscreenElement
            || document.msFullscreenElement
            || null;
    }

    function actualizarBotonFullscreen(){
        if(!fullscreenBtn) return;
        const activo = !!fullscreenElementActual();

        fullscreenBtn.setAttribute('aria-pressed', activo ? 'true' : 'false');
        fullscreenBtn.setAttribute('aria-label', activo ? 'Salir de pantalla completa' : 'Activar pantalla completa');
        fullscreenBtn.title = activo ? 'Salir de pantalla completa' : 'Pantalla completa';
        fullscreenBtn.innerHTML = activo
            ? '<i class="fas fa-compress"></i><span>Salir de pantalla completa</span>'
            : '<i class="fas fa-expand"></i><span>Pantalla completa</span>';

        document.body.classList.toggle('cocina-fullscreen-activo', activo);
    }

    async function alternarPantallaCompleta(){
        if(!fullscreenBtn) return;

        try{
            if(fullscreenElementActual()){
                if(document.exitFullscreen){
                    await document.exitFullscreen();
                }else if(document.webkitExitFullscreen){
                    document.webkitExitFullscreen();
                }else if(document.msExitFullscreen){
                    document.msExitFullscreen();
                }
            }else{
                const objetivo = document.documentElement;
                if(objetivo.requestFullscreen){
                    await objetivo.requestFullscreen();
                }else if(objetivo.webkitRequestFullscreen){
                    objetivo.webkitRequestFullscreen();
                }else if(objetivo.msRequestFullscreen){
                    objetivo.msRequestFullscreen();
                }else{
                    notificar('info','Pantalla completa','Este navegador o televisor no permite activar pantalla completa desde esta página.');
                }
            }
        }catch(error){
            console.warn('[Cocina] Pantalla completa:', error);
            notificar('info','Pantalla completa','El navegador bloqueó el cambio de pantalla completa. Inténtelo nuevamente desde el botón.');
        }finally{
            window.setTimeout(actualizarBotonFullscreen,50);
        }
    }

    function randomHex(bytes=32){ const a=new Uint8Array(bytes); crypto.getRandomValues(a); return Array.from(a,b=>b.toString(16).padStart(2,'0')).join(''); }
    function getDeviceSecret(){ let s=String(localStorage.getItem(DEVICE_KEY)||'').toLowerCase(); if(!/^[a-f0-9]{64}$/.test(s)){ s=randomHex(32); localStorage.setItem(DEVICE_KEY,s); } return s; }
    function getStoredToken(){ const t=String(localStorage.getItem(TOKEN_KEY)||'').toLowerCase(); return /^[a-f0-9]{64}$/.test(t)?t:''; }
    function setToken(token,mode='paired'){ token=String(token||'').toLowerCase(); if(/^[a-f0-9]{64}$/.test(token)){ localStorage.setItem(TOKEN_KEY,token); localStorage.setItem(MODE_KEY,mode==='direct'?'direct':'paired'); COCINA_TOKEN=token; return true; } return false; }
    function accessMode(){ return String(localStorage.getItem(MODE_KEY)||'paired')==='direct'?'direct':'paired'; }
    function deviceHeaders(){ return accessMode()==='paired'?{'X-Cocina-Device':getDeviceSecret()}:{}; }
    function clearToken(){ localStorage.removeItem(TOKEN_KEY); localStorage.removeItem(MODE_KEY); COCINA_TOKEN=''; }
    function cleanAddressBar(){ try{ history.replaceState(null,'',`${SERVERURL}cocina/`); }catch(_){} }

    function firmaComandas(comandas){ const lista=Array.isArray(comandas)?comandas:[]; return JSON.stringify({flujo:flujoCocina,comandas:lista.map(c=>({comanda_id:c.comanda_id||c.id||'',factura_id:c.factura_id||'',mesa:c.mesa||'',servicio_tipo:c.servicio_tipo||'',cliente_nombre:c.cliente_nombre||'',estado:c.estado||'',urgente:!!c.urgente,fecha:c.fecha||'',hora:c.hora||'',observaciones:c.observaciones||'',comentarios_cocina:c.comentarios_cocina||'',items:(Array.isArray(c.items)?c.items:[]).map(i=>({id:i.productos_id||i.id||'',nombre:i.nombre||'',cantidad:i.cantidad||0}))}))}); }

    async function pairPost(action,data={}){
        const response=await fetch(PAIR_ENDPOINT,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8','Accept':'application/json'},body:new URLSearchParams({action,...data}).toString(),cache:'no-store',credentials:'omit'});
        let d=null; try{d=await response.json();}catch(_){}
        if(!response.ok||!d||!d.status) throw new Error(d&&d.message?d.message:'No se pudo vincular esta pantalla.');
        return d;
    }
    function stopPairing(){ if(pairPoll){clearInterval(pairPoll);pairPoll=null;} if(pairCountdown){clearInterval(pairCountdown);pairCountdown=null;} }
    function showPairing(){ stopPairing(); if(pairing) pairing.hidden=false; if(container){ container.hidden=true; container.setAttribute('aria-hidden','true'); } if(refreshBtn) refreshBtn.style.display='none'; }
    function showKitchen(){ stopPairing(); if(pairing) pairing.hidden=true; if(container){ container.hidden=false; container.removeAttribute('aria-hidden'); } if(refreshBtn) refreshBtn.style.display='flex'; }
    function renderPairCountdown(){ if(!pairExpireEl) return; const m=Math.floor(Math.max(0,pairSeconds)/60),s=Math.max(0,pairSeconds)%60; pairExpireEl.textContent=pairSeconds>0?`Este código vence en ${m}:${String(s).padStart(2,'0')}.`:'El código venció. Genere uno nuevo.'; }

    async function crearCodigoVinculacion(){
        showPairing(); pairCode=''; pairSeconds=0;
        if(pairCodeEl) pairCodeEl.textContent='------';
        if(pairStatusEl) pairStatusEl.innerHTML='<i class="fas fa-spinner fa-spin"></i> Generando código seguro…';
        try{
            const d=await pairPost('crear',{device_secret:getDeviceSecret()});
            pairCode=String(d.codigo||''); pairSeconds=Number(d.expira_segundos||600);
            if(pairCodeEl) pairCodeEl.textContent=pairCode.replace(/(\d{3})(\d{3})/,'$1 $2');
            if(pairStatusEl) pairStatusEl.innerHTML='<i class="fas fa-link"></i> Esperando vinculación desde IZZY…';
            renderPairCountdown();
            pairCountdown=setInterval(()=>{ pairSeconds--; renderPairCountdown(); if(pairSeconds<=0){stopPairing(); if(pairStatusEl) pairStatusEl.innerHTML='<i class="fas fa-clock"></i> Código vencido.';} },1000);
            pairPoll=setInterval(comprobarVinculacion,2000);
        }catch(e){ if(pairStatusEl) pairStatusEl.innerHTML=`<i class="fas fa-triangle-exclamation"></i> ${escapeHtml(e.message)}`; }
    }

    async function comprobarVinculacion(){
        if(!pairCode) return;
        try{
            const d=await pairPost('estado',{codigo:pairCode,device_secret:getDeviceSecret()});
            const estado=String(d.estado||'');
            if(estado==='vinculado' && setToken(d.token||'','paired')){
                if(pairStatusEl) pairStatusEl.innerHTML='<i class="fas fa-circle-check"></i> Vinculada. Abriendo Cocina…';
                showKitchen(); await cargarComandas({forzar:true,silencioso:false}); return;
            }
            if(['expirado','no_encontrado','inactivo'].includes(estado)){
                stopPairing();
                if(pairStatusEl) pairStatusEl.innerHTML='<i class="fas fa-clock"></i> El código ya no está disponible. Genere uno nuevo.';
            }
        }catch(e){ console.warn('[Cocina] Vinculación:',e.message); }
    }

    async function cargarComandas(opciones={}){
        const {forzar=false,silencioso=true}=opciones;
        if(cargaComandasEnCurso||!COCINA_TOKEN) return;
        cargaComandasEnCurso=true; if(refreshBtn) refreshBtn.classList.add('is-loading');
        try{
            controladorCarga=new AbortController(); const timeoutId=setTimeout(()=>controladorCarga.abort(),15000);
            const response=await fetch(COCINA_ENDPOINT,{method:'POST',headers:Object.assign({'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8','Accept':'application/json','X-Cocina-Token':COCINA_TOKEN},deviceHeaders()),body:'action=listar',signal:controladorCarga.signal,cache:'no-store',credentials:'omit'});
            clearTimeout(timeoutId); let data=null; try{data=await response.json();}catch(_){throw new Error('El servidor devolvió una respuesta inválida.');}
            if(!response.ok){
                if(response.status===403 && data && /inactiva/i.test(String(data.message||''))){
                    // Si el administrador desactiva temporalmente Cocina no olvidamos la vinculación.
                    // Al reactivarla con el mismo token, esta pantalla vuelve a funcionar sola.
                    if(container) container.innerHTML='<div class="no-comandas" role="status"><div class="no-comandas-icon"><i class="fas fa-power-off"></i></div><strong>Pantalla de Cocina inactiva</strong><p>Esperando que el administrador vuelva a activarla.</p></div>';
                    return;
                }
                if([401,404].includes(response.status)){
                    clearToken(); ultimaFirmaComandas=null; await crearCodigoVinculacion(); return;
                }
                throw new Error(data&&data.message?data.message:'Error al cargar las comandas.');
            }
            if(!data||!data.status) throw new Error(data&&data.message?data.message:'No fue posible cargar las comandas.');
            const comandas=Array.isArray(data.comandas)?data.comandas:[];
            flujoCocina=String((data.config&&data.config.flujo_cocina)||'pasos').toLowerCase()==='directo'?'directo':'pasos';
            const firma=firmaComandas(comandas); if(forzar||firma!==ultimaFirmaComandas){renderizarComandas(comandas);ultimaFirmaComandas=firma;}
        }catch(error){ if(error&&error.name!=='AbortError'&&!silencioso) notificar('error','Error',error.message||'Error al conectar con el servidor.'); }
        finally{cargaComandasEnCurso=false;controladorCarga=null;if(refreshBtn)refreshBtn.classList.remove('is-loading');}
    }

    function renderizarComandas(comandas) {
        if (!container) return;

        if (!Array.isArray(comandas) || comandas.length === 0) {
            container.innerHTML = `
                <div class="no-comandas" role="status">
                    <div class="no-comandas-icon"><i class="fas fa-clipboard-check"></i></div>
                    <strong>Sin comandas pendientes</strong>
                    <p>Las nuevas órdenes de Cocina aparecerán aquí automáticamente.</p>
                </div>
            `;
            return;
        }

        const fragment = document.createDocumentFragment();

        comandas.forEach((comanda, index) => {
            const comandaId = comanda.comanda_id || comanda.id || '';
            const facturaId = comanda.factura_id || '';
            const estado = String(comanda.estado || 'pendiente').toLowerCase();
            const urgente = !!comanda.urgente || estado === 'urgente';
            const esPrueba = Number(comanda.es_prueba||0)===1;
            const items = Array.isArray(comanda.items) ? comanda.items : [];

            const card = document.createElement('article');
            card.className = `comanda-card${urgente ? ' comanda-urgente' : ''}${esPrueba ? ' comanda-prueba' : ''}`;
            card.dataset.estado = estado;
            card.dataset.comandaId = comandaId;
            card.style.setProperty('--card-delay', `${Math.min(index * 35, 175)}ms`);

            const servicio = String(comanda.servicio_tipo || '').toLowerCase();
            const mesaTexto = esPrueba ? 'Prueba de pantalla' : (comanda.mesa
                ? `Mesa ${escapeHtml(comanda.mesa)}`
                : (servicio === 'llevar' ? 'Para llevar' : 'Sin mesa asignada'));
            const mesaIcon = esPrueba ? 'satellite-dish' : (comanda.mesa ? 'chair' : 'shopping-bag');

            const itemsHTML = items.length
                ? items.map(item => `
                    <div class="comanda-item">
                        <span class="item-nombre">${escapeHtml(item.nombre || 'Producto sin nombre')}</span>
                        <span class="item-cantidad" aria-label="Cantidad ${escapeHtml(normalizarCantidad(item.cantidad))}">${escapeHtml(normalizarCantidad(item.cantidad))}</span>
                    </div>
                `).join('')
                : '<div class="comanda-item comanda-item-vacio">Sin productos de Cocina.</div>';

            const estadoLabel = estado.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            const observaciones = comanda.observaciones ? `<p><strong>Notas:</strong> ${escapeHtml(comanda.observaciones)}</p>` : '';
            const comentarios = comanda.comentarios_cocina ? `<p><strong>Cocina:</strong> ${escapeHtml(comanda.comentarios_cocina)}</p>` : '';

            card.innerHTML = `
                <header class="comanda-header">
                    <div class="comanda-header-main">
                        <span class="comanda-mesa"><i class="fas fa-${mesaIcon}"></i>${mesaTexto}</span>
                        <div class="comanda-badges">
                            ${esPrueba ? '<span class="badge-estado pendiente">PRUEBA</span>' : (urgente ? '<span class="badge-urgente">Urgente</span>' : '')}
                            ${!esPrueba && estado !== 'urgente' ? `<span class="badge-estado ${escapeHtml(estado)}">${escapeHtml(estadoLabel)}</span>` : ''}
                        </div>
                    </div>
                    <span class="comanda-hora"><i class="far fa-calendar-alt"></i>${escapeHtml(comanda.fecha || '')}${comanda.fecha && comanda.hora ? ' · ' : ''}<i class="far fa-clock"></i>${escapeHtml(comanda.hora || '')}</span>
                </header>

                <div class="comanda-info">
                    <div class="comanda-cliente"><i class="far fa-user"></i><span><strong>Cliente</strong>${escapeHtml(comanda.cliente_nombre || 'Consumidor Final')}</span></div>
                    <div class="comanda-id"><i class="fas fa-${esPrueba ? 'wifi' : 'receipt'}"></i><span><strong>${esPrueba ? 'Estado' : 'Orden'}</strong>${esPrueba ? 'Conexión correcta' : '#'+escapeHtml(facturaId || comandaId || '—')}</span></div>
                </div>

                <div class="comanda-items">${itemsHTML}</div>

                ${(observaciones || comentarios) ? `<div class="comanda-observaciones">${observaciones}${comentarios}</div>` : ''}

                ${esPrueba ? '<footer class="comanda-actions"><span class="cocina-test-ok"><i class="fas fa-circle-check"></i> Pantalla conectada correctamente</span></footer>' : `<footer class="comanda-actions">
                    ${(flujoCocina === 'pasos' && (estado === 'pendiente' || estado === 'urgente')) ? `
                        <button type="button" class="btn btn-primary btn-preparacion"
                                data-factura-id="${escapeHtml(facturaId)}"
                                data-comanda-id="${escapeHtml(comandaId)}">
                            <i class="fas fa-fire-burner"></i><span>En preparación</span>
                        </button>
                    ` : ''}

                    ${((flujoCocina === 'pasos' && estado === 'en_preparacion') || (flujoCocina === 'directo' && ['pendiente','urgente','en_preparacion'].includes(estado))) ? `
                        <button type="button" class="btn btn-success btn-completar"
                                data-comanda-id="${escapeHtml(comandaId)}"
                                data-factura-id="${escapeHtml(facturaId)}">
                            <i class="fas fa-check-circle"></i><span>Finalizar</span>
                        </button>
                    ` : ''}

                    <button type="button" class="btn btn-warning btn-urgente"
                            data-factura-id="${escapeHtml(facturaId)}"
                            data-urgente="${urgente ? 'true' : 'false'}">
                        <i class="fas fa-exclamation-triangle"></i><span>${urgente ? 'Quitar urgente' : 'Marcar urgente'}</span>
                    </button>
                </footer>`}
            `;

            fragment.appendChild(card);
        });

        container.replaceChildren(fragment);
    }

    async function ejecutarAccion(boton, body, mensajes) {
        if (!boton || boton.disabled) return;

        const htmlOriginal = boton.innerHTML;
        boton.disabled = true;
        boton.classList.add('is-processing');
        boton.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Procesando…</span>';

        try {
            const response = await fetch(COCINA_ENDPOINT, {
                method: 'POST',
                headers: Object.assign({
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'Accept': 'application/json',
                    'X-Cocina-Token': COCINA_TOKEN
                }, deviceHeaders()),
                body,
                cache: 'no-store',
                credentials: 'omit'
            });

            const data = await response.json();
            if (!response.ok || !data.status) {
                throw new Error((data && data.message) || mensajes.error);
            }

            notificar('success', 'Éxito', mensajes.exito);
            // Forzamos una única actualización después de una acción del usuario.
            await cargarComandas({ forzar: true, silencioso: true });
        } catch (error) {
            console.error(mensajes.error, error);
            notificar('error', 'Error', error.message || mensajes.error);
        } finally {
            boton.disabled = false;
            boton.classList.remove('is-processing');
            boton.innerHTML = htmlOriginal;
        }
    }

    if (container) {
        // Delegación: no recreamos listeners en cada refresco.
        container.addEventListener('click', function (event) {
            const btnPreparacion = event.target.closest('.btn-preparacion');
            if (btnPreparacion) {
                const facturaId = btnPreparacion.dataset.facturaId;
                if (!facturaId) return;
                ejecutarAccion(
                    btnPreparacion,
                    `action=enPreparacion&factura_id=${encodeURIComponent(facturaId)}`,
                    { exito: 'Comanda marcada como en preparación', error: 'No se pudo cambiar el estado de la comanda.' }
                );
                return;
            }

            const btnCompletar = event.target.closest('.btn-completar');
            if (btnCompletar) {
                const facturaId = btnCompletar.dataset.facturaId;
                if (!facturaId) return;
                ejecutarAccion(
                    btnCompletar,
                    `action=finalizar&factura_id=${encodeURIComponent(facturaId)}`,
                    { exito: 'Comanda marcada como completada', error: 'No se pudo completar la comanda.' }
                );
                return;
            }

            const btnUrgente = event.target.closest('.btn-urgente');
            if (btnUrgente) {
                const facturaId = btnUrgente.dataset.facturaId;
                const esUrgente = btnUrgente.dataset.urgente === 'true';
                if (!facturaId) return;
                ejecutarAccion(
                    btnUrgente,
                    `action=urgente&factura_id=${encodeURIComponent(facturaId)}&urgente=${esUrgente ? 0 : 1}`,
                    {
                        exito: esUrgente ? 'Comanda marcada como normal' : 'Comanda marcada como urgente',
                        error: 'No se pudo cambiar la prioridad de la comanda.'
                    }
                );
            }
        });
    }

    if (refreshBtn) {
        refreshBtn.setAttribute('role', 'button');
        refreshBtn.setAttribute('tabindex', '0');
        refreshBtn.setAttribute('aria-label', 'Actualizar comandas');
        const refrescarManual=()=>{ if(COCINA_TOKEN) cargarComandas({forzar:true,silencioso:false}); else crearCodigoVinculacion(); };
        refreshBtn.addEventListener('click',refrescarManual);
        refreshBtn.addEventListener('keydown',function(event){ if(event.key==='Enter'||event.key===' '){event.preventDefault();refrescarManual();} });
    }
    if(newCodeBtn) newCodeBtn.addEventListener('click',crearCodigoVinculacion);


    if(fullscreenBtn){
        fullscreenBtn.addEventListener('click',alternarPantallaCompleta);
        actualizarBotonFullscreen();
    }

    document.addEventListener('fullscreenchange',actualizarBotonFullscreen);
    document.addEventListener('webkitfullscreenchange',actualizarBotonFullscreen);
    document.addEventListener('MSFullscreenChange',actualizarBotonFullscreen);

    const urlToken=String(window.COCINA_URL_TOKEN||'').toLowerCase();
    if(/^[a-f0-9]{64}$/.test(urlToken)){
        setToken(urlToken,'direct');
        cleanAddressBar();
    }else{
        COCINA_TOKEN=getStoredToken();
    }

    if(COCINA_TOKEN){
        showKitchen();
        cargarComandas({forzar:true,silencioso:false});
    }else{
        crearCodigoVinculacion();
    }

    window.setInterval(()=>{ if(COCINA_TOKEN && !document.hidden) cargarComandas({silencioso:true}); },5000);
});
