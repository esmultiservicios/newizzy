<!-- ========== SCRIPTS BASE (sin defer - críticos) ========== -->
<!-- 1. jQuery (debe cargarse primero) -->
<script src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/query/jquery-3.5.1.min.js" crossorigin="anonymous"></script>

<!-- 2. Popper.js (requerido por Bootstrap) -->
<script src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/popper/popper.min.js" crossorigin="anonymous"></script>

<!-- 3. Bootstrap -->
<script src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/bootstrap/js/bootstrap.min.js" crossorigin="anonymous"></script>

<!-- 4. InputMask (depende de jQuery) -->
<script src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/js/jquery.inputmask.bundle.js" crossorigin="anonymous"></script>

<!-- ========== SCRIPTS CON DEFER (carga ordenada post-HTML) ========== -->
<!-- 5. DataTables + plugins (dependen de jQuery) -->
<script defer src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/bootstrap/js/jquery.dataTables.min.js" crossorigin="anonymous"></script>
<script defer src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/bootstrap/js/dataTables.bootstrap4.min.js" crossorigin="anonymous"></script>
<script defer src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/bootstrap/js/dataTables.buttons.min.js" crossorigin="anonymous"></script>
<script defer src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/bootstrap/js/dataTables.select.min.js" crossorigin="anonymous"></script>
<script defer src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/bootstrap/js/jszip.min.js" crossorigin="anonymous"></script>
<script defer src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/bootstrap/js/pdfmake.min.js" crossorigin="anonymous"></script>
<script defer src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/bootstrap/js/vfs_fonts.js" crossorigin="anonymous"></script>
<script defer src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/bootstrap/js/buttons.html5.min.js" crossorigin="anonymous"></script>
<script defer src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/bootstrap/js/buttons.print.min.js" crossorigin="anonymous"></script>

<!-- 6. Bootstrap Select (depende de Bootstrap) -->
<script defer src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/bootstrap/js/bootstrap-select.min.js" crossorigin="anonymous"></script>

<!-- 7. Chart.js + plugins -->
<script defer src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/charts/Chart.min.js" crossorigin="anonymous"></script>
<script defer src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/charts/chartjs-plugin-datalabels@2.0.0.js"></script>

<!-- 8. jQuery Custom Scrollbar -->
<script defer src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/js/jquery.mCustomScrollbar.concat.min.js"></script>

<!-- 9. SweetAlert -->
<script defer src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/sweetalert/sweetalert.min.js" crossorigin="anonymous"></script>

<!-- ========== SCRIPTS ASYNC (independientes) ========== -->
<!-- 10. Efectos visuales (sin dependencias) -->
<script async src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/js/snow.js" crossorigin="anonymous"></script>
<script async src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/js/menu-despelgable.js"></script>

<!-- 11. Moment.js y Notyf (si no son críticos) -->
<script async src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/librerias/moment-with-locales.js" crossorigin="anonymous"></script>
<script defer src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/librerias/notyf.min.js" crossorigin="anonymous"></script>

<!-- ========== SCRIPTS PERSONALIZADOS (con defer si usan jQuery/DOM) ========== -->
<!-- 12. main.js y scripts.js (dependen de jQuery?) -->
<script defer src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/js/main.js" crossorigin="anonymous"></script>
<script defer src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/js/scripts.js" crossorigin="anonymous"></script>

<!-- IZZY | UI premium específica de Cotización (no-op fuera de #view_quote) -->
<script defer src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/js/cotizacion_ui_premium.js" crossorigin="anonymous"></script>

<!-- IZZY | UI premium específica de Facturación (no-op fuera de #view_bill) -->
<script defer src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/js/facturas_ui_premium.js" crossorigin="anonymous"></script>



<style id="izzy-global-premium-controls">
/* =========================================================
   IZZY | FIRMA VISUAL GLOBAL - Scrollbars + Checkboxes
   ========================================================= */
:root{
    --izzy-scroll-track:#edf3f7;
    --izzy-scroll-thumb:#315b7c;
    --izzy-scroll-thumb-hover:#1f8fcb;
    --izzy-check:#168bd0;
    --izzy-check-hover:#0f79b7;
}

/* Scrollbar IZZY para todo el sistema: discreta, visible y consistente. */
html,
body,
*{
    scrollbar-width:thin;
    scrollbar-color:var(--izzy-scroll-thumb) var(--izzy-scroll-track);
}

::-webkit-scrollbar{
    width:10px;
    height:10px;
}
::-webkit-scrollbar-track{
    background:var(--izzy-scroll-track);
    border-radius:999px;
}
::-webkit-scrollbar-thumb{
    min-height:34px;
    background:var(--izzy-scroll-thumb);
    border:2px solid var(--izzy-scroll-track);
    border-radius:999px;
}
::-webkit-scrollbar-thumb:hover{
    background:var(--izzy-scroll-thumb-hover);
}
::-webkit-scrollbar-corner{
    background:var(--izzy-scroll-track);
}
::-webkit-scrollbar-button{
    width:0;
    height:0;
    display:none;
}

/* Checkbox premium IZZY. Los switches siguen usando su propio diseño. */
input[type="checkbox"]:not(.custom-control-input):not(.onoffswitch-checkbox){
    width:18px;
    height:18px;
    min-width:18px;
    min-height:18px;
    margin:0;
    border:2px solid #9db3c5;
    border-radius:5px;
    background-color:#fff;
    background-position:center;
    background-repeat:no-repeat;
    background-size:11px 11px;
    appearance:none;
    -webkit-appearance:none;
    cursor:pointer;
    vertical-align:middle;
    transition:border-color .15s ease, background-color .15s ease, box-shadow .15s ease, transform .12s ease;
    box-shadow:0 1px 2px rgba(23,43,77,.06);
}
input[type="checkbox"]:not(.custom-control-input):not(.onoffswitch-checkbox):hover{
    border-color:var(--izzy-check);
    box-shadow:0 0 0 3px rgba(22,139,208,.09);
}
input[type="checkbox"]:not(.custom-control-input):not(.onoffswitch-checkbox):focus-visible{
    outline:0;
    border-color:var(--izzy-check);
    box-shadow:0 0 0 3px rgba(22,139,208,.16);
}
input[type="checkbox"]:not(.custom-control-input):not(.onoffswitch-checkbox):checked{
    border-color:var(--izzy-check);
    background-color:var(--izzy-check);
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2.4' d='M3 8.3 6.3 11.5 13 4.8'/%3E%3C/svg%3E");
    box-shadow:0 3px 7px rgba(22,139,208,.20);
}
input[type="checkbox"]:not(.custom-control-input):not(.onoffswitch-checkbox):checked:hover{
    border-color:var(--izzy-check-hover);
    background-color:var(--izzy-check-hover);
}
input[type="checkbox"]:not(.custom-control-input):not(.onoffswitch-checkbox):active{
    transform:scale(.94);
}
input[type="checkbox"]:not(.custom-control-input):not(.onoffswitch-checkbox):disabled{
    cursor:not-allowed;
    opacity:.55;
    box-shadow:none;
}

/* Los checkbox que funcionan como switch permanecen invisibles bajo su slider. */
.switch input[type="checkbox"],
.config-factura-switch-control input[type="checkbox"],
.tipo-factura-recordar-switch input[type="checkbox"]{
    position:absolute!important;
    opacity:0!important;
    width:0!important;
    height:0!important;
    min-width:0!important;
    min-height:0!important;
    margin:0!important;
    border:0!important;
    box-shadow:none!important;
}
</style>

<script>
/* =========================================================
   IZZY | SELECT2 - LIMPIEZA GLOBAL
   Todos los Select2 simples conservan una opción vacía y,
   al limpiar/resetear un formulario, permanecen realmente vacíos.
   Se excluyen paginadores, múltiples y controles marcados para
   conservar su valor mediante data-no-empty-option="1".
   ========================================================= */
(function ($) {
    'use strict';

    if (!$) {
        return;
    }

    function esSelectExcluido($select) {
        var id = String($select.attr('id') || '');

        return $select.prop('multiple')
            || /(?:PageSize|page_size)$/i.test(id)
            || String($select.attr('data-no-empty-option') || '') === '1';
    }

    function asegurarOpcionVacia($select) {
        if (!$select || !$select.length || !$select.is('select') || esSelectExcluido($select)) {
            return;
        }

        if (!$select.find('option[value=""]').length) {
            $select.prepend($('<option></option>').attr('value', '').text(''));
        }
    }

    function limpiarSelect2Formulario(form) {
        var $form = $(form);

        window.setTimeout(function () {
            $form.find('select').each(function () {
                var $select = $(this);

                if (esSelectExcluido($select)) {
                    return;
                }

                asegurarOpcionVacia($select);
                $select.val('');

                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.trigger('change.select2');
                } else {
                    $select.trigger('change');
                }
            });
        }, 0);
    }

    /* Fallback global: main.php ya agrega la opción vacía al inicializar
       Select2; esto también cubre selects presentes antes de esa conversión. */
    $(function () {
        $('select').each(function () {
            asegurarOpcionVacia($(this));
        });
    });

    $(document)
        .off('reset.izzySelect2Global', 'form')
        .on('reset.izzySelect2Global', 'form', function () {
            limpiarSelect2Formulario(this);
        });
})(window.jQuery);
</script>
