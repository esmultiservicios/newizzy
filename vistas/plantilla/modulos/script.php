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
