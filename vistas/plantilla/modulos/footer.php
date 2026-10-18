<?php
/**
 * Footer global de IZZY.
 * La misma estructura se utiliza en escritorio, tablet y móvil.
 */
?>
<div class="footer izzy-footer-global">
    <div class="izzy-footer-inner">
        <div class="izzy-footer-copyright">
            <span>Copyright <i class="fas fa-copyright" aria-hidden="true"></i> 2020 - <?php echo date('Y'); ?></span>
        </div>

        <div class="izzy-footer-brand">
            <img
                src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/img/icono.png"
                alt="IZZY"
            >
        </div>

        <div class="izzy-footer-version">
            <i class="fas fa-code-branch" aria-hidden="true"></i>
            <span class="version" id="version"></span>
        </div>
    </div>
</div>
