<?php

/**
 * Portada (HOME).
 *
 * Renderiza la lista ordenada de bloques definida en config/pages/home.php.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();

okip_render_page(okip_get_page_blocks('home'));

get_footer();
