<?php

/**
 * Footer del sitio (base mínima).
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<footer class="okip-footer" role="contentinfo">
    <div class="okip-footer__glow" aria-hidden="true"></div>

    <div class="okip-container okip-footer__inner">
        <div class="okip-footer__brand">
            <a class="okip-footer__logo" href="<?php echo esc_url(home_url('/')); ?>">
                <?php echo esc_html(get_bloginfo('name')); ?>
            </a>
            <p class="okip-footer__tagline">
                Inteligencia mexicana para entornos seguros, conectados e independientes.
            </p>
        </div>

        <nav class="okip-footer__nav" aria-label="<?php echo esc_attr__('Enlaces del footer', 'okip'); ?>">
            <a href="<?php echo esc_url(home_url('/')); ?>">Inicio</a>
            <a href="<?php echo esc_url(home_url('/fabrica-de-tecnologias')); ?>">Fábrica de tecnologías</a>
            <a href="<?php echo esc_url(home_url('/sala-de-prensa')); ?>">Sala de prensa</a>
            <a href="<?php echo esc_url(home_url('/contacto')); ?>">Contacto</a>
        </nav>

        <div class="okip-footer__contact" aria-label="<?php echo esc_attr__('Información de contacto', 'okip'); ?>">
            <p class="okip-footer__label">Contacto</p>
            <a href="mailto:contacto@okip.mx">contacto@okip.mx</a>
            <span>México</span>
        </div>
    </div>

    <div class="okip-container okip-footer__bottom">
        <p class="okip-footer__copy">
            &copy; <?php echo esc_html(date_i18n('Y')); ?> <?php echo esc_html(get_bloginfo('name')); ?>.
        </p>
        <p class="okip-footer__note">Base preparada para personalización visual y contenido legal.</p>
    </div>
</footer>
