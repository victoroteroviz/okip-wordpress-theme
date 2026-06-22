<?php

/**
 * Página 404.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();
?>
<div class="okip-container okip-404">
    <h1 class="okip-404__title"><?php esc_html_e('Página no encontrada', 'okip'); ?></h1>
    <p class="okip-404__text"><?php esc_html_e('La página que buscas no existe o fue movida.', 'okip'); ?></p>
    <a class="okip-button" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Volver al inicio', 'okip'); ?></a>
</div>
<?php
get_footer();
